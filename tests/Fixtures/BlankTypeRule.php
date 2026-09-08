<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Fixtures;

use DocumentValidation\Document;
use DocumentValidation\RuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;

final readonly class BlankTypeRule implements ValidationRule
{
    public static function type(): string
    {
        return '   ';
    }

    public static function fromConfiguration(RuleConfiguration $configuration): static
    {
        return new self();
    }

    public function validate(Document $document): RuleResult
    {
        return RuleResult::pass('   ');
    }
}
