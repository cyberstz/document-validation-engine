<?php

declare(strict_types=1);

namespace DocumentValidation;

use InvalidArgumentException;

final readonly class Document
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $content,
        public array $metadata = [],
    ) {
        if (trim($this->id) === '') {
            throw new InvalidArgumentException('A document must have a non-empty id.');
        }

        if (trim($this->tenantId) === '') {
            throw new InvalidArgumentException('A document must belong to a tenant.');
        }
    }

    public function sizeInBytes(): int
    {
        return strlen($this->content);
    }
}
