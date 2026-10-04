<?php

namespace Strukt\Rules\Fixtures;

use DateTimeImmutable;
use Strukt\Rules\Contract\FieldSource;
use Strukt\Rules\Contract\RuleInterface;

/**
 * A custom rule that reads a sibling field, proving the field source reaches
 * third party rules.
 */
final class AfterRule implements RuleInterface
{
    /**
     * @param string $field Field being validated, used for the message.
     * @param string $other Field to compare against.
     */
    public function __construct(
        private readonly string $field,
        private readonly string $other,
    ) {
    }

    public function passes(mixed $value, FieldSource $fields): bool
    {
        $other = $fields->value($this->other);

        if (!is_string($value) || !is_string($other)) {
            return false;
        }

        return new DateTimeImmutable($value) > new DateTimeImmutable($other);
    }

    public function message(): string
    {
        return ucfirst($this->field) . ' must be later than ' . $this->other . '.';
    }

    public function bails(): bool
    {
        return false;
    }
}
