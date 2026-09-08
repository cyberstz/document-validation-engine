<?php

declare(strict_types=1);

namespace DocumentValidation\Tests;

use DocumentValidation\Document;
use DocumentValidation\Exceptions\InvalidRuleConfiguration;
use DocumentValidation\Exceptions\UnknownRuleType;
use DocumentValidation\RuleFactory;
use DocumentValidation\Rules\MaximumDocumentSize;
use DocumentValidation\Rules\ProhibitedWords;
use DocumentValidation\Rules\RequiredMetadataFields;
use DocumentValidation\Tests\Fixtures\BlankTypeRule;
use DocumentValidation\Tests\Fixtures\MinimumWordCount;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(RuleFactory::class)]
final class RuleFactoryTest extends TestCase
{
    #[Test]
    public function it_builds_a_maximum_document_size_rule(): void
    {
        $rule = RuleFactory::withDefaultRules()
            ->create(MaximumDocumentSize::TYPE, ['max_bytes' => 120]);

        self::assertInstanceOf(MaximumDocumentSize::class, $rule);
        self::assertSame(MaximumDocumentSize::TYPE, $rule::type());
    }

    #[Test]
    public function it_builds_a_required_metadata_fields_rule(): void
    {
        $rule = RuleFactory::withDefaultRules()
            ->create(RequiredMetadataFields::TYPE, ['fields' => ['author']]);

        self::assertInstanceOf(RequiredMetadataFields::class, $rule);
    }

    #[Test]
    public function it_builds_a_prohibited_words_rule_with_its_optional_setting(): void
    {
        $factory = RuleFactory::withDefaultRules();

        $insensitive = $factory->create(ProhibitedWords::TYPE, ['words' => ['Secret']]);
        $sensitive = $factory->create(
            ProhibitedWords::TYPE,
            ['words' => ['Secret'], 'case_sensitive' => true],
        );

        $document = new Document('doc-1', 'acme', 'a secret note');

        self::assertTrue($insensitive->validate($document)->failed());
        self::assertTrue($sensitive->validate($document)->passed());
    }

    #[Test]
    public function an_unknown_type_is_rejected_and_the_message_lists_what_is_available(): void
    {
        $this->expectException(UnknownRuleType::class);
        $this->expectExceptionMessage('Unknown rule type "does_not_exist"');
        $this->expectExceptionMessage(MaximumDocumentSize::TYPE);

        RuleFactory::withDefaultRules()->create('does_not_exist');
    }

    #[Test]
    public function malformed_configuration_is_rejected_by_the_rule_it_targets(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('at least 1 byte');

        RuleFactory::withDefaultRules()->create(MaximumDocumentSize::TYPE, ['max_bytes' => 0]);
    }

    #[Test]
    public function missing_configuration_is_reported_against_the_requested_rule(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage(
            'Rule "maximum_document_size" requires the configuration key "max_bytes"',
        );

        RuleFactory::withDefaultRules()->create(MaximumDocumentSize::TYPE);
    }

    #[Test]
    public function registered_types_are_reported_in_a_stable_order(): void
    {
        self::assertSame(
            [
                MaximumDocumentSize::TYPE,
                ProhibitedWords::TYPE,
                RequiredMetadataFields::TYPE,
            ],
            RuleFactory::withDefaultRules()->registeredTypes(),
        );
    }

    #[Test]
    public function a_new_rule_type_can_be_added_without_touching_any_existing_class(): void
    {
        $factory = RuleFactory::withDefaultRules()->register(MinimumWordCount::class);

        $rule = $factory->create(MinimumWordCount::TYPE, ['min_words' => 5]);

        self::assertContains(MinimumWordCount::TYPE, $factory->registeredTypes());
        self::assertInstanceOf(MinimumWordCount::class, $rule);
        self::assertTrue($rule->validate(new Document('doc-1', 'acme', 'one two'))->failed());
        self::assertTrue(
            $rule->validate(new Document('doc-1', 'acme', 'one two three four five'))->passed(),
        );
    }

    #[Test]
    public function registering_the_same_type_twice_is_rejected_rather_than_silently_overriding(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('already registered by');

        RuleFactory::withDefaultRules()->register(MaximumDocumentSize::class);
    }

    #[Test]
    public function a_rule_declaring_a_blank_type_cannot_be_registered(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('must declare a non-blank rule type');

        (new RuleFactory())->register(BlankTypeRule::class);
    }

    #[Test]
    public function a_class_that_is_not_a_validation_rule_cannot_be_registered(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('does not implement');

        /** @phpstan-ignore-next-line intentionally passing a class that is not a rule */
        (new RuleFactory())->register(stdClass::class);
    }

    #[Test]
    public function a_class_that_does_not_exist_cannot_be_registered(): void
    {
        $this->expectException(InvalidRuleConfiguration::class);
        $this->expectExceptionMessage('does not implement');

        /** @phpstan-ignore-next-line intentionally passing a non-existent class */
        (new RuleFactory())->register('DocumentValidation\\Nope');
    }
}
