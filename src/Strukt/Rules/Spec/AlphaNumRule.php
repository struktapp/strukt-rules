<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `alpha_num` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class AlphaNumRule
{
    public const SPEC = ['alpha_num' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'may only contain letters and numbers.', 'bails' => false]];
}