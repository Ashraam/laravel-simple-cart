<?php

use Ashraam\LaravelSimpleCart\Cart;
use Ashraam\LaravelSimpleCart\CartItem;
use Ashraam\LaravelSimpleCart\CartModifier;
use Ashraam\LaravelSimpleCart\Contracts\ModifierManagerInterface;

beforeEach(function () {
    $this->cart = app(Cart::class);
    $this->item = new CartItem('product-1', 'Test Product', 100.00, 1);
    $this->modifier1 = new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT);
    $this->modifier2 = new CartModifier('shipping', 'Shipping Fee', 15, CartModifier::VALUE);
});

test('cart modifiers manager implements interface correctly', function () {
    $manager = $this->cart->modifiers();
    
    expect($manager)->toBeInstanceOf(ModifierManagerInterface::class)
        ->and(method_exists($manager, 'add'))->toBeTrue()
        ->and(method_exists($manager, 'remove'))->toBeTrue()
        ->and(method_exists($manager, 'has'))->toBeTrue()
        ->and(method_exists($manager, 'get'))->toBeTrue()
        ->and(method_exists($manager, 'content'))->toBeTrue()
        ->and(method_exists($manager, 'clear'))->toBeTrue()
        ->and(method_exists($manager, 'total'))->toBeTrue();
    
    // Test all interface methods exist
});

test('cart modifier manager implements all interface methods', function () {
    $cartManager = $this->cart->modifiers();
    
    $cartMethods = get_class_methods($cartManager);
    
    // Cart should have all interface methods
    $interfaceMethods = ['add', 'remove', 'has', 'get', 'content', 'clear', 'total'];
    
    foreach ($interfaceMethods as $method) {
        expect(in_array($method, $cartMethods))->toBeTrue("Cart manager missing method: {$method}");
    }
});

test('cart modifier manager handles empty state correctly', function () {
    $manager = $this->cart->modifiers();
    
    expect($manager->content())->toBeEmpty()
        ->and($manager->has($this->modifier1))->toBeFalse()
        ->and($manager->has('nonexistent'))->toBeFalse()
        ->and($manager->get($this->modifier1))->toBeNull()
        ->and($manager->get('nonexistent'))->toBeNull()
        ->and($manager->total())->toEqual(0.0);
});

test('cart modifier manager add/remove by instance and id', function () {
    $manager = $this->cart->modifiers();
    
    // Add by instance
    $manager->add($this->modifier1);
    expect($manager->has($this->modifier1))->toBeTrue()
        ->and($manager->has($this->modifier1->getId()))->toBeTrue()
        ->and($manager->get($this->modifier1))->toBe($this->modifier1)
        ->and($manager->get($this->modifier1->getId()))->toBe($this->modifier1);

    // Remove by instance
    $manager->remove($this->modifier1);
    expect($manager->has($this->modifier1))->toBeFalse();
    
    // Add again and remove by ID
    $manager->add($this->modifier1);
    $manager->remove($this->modifier1->getId());
    expect($manager->has($this->modifier1))->toBeFalse();
});

test('cart modifier manager content returns collection', function () {
    $manager = $this->cart->modifiers();
    
    $manager->add($this->modifier1);
    $manager->add($this->modifier2);
    
    $content = $manager->content();
    
    expect($content)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($content)->toHaveCount(2)
        ->and($content->contains($this->modifier1))->toBeTrue()
        ->and($content->contains($this->modifier2))->toBeTrue();
});

test('cart modifier manager clear removes all modifiers', function () {
    $manager = $this->cart->modifiers();
    
    $manager->add($this->modifier1);
    $manager->add($this->modifier2);
    expect($manager->content())->toHaveCount(2);
    
    $manager->clear();
    expect($manager->content())->toHaveCount(0)
        ->and($manager->has($this->modifier1))->toBeFalse()
        ->and($manager->has($this->modifier2))->toBeFalse();
});

test('cart modifier manager total calculates based on subtotal', function () {
    $item = new CartItem('product-1', 'Product', 100, 2); // subtotal = 200
    $this->cart->add($item);
    
    $manager = $this->cart->modifiers();
    $manager->add(new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT)); // -20
    $manager->add(new CartModifier('shipping', 'Shipping', 15, CartModifier::VALUE)); // +15
    
    expect($manager->total())->toEqual(-5.0); // -20 + 15
});

test('cart modifier manager handles duplicate IDs correctly', function () {
    $cartManager = $this->cart->modifiers();
    
    $original = new CartModifier('test', 'Original', 10, CartModifier::VALUE);
    $replacement = new CartModifier('test', 'Replacement', 20, CartModifier::VALUE);
    
    // Cart manager
    $cartManager->add($original);
    expect($cartManager->get('test')->getName())->toBe('Original');
    
    $cartManager->add($replacement); // Should replace
    expect($cartManager->get('test')->getName())->toBe('Replacement')
        ->and($cartManager->content())->toHaveCount(1);
});
