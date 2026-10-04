<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `uuid` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class UuidRule
{
    public const SPEC = ['uuid' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be a valid UUID.', 'bails' => false]];
}