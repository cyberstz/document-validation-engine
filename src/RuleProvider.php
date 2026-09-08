<?php

declare(strict_types=1);

namespace DocumentValidation;

interface RuleProvider
{
    public function rulesFor(string $tenantId): array;
}
