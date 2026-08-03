<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\WorkController;
use App\Repository\WorkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WorkRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/works/{id}/start',
            controller: WorkController::class.'::startWork',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'start_work'
        ),
        new Post(
            uriTemplate: '/works/{id}/cancel',
            controller: WorkController::class.'::cancelWork',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'cancel_work'
        ),
        new Post(
            uriTemplate: '/works/{id}/complete',
            controller: WorkController::class.'::completeWork',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'complete_work'
        ),
    ],
)]
class Work
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $title = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $hoursCount = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'works', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read'])]
    private ?int $baseGold = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getHoursCount(): ?int
    {
        return $this->hoursCount;
    }

    public function setHoursCount(int $hoursCount): static
    {
        $this->hoursCount = $hoursCount;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBaseGold(): ?int
    {
        return $this->baseGold;
    }

    public function setBaseGold(int $baseGold): static
    {
        $this->baseGold = $baseGold;

        return $this;
    }
}
