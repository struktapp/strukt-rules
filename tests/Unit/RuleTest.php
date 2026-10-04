<?php

use Strukt\Rules\Fields;
use Strukt\Rules\Messages;
use Strukt\Rules\Rule;
use Strukt\Rules\Rules;

/**
 * Runs a single rule token against a value.
 *
 * @param string $token Rule token, for example `min_length:3`.
 * @param mixed $value Value under test.
 * @param array<string, mixed> $siblings Sibling field values.
 * @return bool True when the rule passes.
 */
function passes(string $token, mixed $value, array $siblings = []): bool
{
    [$name, $params] = Rules::split($token);
    $fields = Fields::from($siblings);

    return (new Rule($name, 'field', $params))->passes($value, $fields);
}

it('lists every built in rule', function (): void {
    expect(Rule::names())
        ->toContain('required', 'email', 'integer', 'min', 'max', 'between', 'in', 'same', 'regex', 'date')
        ->toHaveCount(28);
});

it('knows which rules are built in', function (): void {
    expect(Rule::has('email'))->toBeTrue()
        ->and(Rule::has('nope'))->toBeFalse();
});

/**
 * One rule token per built in rule, with a valid parameter count.
 *
 * @return list<string> Rule tokens.
 */
function specTokens(): array
{
    return [
        'required', 'nullable', 'filled',
        'string', 'integer', 'numeric', 'boolean', 'array',
        'email', 'url', 'uuid', 'ip',
        'alpha', 'alpha_num',
        'min:1', 'max:1', 'between:1,2',
        'length:1', 'min_length:1', 'max_length:1',
        'min_count:1', 'max_count:1',
        'in:a', 'not_in:a', 'same:a', 'different:a',
        'regex:.', 'date', 'date:Y',
    ];
}

it('gives every built in rule a complete specification', function (string $token): void {
    $rule = Rules::parse('field', $token)[0];

    expect($rule->name())->not->toBeEmpty()
        ->and($rule->message())->not->toBe('Field ', $token)
        ->and($rule->message())->toStartWith('Field ')
        ->and($rule->bails())->toBeBool();
})->with(specTokens());

it('covers every built in rule with that check', function (): void {
    $names = array_unique(array_map(
        static fn (string $token): string => Rules::split($token)[0],
        specTokens(),
    ));

    expect($names)->toEqual(Rule::names());
});

it('rejects a parameter count the rule does not accept', function (): void {
    new Rule('min', 'field', ['1', '2']);
})->throws(InvalidArgumentException::class, 'Rule [min] accepts 1 parameter(s), 2 given.');

it('rejects a parameter passed to a rule that takes none', function (): void {
    new Rule('email', 'field', ['x']);
})->throws(InvalidArgumentException::class, 'Rule [email] accepts 0 parameter(s), 1 given.');

it('accepts any number of parameters for a list rule', function (): void {
    $rule = new Rule('in', 'field', ['a', 'b', 'c', 'd', 'e']);

    expect($rule->name())->toBe('in');
});

it('reports a missing value for the required rules', function (string $token): void {
    expect(passes($token, null))->toBeFalse()
        ->and(passes($token, ''))->toBeFalse()
        ->and(passes($token, []))->toBeFalse()
        ->and(passes($token, 0))->toBeTrue()
        ->and(passes($token, false))->toBeTrue();
})->with(['required', 'filled']);

it('reports missing values for required', function (): void {
    expect(passes('required', 'a'))->toBeTrue()
        ->and(passes('required', 0))->toBeTrue()
        ->and(passes('required', false))->toBeTrue()
        ->and(passes('required', null))->toBeFalse();
});

it('checks primitive types', function (): void {
    expect(passes('string', 'a'))->toBeTrue()
        ->and(passes('string', 1))->toBeFalse()
        ->and(passes('integer', 5))->toBeTrue()
        ->and(passes('integer', '5'))->toBeTrue()
        ->and(passes('integer', '5.5'))->toBeFalse()
        ->and(passes('integer', '1e3'))->toBeFalse()
        ->and(passes('numeric', '5.5'))->toBeTrue()
        ->and(passes('numeric', 'abc'))->toBeFalse()
        ->and(passes('array', []))->toBeTrue()
        ->and(passes('array', 'a'))->toBeFalse();
});

it('accepts the usual boolean literals', function (): void {
    expect(passes('boolean', true))->toBeTrue()
        ->and(passes('boolean', '1'))->toBeTrue()
        ->and(passes('boolean', 'on'))->toBeTrue()
        ->and(passes('boolean', 'false'))->toBeTrue()
        ->and(passes('boolean', 'maybe'))->toBeFalse();
});

it('checks formats', function (): void {
    expect(passes('email', 'user@example.com'))->toBeTrue()
        ->and(passes('email', 'user@example'))->toBeFalse()
        ->and(passes('url', 'https://example.com'))->toBeTrue()
        ->and(passes('url', 'not a url'))->toBeFalse()
        ->and(passes('uuid', '123e4567-e89b-12d3-a456-426614174000'))->toBeTrue()
        ->and(passes('uuid', 'nope'))->toBeFalse()
        ->and(passes('ip', '192.168.1.1'))->toBeTrue()
        ->and(passes('ip', '999.1.1.1'))->toBeFalse();
});

it('checks letter only fields across scripts', function (): void {
    expect(passes('alpha', 'Ünïcödé'))->toBeTrue()
        ->and(passes('alpha', 'abc1'))->toBeFalse()
        ->and(passes('alpha_num', 'Ünïcödé42'))->toBeTrue()
        ->and(passes('alpha_num', 'a b'))->toBeFalse();
});

it('compares numbers inclusively', function (): void {
    expect(passes('min:18', 18))->toBeTrue()
        ->and(passes('min:18', 17.9))->toBeFalse()
        ->and(passes('max:5', 5))->toBeTrue()
        ->and(passes('between:1,5', 1))->toBeTrue()
        ->and(passes('between:1,5', 5))->toBeTrue()
        ->and(passes('between:1,5', 6))->toBeFalse()
        ->and(passes('between:1,5', 'abc'))->toBeFalse();
});

it('counts characters rather than bytes', function (): void {
    expect(passes('length:5', 'héllo'))->toBeTrue()
        ->and(passes('min_length:5', 'héllo'))->toBeTrue()
        ->and(passes('min_length:6', 'héllo'))->toBeFalse()
        ->and(passes('max_length:5', 'héllo'))->toBeTrue();
});

it('counts array items', function (): void {
    expect(passes('min_count:2', ['a', 'b']))->toBeTrue()
        ->and(passes('min_count:3', ['a', 'b']))->toBeFalse()
        ->and(passes('max_count:2', ['a']))->toBeTrue()
        ->and(passes('min_count:2', 'ab'))->toBeFalse();
});

it('membership rules compare loosely so submitted strings still match', function (): void {
    expect(passes('in:1,2,3', 2))->toBeTrue()
        ->and(passes('in:1,2,3', '2'))->toBeTrue()
        ->and(passes('in:a,b', 'a'))->toBeTrue()
        ->and(passes('in:a,b', 'c'))->toBeFalse()
        ->and(passes('not_in:a,b', 'c'))->toBeTrue()
        ->and(passes('not_in:a,b', 'a'))->toBeFalse();
});

it('compares against sibling fields', function (): void {
    expect(passes('same:password', 'secret', ['password' => 'secret']))->toBeTrue()
        ->and(passes('same:password', 'secret', ['password' => 'other']))->toBeFalse()
        ->and(passes('different:password', 'a', ['password' => 'b']))->toBeTrue()
        ->and(passes('different:password', 'a', ['password' => 'a']))->toBeFalse();
});

it('accepts a pattern with or without delimiters', function (): void {
    expect(passes('regex:^\d{3}$', '123'))->toBeTrue()
        ->and(passes('regex:^\d{3}$', '12a'))->toBeFalse()
        ->and(passes('regex:/^\d{3}$/', '123'))->toBeTrue()
        ->and(passes('regex:/^\d{3}$/i', 'ABC'))->toBeFalse()
        ->and(passes('regex:/^abc$/i', 'ABC'))->toBeTrue();
});

it('keeps a comma inside a pattern argument', function (): void {
    expect(passes('regex:^\d{3},\d{4}$', '123,4567'))->toBeTrue()
        ->and(passes('regex:^\d{3},\d{4}$', '1234567'))->toBeFalse();
});

it('refuses a pattern that will not compile', function (): void {
    passes('regex:^(unclosed', 'anything');
})->throws(InvalidArgumentException::class, 'will not compile');

it('rejects a date that does not exist', function (): void {
    expect(passes('date', '2023-02-30'))->toBeFalse()
        ->and(passes('date', '2024-02-29'))->toBeTrue()
        ->and(passes('date', '2023-02-28'))->toBeTrue()
        ->and(passes('date', '2024-02-30'))->toBeFalse();
});

it('rejects trailing characters after a date', function (): void {
    expect(passes('date', '2024-01-01x'))->toBeFalse()
        ->and(passes('date', '2024-1-1'))->toBeFalse();
});

it('honours a custom date format', function (): void {
    expect(passes('date:m/d/Y', '02/29/2024'))->toBeTrue()
        ->and(passes('date:m/d/Y', '29/02/2024'))->toBeFalse();
});

it('accepts a date object', function (): void {
    expect(passes('date', new DateTimeImmutable('now')))->toBeTrue();
});

it('bails only on the rules that guard the shape of a value', function (): void {
    expect((new Rule('required', 'field'))->bails())->toBeTrue()
        ->and((new Rule('integer', 'field'))->bails())->toBeTrue()
        ->and((new Rule('array', 'field'))->bails())->toBeTrue()
        ->and((new Rule('email', 'field'))->bails())->toBeFalse()
        ->and((new Rule('min_length', 'field', ['3']))->bails())->toBeFalse();
});

it('names itself and the field it validates', function (): void {
    $rule = new Rule('min_length', 'date_of_birth', ['3']);

    expect($rule->name())->toBe('min_length')
        ->and($rule->field())->toBe('date_of_birth');
});

it('builds a readable label from a field name', function (string $field, string $expected): void {
    expect((new Rule('required', $field))->message())->toBe($expected . ' is required.');
})->with([
    ['email', 'Email'],
    ['email_address', 'Email address'],
    ['born_on', 'Born on'],
    ['password_confirmation', 'Password confirmation'],
    ['first-name', 'First name'],
]);

it('substitutes parameters into the message', function (): void {
    expect((new Rule('min_length', 'name', ['8']))->message())->toBe('Name must be at least 8 characters.')
        ->and((new Rule('between', 'age', ['18', '65']))->message())->toBe('Age must be between 18 and 65.')
        ->and((new Rule('in', 'role', ['admin', 'editor']))->message())->toBe('Role must be one of: admin, editor.')
        ->and((new Rule('same', 'password_confirmation', ['password']))->message())->toBe('Password confirmation must match password.');
});

it('lets an override reuse the placeholders', function (): void {
    $messages = new Messages(['min_length' => ':field is too short, use at least :0 characters.']);
    $rule = new Rule('min_length', 'name', ['8'], $messages);

    expect($rule->message())->toBe('Name is too short, use at least 8 characters.');
});

it('uses an override as a complete message', function (): void {
    $messages = new Messages(['required' => 'We need this.']);
    $rule = new Rule('required', 'email', [], $messages);

    expect($rule->message())->toBe('We need this.');
});

it('prefers a field specific override over a rule wide one', function (): void {
    $messages = new Messages([
        'email.required' => 'We need your email.',
        'required' => 'Required.',
    ]);

    expect((new Rule('required', 'email', [], $messages))->message())->toBe('We need your email.')
        ->and((new Rule('required', 'name', [], $messages))->message())->toBe('Required.');
});
