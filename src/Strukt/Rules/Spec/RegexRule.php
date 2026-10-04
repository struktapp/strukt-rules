<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `regex` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class RegexRule
{
    public const SPEC = ['regex' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'has an invalid format.', 'bails' => false]];
}