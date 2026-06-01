<?php

namespace App\Tests;

use App\Entity\BreedRating;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CatControllerTest extends WebTestCase
{
    /**
     * Payload simulant The Cat API.
     * - 'abys' : complet, recevra deux notes (4 et 5) -> moyenne 4.5
     * - 'beng' : pas d'origin, pas de reference_image_id, aucune note -> null
     */
    private const CAT_API_PAYLOAD = [
        [
            'id'                 => 'abys',
            'name'               => 'Abyssinian',
            'origin'             => 'Egypt',
            'temperament'        => 'Active, Energetic',
            'description'        => 'An ancient breed.',
            'reference_image_id' => 'img-abys',
            'life_span'          => '14 - 15', // champ non mappé par /cats : doit être ignoré
        ],
        [
            'id'          => 'beng',
            'name'        => 'Bengal',
            // 'origin' absent
            'temperament' => 'Alert',
            'description' => 'A wild look.',
            // 'reference_image_id' absent
        ],
    ];

    /**
     * Boote le client, remplace l'API tierce par un mock, crée un schéma vierge.
     * @return array{0: KernelBrowser, 1: EntityManagerInterface}
     */
    private function boot(array $payload): array
    {
        $client    = static::createClient();
        $container = static::getContainer();

        // 1) Mock de l'API externe (aucun appel réseau)
        $mockResponse = new MockResponse(json_encode($payload), [
            'http_code'        => 200,
            'response_headers' => ['Content-Type' => 'application/json'],
        ]);
        $container->set('http_client', new MockHttpClient($mockResponse));

        // 2) Schéma de test recréé à neuf (SQLite via .env.test)
        /** @var EntityManagerInterface $em */
        $em        = $container->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata   = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        return [$client, $em];
    }

    private function seedRating(EntityManagerInterface $em, string $breedId, string $sessionId, int $score): void
    {
        $rating = new BreedRating();
        $rating->setBreedId($breedId);
        $rating->setSessionId($sessionId); // contrainte unique (breed_id, session_id)
        $rating->setScore($score);
        $rating->setCreatedAt(new \DateTimeImmutable());
        $em->persist($rating);
    }

    public function testCatsEndpointReturnsSuccessfulJson(): void
    {
        [$client] = $this->boot(self::CAT_API_PAYLOAD);
        $client->request('GET', '/cats');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testBreedExposesExpectedFieldsOnly(): void
    {
        [$client] = $this->boot(self::CAT_API_PAYLOAD);
        $client->request('GET', '/cats');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $data);
        $this->assertSame(
            ['id', 'name', 'origin', 'temperament', 'description', 'image_id', 'avg_rating', 'is_favorite'],
            array_keys($data[0])
        );
        $this->assertSame('abys', $data[0]['id']);
        $this->assertSame('img-abys', $data[0]['image_id']);
        $this->assertArrayNotHasKey('life_span', $data[0]); // champ non mappé absent
    }

    public function testMissingSourceFieldsBecomeNull(): void
    {
        [$client] = $this->boot(self::CAT_API_PAYLOAD);
        $client->request('GET', '/cats');

        $data = json_decode($client->getResponse()->getContent(), true);

        // Bengal : origin et image absents à la source
        $this->assertSame('Bengal', $data[1]['name']);
        $this->assertNull($data[1]['origin']);
        $this->assertNull($data[1]['image_id']);
    }

    public function testAverageRatingIsComputed(): void
    {
        [$client, $em] = $this->boot(self::CAT_API_PAYLOAD);
        // Deux notes pour 'abys' (sessions distinctes), aucune pour 'beng'
        $this->seedRating($em, 'abys', 'sess-1', 4);
        $this->seedRating($em, 'abys', 'sess-2', 5);
        $em->flush();

        $client->request('GET', '/cats');
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(4.5, $data[0]['avg_rating']); // (4+5)/2
        $this->assertNull($data[1]['avg_rating']);       // pas de note
    }

    public function testIsFavoriteFalseWithoutSession(): void
    {
        [$client] = $this->boot(self::CAT_API_PAYLOAD);
        $client->request('GET', '/cats'); // pas de header X-Session-Id

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data[0]['is_favorite']);
    }

    public function testEmptyBreedListReturnsEmptyJsonArray(): void
    {
        [$client] = $this->boot([]);
        $client->request('GET', '/cats');

        $this->assertResponseIsSuccessful();
        $this->assertSame('[]', $client->getResponse()->getContent());
    }
}