<?php

declare(strict_types=1);

namespace DocumentValidation;

use Throwable;

final readonly class Validator
{
    public function __construct(private RuleProvider $ruleProvider)
    {
    }

    public function validate(Document $document): ValidationResult
    {
        $ruleResults = [];

        foreach ($this->ruleProvider->rulesFor($document->tenantId) as $rule) {
            $ruleResults[] = $this->evaluate($rule, $document);
        }

        return ValidationResult::from($ruleResults);
    }

    private function evaluate(ValidationRule $rule, Document $document): RuleResult
    {
        try {
            return $rule->validate($document);
        } catch (Throwable $exception) {
            return RuleResult::fail(
                $rule->type(),
                sprintf(
                    'Rule "%s" could not be evaluated (%s): %s',
                    $rule->type(),
                    $exception::class,
                    $exception->getMessage(),
                ),
            );
        }
    }
}
