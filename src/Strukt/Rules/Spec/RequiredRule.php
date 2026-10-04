<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `required` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class RequiredRule
{
    public const SPEC = ['required' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'is required.', 'bails' => true]];
}