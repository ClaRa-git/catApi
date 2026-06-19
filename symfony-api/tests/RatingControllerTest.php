<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent la validation des entrées de l'endpoint /ratings/{breedId}.
 * Ils servent de "test de sécurité" pour l'activité-type 3 du référentiel CDA :
 * ils vérifient que l'API rejette les requêtes non authentifiées (sans session)
 * et les données hors plage ou mal typées, sans planter ni écrire de donnée invalide.
 */
class RatingControllerTest extends WebTestCase
{
    /**
     * @return array{0: \Symfony\Bundle\FrameworkBundle\KernelBrowser, 1: EntityManagerInterface}
     */
    private function boot(): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        return [$client, $em];
    }

    public function testRatingWithoutSessionHeaderIsRejected(): void
    {
        [$client] = $this->boot();

        $client->request(
            'POST',
            '/ratings/abys',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['score' => 4])
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testRatingAboveMaximumScoreIsRejected(): void
    {
        [$client] = $this->boot();

        $client->request(
            'POST',
            '/ratings/abys',
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SESSION_ID' => 'sess-1'],
            json_encode(['score' => 99])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRatingBelowMinimumScoreIsRejected(): void
    {
        [$client] = $this->boot();

        $client->request(
            'POST',
            '/ratings/abys',
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SESSION_ID' => 'sess-1'],
            json_encode(['score' => 0])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRatingWithNonNumericScoreIsRejectedSafely(): void
    {
        [$client] = $this->boot();

        // Charge utile non numérique : le cast (int) en place dans le contrôleur
        // doit la neutraliser (score = 0) plutôt que de provoquer une erreur serveur.
        $client->request(
            'POST',
            '/ratings/abys',
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SESSION_ID' => 'sess-1'],
            json_encode(['score' => "'; DROP TABLE breed_rating; --"])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRatingWithMalformedJsonBodyDoesNotCrash(): void
    {
        [$client] = $this->boot();

        $client->request(
            'POST',
            '/ratings/abys',
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SESSION_ID' => 'sess-1'],
            '{not-valid-json'
        );

        // json_decode renvoie null sur une charge invalide : le score est alors
        // traité comme manquant (0) et donc rejeté, sans erreur 500.
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRatingWithValidScoreIsAcceptedAndPersisted(): void
    {
        [$client] = $this->boot();

        $client->request(
            'POST',
            '/ratings/abys',
            [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SESSION_ID' => 'sess-1'],
            json_encode(['score' => 5])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(5, $data['user_score']);
        $this->assertEquals(5.0, $data['avg_rating']);
    }

    public function testReSubmittingRatingUpdatesInsteadOfDuplicating(): void
    {
        [$client] = $this->boot();
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SESSION_ID' => 'sess-1'];

        $client->request('POST', '/ratings/abys', [], [], $headers, json_encode(['score' => 2]));
        $client->request('POST', '/ratings/abys', [], [], $headers, json_encode(['score' => 4]));

        $client->request('GET', '/ratings/abys', [], [], ['HTTP_X_SESSION_ID' => 'sess-1']);
        $data = json_decode($client->getResponse()->getContent(), true);

        // La contrainte unique (breed_id, session_id) doit empêcher tout doublon :
        // la moyenne reste celle de la dernière note, pas de la somme des deux.
        $this->assertEquals(4, $data['user_score']);
        $this->assertEquals(4.0, $data['avg_rating']);
    }
}