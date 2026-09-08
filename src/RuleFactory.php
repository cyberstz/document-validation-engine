<?php

declare(strict_types=1);

namespace DocumentValidation;

use Closure;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\Exceptions\UnknownRuleType;
use DocumentValidation\Rules\MaximumDocumentSize;
use DocumentValidation\Rules\ProhibitedWords;
use DocumentValidation\Rules\RequiredMetadataFields;

final class RuleFactory
{
    private array $builders = [];

    public static function withDefaultRules(): self
    {
        return (new self())
            ->register(
                MaximumDocumentSize::TYPE,
                static fn (RuleConfiguration $config): ValidationRule => new MaximumDocumentSize(
                    $config->requireInt('max_bytes')
                )
            )
            ->register(
                RequiredMetadataFields::TYPE,
                static fn (RuleConfiguration $config): ValidationRule => new RequiredMetadataFields(
                    $config->requireList('fields')
                )
            )
            ->register(
                ProhibitedWords::TYPE,
                static fn (RuleConfiguration $config): ValidationRule => new ProhibitedWords(
                    $config->requireList('words'),
                    $config->optionalBool('case_sensitive', false)
                )
            );
    }

    public function register(string $type, Closure $builder): self
    {
        if (trim($type) === '') {
            throw new InvalidRuleConfiguration('A rule type cannot be blank.');
        }

        if (isset($this->builders[$type])) {
            throw new InvalidRuleConfiguration(sprintf('Rule type "%s" is already registered.', $type));
        }

        $this->builders[$type] = $builder;

        return $this;
    }

    public function create(string $type, array $config = []): ValidationRule
    {
        $builder = $this->builders[$type] ?? throw UnknownRuleType::named($type, $this->registeredTypes());

        return $builder(new RuleConfiguration($config, $type));
    }

    public function registeredTypes(): array
    {
        $types = array_keys($this->builders);

        sort($types);

        return $types;
    }
}
