<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api;

use App\Character\Application\Command\CreateCharacter\CreateCharacterCommand;
use App\Character\Domain\Model\Appearance;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateCharacterInput
{
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: Appearance::FACE_VARIANTS - 1)]
    public ?int $face = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: Appearance::HAIR_VARIANTS - 1)]
    public ?int $hair = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: Appearance::BEARD_VARIANTS - 1)]
    public ?int $beard = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: Appearance::SKIN_VARIANTS - 1)]
    public ?int $skin = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: Appearance::TATTOO_VARIANTS - 1)]
    public ?int $tattoo = null;

    public function toCommand(string $userId): CreateCharacterCommand
    {
        return new CreateCharacterCommand(
            $userId,
            $this->face ?? throw new \LogicException('Input must be validated before use.'),
            $this->hair ?? throw new \LogicException('Input must be validated before use.'),
            $this->beard ?? throw new \LogicException('Input must be validated before use.'),
            $this->skin ?? throw new \LogicException('Input must be validated before use.'),
            $this->tattoo ?? throw new \LogicException('Input must be validated before use.'),
        );
    }
}
