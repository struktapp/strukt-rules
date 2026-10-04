<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `email` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class EmailRule
{
    public const SPEC = ['email' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be a valid email address.', 'bails' => false]];
}