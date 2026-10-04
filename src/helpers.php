<?php

use Strukt\Rules\Validator;

if (!function_exists('check')) {
    /**
     * Validates a plain array of values against a rules definition.
     *
     * The engine's standalone entry point, useful when there is no form class
     * to hand, such as validating a config array. Returns the failure
     * messages keyed by field name.
     *
     * Example:
     * ```php
     * $errors = check(['age' => 12], ['age' => 'required|integer|min:18']);
     * ```
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @param array<string, string|list<string|\Strukt\Rules\Contract\RuleInterface>> $rules Rules keyed by field name.
     * @return array<string, list<string>> Failure messages keyed by field name.
     */
    function check(array $values, array $rules): array
    {
        return (new Validator())->validate($values, $rules);
    }
}