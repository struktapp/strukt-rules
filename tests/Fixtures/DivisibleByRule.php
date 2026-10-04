<?php

namespace Strukt\Rules\Fixtures;

use Strukt\Rules\Contract\FieldSource;
use Strukt\Rules\Contract\RuleInterface;

/**
 * A custom rule that takes a parameter, used to check extension by factory.
 */
final class DivisibleByRule implements RuleInterface
{
    public function __construct(
        private readonly string $field,
        private readonly int $divisor,
    ) {
    }

    public function passes(mixed $value, FieldSource $fields): bool
    {
        return is_numeric($value) && (int) $value % $this->divisor === 0;
    }

    public function message(): string
    {
        return ucfirst($this->field) . ' must be divisible by ' . $this->divisor . '.';
    }

    public function bails(): bool
    {
        return false;
    }
}
