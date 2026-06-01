<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'breed_view')]
class BreedView
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $breedId;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $viewCount = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getBreedId(): string { return $this->breedId; }
    public function setBreedId(string $id): void { $this->breedId = $id; }

    public function getViewCount(): int { return $this->viewCount; }
    public function increment(): void { $this->viewCount++; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $dt): void { $this->updatedAt = $dt; }
}