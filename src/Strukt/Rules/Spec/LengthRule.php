<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `length` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class LengthRule
{
    public const SPEC = ['length' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must be exactly :0 characters.', 'bails' => false]];
}