<?php

declare(strict_types=1);

namespace DocumentValidation\Tests;

use DocumentValidation\RuleResult;
use DocumentValidation\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationResult::class)]
final class ValidationResultTest extends TestCase
{
    #[Test]
    public function a_document_validated_against_no_rules_is_valid(): void
    {
        $result = ValidationResult::from([]);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->errors());
        self::assertSame([], $result->ruleResults);
        self::assertSame([], $result->failures());
    }

    #[Test]
    public function it_is_valid_when_every_rule_passed(): void
    {
        $result = ValidationResult::from([
            RuleResult::pass('rule_a'),
            RuleResult::pass('rule_b'),
        ]);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->errors());
        self::assertCount(2, $result->ruleResults);
    }

    #[Test]
    public function a_single_failure_invalidates_the_whole_result(): void
    {
        $result = ValidationResult::from([
            RuleResult::pass('rule_a'),
            RuleResult::fail('rule_b', 'nope'),
        ]);

        self::assertFalse($result->isValid());
    }

    #[Test]
    public function it_flattens_error_messages_across_every_failed_rule(): void
    {
        $result = ValidationResult::from([
            RuleResult::fail('rule_a', 'first', 'second'),
            RuleResult::pass('rule_b'),
            RuleResult::fail('rule_c', 'third'),
        ]);

        self::assertSame(['first', 'second', 'third'], $result->errors());
    }

    #[Test]
    public function it_retains_passing_rules_so_callers_can_report_per_rule_status(): void
    {
        $result = ValidationResult::from([
            RuleResult::pass('rule_a'),
            RuleResult::fail('rule_b', 'nope'),
        ]);

        self::assertCount(2, $result->ruleResults);
        self::assertCount(1, $result->failures());
        self::assertSame('rule_b', $result->failures()[0]->ruleType);
    }

    #[Test]
    public function it_accepts_any_iterable_of_rule_results(): void
    {
        $generator = (static function (): \Generator {
            yield RuleResult::pass('rule_a');
            yield RuleResult::fail('rule_b', 'nope');
        })();

        $result = ValidationResult::from($generator);

        self::assertCount(2, $result->ruleResults);
        self::assertSame(['nope'], $result->errors());
    }
}
