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
        $response = $client->request(
            'GET',
            'https://api.thecatapi.com/v1/breeds'
        );

        $cats = $response->toArray();

        $formattedCats = array_map(function ($cat) {
            return [
                'name' => $cat['name'] ?? null,
                'origin' => $cat['origin'] ?? null,
                'temperament' => $cat['temperament'] ?? null,
                'description' => $cat['description'] ?? null,
            ];
        }, $cats);

        return $this->json($formattedCats);
    }
}