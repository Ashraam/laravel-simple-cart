<?php

use Ashraam\LaravelSimpleCart\Cart;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    Session::shouldReceive('get')
        ->once()
        ->with('laravel_simple_cart.items', [])
        ->andReturn([]);
    Session::shouldReceive('get')
        ->once()
        ->with('laravel_simple_cart.fees', [])
        ->andReturn([]);
    Session::shouldReceive('get')
        ->once()
        ->with('laravel_simple_cart.discounts', [])
        ->andReturn([]);
});

test('can add item to cart', function () {
    // Arrange
    Session::shouldReceive('put')->times(3);
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
    Session::shouldReceive('put')->times(4);
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
    Session::shouldReceive('put')->times(4);
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
    Session::shouldReceive('put')->times(4);
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
    Session::shouldReceive('put')->times(4);
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
    Session::shouldReceive('put')->times(4);
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
    Session::shouldReceive('put')->times(6);
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
    Session::shouldReceive('put')->times(6);
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
    Session::shouldReceive('put')->times(3);
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 29.99, 2, ['size' => 'L']);
    $itemId = md5('product-1' . serialize(['size' => 'L']));

    // Assert
    expect($cart->has($itemId))->toBeTrue()
        ->and($cart->has('non-existent'))->toBeFalse();
});

test('can check if fee exists', function () {
    // Arrange
    Session::shouldReceive('put')->times(3);
    $cart = new Cart();
    $cart->addFee('delivery', 15, 'Delivery fee');

    // Assert
    expect($cart->hasFee('delivery'))->toBeTrue()
        ->and($cart->hasFee('non-existent'))->toBeFalse();
});

test('can check if discount exists', function () {
    // Arrange
    Session::shouldReceive('put')->times(3);
    $cart = new Cart();
    $cart->addDiscount('summer10', 15, 'Summer discount');

    // Assert
    expect($cart->hasDiscount('summer10'))->toBeTrue()
        ->and($cart->hasDiscount('non-existent'))->toBeFalse();
});

test('generates unique item id based on options', function () {
    // Arrange
    Session::shouldReceive('put')->times(5);
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

test('can calculate subtotal', function () {
    // Arrange
    Session::shouldReceive('put')->times(6);
    $cart = new Cart();
    
    // Act
    $cart->add('product-1', 'Test Product 1', 29.99, 2); // 59.98
    $cart->add('product-2', 'Test Product 2', 49.99, 1); // 49.99
    $cart->add('product-3', 'Test Product 3', 19.99, 3); // 59.97

    // Assert
    expect($cart->subtotal())->toBe(169.94); // 59.98 + 49.99 + 59.97 = 169.94
});

test('can add and calculate fees', function () {
    // Arrange
    Session::shouldReceive('put')->times(6);
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 100.00, 1);

    // Act
    $cart->addFee('shipping', 10.00);
    $cart->addFee('handling', 5.00);

    // Assert
    expect($cart->getFees()->count())->toBe(2)
        ->and($cart->totalFees())->toBe(15.00)
        ->and($cart->total())->toBe(115.00); // 100 + 15
});

test('can remove fees', function () {
    // Arrange
    Session::shouldReceive('put')->times(7);
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 100.00, 1);
    $cart->addFee('shipping', 10.00);
    $cart->addFee('handling', 5.00);

    // Act
    $cart->removeFee('shipping');

    // Assert
    expect($cart->getFees()->count())->toBe(1)
        ->and($cart->totalFees())->toBe(5.00)
        ->and($cart->total())->toBe(105.00); // 100 + 5
});

test('can add and calculate discounts', function () {
    // Arrange
    Session::shouldReceive('put')->times(6);
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 100.00, 1);

    // Act
    $cart->addDiscount('SUMMER10', 10.00);
    $cart->addDiscount('WELCOME5', 5.00);

    // Assert
    expect($cart->getDiscounts()->count())->toBe(2)
        ->and($cart->totalDiscounts())->toBe(15.00)
        ->and($cart->total())->toBe(85.00); // 100 - 15
});

test('can remove discounts', function () {
    // Arrange
    Session::shouldReceive('put')->times(7);
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 100.00, 1);
    $cart->addDiscount('SUMMER10', 10.00);
    $cart->addDiscount('WELCOME5', 5.00);

    // Act
    $cart->removeDiscount('SUMMER10');

    // Assert
    expect($cart->getDiscounts()->count())->toBe(1)
        ->and($cart->totalDiscounts())->toBe(5.00)
        ->and($cart->total())->toBe(95.00); // 100 - 5
});

test('total calculation includes both fees and discounts', function () {
    // Arrange
    Session::shouldReceive('put')->times(7);
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 100.00, 1);
    
    // Act
    $cart->addFee('shipping', 10.00);
    $cart->addDiscount('SUMMER10', 20.00);

    // Assert
    expect($cart->subtotal())->toBe(100.00)
        ->and($cart->totalFees())->toBe(10.00)
        ->and($cart->totalDiscounts())->toBe(20.00)
        ->and($cart->total())->toBe(90.00); // 100 + 10 - 20
});

test('clear removes items, fees, and discounts', function () {
    // Arrange
    Session::shouldReceive('put')->times(8);
    $cart = new Cart();
    $cart->add('product-1', 'Test Product', 100.00, 1);
    $cart->addFee('shipping', 10.00);
    $cart->addDiscount('SUMMER10', 20.00);

    // Act
    $cart->clear();

    // Assert
    expect($cart->content()->isEmpty())->toBeTrue()
        ->and($cart->getFees()->isEmpty())->toBeTrue()
        ->and($cart->getDiscounts()->isEmpty())->toBeTrue()
        ->and($cart->total())->toBe(0.0);
});


test('can get a specific discount', function () {
    // Arrange
    Session::shouldReceive('put')->times(3);
    $cart = new Cart();

    // Act
    $cart->addDiscount('SUMMER10', 20.00);

    // Assert
    expect($cart->getDiscount('SUMMER10'))->toBeArray()
        ->and($cart->getDiscount('non-existant'))->toBeNull();
});

test('can get a specific fee', function () {
    // Arrange
    Session::shouldReceive('put')->times(3);
    $cart = new Cart();

    // Act
    $cart->addFee('shipping', 20.00);

    // Assert
    expect($cart->getFee('shipping'))->toBeArray()
        ->and($cart->getFee('non-existant'))->toBeNull();
});
