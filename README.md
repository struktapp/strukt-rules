# Strukt Rules

Data driven validation rules, a parser, a registry, and a value validator.

The engine is rule based without being a class per rule. Every built-in rule
has its own declaration in `src/Strukt/Rules/Spec/` — arity, parameter format,
failure message, and bail behaviour — and they compile into one specification
table read by the shared `Rule` class. That table is the single source of
truth: the parser, the message, and the bail behaviour all read from it, so a
rule cannot drift between what it accepts and what it reports.

```php
use Strukt\Rules\Validator;

$errors = (new Validator())->validate(
    ['age' => 12],
    ['age' => 'required|integer|min:18'],
);

// ['age' => ['Age must be at least 18.']]
```

It powers the form definitions in `strukt/form`.

The package has no runtime dependencies: it is a standalone engine that only
needs PHP itself.

## Install

```bash
composer require strukt/rules
```

## Validating values

`Validator` takes an array of field values and a rules definition keyed by
field name. Each entry is a pipe separated string, or a list of rule tokens.
The same call is available as the standalone `check()` helper:

```php
use Strukt\Rules\Validator;

$errors = (new Validator())->validate(
    ['age' => 12],
    ['age' => 'required|integer|min:18'],
);

// or the helper:
$errors = check(['age' => 12], ['age' => 'required|integer|min:18']);
```

Failures are collected per field, in the order the rules were declared.

## Rules

### Presence

| Rule | Passes when |
| --- | --- |
| `required` | The value is not `null`, `''`, or `[]`. |
| `filled` | Same check as `required`. |
| `nullable` | The value is not `null`. |

`null`, an empty string, and an empty array all count as absent, and `0` and
`false` do not. A field that is not `required` and holds an absent value skips
its remaining rules, so an optional field left blank does not also report a type
error.

### Types

| Rule | Passes when |
| --- | --- |
| `string` | The value is a string. |
| `integer` | An int, or a string of digits. `5.5` and `1e3` fail. |
| `numeric` | `is_numeric`. |
| `boolean` | A bool, or `0`/`1`/`true`/`false`/`on`/`off`/`yes`/`no`. |
| `array` | The value is an array. |

### Formats

| Rule | Passes when |
| --- | --- |
| `email` | `FILTER_VALIDATE_EMAIL`. |
| `url` | `FILTER_VALIDATE_URL`. |
| `uuid` | A RFC 4122 UUID. |
| `ip` | `FILTER_VALIDATE_IP`. |
| `date` | A real date in the given format, default `Y-m-d`. |
| `regex` | The pattern matches. |
| `alpha` | Letters only, across scripts. |
| `alpha_num` | Letters and numbers, across scripts. |

`date` parses the value and formats it again, so `2023-02-30` and
`2024-02-30` are rejected rather than silently rolled over:

```php
'born_on' => 'date',           // Y-m-d
'shipped' => 'date:m/d/Y',     // 02/29/2024
```

`regex` takes the pattern with or without delimiters, and commas inside a
pattern are kept, so both of these work:

```php
'zip'    => 'regex:^\d{5}$',
'ssn'    => 'regex:/^\d{3}-\d{4}$/',
'csv_id' => 'regex:^\d{3},\d{4}$',
```

### Size

| Rule | Passes when |
| --- | --- |
| `min:n` | The number is at least `n`. |
| `max:n` | The number is at most `n`. |
| `between:min,max` | The number is within the inclusive range. |
| `length:n` | The string is exactly `n` characters. |
| `min_length:n` | The string is at least `n` characters. |
| `max_length:n` | The string is at most `n` characters. |
| `min_count:n` | The array has at least `n` items. |
| `max_count:n` | The array has at most `n` items. |

Size rules are explicit about what they measure, so a rule never guesses:
`min` compares numbers, `min_length` counts characters with `mb_strlen` rather
than bytes, and `min_count` counts items.

### Sets and fields

| Rule | Passes when |
| --- | --- |
| `in:a,b,c` | The value is one of the listed options. |
| `not_in:a,b,c` | The value is none of the listed options. |
| `same:field` | The value equals the named field. |
| `different:field` | The value differs from the named field. |

`in` and `not_in` compare loosely, so a `2` submitted as `'2'` still matches an
option list of strings. `same` and `different` read the named field through the
`FieldSource` every rule receives, which the validator builds from the values.

### Bailing

A rule that guards the shape of a value, such as `required`, `integer`, or
`array`, stops the field when it fails. The checks that assume a value is
present do not then add a second, confusing message:

```php
'age' => 'required|integer|min:18'
```

With `age => 'not a number'`, only `Age must be an integer.` is reported.

## Messages

Every message is built from a readable field label plus the rule's wording.
Underscores and dashes become spaces and only the first letter is capitalized,
so `email_address` becomes `Email address`, and `min_length:8` on `name`
becomes `Name must be at least 8 characters.`

Override a message per rule, or for one field with `field.rule`. A field
specific override wins. An override is a complete message used as written, and
may still use `:field`, `:0`, `:1`, `:list`, and `:format`.

```php
use Strukt\Rules\Validator;
use Strukt\Rules\Messages;

$validator = new Validator(null, new Messages([
    'email.required' => 'We need an email address to reach you.',
    'min_length'     => ':field is too short, use at least :0 characters.',
]));
```

## Custom rules

Implement `RuleInterface` and register it. A rule receives the value under test
and a `FieldSource` for reading sibling fields.

```php
use Strukt\Rules\Contract\FieldSource;
use Strukt\Rules\Contract\RuleInterface;

final class AfterRule implements RuleInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string $other,
    ) {
    }

    public function passes(mixed $value, FieldSource $fields): bool
    {
        $other = $fields->value($this->other);

        return is_string($value) && is_string($other) && $value > $other;
    }

    public function message(): string
    {
        return ucfirst($this->field) . ' must be later than ' . $this->other . '.';
    }

    public function bails(): bool
    {
        return false;
    }
}
```

Register it globally, or pass a registry to the validator.

```php
use Strukt\Rules\RuleRegistry;
use Strukt\Rules\Validator;

RuleRegistry::shared()->extend('after', fn (string $field, array $params) => new AfterRule($field, $params[0]));

$validator = new Validator(RuleRegistry::shared());
```

`extend()` accepts a rule instance, which is returned for every field, or a
factory called with the field name and the parsed parameters. Built-in names
cannot be replaced, and a misspelled rule name throws
`Strukt\Rules\Exception\UnknownRule` while the rules are parsed, listing the
names that are available.

## Building another engine

The validator has no concept of a form. `strukt/form` is one consumer; any
rule based engine can be built on the same foundations. Values reach a rule
through `Contract\FieldSource`, and a rule set is just an array — nothing in
this package is form specific.

`examples/config.php` builds a different engine next to `form`: a config
validator that reaches nested leaves with dot notation, casts string config
values to their declared types before the rules run, and adds its own `float`
and `port` rules. It is composition over `Validator`, and it owns a private
registry and message vocabulary:

```php
use Strukt\Rules\Messages;
use Strukt\Rules\RuleRegistry;
use Strukt\Rules\Validator;

$registry = RuleRegistry::create();
$registry->extend('port', fn (string $field, array $params) => new PortRule($field, $params));

$engine = new Validator($registry, new Messages([
    'required' => ':field is required in the config.',
]));

$errors = $engine->validate($values, $definition);
```

A private registry keeps an engine's rules and wording local: `port` and
`float` exist only on the config engine and never reach
`RuleRegistry::shared()`, so form validation is unaffected by them.

```bash
php examples/config.php
```

## Tests

```bash
composer test
composer lint
```

## License

MIT. See [LICENSE](LICENSE).