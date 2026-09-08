<?php

declare(strict_types=1);

namespace DocumentValidation;

use InvalidArgumentException;

final readonly class RuleResult
{
    private function __construct(
        public string $ruleType,
        public array $errors,
    ) {}

    public static function pass(string $ruleType): self
    {
        return new self($ruleType, []);
    }

    public static function fail(string $ruleType, string ...$errors): self
    {
        if ($errors === []) {
            throw new InvalidArgumentException('A failed rule result must carry at least one error message.');
        }

        return new self($ruleType, array_values($errors));
    }

    public static function fromErrors(string $ruleType, array $errors): self
    {
        return $errors === []
            ? self::pass($ruleType)
            : self::fail($ruleType, ...$errors);
    }

    public function passed(): bool
    {
        return $this->errors === [];
    }

    public function failed(): bool
    {
        return !$this->passed();
    }
}
