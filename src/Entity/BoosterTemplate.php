<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\BoosterType;
use App\Repository\BoosterTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BoosterTemplateRepository::class)]
class BoosterTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', enumType: BoosterType::class)]
    #[Groups(['user:read'])]
    private ?BoosterType $type = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $effectAmount = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $tier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?BoosterType
    {
        return $this->type;
    }

    public function setType(BoosterType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEffectAmount(): ?int
    {
        return $this->effectAmount;
    }

    public function setEffectAmount(int $effectAmount): static
    {
        $this->effectAmount = $effectAmount;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTier(): ?int
    {
        return $this->tier;
    }

    public function setTier(int $tier): static
    {
        $this->tier = $tier;

        return $this;
    }
}
