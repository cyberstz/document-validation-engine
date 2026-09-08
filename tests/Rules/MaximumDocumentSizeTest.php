<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Rules;

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\Rules\MaximumDocumentSize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaximumDocumentSize::class)]
final class MaximumDocumentSizeTest extends TestCase
{
    #[Test]
    public function it_passes_a_document_below_the_limit(): void
    {
        self::assertTrue((new MaximumDocumentSize(10))->validate(self::document('12345'))->passed());
    }

    #[Test]
    public function it_passes_a_document_exactly_on_the_limit(): void
    {
        self::assertTrue((new MaximumDocumentSize(5))->validate(self::document('12345'))->passed());
    }

    #[Test]
    public function it_fails_a_document_one_byte_over_the_limit(): void
    {
        $result = (new MaximumDocumentSize(4))->validate(self::document('12345'));

        self::assertTrue($result->failed());
        self::assertSame(MaximumDocumentSize::TYPE, $result->ruleType);
        self::assertStringContainsString('5 bytes exceeds the maximum of 4 bytes', $result->errors[0]);
    }

    #[Test]
    public function it_passes_an_empty_document(): void
    {
        self::assertTrue((new MaximumDocumentSize(1))->validate(self::document(''))->passed());
    }

    #[Test]
    public function multibyte_characters_are_counted_as_the_bytes_they_occupy(): void
    {
        // Three two-byte characters: six bytes, three characters
        $document = self::document('абв');

        self::assertTrue((new MaximumDocumentSize(6))->validate($document)->passed());
        self::assertTrue((new MaximumDocumentSize(5))->validate($document)->failed());
    }

    #[Test]
    #[DataProvider('unsatisfiableLimits')]
    public function it_rejects_a_limit_no_document_could_satisfy(int $maxBytes): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('at least 1 byte');

        new MaximumDocumentSize($maxBytes);
    }

    public static function unsatisfiableLimits(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    private static function document(string $content): Document
    {
        return new Document('doc-1', 'acme', $content);
    }
}
