<?php

declare(strict_types=1);

namespace DocumentValidation\Rules;

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\RuleResult;
use DocumentValidation\ValidationRule;
use RuntimeException;

final readonly class ProhibitedWords implements ValidationRule
{
    public const string TYPE = 'prohibited_words';

    private array $words;

    public function __construct(array $words, private bool $caseSensitive = false)
    {
        $normalized = [];

        foreach ($words as $word) {
            if (!is_string($word)) {
                throw new InvalidRuleConfiguration(
                    sprintf(
                        'Prohibited words must be strings, %s given.',
                        get_debug_type($word)
                    )
                );
            }

            $word = trim($word);

            if ($word === '') {
                throw new InvalidRuleConfiguration('Prohibited words cannot be blank.');
            }

            if (!in_array($word, $normalized, true)) {
                $normalized[] = $word;
            }
        }

        if ($normalized === []) {
            throw new InvalidRuleConfiguration('At least one prohibited word must be configured.');
        }

        $this->words = $normalized;
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function validate(Document $document): RuleResult
    {
        $errors = [];

        foreach ($this->words as $word) {
            if ($this->occursIn($document->content, $word)) {
                $errors[] = sprintf('Document contains the prohibited word "%s".', $word);
            }
        }

        return RuleResult::fromErrors(self::TYPE, $errors);
    }

    private function occursIn(string $content, string $word): bool
    {
        $pattern = '/(?<!\w)' . preg_quote($word, '/') . '(?!\w)/u';

        if (!$this->caseSensitive) {
            $pattern .= 'i';
        }

        $matched = preg_match($pattern, $content);

        if ($matched === false) {
            throw new RuntimeException(
                sprintf(
                    'Could not scan document content for "%s": %s.',
                    $word,
                    preg_last_error_msg()
                )
            );
        }

        return $matched === 1;
    }
}
