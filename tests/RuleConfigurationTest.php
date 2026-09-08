<?php

declare(strict_types=1);

namespace DocumentValidation\Tests;

use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\RuleConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RuleConfiguration::class)]
final class RuleConfigurationTest extends TestCase
{
    #[Test]
    public function it_reads_an_integer(): void
    {
        self::assertSame(120, self::config(['max_bytes' => 120])->requireInt('max_bytes'));
    }

    #[Test]
    public function it_accepts_an_integer_that_arrived_as_a_string(): void
    {
        self::assertSame(120, self::config(['max_bytes' => '120'])->requireInt('max_bytes'));
        self::assertSame(-5, self::config(['max_bytes' => '-5'])->requireInt('max_bytes'));
    }

    #[Test]
    #[DataProvider('nonIntegerValues')]
    public function it_rejects_a_value_that_is_not_an_integer(mixed $value): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('expects "max_bytes" to be an integer');

        self::config(['max_bytes' => $value])->requireInt('max_bytes');
    }

    #[Test]
    public function a_missing_key_names_both_the_rule_and_the_key(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('Rule "demo_rule" requires the configuration key "max_bytes"');

        self::config([])->requireInt('max_bytes');
    }

    #[Test]
    public function it_reads_a_list_and_reindexes_it(): void
    {
        $list = self::config(['fields' => [3 => 'author', 7 => 'department']])->requireList('fields');

        self::assertSame(['author', 'department'], $list);
    }

    #[Test]
    public function it_rejects_a_list_value_that_is_not_an_array(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('expects "fields" to be a list, string given');

        self::config(['fields' => 'author'])->requireList('fields');
    }

    #[Test]
    public function an_optional_boolean_falls_back_to_its_default(): void
    {
        self::assertTrue(self::config([])->optionalBool('case_sensitive', true));
        self::assertFalse(self::config([])->optionalBool('case_sensitive', false));
    }

    #[Test]
    #[DataProvider('truthyValues')]
    public function it_accepts_the_encoded_forms_of_true(mixed $value): void
    {
        self::assertTrue(self::config(['case_sensitive' => $value])->optionalBool('case_sensitive', false));
    }

    #[Test]
    #[DataProvider('falsyValues')]
    public function it_accepts_the_encoded_forms_of_false(mixed $value): void
    {
        self::assertFalse(self::config(['case_sensitive' => $value])->optionalBool('case_sensitive', true));
    }

    #[Test]
    public function it_rejects_a_value_that_is_not_a_boolean(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('expects "case_sensitive" to be a boolean');

        self::config(['case_sensitive' => 'yes'])->optionalBool('case_sensitive', false);
    }

    public static function nonIntegerValues(): iterable
    {
        yield 'null' => [null];
        yield 'float' => [1.5];
        yield 'non-numeric string' => ['a lot'];
        yield 'numeric string with a decimal point' => ['12.0'];
        yield 'array' => [[120]];
        yield 'bool' => [true];
    }

    public static function truthyValues(): iterable
    {
        yield 'bool' => [true];
        yield 'int' => [1];
        yield 'string int' => ['1'];
        yield 'string literal' => ['true'];
    }

    public static function falsyValues(): iterable
    {
        yield 'bool' => [false];
        yield 'int' => [0];
        yield 'string int' => ['0'];
        yield 'string literal' => ['false'];
    }

    private static function config(array $values): RuleConfiguration
    {
        return new RuleConfiguration($values, 'demo_rule');
    }
}
