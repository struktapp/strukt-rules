<?php

namespace Strukt\Rules;

/**
 * An in-memory set of field values, usable as a rule's field source.
 *
 * This lets a rule set be validated without building a form, and is what the
 * {@see Validator} hands to every rule.
 */
final class Fields implements Contract\FieldSource
{
    /**
     * @param array<string, mixed> $values Field values keyed by field name.
     */
    public function __construct(private array $values = [])
    {
    }

    /**
     * Creates a field source from an array of values.
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @return self New field source.
     */
    public static function from(array $values = []): self
    {
        return new self($values);
    }

    public function value(string $field, mixed $default = null): mixed
    {
        return array_key_exists($field, $this->values)
            ? $this->values[$field]
            : $default;
    }

    /**
     * Returns every stored value.
     *
     * @return array<string, mixed> Field values keyed by field name.
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * Returns a copy holding one extra or replaced value.
     *
     * @param string $field Field name.
     * @param mixed $value Value to store.
     * @return self New field source.
     */
    public function with(string $field, mixed $value): self
    {
        $values = $this->values;
        $values[$field] = $value;

        return new self($values);
    }
}
