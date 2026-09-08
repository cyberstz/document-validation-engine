<?php

declare(strict_types=1);

namespace DocumentValidation\Tests;

use DocumentValidation\Document;
use DocumentValidation\InMemoryRuleProvider;
use DocumentValidation\Tests\Fixtures\FailingRule;
use DocumentValidation\Tests\Fixtures\PassingRule;
use DocumentValidation\Tests\Fixtures\RecordingRule;
use DocumentValidation\Tests\Fixtures\ThrowingRule;
use DocumentValidation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    #[Test]
    public function a_document_is_valid_when_every_applicable_rule_passes(): void
    {
        $validator = new Validator(new InMemoryRuleProvider([
            'acme' => [new PassingRule(), new PassingRule()],
        ]));

        $result = $validator->validate(self::document());

        self::assertTrue($result->isValid());
        self::assertSame([], $result->errors());
        self::assertCount(2, $result->ruleResults);
    }

    #[Test]
    public function it_collects_errors_from_every_failing_rule_not_just_the_first(): void
    {
        $validator = new Validator(new InMemoryRuleProvider([
            'acme' => [
                new FailingRule('first'),
                new PassingRule(),
                new FailingRule('second', 'third'),
            ],
        ]));

        $result = $validator->validate(self::document());

        self::assertFalse($result->isValid());
        self::assertSame(['first', 'second', 'third'], $result->errors());
    }

    #[Test]
    public function it_applies_only_the_rules_belonging_to_the_documents_tenant(): void
    {
        $validator = new Validator(new InMemoryRuleProvider([
            'acme' => [new PassingRule()],
            'globex' => [new FailingRule('should never run')],
        ]));

        $result = $validator->validate(self::document('acme'));

        self::assertTrue($result->isValid());
        self::assertCount(1, $result->ruleResults);
        self::assertSame(PassingRule::TYPE, $result->ruleResults[0]->ruleType);
    }

    #[Test]
    public function a_tenant_without_configured_rules_produces_a_valid_result(): void
    {
        $validator = new Validator(new InMemoryRuleProvider());

        $result = $validator->validate(self::document('initech'));

        self::assertTrue($result->isValid());
        self::assertSame([], $result->ruleResults);
    }

    #[Test]
    public function a_rule_that_throws_is_reported_as_a_failure_instead_of_aborting_the_run(): void
    {
        $validator = new Validator(new InMemoryRuleProvider([
            'acme' => [new ThrowingRule(new RuntimeException('storage is down'))],
        ]));

        $result = $validator->validate(self::document());

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->ruleResults);
        self::assertSame(ThrowingRule::TYPE, $result->ruleResults[0]->ruleType);
        self::assertStringContainsString('could not be evaluated', $result->errors()[0]);
        self::assertStringContainsString('storage is down', $result->errors()[0]);
        self::assertStringContainsString(RuntimeException::class, $result->errors()[0]);
    }

    #[Test]
    public function a_broken_rule_does_not_prevent_the_remaining_rules_from_running(): void
    {
        $validator = new Validator(new InMemoryRuleProvider([
            'acme' => [
                new ThrowingRule(new RuntimeException('boom')),
                new PassingRule(),
            ],
        ]));

        $result = $validator->validate(self::document());

        self::assertCount(2, $result->ruleResults);
        self::assertTrue($result->ruleResults[0]->failed());
        self::assertTrue($result->ruleResults[1]->passed());
    }

    #[Test]
    public function every_rule_receives_the_document_under_validation(): void
    {
        $recordingRule = new RecordingRule();

        (new Validator(new InMemoryRuleProvider(['acme' => [$recordingRule]])))
            ->validate(self::document());

        self::assertSame(['doc-1'], $recordingRule->documentIdsSeen);
    }

    private static function document(string $tenantId = 'acme'): Document
    {
        return new Document('doc-1', $tenantId, 'Content.', ['author' => 'Ada']);
    }
}
