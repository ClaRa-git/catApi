<?php
namespace App\Controller;

use App\Entity\BreedView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ViewsController extends AbstractController
{
    #[Route('/views/{breedId}', name: 'views_increment', methods: ['POST'])]
    public function increment(string $breedId, EntityManagerInterface $em): JsonResponse
    {
        $view = $em->getRepository(BreedView::class)->find($breedId);

        if (!$view) {
            $view = new BreedView();
            $view->setBreedId($breedId);
        }

        $view->increment();
        $view->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($view);
        $em->flush();

        return $this->json(['breed_id' => $breedId, 'view_count' => $view->getViewCount()]);
    }

    #[Route('/views/{breedId}', name: 'views_get', methods: ['GET'])]
    public function get(string $breedId, EntityManagerInterface $em): JsonResponse
    {
        $view = $em->getRepository(BreedView::class)->find($breedId);
        return $this->json(['breed_id' => $breedId, 'view_count' => $view?->getViewCount() ?? 0]);
    }
}