<?php

use Ashraam\LaravelSimpleCart\Cart;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    Session::shouldReceive('get')
        ->once()
        ->with('laravel_simple_cart', [])
        ->andReturn([]);
});

test('can add item to cart', function () {
    // Arrange
    Session::shouldReceive('put')->once();
    $cart = new Cart();

    // Act
    $cart->add(
        id: 'product-1',
        name: 'Test Product',
        price: 29.99,
        quantity: 2,
        options: ['size' => 'L'],
        meta: ['category' => 'clothing']
    );

    // Assert
    $items = $cart->content();
    $itemId = md5('product-1' . serialize(['size' => 'L']));
    
    expect($items->has($itemId))->toBeTrue()
        ->and($items->get($itemId))
        ->toHaveKey('id', 'product-1')
        ->toHaveKey('name', 'Test Product')
        ->toHaveKey('price', 29.99)
        ->toHaveKey('quantity', 2)
        ->toHaveKey('options', ['size' => 'L'])
        ->toHaveKey('meta', ['category' => 'clothing']);
});

test('adds quantity when adding same item', function () {
    // Arrange
    Session::shouldReceive('put')->twice();
    $cart = new Cart();

    // Act
    $cart->add('product-1', 'Test Product', 29.99, 2, ['size' => 'L']);
    $cart->add('product-1', 'Test Product', 29.99, 3, ['size' => 'L']);

    // Assert
    $itemId = md5('product-1' . serialize(['size' => 'L']));
    expect($cart->get($itemId))->toHaveKey('quantity', 5);
});

test('can update item quantity', function () {
    // Arrange
    Session::shouldReceive('put')->twice();
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 29.99, 2, ['size' => 'L']);
    $itemId = md5('product-1' . serialize(['size' => 'L']));

    // Act
    $cart->update($itemId, 4);

    // Assert
    expect($cart->get($itemId))->toHaveKey('quantity', 4);
});

test('removes item when updating quantity to zero', function () {
    // Arrange
    Session::shouldReceive('put')->twice();
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 29.99, 2, ['size' => 'L']);
    $itemId = md5('product-1' . serialize(['size' => 'L']));

    // Act
    $cart->update($itemId, 0);

    // Assert
    expect($cart->has($itemId))->toBeFalse();
});

test('can remove item from cart', function () {
    // Arrange
    Session::shouldReceive('put')->twice();
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 29.99, 2, ['size' => 'L']);
    $itemId = md5('product-1' . serialize(['size' => 'L']));

    // Act
    $cart->remove($itemId);

    // Assert
    expect($cart->has($itemId))->toBeFalse();
});

test('can clear cart', function () {
    // Arrange
    Session::shouldReceive('put')->twice();
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 29.99, 2, ['size' => 'L']);

    // Act
    $cart->clear();

    // Assert
    expect($cart->content()->isEmpty())->toBeTrue()
        ->and($cart->count())->toBe(0)
        ->and($cart->total())->toBe(0.0);
});

test('can calculate total', function () {
    // Arrange
    Session::shouldReceive('put')->times(3);
    $cart = new Cart();
    
    // Act
    $cart->add('product-1', 'Test Product 1', 29.99, 2, ['size' => 'L']);
    $cart->add('product-2', 'Test Product 2', 49.99, 1, ['size' => 'M']);
    $cart->add('product-3', 'Test Product 3', 19.99, 3, ['size' => 'S']);

    // Assert
    // (29.99 * 2) + (49.99 * 1) + (19.99 * 3) = 59.98 + 49.99 + 59.97 = 169.94
    expect($cart->total())->toBe(169.94);
});

test('can count items', function () {
    // Arrange
    Session::shouldReceive('put')->times(3);
    $cart = new Cart();
    
    // Act
    $cart->add('product-1', 'Test Product 1', 29.99, 2, ['size' => 'L']);
    $cart->add('product-2', 'Test Product 2', 49.99, 1, ['size' => 'M']);
    $cart->add('product-3', 'Test Product 3', 19.99, 3, ['size' => 'S']);

    // Assert
    expect($cart->count())->toBe(6); // 2 + 1 + 3 = 6 items
});

test('can check if item exists', function () {
    // Arrange
    Session::shouldReceive('put')->once();
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 29.99, 2, ['size' => 'L']);
    $itemId = md5('product-1' . serialize(['size' => 'L']));

    // Assert
    expect($cart->has($itemId))->toBeTrue()
        ->and($cart->has('non-existent'))->toBeFalse();
});

test('generates unique item id based on options', function () {
    // Arrange
    Session::shouldReceive('put')->twice();
    $cart = new Cart();
    
    // Act
    $cart->add('product-1', 'Test Product', 29.99, 1, ['size' => 'L']);
    $cart->add('product-1', 'Test Product', 29.99, 1, ['size' => 'M']);

    // Assert
    $itemId1 = md5('product-1' . serialize(['size' => 'L']));
    $itemId2 = md5('product-1' . serialize(['size' => 'M']));
    
    expect($cart->has($itemId1))->toBeTrue()
        ->and($cart->has($itemId2))->toBeTrue()
        ->and($cart->count())->toBe(2);
});
