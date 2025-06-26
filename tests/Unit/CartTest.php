<?php

use Ashraam\LaravelSimpleCart\CartItem;

beforeEach(function () {
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

test('it can retrieve an item from the cart', function () {
    $this->cart->add($this->item);

    $item = $this->cart->get($this->item);

    expect($item)
        ->toBeInstanceOf(CartItem::class)
        ->and($item)
        ->toBe($this->item);
});

test('it can check if an item exists in the cart', function () {
    expect($this->cart->has($this->item))->toBeFalse();

    $this->cart->add($this->item);

    expect($this->cart->has($this->item))->toBeTrue();
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

test('it can add an item to the cart', function () {
    $this->cart->add($this->item);

    expect($this->cart->count())
        ->toEqual(1)
        ->and($this->cart->has($this->item))
        ->toBeTrue();
});

test('it throws if you try to add an item other than an CartItem instance', function () {
    $this->cart->add([]);
})->throws(TypeError::class);

test('it updates the item quantity', function () {
    $this->cart->add($this->item);

    $this->cart->update($this->item, 2);

    expect($this->cart->count())
        ->toEqual(2)
        ->and($this->cart->get($this->item)->getQuantity())
        ->toEqual(2);
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

test('it returns the total of the cart', function () {
    $this->cart->add($this->item);

    expect($this->cart->total())
        ->toEqual(100);

    $this->cart->update($this->item, 2);

    expect($this->cart->total())
        ->toEqual(200);
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
