<?php

namespace Strukt\Rules\Exception;

use InvalidArgumentException;

/**
 * Raised when a rule name has no registered implementation.
 *
 * This is a programming error rather than a validation failure, so it is
 * thrown while the rules are being parsed rather than being collected as a
 * field error.
 */
final class UnknownRule extends InvalidArgumentException
{
    /**
     * Creates the exception for an unregistered rule name.
     *
     * @param string $name Rule name that could not be resolved.
     * @param list<string> $known Registered rule names.
     * @return self New exception.
     */
    public static function for(string $name, array $known): self
    {
        sort($known);

        return new self(sprintf(
            'Unknown validation rule [%s]. Registered rules: [%s]. '
            . 'Register a custom one with RuleRegistry::extend().',
            $name,
            implode(', ', $known),
        ));
    }
}
