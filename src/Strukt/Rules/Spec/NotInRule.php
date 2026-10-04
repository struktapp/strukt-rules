<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `not_in` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class NotInRule
{
    public const SPEC = ['not_in' => ['min' => 1, 'max' => null, 'comma' => true, 'message' => 'may not be one of: :list.', 'bails' => false]];
}