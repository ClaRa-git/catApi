<?php
namespace App\Controller;

use App\Entity\BreedFavorite;
use App\Entity\BreedRating;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CatController extends AbstractController
{
    #[Route('/cats', name: 'cats_list', methods: ['GET'])]
    public function index(HttpClientInterface $client, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $headers = [
            'x-api-key' => 'live_m7U1JLOcnWSmGPLZhMprAFeDXCKxspEgNDoajnK2AF8EOcDb28KsNA880lLtxZug',
        ];

        $response = $client->request('GET', 'https://api.thecatapi.com/v1/breeds', ['headers' => $headers]);
        $cats = $response->toArray();

        // Moyennes de notes en une seule requête
        $avgRatings = $em->createQuery(
            'SELECT r.breedId, AVG(r.score) as avg FROM App\Entity\BreedRating r GROUP BY r.breedId'
        )->getResult();
        $avgMap = array_column($avgRatings, 'avg', 'breedId');

        // Favoris de la session
        $sessionId = $request->headers->get('X-Session-Id');
        $favIds = [];
        if ($sessionId) {
            $favs = $em->getRepository(BreedFavorite::class)->findBy(['sessionId' => $sessionId]);
            $favIds = array_map(fn($f) => $f->getBreedId(), $favs);
        }

        $formattedCats = array_map(function ($cat) use ($avgMap, $favIds) {
            return [
                'id'          => $cat['id'] ?? null,
                'name'        => $cat['name'] ?? null,
                'origin'      => $cat['origin'] ?? null,
                'temperament' => $cat['temperament'] ?? null,
                'description' => $cat['description'] ?? null,
                'image_id'    => $cat['reference_image_id'] ?? null,
                'avg_rating'  => isset($avgMap[$cat['id']]) ? round((float)$avgMap[$cat['id']], 1) : null,
                'is_favorite' => in_array($cat['id'], $favIds),
            ];
        }, $cats);

        return $this->json($formattedCats);
    }

    #[Route('/cats/{id}', name: 'cats_show', methods: ['GET'])]
    public function show(string $id, HttpClientInterface $client, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $headers = ['x-api-key' => 'live_m7U1JLOcnWSmGPLZhMprAFeDXCKxspEgNDoajnK2AF8EOcDb28KsNA880lLtxZug'];

        $response = $client->request('GET', 'https://api.thecatapi.com/v1/breeds/' . $id, ['headers' => $headers]);
        $cat = $response->toArray();

        // Note moyenne
        $avg = $em->createQuery(
            'SELECT AVG(r.score) FROM App\Entity\BreedRating r WHERE r.breedId = :id'
        )->setParameter('id', $id)->getSingleScalarResult();

        // Note et favori de la session
        $sessionId = $request->headers->get('X-Session-Id');
        $userScore = null;
        $isFav = false;
        if ($sessionId) {
            $rating = $em->getRepository(\App\Entity\BreedRating::class)->findOneBy(['breedId' => $id, 'sessionId' => $sessionId]);
            $userScore = $rating?->getScore();
            $isFav = (bool) $em->getRepository(\App\Entity\BreedFavorite::class)->findOneBy(['breedId' => $id, 'sessionId' => $sessionId]);
        }

        return $this->json([
            'id'              => $cat['id'] ?? null,
            'name'            => $cat['name'] ?? null,
            'origin'          => $cat['origin'] ?? null,
            'temperament'     => $cat['temperament'] ?? null,
            'description'     => $cat['description'] ?? null,
            'life_span'       => $cat['life_span'] ?? null,
            'weight'          => $cat['weight']['metric'] ?? null,
            'wikipedia_url'   => $cat['wikipedia_url'] ?? null,
            'adaptability'    => $cat['adaptability'] ?? null,
            'affection_level' => $cat['affection_level'] ?? null,
            'energy_level'    => $cat['energy_level'] ?? null,
            'grooming'        => $cat['grooming'] ?? null,
            'intelligence'    => $cat['intelligence'] ?? null,
            'social_needs'    => $cat['social_needs'] ?? null,
            'image_id'        => $cat['reference_image_id'] ?? null,
            'avg_rating'      => $avg ? round((float)$avg, 1) : null,
            'user_score'      => $userScore,
            'is_favorite'     => $isFav,
        ]);
    }
}