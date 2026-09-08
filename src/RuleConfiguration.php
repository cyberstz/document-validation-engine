<?php

declare(strict_types=1);

namespace DocumentValidation;

use DocumentValidation\Exceptions\InvalidRuleConfiguration;

final readonly class RuleConfiguration
{
    public function __construct(
        private array $values,
        private string $ruleType,
    ) {}

    public function requireInt(string $key): int
    {
        $value = $this->requireKey($key);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw $this->typeError($key, 'an integer', $value);
    }

    public function requireList(string $key): array
    {
        $value = $this->requireKey($key);

        if (!is_array($value)) {
            throw $this->typeError($key, 'a list', $value);
        }

        return array_values($value);
    }

    public function optionalBool(string $key, bool $default = false): bool
    {
        if (!array_key_exists($key, $this->values)) {
            return $default;
        }

        $value = $this->values[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (in_array($value, [1, '1', 'true'], true)) {
            return true;
        }

        if (in_array($value, [0, '0', 'false'], true)) {
            return false;
        }

        throw $this->typeError($key, 'a boolean', $value);
    }

    private function requireKey(string $key): mixed
    {
        if (!array_key_exists($key, $this->values)) {
            throw new InvalidRuleConfiguration(
                sprintf(
                    'Rule "%s" requires the configuration key "%s".',
                    $this->ruleType,
                    $key
                )
            );
        }

        return $this->values[$key];
    }

    private function typeError(string $key, string $expected, mixed $actual): InvalidRuleConfiguration
    {
        return new InvalidRuleConfiguration(
            sprintf(
                'Rule "%s" expects "%s" to be %s, %s given.',
                $this->ruleType,
                $key,
                $expected,
                get_debug_type($actual)
            )
        );
    }
}
