<?php
namespace App\Controller;

use App\Entity\BreedFavorite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class FavoriteController extends AbstractController
{
    // Toggle favori
    #[Route('/favorites/{breedId}', methods: ['POST'])]
    public function toggle(string $breedId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sessionId = $request->headers->get('X-Session-Id');
        if (!$sessionId) {
            return $this->json(['error' => 'Session manquante'], 400);
        }

        $repo = $em->getRepository(BreedFavorite::class);
        $existing = $repo->findOneBy(['breedId' => $breedId, 'sessionId' => $sessionId]);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            return $this->json(['breed_id' => $breedId, 'favorited' => false]);
        }

        $fav = new BreedFavorite();
        $fav->setBreedId($breedId);
        $fav->setSessionId($sessionId);
        $fav->setCreatedAt(new \DateTimeImmutable());
        $em->persist($fav);
        $em->flush();

        return $this->json(['breed_id' => $breedId, 'favorited' => true]);
    }

    // Liste des favoris de la session
    #[Route('/favorites', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sessionId = $request->headers->get('X-Session-Id');
        if (!$sessionId) {
            return $this->json([]);
        }

        $favs = $em->getRepository(BreedFavorite::class)->findBy(['sessionId' => $sessionId]);
        return $this->json(array_map(fn($f) => $f->getBreedId(), $favs));
    }

    // Statut d'un favori
    #[Route('/favorites/{breedId}', methods: ['GET'])]
    public function status(string $breedId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sessionId = $request->headers->get('X-Session-Id');
        $existing = $sessionId
            ? $em->getRepository(BreedFavorite::class)->findOneBy(['breedId' => $breedId, 'sessionId' => $sessionId])
            : null;

        return $this->json(['breed_id' => $breedId, 'favorited' => (bool) $existing]);
    }
}