<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

final readonly class NpcLook
{
    public function __construct(
        public int $face,
        public int $hair,
        public int $beard,
        public int $skin,
        public int $tattoo,
    ) {
    }

    /**
     * @return array{face: int, hair: int, beard: int, skin: int, tattoo: int}
     */
    public function toArray(): array
    {
        return [
            'face' => $this->face,
            'hair' => $this->hair,
            'beard' => $this->beard,
            'skin' => $this->skin,
            'tattoo' => $this->tattoo,
        ];
    }
}
