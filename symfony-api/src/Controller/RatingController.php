<?php
namespace App\Controller;

use App\Entity\BreedRating;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RatingController extends AbstractController
{
    // Soumettre ou mettre à jour une note
    #[Route('/ratings/{breedId}', methods: ['POST'])]
    public function rate(string $breedId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sessionId = $request->headers->get('X-Session-Id');
        if (!$sessionId) {
            return $this->json(['error' => 'Session manquante'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $score = (int) ($data['score'] ?? 0);

        if ($score < 1 || $score > 5) {
            return $this->json(['error' => 'Score invalide (1-5)'], 400);
        }

        $repo = $em->getRepository(BreedRating::class);
        $rating = $repo->findOneBy(['breedId' => $breedId, 'sessionId' => $sessionId]);

        if (!$rating) {
            $rating = new BreedRating();
            $rating->setBreedId($breedId);
            $rating->setSessionId($sessionId);
            $rating->setCreatedAt(new \DateTimeImmutable());
        }

        $rating->setScore($score);
        $em->persist($rating);
        $em->flush();

        // Calculer la moyenne
        $avg = $em->createQuery(
            'SELECT AVG(r.score) FROM App\Entity\BreedRating r WHERE r.breedId = :id'
        )->setParameter('id', $breedId)->getSingleScalarResult();

        return $this->json([
            'breed_id'   => $breedId,
            'user_score' => $score,
            'avg_rating' => round((float) $avg, 1),
        ]);
    }

    // Récupérer la note d'une race
    #[Route('/ratings/{breedId}', methods: ['GET'])]
    public function get(string $breedId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sessionId = $request->headers->get('X-Session-Id');

        $userRating = $sessionId
            ? $em->getRepository(BreedRating::class)->findOneBy(['breedId' => $breedId, 'sessionId' => $sessionId])
            : null;

        $avg = $em->createQuery(
            'SELECT AVG(r.score) FROM App\Entity\BreedRating r WHERE r.breedId = :id'
        )->setParameter('id', $breedId)->getSingleScalarResult();

        return $this->json([
            'breed_id'   => $breedId,
            'user_score' => $userRating?->getScore(),
            'avg_rating' => $avg ? round((float) $avg, 1) : null,
        ]);
    }
}