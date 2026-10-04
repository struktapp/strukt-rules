<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `string` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class StringRule
{
    public const SPEC = ['string' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be a string.', 'bails' => true]];
}