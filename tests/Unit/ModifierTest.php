<?php

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

test('it checks if the modifiers exists', function () {
    expect($this->cart->modifiers()->has('shipping'))
        ->toBeFalse();

    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->has('shipping'))
        ->toBeTrue();
});

test('it returns the modifier', function () {
    $this->cart->modifiers()->add($this->modifier);

    expect($this->cart->modifiers()->get('shipping'))
        ->toBe($this->modifier);
});

test('it clears the modifiers', function () {
    $this->cart->modifiers()->add($this->modifier);
    $this->cart->modifiers()->add(new CartModifier('summer10', 'Summer Sale', -10, '10€ off on all summer products'));

    expect($this->cart->modifiers()->content())
        ->toHaveCount(2);

    $this->cart->modifiers()->clear();

    expect($this->cart->modifiers()->content())
        ->toHaveCount(0);
});

test('it returns the sum of all modifiers', function () {
    $this->cart->modifiers()->add($this->modifier);
    $this->cart->modifiers()->add(new CartModifier('summer10', 'Summer Sale', -10, '10€ off on all summer products'));

    expect($this->cart->modifiers()->total())
        ->toEqual(-3.5);
});
