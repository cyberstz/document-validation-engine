<?php

/**
 * Integration script for the document validation engine.
 *
 * Demonstrates the full flow: building rules from stored configuration,
 * resolving the rules that apply to a given tenant, validating documents, and
 * reporting both success and validation errors.
 *
 * Run with: composer demo
 */

declare(strict_types=1);

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\Exceptions\UnknownRuleType;
use DocumentValidation\InMemoryRuleProvider;
use DocumentValidation\RuleFactory;
use DocumentValidation\ValidationResult;
use DocumentValidation\ValidationRule;
use DocumentValidation\Validator;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Rule configuration as it would arrive from persistent storage: one record per
 * configured rule, owned by a tenant and identified by type. Nothing in the
 * component cares that this happens to be a literal array here.
 */
$storedRuleRecords = [
    [
        'tenant_id' => 'acme',
        'type' => 'maximum_document_size',
        'config' => ['max_bytes' => 120],
    ],
    [
        'tenant_id' => 'acme',
        'type' => 'required_metadata_fields',
        'config' => ['fields' => ['author', 'department']],
    ],
    [
        'tenant_id' => 'acme',
        'type' => 'prohibited_words',
        'config' => ['words' => ['confidential', 'internal only']],
    ],
    [
        'tenant_id' => 'globex',
        'type' => 'required_metadata_fields',
        'config' => ['fields' => ['author']],
    ],
    [
        'tenant_id' => 'globex',
        'type' => 'prohibited_words',
        'config' => ['words' => ['draft'], 'case_sensitive' => true],
    ],
];

/**
 * Presentation only — the engine itself never formats anything.
 */
$report = static function (string $label, Document $document, ValidationResult $result): void {
    printf('%s%s', $label, PHP_EOL);
    printf('  document %s, tenant "%s"%s', $document->id, $document->tenantId, PHP_EOL);
    printf('  valid: %s%s', $result->isValid() ? 'yes' : 'no', PHP_EOL);

    if ($result->ruleResults === []) {
        printf('  no rules applied%s%s', PHP_EOL, PHP_EOL);

        return;
    }

    foreach ($result->ruleResults as $ruleResult) {
        printf(
            '  [%s] %s%s',
            $ruleResult->passed() ? 'PASS' : 'FAIL',
            $ruleResult->ruleType,
            PHP_EOL,
        );

        foreach ($ruleResult->errors as $error) {
            printf('         %s%s', $error, PHP_EOL);
        }
    }

    if (! $result->isValid()) {
        // The flat message list required by the specification.
        printf('  %d error message(s) in total%s', count($result->errors()), PHP_EOL);
    }

    echo PHP_EOL;
};

// 1. Create several validation rules from that configuration.
$ruleFactory = RuleFactory::withDefaultRules();
$ruleProvider = new InMemoryRuleProvider();

try {
    foreach ($storedRuleRecords as $record) {
        $ruleProvider->add(
            $record['tenant_id'],
            $ruleFactory->create($record['type'], $record['config']),
        );
    }
} catch (UnknownRuleType | InvalidRuleConfiguration $exception) {
    // A rule that cannot be built is a configuration fault, not a document
    // problem, so it is fatal here rather than reported as a validation error.
    fwrite(STDERR, 'Cannot build the rule set: ' . $exception->getMessage() . PHP_EOL);

    exit(1);
}

printf('Registered rule types: %s%s%s', implode(', ', $ruleFactory->registeredTypes()), PHP_EOL, PHP_EOL);

// 2. Create a validator. It is bound to the provider, not to a rule list.
$validator = new Validator($ruleProvider);

// 3. Determine which rules apply for a given tenant id.
printf('Rules applicable per tenant%s', PHP_EOL);

foreach (['acme', 'globex', 'initech'] as $tenantId) {
    $applicableTypes = array_map(
        static fn (ValidationRule $rule): string => $rule::type(),
        $ruleProvider->rulesFor($tenantId),
    );

    printf(
        '  %-8s %s%s',
        $tenantId,
        $applicableTypes === [] ? '(none configured)' : implode(', ', $applicableTypes),
        PHP_EOL,
    );
}

echo PHP_EOL;

// 4 & 5. Validate documents, handling both success and validation errors.
$documents = [
    'A document that satisfies every rule' => new Document(
        id: 'doc-1',
        tenantId: 'acme',
        content: 'Quarterly summary prepared for the board.',
        metadata: ['author' => 'S. Kovalenko', 'department' => 'Finance'],
    ),
    'A document that violates all three rules' => new Document(
        id: 'doc-2',
        tenantId: 'acme',
        content: str_repeat('This confidential draft is internal only. ', 5),
        metadata: ['author' => '   '],
    ),
    'A document whose tenant has configured no rules' => new Document(
        id: 'doc-3',
        tenantId: 'initech',
        content: 'Nothing constrains this tenant yet.',
        metadata: [],
    ),
];

foreach ($documents as $label => $document) {
    $report($label, $document, $validator->validate($document));
}

exit(0);
