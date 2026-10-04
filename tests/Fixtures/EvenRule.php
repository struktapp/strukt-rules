<?php

namespace Strukt\Rules\Fixtures;

use Strukt\Rules\Contract\FieldSource;
use Strukt\Rules\Contract\RuleInterface;

/**
 * A custom rule that accepts only an even integer, used to check extension by
 * instance.
 */
final class EvenRule implements RuleInterface
{
    public function __construct(private readonly string $field = 'value')
    {
    }

    public function passes(mixed $value, FieldSource $fields): bool
    {
        return is_int($value) && $value % 2 === 0;
    }

    public function message(): string
    {
        return ucfirst($this->field) . ' must be an even number.';
    }

    public function bails(): bool
    {
        return true;
    }
}
