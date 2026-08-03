<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserAvailableBoosterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserAvailableBoosterRepository::class)]
class UserAvailableBooster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userAvailableBoosters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: BoosterTemplate::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private ?BoosterTemplate $boosterTemplate = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $price = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?bool $useGold = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBoosterTemplate(): ?BoosterTemplate
    {
        return $this->boosterTemplate;
    }

    public function setBoosterTemplate(?BoosterTemplate $boosterTemplate): static
    {
        $this->boosterTemplate = $boosterTemplate;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isUseGold(): ?bool
    {
        return $this->useGold;
    }

    public function setUseGold(bool $useGold): static
    {
        $this->useGold = $useGold;

        return $this;
    }
}
