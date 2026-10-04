<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `date` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class DateRule
{
    public const SPEC = ['date' => ['min' => 0, 'max' => 1, 'comma' => false, 'message' => 'must be a valid date in :format format.', 'bails' => false]];
}