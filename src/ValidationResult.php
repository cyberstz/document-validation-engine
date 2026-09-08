<?php

declare(strict_types=1);

namespace DocumentValidation;

final readonly class ValidationResult
{
    private function __construct(public array $ruleResults) {}

    public static function from(iterable $ruleResults): self
    {
        $collected = [];

        foreach ($ruleResults as $ruleResult) {
            $collected[] = $ruleResult;
        }

        return new self($collected);
    }

    public function isValid(): bool
    {
        return $this->failures() === [];
    }

    public function errors(): array
    {
        $errors = [];

        foreach ($this->ruleResults as $ruleResult) {
            foreach ($ruleResult->errors as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    public function failures(): array
    {
        return array_values(
            array_filter(
                $this->ruleResults,
                static fn (RuleResult $ruleResult): bool => $ruleResult->failed(),
            ),
        );
    }
}
