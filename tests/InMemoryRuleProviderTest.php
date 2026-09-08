<?php

declare(strict_types=1);

namespace DocumentValidation\Tests;

use DocumentValidation\InMemoryRuleProvider;
use DocumentValidation\Tests\Fixtures\FailingRule;
use DocumentValidation\Tests\Fixtures\PassingRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryRuleProvider::class)]
final class InMemoryRuleProviderTest extends TestCase
{
    #[Test]
    public function it_returns_the_rules_registered_for_a_tenant_in_order(): void
    {
        $first = new PassingRule();
        $second = new FailingRule('nope');

        $provider = new InMemoryRuleProvider();
        $provider->add('acme', $first, $second);

        self::assertSame([$first, $second], $provider->rulesFor('acme'));
    }

    #[Test]
    public function it_keeps_tenants_isolated_from_one_another(): void
    {
        $acmeRule = new PassingRule();
        $globexRule = new FailingRule('nope');

        $provider = new InMemoryRuleProvider();
        $provider->add('acme', $acmeRule);
        $provider->add('globex', $globexRule);

        self::assertSame([$acmeRule], $provider->rulesFor('acme'));
        self::assertSame([$globexRule], $provider->rulesFor('globex'));
    }

    #[Test]
    public function an_unknown_tenant_yields_an_empty_rule_set_rather_than_an_error(): void
    {
        self::assertSame([], (new InMemoryRuleProvider())->rulesFor('nobody'));
    }

    #[Test]
    public function it_can_be_seeded_through_the_constructor(): void
    {
        $first = new PassingRule();
        $second = new FailingRule('nope');
        $third = new PassingRule();

        $provider = new InMemoryRuleProvider([
            'acme' => [$first, $second],
            'globex' => [$third],
        ]);

        self::assertSame([$first, $second], $provider->rulesFor('acme'));
        self::assertSame([$third], $provider->rulesFor('globex'));
    }

    #[Test]
    public function a_numeric_tenant_id_is_not_silently_turned_into_an_integer_key(): void
    {
        $rule = new PassingRule();

        $provider = new InMemoryRuleProvider(['42' => [$rule]]);

        self::assertSame([$rule], $provider->rulesFor('42'));
    }

    #[Test]
    public function adding_rules_twice_appends_rather_than_replaces(): void
    {
        $first = new PassingRule();
        $second = new FailingRule('nope');

        $provider = new InMemoryRuleProvider();
        $provider->add('acme', $first);
        $provider->add('acme', $second);

        self::assertSame([$first, $second], $provider->rulesFor('acme'));
    }
}
