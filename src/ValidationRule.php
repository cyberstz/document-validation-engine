<?php

declare(strict_types=1);

namespace DocumentValidation;

interface ValidationRule
{
    public function type(): string;

    public function validate(Document $document): RuleResult;
}
