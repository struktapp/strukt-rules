<?php

namespace Strukt\Rules;

/**
 * Turns a rules definition into rule instances.
 *
 * A definition is either a pipe separated string such as `required|email|max:255`
 * or a list of such strings. A definition may also contain ready made
 * {@see Contract\RuleInterface} instances, which are passed through untouched.
 */
final class Rules
{
    /**
     * Splits a rule token into its name and parameters.
     *
     * Parameters are split on commas only for rules that declare a comma
     * separated list, so a pattern such as `regex:/^\d{3},\d{4}$/` keeps its
     * comma.
     *
     * @param string $token Rule token, for example `min:3`.
     * @return array{0: string, 1: list<string>} Rule name and parameters.
     * @throws Exception\UnknownRule When the rule name is not registered.
     */
    public static function split(string $token, ?RuleRegistry $registry = null): array
    {
        $registry ??= RuleRegistry::shared();
        $token = trim($token);

        [$name, $arguments] = array_pad(explode(':', $token, 2), 2, null);
        $name = strtolower(trim($name));

        if (!$registry->has($name)) {
            throw Exception\UnknownRule::for($name, $registry->names());
        }

        if ($arguments === null || trim($arguments) === '') {
            return [$name, []];
        }

        $arguments = trim($arguments);

        if (Rule::has($name) && Rule::usesCommaParams($name)) {
            return [$name, array_values(array_map('trim', explode(',', $arguments)))];
        }

        return [$name, [$arguments]];
    }

    /**
     * Parses a definition for one field.
     *
     * @param string $field Field the rules belong to.
     * @param string|list<string|Contract\RuleInterface> $definition Rule definition.
     * @param RuleRegistry|null $registry Registry used to resolve names.
     * @param Messages|null $messages Message overrides for the owning form.
     * @return list<Contract\RuleInterface> Parsed rules, in declaration order.
     */
    public static function parse(
        string $field,
        string|array $definition,
        ?RuleRegistry $registry = null,
        ?Messages $messages = null,
    ): array {
        $registry ??= RuleRegistry::shared();
        $tokens = is_array($definition) ? $definition : explode('|', $definition);

        $rules = [];
        foreach ($tokens as $token) {
            if ($token instanceof Contract\RuleInterface) {
                $rules[] = $token;
                continue;
            }

            if (!is_scalar($token)) {
                throw Exception\UnknownRule::for(get_debug_type($token), $registry->names());
            }

            $token = trim((string) $token);

            if ($token === '') {
                continue;
            }

            [$name, $params] = self::split($token, $registry);
            $rules[] = $registry->make($name, $field, $params, $messages);
        }

        return $rules;
    }

    /**
     * Parses every field of a rules definition.
     *
     * @param array<string, string|list<string|Contract\RuleInterface>> $definitions Rules keyed by field.
     * @param RuleRegistry|null $registry Registry used to resolve names.
     * @param Messages|null $messages Message overrides for the owning form.
     * @return array<string, list<Contract\RuleInterface>> Parsed rules keyed by field.
     */
    public static function parseAll(
        array $definitions,
        ?RuleRegistry $registry = null,
        ?Messages $messages = null,
    ): array {
        $parsed = [];

        foreach ($definitions as $field => $definition) {
            $parsed[(string) $field] = self::parse((string) $field, $definition, $registry, $messages);
        }

        return $parsed;
    }
}
