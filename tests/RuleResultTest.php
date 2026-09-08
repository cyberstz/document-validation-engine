<?php

declare(strict_types=1);

namespace DocumentValidation\Tests;

use DocumentValidation\RuleResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RuleResult::class)]
final class RuleResultTest extends TestCase
{
    #[Test]
    public function a_passing_result_carries_no_errors(): void
    {
        $result = RuleResult::pass('some_rule');

        self::assertTrue($result->passed());
        self::assertFalse($result->failed());
        self::assertSame([], $result->errors);
        self::assertSame('some_rule', $result->ruleType);
    }

    #[Test]
    public function a_failing_result_carries_every_message_it_was_given(): void
    {
        $result = RuleResult::fail('some_rule', 'first problem', 'second problem');

        self::assertTrue($result->failed());
        self::assertFalse($result->passed());
        self::assertSame(['first problem', 'second problem'], $result->errors);
    }

    #[Test]
    public function a_failure_without_an_explanation_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one error message');

        RuleResult::fail('some_rule');
    }

    #[Test]
    public function from_errors_produces_a_pass_when_nothing_went_wrong(): void
    {
        self::assertTrue(RuleResult::fromErrors('some_rule', [])->passed());
    }

    #[Test]
    public function from_errors_produces_a_failure_when_something_went_wrong(): void
    {
        $result = RuleResult::fromErrors('some_rule', ['problem']);

        self::assertTrue($result->failed());
        self::assertSame(['problem'], $result->errors);
    }
}
