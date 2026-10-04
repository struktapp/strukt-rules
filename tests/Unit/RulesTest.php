<?php

use Strukt\Rules\Exception\UnknownRule;
use Strukt\Rules\RuleRegistry;
use Strukt\Rules\Rules;

it('parses pipe separated rule definitions', function (): void {
    $parsed = Rules::parse('email', 'required|email|max_length:120');

    expect($parsed)->toHaveCount(3)
        ->and($parsed[0]->name())->toBe('required')
        ->and($parsed[1]->name())->toBe('email')
        ->and($parsed[2]->name())->toBe('max_length')
        ->and($parsed[2]->passes('long@example.com', new \Strukt\Rules\Fields()))->toBeTrue();
});

it('ignores empty rule tokens', function (): void {
    $parsed = Rules::parse('name', 'required||string');

    expect($parsed)->toHaveCount(2)
        ->and($parsed[0]->name())->toBe('required')
        ->and($parsed[1]->name())->toBe('string');
});

it('parses an array of rule tokens and passes through rule instances', function (): void {
    $instance = new \Strukt\Rules\Rule('email', 'email');
    $parsed = Rules::parse('email', ['required', $instance]);

    expect($parsed)->toHaveCount(2)
        ->and($parsed[0]->name())->toBe('required')
        ->and($parsed[1])->toBe($instance);
});

it('throws for an unknown rule name', function (): void {
    Rules::parse('field', 'unknown_rule');
})->throws(UnknownRule::class);

it('splits rules that use comma separated parameters', function (): void {
    [$name, $params] = Rules::split('in:a,b,c');

    expect($name)->toBe('in')
        ->and($params)->toBe(['a', 'b', 'c']);
});

it('does not split a comma inside a pattern', function (): void {
    [$name, $params] = Rules::split('regex:/^\d{3},\d{4}$/');

    expect($name)->toBe('regex')
        ->and($params)->toBe(['/^\d{3},\d{4}$/']);
});

it('treats an unset colon as no parameters', function (): void {
    [$name, $params] = Rules::split('email');

    expect($name)->toBe('email')
        ->and($params)->toBe([]);
});

it('registers a custom rule instance', function (): void {
    $registry = RuleRegistry::create();
    $custom = new \Strukt\Rules\Fixtures\EvenRule('number');

    $registry->extend('even', $custom);
    $rule = $registry->make('even', 'number', []);

    expect($rule)->toBe($custom)
        ->and($registry->has('even'))->toBeTrue()
        ->and($registry->names())->toContain('even');
});

it('registers a custom rule factory that receives parameters', function (): void {
    $registry = RuleRegistry::create();

    $registry->extend('div_by', fn (string $field, array $params): \Strukt\Rules\Contract\RuleInterface => new \Strukt\Rules\Fixtures\DivisibleByRule($field, (int) ($params[0] ?? 1)));
    $rule = $registry->make('div_by', 'count', ['3']);

    expect($rule->passes(9, new \Strukt\Rules\Fields()))->toBeTrue()
        ->and($rule->passes(10, new \Strukt\Rules\Fields()))->toBeFalse()
        ->and($rule->message())->toContain('divisible by 3');
});

it('throws when trying to extend a built in rule', function (): void {
    RuleRegistry::create()->extend('email', new \Strukt\Rules\Fixtures\EvenRule());
})->throws(InvalidArgumentException::class, 'built in');

it('forgets a custom rule', function (): void {
    $registry = RuleRegistry::create();
    $registry->extend('even', new \Strukt\Rules\Fixtures\EvenRule());
    $registry->forget('even');

    expect($registry->has('even'))->toBeFalse();
});

it('parses a whole rules definition at once', function (): void {
    $parsed = Rules::parseAll([
        'name' => 'required|string',
        'age' => 'nullable|integer',
    ]);

    expect(array_keys($parsed))->toBe(['name', 'age'])
        ->and($parsed['name'])->toHaveCount(2)
        ->and($parsed['name'][0]->name())->toBe('required')
        ->and($parsed['name'][1]->name())->toBe('string')
        ->and($parsed['age'])->toHaveCount(2);
});
