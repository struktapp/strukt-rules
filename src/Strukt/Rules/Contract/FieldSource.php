<?php

namespace Strukt\Rules\Contract;

/**
 * Reads sibling field values so a rule can compare across fields.
 *
 * The form itself satisfies this interface, which is why a rule such as
 * `same:password` can reach the value it has to be compared against.
 */
interface FieldSource
{
    /**
     * Reads one field value.
     *
     * @param string $field Field name.
     * @param mixed $default Value returned when the field is undefined.
     * @return mixed Stored value, or the default when undefined.
     */
    public function value(string $field, mixed $default = null): mixed;
}
