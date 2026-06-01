<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'breed_rating')]
#[ORM\UniqueConstraint(columns: ['breed_id', 'session_id'])]
class BreedRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $breedId;

    #[ORM\Column(type: 'string')]
    private string $sessionId;

    #[ORM\Column(type: 'smallint')]
    private int $score;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function getId(): int { return $this->id; }

    public function getBreedId(): string { return $this->breedId; }
    public function setBreedId(string $id): void { $this->breedId = $id; }

    public function getSessionId(): string { return $this->sessionId; }
    public function setSessionId(string $id): void { $this->sessionId = $id; }

    public function getScore(): int { return $this->score; }
    public function setScore(int $score): void { $this->score = $score; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): void { $this->createdAt = $dt; }
}