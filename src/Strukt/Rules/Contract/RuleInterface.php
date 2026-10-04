<?php

namespace Strukt\Rules\Contract;

/**
 * A single validation check for one field.
 *
 * A rule is told the value under test and a {@see FieldSource} for reading
 * sibling fields. Implement this to add a check the built-in rules do not
 * cover, then register it on a {@see \Strukt\Rules\RuleRegistry}.
 */
interface RuleInterface
{
    /**
     * Checks the value.
     *
     * @param mixed $value Submitted value for the field under test.
     * @param FieldSource $fields Sibling values, for rules that compare fields.
     * @return bool True when the value satisfies the rule.
     */
    public function passes(mixed $value, FieldSource $fields): bool;

    /**
     * Describes the failure in a form ready to show a user.
     *
     * @return string Failure message.
     */
    public function message(): string;

    /**
     * Reports whether a failure should end validation of the field.
     *
     * A rule that guards the shape of a value, such as `required`, returns true
     * so the checks that assume a value is present do not also report a
     * confusing second failure.
     *
     * @return bool True when later rules for the field should be skipped.
     */
    public function bails(): bool;
}
