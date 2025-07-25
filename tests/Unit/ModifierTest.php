<?php

use Ashraam\LaravelSimpleCart\CartItem;
use Ashraam\LaravelSimpleCart\CartModifier;

beforeEach(function () {
    $this->cart = app(\Ashraam\LaravelSimpleCart\Cart::class);

    $this->modifier = new CartModifier(
        id: 'shipping',
        name: 'Express shipping',
        value: 6.5,
        description: 'Express shipping (2-3 days)'
    );
});

test('it adds a modifier', function () {
    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->content())
        ->toHaveKey('shipping')
        ->toHaveCount(1);
});

test('it overwrites existing modifier with same id', function () {
    $modifier1 = new CartModifier('same-id', 'First', 10);
    $modifier2 = new CartModifier('same-id', 'Second', 20);

    $this->cart->modifiers()->add($modifier1);
    $this->cart->modifiers()->add($modifier2);

    expect($this->cart->modifiers()->content())
        ->toHaveCount(1)
        ->and($this->cart->modifiers()->get('same-id')?->getName())
        ->toBe('Second')
        ->and($this->cart->modifiers()->get('same-id')?->getValue())
        ->toEqual(20);
});


test('it returns all the modifiers', function () {
    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->content())
        ->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

test('it removes a modifier', function () {
    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->content())
        ->toHaveCount(1);

    $this->cart->modifiers()->remove('shipping');

    expect($this->cart->modifiers()->content())
        ->toHaveCount(0);
});

test('it removes modifier by id string', function () {
    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->content())
        ->toHaveCount(1);

    $this->cart->modifiers()->remove($this->modifier->getId());

    expect($this->cart->modifiers()->content())
        ->toHaveCount(0);
});

test('it checks if the modifiers exists', function () {
    expect($this->cart->modifiers()->has('shipping'))
        ->toBeFalse();

    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->has('shipping'))
        ->toBeTrue();
});

test('it checks if the modifier exists by CartModifier instance', function () {
    expect($this->cart->modifiers()->has($this->modifier))
        ->toBeFalse();

    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->has($this->modifier))
        ->toBeTrue();
});

test('it returns the modifier', function () {
    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->get('shipping'))
        ->toBe($this->modifier);
});

test('it returns modifier by CartModifier instance', function () {
    $this->cart->modifiers()->add($this->modifier);

    $retrieved = $this->cart->modifiers()->get($this->modifier);

    expect($retrieved)
        ->toBe($this->modifier);
});

test('it returns null when getting non-existent modifier', function () {
    $nonExistent = new CartModifier('non-existent', 'Non-existent', 10);

    $result = $this->cart->modifiers()->get($nonExistent);

    expect($result)->toBeNull();
});

test('it clears the modifiers', function () {
    $this->cart->modifiers()->add($this->modifier);
    $this->cart->modifiers()->add(new CartModifier(id: 'summer10', name: 'Summer Sale', value: -10, description: '10€ off on all summer products'));

    expect($this->cart->modifiers()->content())
        ->toHaveCount(2);

    $this->cart->modifiers()->clear();

    expect($this->cart->modifiers()->content())
        ->toHaveCount(0);
});

test('it returns the sum of all modifiers', function () {
    $this->cart->modifiers()->add($this->modifier);
    $this->cart->modifiers()->add(new CartModifier(id: 'summer10', name: 'Summer Sale', value: -10, description: '10€ off on all summer products'));

    expect($this->cart->modifiers()->total())
        ->toEqual(-3.5);
});

test('Percent modifiers are calculated based on the cart subtotal', function () {
    $this->cart->add(new CartItem(id: 'product-1', name: 'Test Product', price: 50, quantity: 1));
    $this->cart->modifiers()->add(new CartModifier(id: 'summer10', name: 'Summer Sale', value: -10, type: CartModifier::PERCENT));

    expect($this->cart->modifiers()->total())
        ->toEqual(-5);
});

test('it handles empty cart with percent modifiers', function () {
    $percentModifier = new CartModifier('percent', 'Percent Test', 20, CartModifier::PERCENT);
    $this->cart->modifiers()->add($percentModifier);

    // When the cart is empty, the percent calculation should be 0% of 0 = 0
    expect($this->cart->modifiers()->total())
        ->toEqual(0);
});

test('it returns zero total when no modifiers exist', function () {
    expect($this->cart->modifiers()->total())
        ->toEqual(0);
});
