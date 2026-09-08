<?php

declare(strict_types=1);

namespace DocumentValidation;

use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\Exceptions\UnknownRuleType;
use DocumentValidation\Rules\MaximumDocumentSize;
use DocumentValidation\Rules\ProhibitedWords;
use DocumentValidation\Rules\RequiredMetadataFields;

final class RuleFactory
{
    private array $ruleClasses = [];

    public static function withDefaultRules(): self
    {
        return (new self())
            ->register(MaximumDocumentSize::class)
            ->register(RequiredMetadataFields::class)
            ->register(ProhibitedWords::class);
    }

    public function register(string $ruleClass): self
    {
        if (!is_subclass_of($ruleClass, ValidationRule::class)) {
            throw new InvalidRuleConfiguration(
                sprintf(
                    '"%s" cannot be registered: it does not implement %s.',
                    $ruleClass,
                    ValidationRule::class
                )
            );
        }

        $type = $ruleClass::type();

        if (trim($type) === '') {
            throw new InvalidRuleConfiguration(
                sprintf(
                    '%s must declare a non-blank rule type.',
                    $ruleClass
                )
            );
        }

        if (isset($this->ruleClasses[$type])) {
            throw new InvalidRuleConfiguration(
                sprintf(
                    'Rule type "%s" is already registered by %s.',
                    $type,
                    $this->ruleClasses[$type]
                )
            );
        }

        $this->ruleClasses[$type] = $ruleClass;

        return $this;
    }

    public function create(string $type, array $config = []): ValidationRule
    {
        $ruleClass = $this->ruleClasses[$type]
            ?? throw UnknownRuleType::named($type, $this->registeredTypes());

        return $ruleClass::fromConfiguration(new RuleConfiguration($config, $type));
    }

    public function registeredTypes(): array
    {
        $types = array_keys($this->ruleClasses);

        // For stable order
        sort($types);

        return $types;
    }
}
