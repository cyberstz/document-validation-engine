<?php

declare(strict_types=1);

namespace DocumentValidation\Rules;

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\RuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;

final readonly class RequiredMetadataFields implements ValidationRule
{
    public const string TYPE = 'required_metadata_fields';

    private array $fields;

    public function __construct(array $fields)
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (!is_string($field)) {
                throw new InvalidRuleConfiguration(
                    sprintf(
                        'Metadata field names must be strings, %s given.',
                        get_debug_type($field)
                    )
                );
            }

            $field = trim($field);

            if ($field === '') {
                throw new InvalidRuleConfiguration('Metadata field names cannot be blank.');
            }

            if (!in_array($field, $normalized, true)) {
                $normalized[] = $field;
            }
        }

        if ($normalized === []) {
            throw new InvalidRuleConfiguration('At least one required metadata field must be configured.');
        }

        $this->fields = $normalized;
    }

    public static function type(): string
    {
        return self::TYPE;
    }

    public static function fromConfiguration(RuleConfiguration $configuration): static
    {
        return new self($configuration->requireList('fields'));
    }

    public function validate(Document $document): RuleResult
    {
        $errors = [];

        foreach ($this->fields as $field) {
            if (!$this->isSatisfiedBy($document->metadata, $field)) {
                $errors[] = sprintf('Required metadata field "%s" is missing or empty.', $field);
            }
        }

        return RuleResult::fromErrors(self::TYPE, $errors);
    }

    private function isSatisfiedBy(array $metadata, string $field): bool
    {
        if (!array_key_exists($field, $metadata)) {
            return false;
        }

        $value = $metadata[$field];

        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }
}
