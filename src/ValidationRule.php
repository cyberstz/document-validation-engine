<?php

declare(strict_types=1);

namespace DocumentValidation;

interface ValidationRule
{
    public static function type(): string;

    public static function fromConfiguration(RuleConfiguration $configuration): static;

    public function validate(Document $document): RuleResult;
}
