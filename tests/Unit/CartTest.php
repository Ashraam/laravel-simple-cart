<?php

use Ashraam\LaravelSimpleCart\CartItem;
use Ashraam\LaravelSimpleCart\CartModifier;
use Ashraam\LaravelSimpleCart\Events\CartCleared;
use Ashraam\LaravelSimpleCart\Events\ItemAdded;
use Ashraam\LaravelSimpleCart\Events\ItemQuantityUpdated;
use Ashraam\LaravelSimpleCart\Events\ItemRemoved;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['laravel-simple-cart.default_session_key' => 'default_cart']);
    $this->cart = app(\Ashraam\LaravelSimpleCart\Cart::class);
    $this->item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
    );

    $this->cart->clear();
});

test('it can set an instance on the fly', function () {
    $this->cart->instance('wishlist')->add($this->item);

    expect($this->cart->instance('wishlist')->count())
        ->toEqual(1)
        ->and($this->cart->instance('wishlist')->has($this->item))
        ->toBeTrue()
        ->and($this->cart->instance()->count())
        ->toEqual(0);
});

test('it uses default instance when none specified', function () {
    $defaultInstance = $this->cart->getInstance();
    $explicitDefault = $this->cart->instance()->getInstance();

    expect($defaultInstance)
        ->toBe($explicitDefault);
});

test('it can retrieve an item from the cart', function () {
    $this->cart->add($this->item);

    $item = $this->cart->get($this->item);

    expect($item)
        ->toBeInstanceOf(CartItem::class)
        ->and($item)
        ->toBe($this->item);
});

test('it can retrieve an item by hash string', function () {
    $this->cart->add($this->item);
    $hash = $this->item->getHash();

    $retrieved = $this->cart->get($hash);

    expect($retrieved)
        ->toBeInstanceOf(CartItem::class)
        ->and($retrieved->getId())
        ->toBe($this->item->getId());
});

test('it returns null when getting non-existent item', function () {
    $result = $this->cart->get('non-existent');

    expect($result)->toBeNull();
});

test('it can find an item by its id and options', function () {
    $this->cart->add(new CartItem(id: 'product-1', name: 'T-Shirt', price: 25, quantity: 1, options: ['size' => 'M']));

    $item = $this->cart->find('product-1', ['size' => 'M']);

    expect($item)
        ->toBeInstanceOf(CartItem::class)
        ->and($item?->getId())
        ->toBe('product-1');
});

test('it returns null if cannot find the item', function () {
    $this->cart->add(new CartItem(id: 'product-1', name: 'T-Shirt', price: 25, quantity: 1, options: ['size' => 'M', 'color' => 'red']));

    $item = $this->cart->find('product-1', ['size' => 'M']);

    expect($item)->toBeNull();
});

test('it can check if an item exists in the cart', function () {
    expect($this->cart->has($this->item))->toBeFalse();

    $this->cart->add($this->item);

    expect($this->cart->has($this->item))->toBeTrue();
});

test('it can check if an item exists in the cart by hash string', function () {
    $this->cart->add($this->item);
    $hash = $this->item->getHash();

    expect($this->cart->has($hash))->toBeTrue();
});

test('it search items in the cart', function () {
    $otherItem = new CartItem('product-2', 'Test Product 2', 80, 1);

    $this->cart->add($this->item);
    $this->cart->add($otherItem);

    $results = $this->cart->search(function ($item) {
        return $item->getPrice() <= 80;
    });

    expect($results)
        ->toHaveCount(1)
        ->and($results->first())
        ->toBe($otherItem);
});

test('the search function throws if the argument if not a callable', function () {
    $this->cart->search([]);
    $this->cart->search('test');
    $this->cart->search(1);
    $this->cart->search(true);
})->throws(\TypeError::class);

test('search returns empty collection when no items match', function () {
    $this->cart->add($this->item);

    $results = $this->cart->search(function ($item) {
        return $item->getPrice() > 200;
    });

    expect($results)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toHaveCount(0);
});

test('it can add an item to the cart', function () {
    $this->cart->add($this->item);

    expect($this->cart->count())
        ->toEqual(1)
        ->and($this->cart->has($this->item))
        ->toBeTrue();
});

test('it dispatch an event when adding an item', function () {
    Event::fake();

    $this->cart->add($this->item);

    $hash = $this->item->getHash();

    Event::assertDispatched(ItemAdded::class, function ($event) use($hash) {
        return $event->instance === 'default_cart' && $event->item->getHash() === $hash;
    });
});

test('it throws if you try to add an item other than an CartItem instance', function () {
    $this->cart->add([]);
})->throws(TypeError::class);

test('adding same item with different options creates separate entries', function () {
    $item1 = new CartItem(id: 'product-1', name: 'T-Shirt', price: 25, quantity: 1, options: ['size' => 'M']);
    $item2 = new CartItem(id: 'product-1', name: 'T-Shirt', price: 25, quantity: 1, options: ['size' => 'L']);

    $this->cart->add($item1);
    $this->cart->add($item2);

    expect($this->cart->count())
        ->toEqual(2)
        ->and($this->cart->content())
        ->toHaveCount(2);
});

test('adding same item with same options increments quantity', function () {
    $item1 = new CartItem(id: 'product-1', name: 'T-Shirt', price: 25, quantity: 2, options: ['size' => 'M']);
    $item2 = new CartItem(id: 'product-1', name: 'T-Shirt', price: 25, quantity: 3, options: ['size' => 'M']);

    $this->cart->add($item1);
    $this->cart->add($item2);

    expect($this->cart->count())
        ->toEqual(5)
        ->and($this->cart->content())
        ->toHaveCount(1);
});

test('it updates the item quantity', function () {
    $this->cart->add($this->item);

    $this->cart->update($this->item, 2);

    expect($this->cart->count())
        ->toEqual(2)
        ->and($this->cart->get($this->item)?->getQuantity())
        ->toEqual(2);
});

test('it dispatch an event when update an item quantity', function () {
    Event::fake();

    $this->cart->add($this->item);

    $this->cart->update($this->item, 2);

    $hash = $this->item->getHash();

    Event::assertDispatched(ItemQuantityUpdated::class, function ($event) use($hash) {
        return $event->instance === 'default_cart' && $event->item->getHash() === $hash;
    });
});

test('it updates an item by hash string', function () {
    $this->cart->add($this->item);
    $hash = $this->item->getHash();

    $this->cart->update($hash, 3);

    expect($this->cart->get($hash)?->getQuantity())
        ->toEqual(3);
});

test('it handles updating non-existent item gracefully', function () {
    $nonExistentItem = new CartItem('non-existent', 'Non-existent', 50, 1);

    $this->cart->update($nonExistentItem, 5);

    expect($this->cart->has($nonExistentItem))->toBeFalse();
});


test('it remove the item if the quantity is less than 1', function () {
    $this->cart->add($this->item);

    expect($this->cart->empty())->toBeFalse();

    $this->cart->update($this->item, 0);

    expect($this->cart->empty())->toBeTrue();
});

test('it removes an item from the cart', function () {
    $this->cart->add($this->item);

    expect($this->cart->has($this->item))
        ->toBeTrue()
        ->and($this->cart->empty())
        ->toBeFalse();

    $this->cart->remove($this->item);

    expect($this->cart->has($this->item))
        ->toBeFalse()
        ->and($this->cart->empty())
        ->toBeTrue();
});

test('it dispatch an event when removing an item', function () {
    Event::fake();

    $this->cart->add($this->item);

    $this->cart->remove($this->item);

    Event::assertDispatched(ItemRemoved::class, function ($event) {
        return $event->instance === 'default_cart';
    });
});

test('it removes an item by hash string', function () {
    $this->cart->add($this->item);
    $hash = $this->item->getHash();

    $this->cart->remove($hash);

    expect($this->cart->has($hash))->toBeFalse();
});

test('it returns the content of the cart', function () {
    $this->cart->add($this->item);

    $content = $this->cart->content();

    expect($content)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toHaveCount(1);

});

test('it clears the cart', function () {
    $this->cart->add($this->item);

    expect($this->cart->content())
        ->toHaveCount(1);

    $this->cart->clear();

    expect($this->cart->content())
        ->toHaveCount(0);
});

test('it dispatch an event when clearing the cart', function () {
    Event::fake();

    $this->cart->add($this->item);

    $this->cart->clear();

    Event::assertDispatched(CartCleared::class, function ($event) {
        return $event->instance === 'default_cart';
    });
});

test('clearing the cart also clear the modifiers', function () {
    $this->cart->modifiers()->add(new CartModifier('test', 'Test', 10, CartModifier::PERCENT));

    expect($this->cart->modifiers()->content())
        ->toHaveCount(1);

    $this->cart->clear();

    expect($this->cart->modifiers()->content())
        ->toHaveCount(0);
});

test('it returns the subtotal of the cart', function () {
    $this->cart->add($this->item);

    expect($this->cart->subtotal())
        ->toEqual(100);

    $this->cart->update($this->item, 2);

    expect($this->cart->subtotal())
        ->toEqual(200);
});

test('it returns the total of the cart', function () {
    $this->cart->add($this->item);
    $this->cart->modifiers()->add(new \Ashraam\LaravelSimpleCart\CartModifier(id: 'ship', name: 'Shipping', value: 10, description: 'Free shipping'));

    expect($this->cart->total())
        ->toEqual(110);
});

test('it returns 0 if the total of modifiers is greater than the subtotal', function () {
    $this->cart->add($this->item);
    $this->cart->modifiers()->add(new \Ashraam\LaravelSimpleCart\CartModifier(id: 'test', name: 'Test', value: -1000));

    expect($this->cart->total())
        ->toEqual(0);
});

test('it checks if the cart is empty', function () {
    expect($this->cart->empty())->toBeTrue();

    $this->cart->add($this->item);

    expect($this->cart->empty())->toBeFalse();
});

test('it returns the total quantity of items in the cart', function () {
    expect($this->cart->count())->toEqual(0);

    $this->item->setQuantity(4);
    $this->cart->add($this->item);

    expect($this->cart->count())->toEqual(4);
});

test('it returns the instance name', function () {
    expect($this->cart->instance('default')->getInstance())->toBe('default');
});

test('it returns the session manager', function () {
    expect($this->cart->getSession())
        ->toBeInstanceOf(\Illuminate\Session\SessionManager::class);
});

test('it returns the modifiers', function () {
    expect($this->cart->modifiers())
        ->toBeInstanceOf(\Ashraam\LaravelSimpleCart\Modifiers::class);
});

test('multiple percent modifiers are calculated correctly', function () {
    $item = new CartItem('product-1', 'Product', 100, 1);
    $this->cart->add($item);

    $discount1 = new CartModifier('discount1', '10% Discount', -10, CartModifier::PERCENT);
    $discount2 = new CartModifier('discount2', '5% Additional', -5, CartModifier::PERCENT);
    $tax = new CartModifier('tax', 'VAT', 20, CartModifier::PERCENT);

    $this->cart->modifiers()->add($discount1);
    $this->cart->modifiers()->add($discount2);
    $this->cart->modifiers()->add($tax);

    // Subtotal: 100
    // discount1: -10 (10% of 100)
    // discount2: -5 (5% of 100)
    // tax: +20 (20% of 100)
    // Total modifier: -10 + -5 + 20 = 5
    // Final total: 100 + 5 = 105

    expect($this->cart->modifiers()->total())
        ->toEqual(5)
        ->and($this->cart->total())
        ->toEqual(105);
});

test('mixed value and percent modifiers work together', function () {
    $item = new CartItem('product-1', 'Product', 100, 1);
    $this->cart->add($item);

    $shipping = new CartModifier('shipping', 'Shipping', 15, CartModifier::VALUE);
    $discount = new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT);
    $handling = new CartModifier('handling', 'Handling Fee', 5, CartModifier::VALUE);

    $this->cart->modifiers()->add($shipping);
    $this->cart->modifiers()->add($discount);
    $this->cart->modifiers()->add($handling);

    // Subtotal: 100
    // shipping: +15
    // discount: -10 (10% of 100)
    // handling: +5
    // Total modifier: 15 + (-10) + 5 = 10
    // Final total: 100 + 10 = 110

    expect($this->cart->modifiers()->total())
        ->toEqual(10)
        ->and($this->cart->total())
        ->toEqual(110);
});

test('cart handles multiple items with complex modifiers', function () {
    $item1 = new CartItem(id: 'product-1', name: 'Product 1', price: 50, quantity: 2);
    $item2 = new CartItem(id: 'product-2', name: 'Product 2', price: 30, quantity: 1);
    $item3 = new CartItem(id: 'product-3', name: 'Product 3', price: 75, quantity: 1, options: ['color' => 'red']);

    $this->cart->add($item1);
    $this->cart->add($item2);
    $this->cart->add($item3);

    // Subtotal: (50*2) + 30 + 75 = 205
    expect($this->cart->subtotal())->toEqual(205);

    $shipping = new CartModifier('shipping', 'Shipping', 20, CartModifier::VALUE);
    $discount = new CartModifier('discount', '15% Discount', -15, CartModifier::PERCENT);

    $this->cart->modifiers()->add($shipping);
    $this->cart->modifiers()->add($discount);

    // discount: -30.75 (15% of 205)
    // shipping: +20
    // Total modifier: 20 + (-30.75) = -10.75
    // Final total: 205 + (-10.75) = 194.25

    expect($this->cart->modifiers()->total())
        ->toEqual(-10.75)
        ->and($this->cart->total())
        ->toEqual(194.25);
});

test('updating item quantity affects modifier calculations', function () {
    $item = new CartItem('product-1', 'Product', 100, 1);
    $this->cart->add($item);

    $discount = new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT);
    $this->cart->modifiers()->add($discount);

    expect($this->cart->total())->toEqual(90); // 100 - 10

    $this->cart->update($item, 2);

    expect($this->cart->subtotal())
        ->toEqual(200)
        ->and($this->cart->modifiers()->total())
        ->toEqual(-20) // 10% of 200
        ->and($this->cart->total())
        ->toEqual(180);
});

test('cart instance isolation works with modifiers', function () {
    $item = new CartItem('product-1', 'Product', 100, 1);
    $modifier = new CartModifier('shipping', 'Shipping', 10, CartModifier::VALUE);

    $this->cart->instance('cart1')->add($item);
    $this->cart->instance('cart1')->modifiers()->add($modifier);

    $this->cart->instance('cart2')->add($item);

    expect($this->cart->instance('cart1')->total())
        ->toEqual(110)
        ->and($this->cart->instance('cart2')->total())
        ->toEqual(100)
        ->and($this->cart->instance('cart1')->modifiers()->content())
        ->toHaveCount(1)
        ->and($this->cart->instance('cart2')->modifiers()->content())
        ->toHaveCount(0);
});

test('removing all items affects modifier calculations', function () {
    $item1 = new CartItem('product-1', 'Product 1', 50, 1);
    $item2 = new CartItem('product-2', 'Product 2', 30, 1);

    $this->cart->add($item1);
    $this->cart->add($item2);

    $discount = new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT);
    $this->cart->modifiers()->add($discount);

    expect($this->cart->total())->toEqual(72); // 80 - 8

    $this->cart->clear();

    expect($this->cart->subtotal())
        ->toEqual(0)
        ->and($this->cart->modifiers()->total())
        ->toEqual(0) // 10% of 0
        ->and($this->cart->total())
        ->toEqual(0);
});

test('it returns the sum of all items without vat', function () {
    \Illuminate\Support\Facades\Config::set('laravel-simple-cart.default_vat_rate', 20);

    $item1 = new CartItem('product-1', 'Product 1', 50, 1);
    $item2 = new CartItem('product-2', 'Product 2', 30, 1);

    $this->cart->add($item1);
    $this->cart->add($item2);

    $discount = new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT);
    $this->cart->modifiers()->add($discount);

    expect($this->cart->subtotalWithoutVat())->toEqual(80);
});

test('it returns the sum of all items with vat', function () {
    \Illuminate\Support\Facades\Config::set('laravel-simple-cart.default_vat_rate', 20);

    $item1 = new CartItem('product-1', 'Product 1', 50, 1); // 50 + 20% = 60
    $item2 = new CartItem('product-2', 'Product 2', 30, 1); // 36 + 20% = 36

    $this->cart->add($item1);
    $this->cart->add($item2);

    $discount = new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT);
    $this->cart->modifiers()->add($discount);

    expect($this->cart->subtotal())->toEqual(96);
});

test('it returns the total vat amount', function () {
    \Illuminate\Support\Facades\Config::set('laravel-simple-cart.default_vat_rate', 20);

    $item1 = new CartItem('product-1', 'Product 1', 50, 1); // 50 x 0.2 = 10
    $item2 = new CartItem('product-2', 'Product 2', 30, 1); // 36 x 0.2 = 6

    $this->cart->add($item1);
    $this->cart->add($item2);

    expect($this->cart->vat())->toEqual(16);
});

test('cart handles very large quantities', function () {
    $item = new CartItem('bulk-item', 'Bulk Item', 0.01, 999999);
    $this->cart->add($item);

    expect($this->cart->count())->toEqual(999999)
        ->and($this->cart->subtotal())->toEqual(9999.99);
});

test('cart handles very large prices', function () {
    $expensiveItem = new CartItem('luxury-item', 'Luxury Item', 999999.99, 1);
    $this->cart->add($expensiveItem);

    expect($this->cart->subtotal())->toEqual(999999.99);
});

test('cart modifiers do not affect VAT calculations', function () {
    $item = new CartItem('product-1', 'Product', 100, 1, vat_rate: 20);
    $this->cart->add($item);

    $cartDiscount = new CartModifier('cart-discount', 'Cart Discount', -10, CartModifier::VALUE);
    $this->cart->modifiers()->add($cartDiscount);

    // Item VAT calculated before cart modifiers
    expect($this->cart->vat())->toEqual(20)
        ->and($this->cart->subtotal())->toEqual(120)
        ->and($this->cart->total())->toEqual(110);
    // VAT on 100
    // 100 + 20 VAT
    // 120 - 10 cart discount
});

test('mixed cart instances maintain modifier isolation', function () {
    $item = new CartItem('product-1', 'Product', 100, 1);

    $cart1Discount = new CartModifier('discount1', 'Cart 1 Discount', -10, CartModifier::VALUE);
    $cart2Discount = new CartModifier('discount2', 'Cart 2 Discount', -20, CartModifier::VALUE);

    $this->cart->instance('cart1')->add($item);
    $this->cart->instance('cart1')->modifiers()->add($cart1Discount);

    $this->cart->instance('cart2')->add($item);
    $this->cart->instance('cart2')->modifiers()->add($cart2Discount);

    expect($this->cart->instance('cart1')->total())->toEqual(90)
        ->and($this->cart->instance('cart2')->total())->toEqual(80)
        ->and($this->cart->instance('cart1')->modifiers()->has($cart1Discount))->toBeTrue()
        ->and($this->cart->instance('cart1')->modifiers()->has($cart2Discount))->toBeFalse()
        ->and($this->cart->instance('cart2')->modifiers()->has($cart2Discount))->toBeTrue()
        ->and($this->cart->instance('cart2')->modifiers()->has($cart1Discount))->toBeFalse();

});

test('cart handles floating point precision correctly', function () {
    $item = new CartItem('product-1', 'Product', 10.99, 3);
    $this->cart->add($item);

    $discount = new CartModifier('discount', '10% Discount', -10, CartModifier::PERCENT);
    $this->cart->modifiers()->add($discount);

    // Should handle floating point arithmetic correctly
    expect($this->cart->subtotal())->toEqual(32.97)
        ->and($this->cart->modifiers()->total())->toEqual(-3.297)
        ->and($this->cart->total())->toEqual(29.673);
    // 10.99 * 3
    // 10% of 32.97
    // 32.97 - 3.297
});

test('cart handles extreme modifier combinations', function () {
    $item = new CartItem('product-1', 'Product', 100, 1);
    $this->cart->add($item);

    // Add multiple extreme modifiers
    $this->cart->modifiers()->add(new CartModifier('huge-discount', 'Huge Discount', -99, CartModifier::PERCENT));
    $this->cart->modifiers()->add(new CartModifier('small-fee', 'Small Fee', 0.01, CartModifier::VALUE));
    $this->cart->modifiers()->add(new CartModifier('another-discount', 'Another Discount', -50, CartModifier::VALUE));

    // Should not go below 0
    expect($this->cart->total())->toEqual(0);
});
