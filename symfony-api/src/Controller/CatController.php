<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
final class CatController extends AbstractController
{
    #[Route('/cats', name: 'cats_list', methods: ['GET'])]
    public function index(HttpClientInterface $client): JsonResponse
    {
        $headers = [
        'x-api-key' => 'live_m7U1JLOcnWSmGPLZhMprAFeDXCKxspEgNDoajnK2AF8EOcDb28KsNA880lLtxZug',
                ];
        $response = $client->request(
        'GET',
        'https://api.thecatapi.com/v1/breeds',
                    ['headers' => $headers]
                );
        $cats = $response->toArray();
        $formattedCats = array_map(function ($cat) {
        return [
        'name'        => $cat['name'] ?? null,
        'origin'      => $cat['origin'] ?? null,
        'temperament' => $cat['temperament'] ?? null,
        'description' => $cat['description'] ?? null,
        'image_id'   => $cat['reference_image_id'] ?? null,
                    ];
                }, $cats);
        return $this->json($formattedCats);
    }

    #[Route('/cats/{id}', name: 'cats_show', methods: ['GET'])]
    public function show(string $id, HttpClientInterface $client): JsonResponse
    {
        $headers = [
            'x-api-key' => 'live_m7U1JLOcnWSmGPLZhMprAFeDXCKxspEgNDoajnK2AF8EOcDb28KsNA880lLtxZug',
        ];

        $response = $client->request(
            'GET',
            'https://api.thecatapi.com/v1/breeds/' . $id,
            ['headers' => $headers]
        );

        $cat = $response->toArray();

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
        ]);
    }
}