<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Fixtures;

use DocumentValidation\Document;
use DocumentValidation\RuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;

final readonly class FailingRule implements ValidationRule
{
    public const string TYPE = 'failing_rule';

    private array $errors;

    public function __construct(string ...$errors)
    {
        $this->errors = array_values($errors);
    }

    public static function type(): string
    {
        return self::TYPE;
    }

    public static function fromConfiguration(RuleConfiguration $configuration): static
    {
        return new self('configured failure');
    }

    public function validate(Document $document): RuleResult
    {
        return RuleResult::fail(self::TYPE, ...$this->errors);
    }
}
