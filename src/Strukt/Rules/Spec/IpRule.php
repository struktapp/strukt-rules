<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `ip` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class IpRule
{
    public const SPEC = ['ip' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be a valid IP address.', 'bails' => false]];
}