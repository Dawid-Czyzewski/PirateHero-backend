<?php

declare(strict_types=1);

namespace App\Dto\Api\Ship;

final readonly class ShipMessageDto
{
    /**
     * @param array{id: int|string, username: string}|null $author
     */
    public function __construct(
        public int $id,
        public string $content,
        public string $createdAt,
        public bool $isSystem,
        public ?array $author = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'id' => $this->id,
            'content' => $this->content,
            'createdAt' => $this->createdAt,
            'isSystem' => $this->isSystem,
        ];

        if ($this->author !== null) {
            $payload['author'] = $this->author;
        }

        return $payload;
    }
}
