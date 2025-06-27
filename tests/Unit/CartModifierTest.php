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

test('Type must be value or percent', function () {
    new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        type: 'unknown'
    );
})->throws(InvalidArgumentException::class, 'Please provide a valid type for the modifier (value or percent).');

test('if type is percent, value must be between 0 and 100', function () {
    new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 150,
        type: CartModifier::PERCENT
    );
})->throws(InvalidArgumentException::class, 'Please provide a valid value for the modifier between -100 and 100.');

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

test('It returns the raw value', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        description: 'Desc'
    );

    expect($modifier->getRawValue())->toEqual(1000);
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

test('it returns the type', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        type: CartModifier::PERCENT,
        description: 'Desc'
    );

    expect($modifier->getType())->toBe(CartModifier::PERCENT);
});

test('it updates the type and the value', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 50,
        type: CartModifier::PERCENT,
        description: 'Desc'
    );

    $modifier->setType(CartModifier::VALUE);

    expect($modifier->getRawValue())->toEqual(5000);

    $modifier->setType(CartModifier::PERCENT);

    expect($modifier->getRawValue())->toEqual(50);
});

test('it prevents to change the type to percent if value is greater than 100', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 150,
        type: CartModifier::VALUE,
        description: 'Desc'
    );

    $modifier->setType(CartModifier::PERCENT);
})->throws(InvalidArgumentException::class, 'Cannot change the type to percent if the value is not between -100 and 100.');

test('it prevents to change the type to percent if value is lesser than -100', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: -150,
        type: CartModifier::VALUE,
        description: 'Desc'
    );

    $modifier->setType(CartModifier::PERCENT);
})->throws(InvalidArgumentException::class, 'Cannot change the type to percent if the value is not between -100 and 100.');

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

test('it allows null description', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10
    );

    expect($modifier->getDescription())->toBeNull();
});

test('it handles zero value correctly', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 0
    );

    expect($modifier->getValue())->toEqual(0);
    expect($modifier->getRawValue())->toEqual(0);
});

test('it handles negative percent values', function () {
    $modifier = new CartModifier(
        id: 'discount',
        name: 'Discount',
        value: -50,
        type: CartModifier::PERCENT
    );

    expect($modifier->getValue())->toEqual(-50);
    expect($modifier->getType())->toBe(CartModifier::PERCENT);
});

test('it prevents changing to same type', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 10,
        type: CartModifier::VALUE
    );

    $originalValue = $modifier->getRawValue();
    $modifier->setType(CartModifier::VALUE);

    expect($modifier->getRawValue())->toEqual($originalValue);
});

test('it properly converts between value and percent types', function () {
    $modifier = new CartModifier(
        id: 'test',
        name: 'Test modifier',
        value: 1,
        type: CartModifier::VALUE
    );

    expect($modifier->getRawValue())->toEqual(100); // 1 * 100 cents

    $modifier->setType(CartModifier::PERCENT);

    expect($modifier->getRawValue())->toEqual(1); // 100 / 100 = 1%
    expect($modifier->getValue())->toEqual(1);
});

test('it handles edge case of 100 percent', function () {
    $modifier = new CartModifier(
        id: 'full',
        name: 'Full amount',
        value: 100,
        type: CartModifier::PERCENT
    );

    expect($modifier->getValue())->toEqual(100);
});

test('it handles edge case of -100 percent', function () {
    $modifier = new CartModifier(
        id: 'full_discount',
        name: 'Full discount',
        value: -100,
        type: CartModifier::PERCENT
    );

    expect($modifier->getValue())->toEqual(-100);
});
