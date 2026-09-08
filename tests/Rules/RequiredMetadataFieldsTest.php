<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Rules;

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\Rules\RequiredMetadataFields;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequiredMetadataFields::class)]
final class RequiredMetadataFieldsTest extends TestCase
{
    #[Test]
    public function it_passes_when_every_required_field_carries_a_value(): void
    {
        $rule = new RequiredMetadataFields(['author', 'department']);

        $result = $rule->validate(self::document(['author' => 'Ada', 'department' => 'R&D']));

        self::assertTrue($result->passed());
    }

    #[Test]
    public function unrelated_metadata_is_ignored(): void
    {
        $rule = new RequiredMetadataFields(['author']);

        self::assertTrue($rule->validate(self::document(['author' => 'Ada', 'extra' => 'x']))->passed());
    }

    #[Test]
    public function it_reports_every_missing_field_not_only_the_first(): void
    {
        $rule = new RequiredMetadataFields(['author', 'department', 'reviewer']);

        $result = $rule->validate(self::document(['department' => 'R&D']));

        self::assertTrue($result->failed());
        self::assertCount(2, $result->errors);
        self::assertStringContainsString('"author"', $result->errors[0]);
        self::assertStringContainsString('"reviewer"', $result->errors[1]);
    }

    #[Test]
    public function it_fails_when_metadata_is_entirely_absent(): void
    {
        $result = (new RequiredMetadataFields(['author']))->validate(self::document([]));

        self::assertTrue($result->failed());
        self::assertCount(1, $result->errors);
    }

    #[Test]
    #[DataProvider('valuelessEntries')]
    public function a_field_present_but_carrying_no_value_does_not_satisfy_the_requirement(mixed $value): void
    {
        $result = (new RequiredMetadataFields(['author']))->validate(self::document(['author' => $value]));

        self::assertTrue($result->failed());
    }

    #[Test]
    #[DataProvider('meaningfulFalsyValues')]
    public function falsy_but_meaningful_values_do_satisfy_the_requirement(mixed $value): void
    {
        $result = (new RequiredMetadataFields(['page_count']))->validate(
            self::document(['page_count' => $value]),
        );

        self::assertTrue($result->passed());
    }

    #[Test]
    public function a_field_listed_twice_is_reported_once(): void
    {
        $result = (new RequiredMetadataFields(['author', 'author', ' author ']))->validate(self::document([]));

        self::assertCount(1, $result->errors);
    }

    #[Test]
    public function it_rejects_an_empty_field_list(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('At least one required metadata field');

        new RequiredMetadataFields([]);
    }

    #[Test]
    public function it_rejects_a_blank_field_name(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('cannot be blank');

        new RequiredMetadataFields(['author', '  ']);
    }

    #[Test]
    public function it_rejects_a_field_name_that_is_not_a_string(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('must be strings, int given');

        new RequiredMetadataFields(['author', 42]);
    }

    public static function valuelessEntries(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
    }

    public static function meaningfulFalsyValues(): iterable
    {
        yield 'zero' => [0];
        yield 'zero float' => [0.0];
        yield 'false' => [false];
        yield 'string zero' => ['0'];
    }

    private static function document(array $metadata): Document
    {
        return new Document('doc-1', 'acme', 'Content.', $metadata);
    }
}
