<?php

use Strukt\Rules\Validator;

it('passes when every field satisfies its rules', function (): void {
    $v = new Validator();

    $result = $v->validate([
        'email' => 'user@example.com',
        'age' => 25,
    ], [
        'email' => 'required|email',
        'age' => 'nullable|integer|min:18',
    ]);

    expect($result)->toBeEmpty()
        ->and($v->passes([
            'email' => 'user@example.com',
            'age' => 25,
        ], [
            'email' => 'required|email',
            'age' => 'nullable|integer|min:18',
        ]))->toBeTrue();
});

it('collects all failures in order and reports them per field', function (): void {
    $v = new Validator();
    $errors = $v->validate([
        'email' => 'nope',
        'age' => 12,
    ], [
        'email' => 'required|email|max_length:10',
        'age' => 'required|integer|min:18',
    ]);

    expect($errors)->toHaveCount(2)
        ->and($errors['email'])->toBe(['Email must be a valid email address.'])
        ->and($errors['age'])->toBe(['Age must be at least 18.']);
});

it('skips subsequent rules after a bailing rule fails', function (): void {
    $v = new Validator();
    $errors = $v->validate([
        'age' => 'not a number',
    ], [
        'age' => 'required|integer|min:18',
    ]);

    expect($errors['age'])->toBe(['Age must be an integer.']);
});

it('does not bail on non guard rules', function (): void {
    $v = new Validator();
    $errors = $v->validate([
        'email' => 'nope',
    ], [
        'email' => 'required|email|min_length:100',
    ]);

    expect($errors['email'])->toContain('Email must be a valid email address.')
        ->and($errors['email'])->toContain('Email must be at least 100 characters.');
});

it('skips all non required rules when the value is empty', function (): void {
    $v = new Validator();
    $errors = $v->validate([], [
        'nickname' => 'string|min_length:3',
    ]);

    expect($errors)->toBeEmpty();
});

it('does not skip rules when the field is required but empty', function (): void {
    $v = new Validator();
    $errors = $v->validate([], [
        'nickname' => 'required|string|min_length:3',
    ]);

    expect($errors['nickname'])->toContain('Nickname is required.');
});

it('allows null for a nullable field without applying type rules', function (): void {
    $v = new Validator();
    $errors = $v->validate(['age' => null], [
        'age' => 'nullable|integer|min:18',
    ]);

    expect($errors)->toBeEmpty();
});

it('still enforces rules when nullable is combined with a non empty value', function (): void {
    $v = new Validator();
    $errors = $v->validate(['age' => 'young'], [
        'age' => 'nullable|integer|min:18',
    ]);

    expect($errors['age'])->toBe(['Age must be an integer.']);
});

it('uses message overrides from the validator', function (): void {
    $v = new Validator(null, new \Strukt\Rules\Messages([
        'email.required' => 'Please enter your email.',
        'email' => 'The email you provided is not valid.',
    ]));

    $errors = $v->validate([], ['email' => 'required|email']);

    expect($errors['email'])->toContain('Please enter your email.');
});

it('validates with sibling fields for cross field rules', function (): void {
    $v = new Validator();
    $ok = $v->passes([
        'password' => 'secret',
        'password_confirmation' => 'secret',
    ], [
        'password' => 'required',
        'password_confirmation' => 'required|same:password',
    ]);

    expect($ok)->toBeTrue();
});

it('returns failures for the failures method', function (): void {
    $v = new Validator();
    $failures = $v->failures('age', 'required|integer|min:18', \Strukt\Rules\Fields::from(['age' => 12]));

    expect($failures)->toBe(['Age must be at least 18.']);
});
