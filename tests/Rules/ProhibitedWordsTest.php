<?php

declare(strict_types=1);

namespace DocumentValidation\Tests\Rules;

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\Rules\ProhibitedWords;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProhibitedWords::class)]
final class ProhibitedWordsTest extends TestCase
{
    #[Test]
    public function it_passes_content_that_contains_none_of_the_words(): void
    {
        $result = (new ProhibitedWords(['secret']))->validate(self::document('A perfectly ordinary memo.'));

        self::assertTrue($result->passed());
    }

    #[Test]
    public function it_fails_content_that_contains_a_prohibited_word(): void
    {
        $result = (new ProhibitedWords(['secret']))->validate(self::document('This is a secret memo.'));

        self::assertTrue($result->failed());
        self::assertStringContainsString('"secret"', $result->errors[0]);
    }

    #[Test]
    public function it_reports_each_prohibited_word_it_finds(): void
    {
        $result = (new ProhibitedWords(['secret', 'classified', 'absent']))
            ->validate(self::document('This secret memo is classified.'));

        self::assertCount(2, $result->errors);
    }

    #[Test]
    public function it_matches_whole_words_only(): void
    {
        $rule = new ProhibitedWords(['class']);

        self::assertTrue($rule->validate(self::document('A classic and classified case.'))->passed());
        self::assertTrue($rule->validate(self::document('The class begins.'))->failed());
    }

    #[Test]
    public function it_matches_a_word_bounded_by_punctuation(): void
    {
        $rule = new ProhibitedWords(['secret']);

        self::assertTrue($rule->validate(self::document('It is (secret), apparently.'))->failed());
    }

    #[Test]
    public function it_ignores_case_by_default(): void
    {
        $rule = new ProhibitedWords(['secret']);

        self::assertTrue($rule->validate(self::document('This is SECRET.'))->failed());
        self::assertTrue($rule->validate(self::document('This is Secret.'))->failed());
    }

    #[Test]
    public function it_respects_case_when_configured_to(): void
    {
        $rule = new ProhibitedWords(['Secret'], caseSensitive: true);

        self::assertTrue($rule->validate(self::document('This is secret.'))->passed());
        self::assertTrue($rule->validate(self::document('This is Secret.'))->failed());
    }

    #[Test]
    public function it_matches_a_multi_word_phrase(): void
    {
        $rule = new ProhibitedWords(['internal only']);

        self::assertTrue($rule->validate(self::document('Marked internal only, do not share.'))->failed());
        self::assertTrue($rule->validate(self::document('Internal use only.'))->passed());
    }

    #[Test]
    public function regular_expression_characters_in_a_word_are_treated_literally(): void
    {
        $rule = new ProhibitedWords(['a.b']);

        self::assertTrue($rule->validate(self::document('axb is fine'))->passed());
        self::assertTrue($rule->validate(self::document('a.b is not'))->failed());
    }

    #[Test]
    public function it_matches_non_ascii_words(): void
    {
        $rule = new ProhibitedWords(['таємно']);

        self::assertTrue($rule->validate(self::document('Це таємно.'))->failed());
    }

    #[Test]
    public function content_that_cannot_be_scanned_raises_an_error_rather_than_passing_silently(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not scan document content');

        // A lone continuation byte: not valid UTF-8, so a /u pattern cannot run.
        (new ProhibitedWords(['secret']))->validate(self::document("valid text \xC3\x28 more text"));
    }

    #[Test]
    public function it_rejects_an_empty_word_list(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('At least one prohibited word');

        new ProhibitedWords([]);
    }

    #[Test]
    public function it_rejects_a_blank_word(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('cannot be blank');

        new ProhibitedWords(['secret', '   ']);
    }

    #[Test]
    public function it_rejects_a_word_that_is_not_a_string(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('must be strings, int given');

        new ProhibitedWords(['secret', 42]);
    }

    private static function document(string $content): Document
    {
        return new Document('doc-1', 'acme', $content);
    }
}
