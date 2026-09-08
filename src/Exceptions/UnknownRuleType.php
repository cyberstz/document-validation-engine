<?php

declare(strict_types=1);

namespace DocumentValidation\Exceptions;

use InvalidArgumentException;

final class UnknownRuleType extends InvalidArgumentException
{
    public static function named(string $type, array $registeredTypes): self
    {
        return new self(
            sprintf(
                'Unknown rule type "%s". Registered types: %s.',
                $type,
                $registeredTypes === [] ? '(none)' : implode(', ', $registeredTypes)
            )
        );
    }
}
