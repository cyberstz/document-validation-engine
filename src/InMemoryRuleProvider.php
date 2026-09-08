<?php

declare(strict_types=1);

namespace DocumentValidation;

final class InMemoryRuleProvider implements RuleProvider
{
    private array $rulesByTenant = [];

    public function __construct(array $rulesByTenant = [])
    {
        foreach ($rulesByTenant as $tenantId => $rules) {
            foreach ($rules as $rule) {
                $this->add((string) $tenantId, $rule);
            }
        }
    }

    public function add(string $tenantId, ValidationRule ...$rules): self
    {
        foreach ($rules as $rule) {
            $this->rulesByTenant[$tenantId][] = $rule;
        }

        return $this;
    }

    public function rulesFor(string $tenantId): array
    {
        return $this->rulesByTenant[$tenantId] ?? [];
    }
}
