<?php

/**
 * A different rule-based engine, built on strukt/rules and not on a form.
 *
 * Where strukt/form validates user input through form classes, this engine
 * validates a plain configuration array. It reuses the rules engine exactly as
 * it is -- the parser, the registry, the built-in rules and the validator:
 *
 *   * keys may reach a nested leaf with dot notation, e.g. `server.port`,
 *   * a string leaf is cast to its declared type before the rules run, so a
 *     config file holding `"true"` or `"60"` validates and yields typed
 *     values,
 *   * the engine owns a private registry and message vocabulary, so its
 *     additions never leak into the shared registry form validation uses.
 *
 * Run with: php examples/config.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Strukt\Rules\Contract\FieldSource;
use Strukt\Rules\Contract\RuleInterface;
use Strukt\Rules\Messages;
use Strukt\Rules\RuleRegistry;
use Strukt\Rules\Validator;

/**
 * A config native rule: a port number inside an inclusive range.
 *
 * The built-in engine has no port rule, so this example adds one to a private
 * registry via {@see RuleRegistry::extend()}, the same extension point a form
 * would use for its own custom rules. No form is involved here.
 */
final class PortRule implements RuleInterface
{
    /**
     * @param string $field Dotted path the rule validates, used for the message.
     * @param list<string> $params Optional `min,max` range.
     */
    public function __construct(
        private readonly string $field,
        private readonly array $params = [],
    ) {
    }

    public function passes(mixed $value, FieldSource $fields): bool
    {
        if (!is_int($value)) {
            return false;
        }

        [$min, $max] = $this->range();

        return $value >= $min && $value <= $max;
    }

    public function message(): string
    {
        [$min, $max] = $this->range();
        $label = ucfirst(str_replace(['_', '-'], ' ', $this->field));

        return sprintf('%s must be a port between %d and %d.', $label, $min, $max);
    }

    public function bails(): bool
    {
        return true;
    }

    /**
     * @return array{0: int, 1: int} Inclusive port range, defaults to 1-65535.
     */
    private function range(): array
    {
        $raw = $this->params[0] ?? '';
        $parts = str_contains($raw, ',') ? explode(',', $raw) : [$raw];
        $min = isset($parts[0]) && $parts[0] !== '' ? max(1, (int) $parts[0]) : 1;
        $max = isset($parts[1]) && $parts[1] !== '' ? min(65535, (int) $parts[1]) : 65535;

        return [min($min, $max), $max];
    }
}

/**
 * A config native rule: a number with a fractional part.
 */
final class FloatRule implements RuleInterface
{
    /**
     * @param string $field Dotted path the rule validates, used for the message.
     */
    public function __construct(private readonly string $field)
    {
    }

    public function passes(mixed $value, FieldSource $fields): bool
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
    }

    public function message(): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $this->field)) . ' must be a number with a fractional part.';
    }

    public function bails(): bool
    {
        return true;
    }
}

/**
 * A configuration validation engine.
 *
 * Built on {@see Validator} by composition. It adds the semantics a config
 * needs and a form does not: dotted access to nested leaves, casting leaves to
 * their declared types, and its own registry and message wording.
 */
final class ConfigValidator
{
    private readonly Validator $engine;

    private readonly RuleRegistry $registry;

    public function __construct()
    {
        $this->registry = RuleRegistry::create();
        $this->registry->extend(
            'float',
            static fn (string $field, array $params): RuleInterface => new FloatRule($field),
        );
        $this->registry->extend(
            'port',
            static fn (string $field, array $params): RuleInterface => new PortRule($field, $params),
        );

        $this->engine = new Validator(
            $this->registry,
            new Messages([
                'required' => ':field is required in the config.',
                'integer'  => ':field must be a whole number.',
            ]),
        );
    }

    /**
     * Validates a config array and returns the failures plus the normalized
     * config, with every leaf that validated cast to its declared type.
     *
     * @param array<string, mixed> $config Config array, possibly nested.
     * @param array<string, string> $definition Rules keyed by dotted path.
     * @return array{errors: array<string, list<string>>, config: array<string, mixed>}
     */
    public function validate(array $config, array $definition): array
    {
        $values = [];
        $typed = [];

        foreach ($definition as $key => $rules) {
            $key = (string) $key;
            [$present, $raw] = $this->find($config, $key);

            $value = $this->cast($raw, (string) $rules);
            $values[$key] = $value;

            if ($present) {
                $typed[$key] = $value;
            }
        }

        $errors = $this->engine->validate($values, $definition);

        foreach ($typed as $key => $value) {
            $config = $this->put($config, $key, $value);
        }

        return ['errors' => $errors, 'config' => $config];
    }

    /**
     * @return RuleRegistry Private registry, exposed so the isolation demo can
     *                      compare it with the shared one.
     */
    public function registry(): RuleRegistry
    {
        return $this->registry;
    }

    /**
     * @return array{0: bool, 1: mixed} Whether the dotted path resolves in the
     *                                   config, and its leaf value.
     */
    private function find(array $config, string $key): array
    {
        $node = $config;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return [false, null];
            }

            $node = $node[$segment];
        }

        return [true, $node];
    }

    /**
     * Writes a leaf value back into a nested config array.
     *
     * @return array<string, mixed> Copy of the config with the leaf replaced.
     */
    private function put(array $config, string $key, mixed $value): array
    {
        $segments = explode('.', $key);
        $cursor = &$config;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $cursor[$segment] = $value;
                break;
            }

            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }

        return $config;
    }

    /**
     * Casts a scalar leaf to the type its rules declare. Values that do not
     * cast are left untouched, so the rule itself reports the failure.
     */
    private function cast(mixed $value, string $rules): mixed
    {
        if ($value === null || $value === '' || !is_scalar($value)) {
            return $value;
        }

        $tokens = array_values(array_filter(array_map(
            static fn (string $token): ?string => trim($token) === ''
                ? null
                : strtolower(trim(explode(':', $token, 2)[0])),
            explode('|', $rules),
        )));

        if (in_array('boolean', $tokens, true)) {
            return match (strtolower((string) $value)) {
                'true', 'yes', 'on', '1' => true,
                'false', 'no', 'off', '0' => false,
                default => $value,
            };
        }

        if (in_array('port', $tokens, true) || in_array('integer', $tokens, true)) {
            if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value))) {
                return (int) $value;
            }

            return $value;
        }

        if (in_array('float', $tokens, true)) {
            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                return (float) $value;
            }

            return $value;
        }

        return $value;
    }
}

// --- use it -----------------------------------------------------------------

$config = [
    'app'      => ['debug' => 'true', 'port' => '8080', 'rate' => '0.75'],
    'database' => ['idle' => '60'],
    'cache'    => ['ttl' => '120'],
];

$definition = [
    'app.debug'    => 'required|boolean',
    'app.port'     => 'required|port:1024,65535',
    'app.rate'     => 'required|float|min:0.5',
    'database.idle' => 'required|integer|min:5',
    'cache.ttl'    => 'required|integer|min:10',
    'mail.from'    => 'nullable|email', // optional leaf -- absent and fine
];

$configValidator = new ConfigValidator();
$result = $configValidator->validate($config, $definition);

echo "config A:\n";
if ($result['errors'] === []) {
    echo "  errors:   (none -- passes)\n";
} else {
    foreach ($result['errors'] as $field => $messages) {
        foreach ($messages as $message) {
            echo "  {$field}: {$message}\n";
        }
    }
}
echo "  config:   " . json_encode($result['config'], JSON_UNESCAPED_SLASHES) . "\n";

$badConfig = $config;
$badConfig['app']['port'] = '443';      // below the range this config demands
$badConfig['database']['idle'] = '5.5'; // not a whole number after casting

$result = $configValidator->validate($badConfig, $definition);

echo "\nconfig B (invalid):\n";
foreach ($result['errors'] as $field => $messages) {
    foreach ($messages as $message) {
        echo "  {$field}: {$message}\n";
    }
}

echo "\nisolation:\n";
printf("  'port' registered privately?        %s\n", $configValidator->registry()->has('port') ? 'yes' : 'no');
printf("  shared registry kept it clean?      %s\n", RuleRegistry::shared()->has('port') ? 'no, leaked!' : 'yes');