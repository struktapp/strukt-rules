<?php

namespace Strukt\Rules;

/**
 * Resolves the failure message for a rule, with per form overrides.
 *
 * An override may be keyed by the rule name alone, which applies to every
 * field using that rule, or by `field.rule`, which applies to one field.
 */
final class Messages
{
    /**
     * @param array<string, string> $overrides Message overrides. Keys are
     *                                        either a rule name or `field.rule`.
     */
    public function __construct(private array $overrides = [])
    {
    }

    /**
     * Picks the override that applies to a field and rule.
     *
     * @param string $field Field name.
     * @param string $rule Rule name.
     * @return string|null The override, or null when none applies.
     */
    public function override(string $field, string $rule): ?string
    {
        return $this->overrides[$field . '.' . $rule]
            ?? $this->overrides[$rule]
            ?? null;
    }

    /**
     * Returns every override.
     *
     * @return array<string, string> Message overrides.
     */
    public function all(): array
    {
        return $this->overrides;
    }
}
