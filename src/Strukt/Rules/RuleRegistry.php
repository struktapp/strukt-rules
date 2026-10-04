<?php

namespace Strukt\Rules;

use InvalidArgumentException;

/**
 * Resolves a rule name into a rule instance.
 *
 * The built-in rules are always available. A custom check is added with
 * {@see extend()}, either as a ready made instance or as a factory that
 * receives the field name and the parsed parameters.
 */
final class RuleRegistry
{
    /**
     * The registry used when no other is supplied.
     *
     * @var self|null Shared registry.
     */
    private static ?self $shared = null;

    /**
     * Custom rule factories, keyed by rule name.
     *
     * @var array<string, callable(string, list<string>): Contract\RuleInterface>
     */
    private array $factories = [];

    /**
     * Creates a registry holding only the built-in rules.
     *
     * @return self New registry.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Returns the process wide registry, creating it on first use.
     *
     * @return self Shared registry.
     */
    public static function shared(): self
    {
        return self::$shared ??= new self();
    }

    /**
     * Registers a custom rule under a name.
     *
     * Passing a {@see Contract\RuleInterface} returns that same instance for
     * every field, which suits a stateless rule. Passing a callable invokes it
     * with the field name and the parsed parameters, which suits a rule that
     * needs its own configuration.
     *
     * @param string $name Rule name used in a rules definition.
     * @param Contract\RuleInterface|callable(string, list<string>): Contract\RuleInterface $rule
     *        Rule instance or factory.
     * @return self This registry, for chaining.
     * @throws InvalidArgumentException When the name collides with a built-in rule.
     */
    public function extend(string $name, Contract\RuleInterface|callable $rule): self
    {
        if (Rule::has($name)) {
            throw new InvalidArgumentException(sprintf(
                'Rule [%s] is built in and cannot be replaced. Choose another name.',
                $name,
            ));
        }

        $this->factories[$name] = $rule instanceof Contract\RuleInterface
            ? static fn (): Contract\RuleInterface => $rule
            : static fn (string $field, array $params): Contract\RuleInterface => $rule($field, $params);

        return $this;
    }

    /**
     * Removes a custom rule.
     *
     * @param string $name Rule name.
     * @return self This registry, for chaining.
     */
    public function forget(string $name): self
    {
        unset($this->factories[$name]);

        return $this;
    }

    /**
     * Reports whether a rule name resolves.
     *
     * @param string $name Rule name.
     * @return bool True for a built-in or custom rule.
     */
    public function has(string $name): bool
    {
        return Rule::has($name) || isset($this->factories[$name]);
    }

    /**
     * Lists every available rule name.
     *
     * @return list<string> Rule names, built in first.
     */
    public function names(): array
    {
        return [...Rule::names(), ...array_keys($this->factories)];
    }

    /**
     * Builds a rule for a field.
     *
     * @param string $name Rule name.
     * @param string $field Field the rule validates.
     * @param list<string> $params Parsed rule parameters.
     * @param Messages|null $messages Message overrides for the owning form.
     * @return Contract\RuleInterface Resolved rule.
     * @throws Exception\UnknownRule When the name does not resolve.
     */
    public function make(string $name, string $field, array $params = [], ?Messages $messages = null): Contract\RuleInterface
    {
        if (isset($this->factories[$name])) {
            return ($this->factories[$name])($field, $params);
        }

        if (!Rule::has($name)) {
            throw Exception\UnknownRule::for($name, $this->names());
        }

        return new Rule($name, $field, $params, $messages);
    }
}
