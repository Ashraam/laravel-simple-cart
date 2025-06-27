<?php

use Ashraam\LaravelSimpleCart\CartModifier;

test('ID must be a string', function () {
    new CartModifier(
        id: [],
        name: 'Test Product',
        value: 10
    );
})->throws(TypeError::class);

test('Name must be a string', function () {
    new CartModifier(
        id: 'test',
        name: null,
        value: 10
    );
})->throws(TypeError::class);

test('Value must be numeric', function () {
    new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: []
    );
})->throws(TypeError::class);

test('It returns the ID', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    expect($modifier->getId())->toBe('test');
});

test('It returns the name', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    expect($modifier->getName())->toBe('Test modifier');
});

test('It updates the name', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    $modifier->setName('Updated name');

    expect($modifier->getName())->toBe('Updated name');
});

test('It returns the value', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    expect($modifier->getValue())->toEqual(10);
});

test('It updates the value', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    $modifier->setValue(20);

    expect($modifier->getValue())->toEqual(20);
});

test('It returns the description', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    expect($modifier->getDescription())->toBe('Desc');
});

test('It updates the description', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    $modifier->setDescription('Updated desc');

    expect($modifier->getDescription())->toBe('Updated desc');
});
