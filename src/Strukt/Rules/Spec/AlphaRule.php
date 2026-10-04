<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `alpha` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class AlphaRule
{
    public const SPEC = ['alpha' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'may only contain letters.', 'bails' => false]];
}