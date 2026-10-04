<?php

use Strukt\Rules\Validator;

it('validates values against a rules definition', function (): void {
    expect(check(['age' => 20], ['age' => 'required|integer|min:18']))->toBeEmpty()
        ->and(check(['age' => 12], ['age' => 'required|integer|min:18']))
        ->toBe(['age' => ['Age must be at least 18.']]);
});

it('returns an empty array for every passing field', function (): void {
    $errors = check([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ], [
        'name' => 'required|string|min_length:2',
        'email' => 'required|email',
    ]);

    expect($errors)->toBeEmpty();
});

it('keeps the same behaviour as the validator', function (): void {
    $values = ['email' => 'nope'];
    $rules = ['email' => 'required|email|max_length:10'];

    expect(check($values, $rules))->toBe((new Validator())->validate($values, $rules));
});