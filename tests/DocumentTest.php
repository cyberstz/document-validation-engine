<?php

declare(strict_types=1);

namespace DocumentValidation\Tests;

use DocumentValidation\Document;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Document::class)]
final class DocumentTest extends TestCase
{
    #[Test]
    public function it_exposes_the_values_it_was_constructed_with(): void
    {
        $document = new Document('doc-1', 'acme', 'Hello.', ['author' => 'Ada']);

        self::assertSame('doc-1', $document->id);
        self::assertSame('acme', $document->tenantId);
        self::assertSame('Hello.', $document->content);
        self::assertSame(['author' => 'Ada'], $document->metadata);
    }

    #[Test]
    public function metadata_defaults_to_an_empty_array(): void
    {
        self::assertSame([], (new Document('doc-1', 'acme', 'Hello.'))->metadata);
    }

    #[Test]
    public function it_measures_content_size_in_bytes_not_characters(): void
    {
        $document = new Document('doc-1', 'acme', 'привіт');

        self::assertSame(12, $document->sizeInBytes());
    }

    #[Test]
    public function empty_content_has_zero_size(): void
    {
        self::assertSame(0, (new Document('doc-1', 'acme', ''))->sizeInBytes());
    }

    #[Test]
    #[DataProvider('blankValues')]
    public function it_rejects_a_blank_id(string $blank): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty id');

        new Document($blank, 'acme', 'Hello.');
    }

    #[Test]
    #[DataProvider('blankValues')]
    public function it_rejects_a_blank_tenant_id(string $blank): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must belong to a tenant');

        new Document('doc-1', $blank, 'Hello.');
    }

    public static function blankValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'spaces' => ['   '];
        yield 'tab and newline' => ["\t\n"];
    }
}
