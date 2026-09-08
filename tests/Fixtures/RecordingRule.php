<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Fixtures;

use DocumentValidation\Document;
use DocumentValidation\RuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;

final class RecordingRule implements ValidationRule
{
    public const string TYPE = 'recording_rule';

    public array $documentIdsSeen = [];

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
        $this->documentIdsSeen[] = $document->id;

        return RuleResult::pass(self::TYPE);
    }
}
