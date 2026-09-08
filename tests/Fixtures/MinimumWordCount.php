<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Fixtures;

use DocumentValidation\Document;
use DocumentValidation\RuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;

final readonly class MinimumWordCount implements ValidationRule
{
    public const string TYPE = 'minimum_word_count';

    public function __construct(private int $minimumWords)
    {
    }

    public static function type(): string
    {
        return self::TYPE;
    }

    public static function fromConfiguration(RuleConfiguration $configuration): static
    {
        return new self($configuration->requireInt('min_words'));
    }

    public function validate(Document $document): RuleResult
    {
        $words = str_word_count($document->content);

        if ($words >= $this->minimumWords) {
            return RuleResult::pass(self::TYPE);
        }

        return RuleResult::fail(self::TYPE, sprintf(
            'Document has %d words, at least %d are required.',
            $words,
            $this->minimumWords,
        ));
    }
}
