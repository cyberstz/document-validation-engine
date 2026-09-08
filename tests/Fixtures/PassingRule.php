<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Fixtures;

use DocumentValidation\Document;
use DocumentValidation\RuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;

final readonly class PassingRule implements ValidationRule
{
    public const string TYPE = 'passing_rule';

    public static function type(): string
    {
        return self::TYPE;
    }

    public static function fromConfiguration(RuleConfiguration $configuration): static
    {
        return new self();
    }

    public function validate(Document $document): RuleResult
    {
        return RuleResult::pass(self::TYPE);
    }
}
