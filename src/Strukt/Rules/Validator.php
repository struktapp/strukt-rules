<?php

namespace Strukt\Rules;

/**
 * Runs a rules definition against a set of values and collects the failures.
 *
 * Three field level behaviours keep the reported errors useful rather than
 * repetitive:
 *
 * * A field that is not `required` and holds an empty value skips its remaining
 *   rules, so an optional field that was left blank does not also report a type
 *   error.
 * * A field marked `nullable` accepts `null` outright.
 * * A rule that bails, such as `required` or `integer`, ends the field when it
 *   fails, so later rules do not pile confusing messages onto the same value.
 */
final class Validator
{
    /**
     * Creates a validator.
     *
     * @param RuleRegistry|null $registry Registry used to resolve rule names.
     *                                    Defaults to the shared registry.
     * @param Messages|null $messages Message overrides.
     */
    public function __construct(
        private readonly ?RuleRegistry $registry = null,
        private readonly ?Messages $messages = null,
    ) {
    }

    /**
     * Returns the registry in use.
     *
     * @return RuleRegistry Registry used to resolve rule names.
     */
    public function registry(): RuleRegistry
    {
        return $this->registry ?? RuleRegistry::shared();
    }

    /**
     * Validates values against a rules definition.
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @param array<string, string|list<string|Contract\RuleInterface>> $rules Rules keyed by field name.
     * @return array<string, list<string>> Failure messages keyed by field name,
     *                                          omitting the fields that passed.
     */
    public function validate(array $values, array $rules): array
    {
        $fields = Fields::from($values);
        $errors = [];

        foreach ($rules as $field => $definition) {
            $messages = $this->failures((string) $field, $definition, $fields);

            if ($messages !== []) {
                $errors[(string) $field] = $messages;
            }
        }

        return $errors;
    }

    /**
     * Reports whether values satisfy a rules definition.
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @param array<string, string|list<string|Contract\RuleInterface>> $rules Rules keyed by field name.
     * @return bool True when no field records a failure.
     */
    public function passes(array $values, array $rules): bool
    {
        return $this->validate($values, $rules) === [];
    }

    /**
     * Collects the failure messages for a single field.
     *
     * @param string $field Field name.
     * @param string|list<string|Contract\RuleInterface> $definition Rule definition.
     * @param Fields $fields Sibling values.
     * @return list<string> Failure messages, in the order the rules were declared.
     */
    public function failures(string $field, string|array $definition, Fields $fields): array
    {
        $rules = Rules::parse($field, $definition, $this->registry, $this->messages);
        $value = $fields->value($field);

        if ($this->skips($rules, $value)) {
            return [];
        }

        $messages = [];

        foreach ($rules as $rule) {
            if ($rule->passes($value, $fields)) {
                continue;
            }

            $messages[] = $rule->message();

            if ($rule->bails()) {
                break;
            }
        }

        return $messages;
    }

    /**
     * Reports whether a field's remaining rules should be skipped.
     *
     * @param list<Contract\RuleInterface> $rules Parsed rules for the field.
     * @param mixed $value Submitted value.
     * @return bool True when the value is acceptable to skip.
     */
    private function skips(array $rules, mixed $value): bool
    {
        $required = false;
        $nullable = false;

        foreach ($rules as $rule) {
            $name = $rule instanceof Rule ? $rule->name() : null;

            if ($name === 'required') {
                $required = true;
            }

            if ($name === 'nullable') {
                $nullable = true;
            }
        }

        if ($nullable && $value === null) {
            return true;
        }

        if ($required) {
            return false;
        }

        return $value === null || $value === '' || $value === [];
    }
}
