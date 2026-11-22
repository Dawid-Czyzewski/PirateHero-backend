<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

final readonly class Npc
{
    public function __construct(
        public string $id,
        public Fighter $fighter,
        public NpcLook $look,
    ) {
    }

    public function nick(): string
    {
        return $this->fighter->name();
    }

    public function level(): int
    {
        return $this->fighter->level();
    }
}
