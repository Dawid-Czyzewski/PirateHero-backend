<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\MissionController;
use App\Repository\MissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MissionRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/missions/{id}/start',
            controller: MissionController::class.'::startMission',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'start_mission'
        ),
        new Post(
            uriTemplate: '/missions/{id}/cancel',
            controller: MissionController::class.'::cancelMission',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'cancel_mission'
        ),
        new Post(
            uriTemplate: '/missions/{id}/complete',
            controller: MissionController::class.'::completeMission',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'complete_mission'
        ),
    ],
)]
class Mission
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
    private ?int $goldReward = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $expReward = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $durationInSeconds = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read'])]
    private ?int $energyCost = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'missions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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

    public function getGoldReward(): ?int
    {
        return $this->goldReward;
    }

    public function setGoldReward(int $goldReward): static
    {
        $this->goldReward = $goldReward;

        return $this;
    }

    public function getExpReward(): ?int
    {
        return $this->expReward;
    }

    public function setExpReward(int $expReward): static
    {
        $this->expReward = $expReward;

        return $this;
    }

    public function getDurationInSeconds(): ?int
    {
        return $this->durationInSeconds;
    }

    public function setDurationInSeconds(int $durationInSeconds): static
    {
        $this->durationInSeconds = $durationInSeconds;

        return $this;
    }

    public function getEnergyCost(): ?int
    {
        return $this->energyCost;
    }

    public function setEnergyCost(int $energyCost): self
    {
        $this->energyCost = $energyCost;

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
}
