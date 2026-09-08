<?php

declare(strict_types=1);

namespace DocumentValidation\Rules;

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;

final readonly class MaximumDocumentSize implements ValidationRule
{
    public const string TYPE = 'maximum_document_size';

    public function __construct(private int $maxBytes)
    {
        if ($maxBytes < 1) {
            throw new InvalidRuleConfiguration(
                sprintf(
                    'Maximum document size must be at least 1 byte, %d given.',
                    $maxBytes
                )
            );
        }
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function validate(Document $document): RuleResult
    {
        $actualBytes = $document->sizeInBytes();

        if ($actualBytes <= $this->maxBytes) {
            return RuleResult::pass(self::TYPE);
        }

        return RuleResult::fail(
            self::TYPE,
            sprintf(
                'Document size of %d bytes exceeds the maximum of %d bytes.',
                $actualBytes,
                $this->maxBytes
            )
        );
    }
}
