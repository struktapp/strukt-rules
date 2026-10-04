<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `in` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class InRule
{
    public const SPEC = ['in' => ['min' => 1, 'max' => null, 'comma' => true, 'message' => 'must be one of: :list.', 'bails' => false]];
}