<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `url` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class UrlRule
{
    public const SPEC = ['url' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be a valid URL.', 'bails' => false]];
}