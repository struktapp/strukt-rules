<?php
namespace Strukt\Rules;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * The built-in validation rules, driven by one merged specification table.
 *
 * Every rule is described once in `src/Strukt/Rules/Spec/`, one file per
 * rule, which declares how many
 * parameters it accepts, whether those parameters are comma separated, the
 * wording of its failure message, and whether a failure ends validation of the
 * field. Keeping that in one table means a rule cannot drift between its parser
 * contract and the message it reports.
 *
 * Size rules are deliberately explicit rather than switching on the type of the
 * value: `min` compares numbers, `min_length` compares string length, and
 * `min_count` counts the items of an array. A rule never guesses.
 */
final class Rule implements Contract\RuleInterface
{
    /**
     * Spec files, one per built-in rule, in the order the names are reported.
     *
     * Each class in the `Spec` namespace holds the entry for a single rule in
     * its `SPEC` constant. They compile into one table via {@see specTable()},
     * so the parser contract, message, and bail behaviour still read from a
     * single merged specification.
     *
     * @var list<class-string<\Strukt\Rules\Spec\Rule>>
     */
    private const SPEC_FILES = [
        \Strukt\Rules\Spec\RequiredRule::class,
        \Strukt\Rules\Spec\NullableRule::class,
        \Strukt\Rules\Spec\FilledRule::class,

        \Strukt\Rules\Spec\StringRule::class,
        \Strukt\Rules\Spec\IntegerRule::class,
        \Strukt\Rules\Spec\NumericRule::class,
        \Strukt\Rules\Spec\BooleanRule::class,
        \Strukt\Rules\Spec\ArrayRule::class,

        \Strukt\Rules\Spec\EmailRule::class,
        \Strukt\Rules\Spec\UrlRule::class,
        \Strukt\Rules\Spec\UuidRule::class,
        \Strukt\Rules\Spec\IpRule::class,

        \Strukt\Rules\Spec\AlphaRule::class,
        \Strukt\Rules\Spec\AlphaNumRule::class,

        \Strukt\Rules\Spec\MinRule::class,
        \Strukt\Rules\Spec\MaxRule::class,
        \Strukt\Rules\Spec\BetweenRule::class,

        \Strukt\Rules\Spec\LengthRule::class,
        \Strukt\Rules\Spec\MinLengthRule::class,
        \Strukt\Rules\Spec\MaxLengthRule::class,

        \Strukt\Rules\Spec\MinCountRule::class,
        \Strukt\Rules\Spec\MaxCountRule::class,

        \Strukt\Rules\Spec\InRule::class,
        \Strukt\Rules\Spec\NotInRule::class,
        \Strukt\Rules\Spec\SameRule::class,
        \Strukt\Rules\Spec\DifferentRule::class,

        \Strukt\Rules\Spec\RegexRule::class,
        \Strukt\Rules\Spec\DateRule::class,
    ];

    /**
     * The merged specification table, built once on first use.
     *
     * @var array<string, array{min: int, max: int|null, comma: bool, message: string, bails: bool}>|null
     */
    private static ?array $spec = null;

    /**
     * Creates a rule.
     *
     * @param string $name Rule name, which must appear in the merged spec.
     * @param string $field Field the rule validates.
     * @param list<string> $params Rule parameters, already split.
     * @param Messages|null $messages Message overrides for this form.
     *
     * @throws InvalidArgumentException When the name is unknown or the number
     *                                 of parameters is not accepted.
     */
    public function __construct(
        private readonly string $name,
        private readonly string $field,
        private readonly array $params = [],
        private readonly ?Messages $messages = null,
    ) {
        $spec  = self::spec($name);
        $count = count($params);

        if ($count < $spec['min'] || ($spec['max'] !== null && $count > $spec['max'])) {
            throw new InvalidArgumentException(sprintf(
                'Rule [%s] accepts %s, %d given.',
                $name,
                $spec['min'] === $spec['max']
                    ? sprintf('%d parameter(s)', $spec['min'])
                    : sprintf('%s%d parameter(s)', $spec['min'], $spec['max'] === null ? ' or more' : sprintf(' to %d', $spec['max'])),
                $count,
            ));
        }
    }

    /**
     * Lists the built-in rule names.
     *
     * @return list<string> Rule names.
     */
    public static function names(): array
    {
        return array_keys(self::specTable());
    }

    /**
     * Reports whether a rule name is built in.
     *
     * @param string $name Rule name.
     * @return bool True when the rule exists.
     */
    public static function has(string $name): bool
    {
        return isset(self::specTable()[$name]);
    }

    /**
     * Reports whether a rule splits its parameters on commas.
     *
     * @param string $name Rule name.
     * @return bool True when parameters are comma separated.
     */
    public static function usesCommaParams(string $name): bool
    {
        return self::spec($name)['comma'];
    }

    /**
     * Returns the specification for a rule name.
     *
     * @param string $name Rule name.
     * @return array{min: int, max: int|null, comma: bool, message: string, bails: bool} Specification.
     * @throws InvalidArgumentException When the name is not built in.
     */
    private static function spec(string $name): array
    {
        $spec = self::specTable();

        if (! isset($spec[$name])) {
            throw new InvalidArgumentException(sprintf('Rule [%s] is not a built-in rule.', $name));
        }

        return $spec[$name];
    }

    /**
     * Compiles the merged specification table from the spec files.
     *
     * The table is built once per process. Each spec file contributes one
     * entry under its rule name, so any spec can be located in its own file
     * while the engine still reads a single table.
     *
     * @return array<string, array{min: int, max: int|null, comma: bool, message: string, bails: bool}>
     *                    Compiled specification keyed by rule name.
     */
    private static function specTable(): array
    {
        return self::$spec ??= array_merge(...array_map(
            static fn (string $class): array => $class::SPEC,
            self::SPEC_FILES,
        ));
    }

    public function passes(mixed $value, Contract\FieldSource $fields): bool
    {
        return match ($this->name) {
            'required'   => ! $this->isEmpty($value),
            'nullable'   => $value !== null,
            'filled'     => ! $this->isEmpty($value),

            'string'     => is_string($value),
            'integer'    => $this->isInteger($value),
            'numeric'    => is_numeric($value),
            'boolean'    => $this->isBoolean($value),
            'array'      => is_array($value),

            'email'      => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url'        => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
            'uuid'       => is_string($value) && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i', $value) === 1,
            'ip'         => is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false,

            'alpha'      => is_string($value) && preg_match('/\A\p{L}+\z/u', $value) === 1,
            'alpha_num'  => is_string($value) && preg_match('/\A[\p{L}\p{N}]+\z/u', $value) === 1,

            'min'        => is_numeric($value) && (float) $value >= (float) $this->param(0),
            'max'        => is_numeric($value) && (float) $value <= (float) $this->param(0),
            'between'    => is_numeric($value)
            && (float) $value >= (float) $this->param(0)
            && (float) $value <= (float) $this->param(1),

            'length'     => is_string($value) && mb_strlen($value) === (int) $this->param(0),
            'min_length' => is_string($value) && mb_strlen($value) >= (int) $this->param(0),
            'max_length' => is_string($value) && mb_strlen($value) <= (int) $this->param(0),

            'min_count'  => $this->countOf($value) !== null && $this->countOf($value) >= (int) $this->param(0),
            'max_count'  => $this->countOf($value) !== null && $this->countOf($value) <= (int) $this->param(0),

            // Compared loosely on purpose: a value that arrived as a string
            // from a form submission should still match a numeric option list.
            'in'         => in_array($value, $this->params),
            'not_in'     => ! in_array($value, $this->params),

            'same'       => $value === $fields->value($this->param(0)),
            'different'  => $value !== $fields->value($this->param(0)),

            'regex'      => is_string($value) && preg_match($this->pattern(), $value) === 1,
            'date'       => $this->isDate($value),
        };
    }

    public function message(): string
    {
        $override = $this->messages?->override($this->field, $this->name);

        if ($override !== null) {
            return $this->interpolate($override);
        }

        return $this->label() . ' ' . $this->interpolate(self::specTable()[$this->name]['message']);
    }

    /**
     * Fills a message template.
     *
     * Placeholders are `:0` and `:1` for the first two parameters, `:list` for
     * every parameter, `:field` for the field's label, and `:format` for the
     * expected date format.
     *
     * A per form override is a complete message and is only run through this
     * substitution, so it can use the same placeholders without the field label
     * being added in front of it.
     *
     * @param string $template Message template.
     * @return string Message with placeholders replaced.
     */
    private function interpolate(string $template): string
    {
        return str_replace(
            [':0', ':1', ':list', ':field', ':format'],
            [
                $this->param(0),
                $this->param(1),
                implode(', ', $this->params),
                $this->label(),
                $this->dateFormat(),
            ],
            $template,
        );
    }

    public function bails(): bool
    {
        return self::specTable()[$this->name]['bails'];
    }

    /**
     * Returns the rule name.
     *
     * @return string Rule name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the field the rule validates.
     *
     * @return string Field name.
     */
    public function field(): string
    {
        return $this->field;
    }

    /**
     * Returns a parameter.
     *
     * @param int $index Zero based parameter position.
     * @param string $default Value returned when the parameter is absent.
     * @return string Parameter value.
     */
    private function param(int $index, string $default = ''): string
    {
        return $this->params[$index] ?? $default;
    }

    /**
     * Turns a field name into a readable label for a message.
     *
     * Only the first letter is capitalized, so a name with a preposition in it
     * still reads as a sentence: `born_on` becomes `Born on`, not `Born On`.
     *
     * @param string|null $field Field name, defaulting to this rule's field.
     * @return string Human readable label.
     */
    private function label(?string $field = null): string
    {
        $field ??= $this->field;

        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }

    /**
     * Reports whether a value counts as absent.
     *
     * `null`, an empty string, and an empty array are all treated as absent, so
     * a form that was never filled in does not report a type error for every
     * optional field.
     *
     * @param mixed $value Value under test.
     * @return bool True when the value counts as absent.
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Reports whether a value is an integer, tolerating a numeric string.
     *
     * @param mixed $value Value under test.
     * @return bool True for an int or an integer shaped string.
     */
    private function isInteger(mixed $value): bool
    {
        if (is_int($value)) {
            return true;
        }

        return is_string($value) && preg_match('/\A-?\d+\z/', $value) === 1;
    }

    /**
     * Reports whether a value is a boolean, tolerating form and query strings.
     *
     * @param mixed $value Value under test.
     * @return bool True for a bool or a common boolean literal.
     */
    private function isBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        return in_array($value, [0, 1, '0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'], true);
    }

    /**
     * Counts the items of a countable value.
     *
     * @param mixed $value Value under test.
     * @return int|null Item count, or null when the value is not countable.
     */
    private function countOf(mixed $value): ?int
    {
        if (is_array($value) || $value instanceof \Countable) {
            return count($value);
        }

        return null;
    }

    /**
     * Resolves a pattern into a PCRE expression.
     *
     * A value that already carries delimiters is used as written, otherwise it
     * is wrapped in slashes so that `regex:\d{4}` works.
     *
     * @return string PCRE expression.
     * @throws InvalidArgumentException When the expression cannot be compiled.
     */
    private function pattern(): string
    {
        $pattern = $this->param(0);

        if (preg_match('/\A(?<open>[^\w\s\\\\])(?<body>.*)\k<open>[imsxuADSUXJn]*\z/s', $pattern, $matches) !== 1) {
            $pattern = '/' . str_replace('/', '\\/', $pattern) . '/';
        }

        set_error_handler(static fn(): bool => true);

        try {
            if (preg_match($pattern, '') === false) {
                throw new InvalidArgumentException(sprintf(
                    'Rule [regex] was given a pattern that will not compile: %s',
                    $pattern,
                ));
            }
        } finally {
            restore_error_handler();
        }

        return $pattern;
    }

    /**
     * Returns the date format the rule expects.
     *
     * @return string Format string, defaulting to `Y-m-d`.
     */
    private function dateFormat(): string
    {
        return $this->param(0, 'Y-m-d');
    }

    /**
     * Reports whether a value is a real calendar date in the expected format.
     *
     * The value is parsed and then formatted again, so an impossible date such
     * as `2023-02-30`, or a date with trailing characters, is rejected instead
     * of being silently accepted by the parser.
     *
     * @param mixed $value Value under test.
     * @return bool True for a date matching the requested format.
     */
    private function isDate(mixed $value): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        $format = $this->dateFormat();
        $date   = DateTimeImmutable::createFromFormat('!' . $format, $value);

        if ($date === false) {
            return false;
        }

        $errors = DateTimeImmutable::getLastErrors();

        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }

        return $date->format($format) === $value;
    }
}
