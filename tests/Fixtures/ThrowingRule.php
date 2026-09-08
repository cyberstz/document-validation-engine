<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Fixtures;

use DocumentValidation\Document;
use DocumentValidation\RuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;
use RuntimeException;
use Throwable;

final readonly class ThrowingRule implements ValidationRule
{
    public const string TYPE = 'throwing_rule';

    public function __construct(private Throwable $exception)
    {
    }

    public static function type(): string
    {
        return self::TYPE;
    }

    public static function fromConfiguration(RuleConfiguration $configuration): static
    {
        return new self(new RuntimeException('configured to throw'));
    }

    public function validate(Document $document): RuleResult
    {
        throw $this->exception;
    }
}
