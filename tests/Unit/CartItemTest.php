<?php

use Ashraam\LaravelSimpleCart\CartItem;

test('it requires an id', function () {
    new CartItem(
    //id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1
    );
})->throws(\ArgumentCountError::class);

test('it requires a name', function () {
    new CartItem(
        id: 'product-1',
        //name: 'Test Product',
        price: 100,
        quantity: 1
    );
})->throws(\ArgumentCountError::class);

test('it requires a price', function () {
    new CartItem(
        id: 'product-1',
        name: 'Test Product',
        //price: 100,
        quantity: 1
    );
})->throws(\ArgumentCountError::class);

test('the price cannot be less than 0', function () {
    new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: -0.01,
        quantity: 1
    );
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidPrice::class);

test('the quantity cannot be less than 1', function () {
    new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 0
    );
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity::class);

test('it returns the item hash', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    expect($item->getHash())->toBe(md5('product-1'.serialize(['size' => 'L'])));
});

test('it returns the item id', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    expect($item->getId())->toBe('product-1');
});

test('it updates the item id', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setId('product-2');

    expect($item->getId())->toBe('product-2');
});

test('it recalculates the item hash when the id is updated', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setId('product-2');

    expect($item->getHash())->toBe(md5('product-2'.serialize(['size' => 'L'])));
});

test('it returns the item name', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    expect($item->getName())->toBe('Test Product');
});

test('it updates the item name', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setName('Test Product 2');

    expect($item->getName())->toBe('Test Product 2');
});

test('it returns the base item price', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    expect($item->getPrice())->toEqual(100);
});

test('it updates the item price', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setPrice(50);

    expect($item->getPrice())->toEqual(50);
});

test('it throws if the price is less than 0', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setPrice(-10);
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidPrice::class);

test('it returns the item quantity', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    expect($item->getQuantity())->toEqual(1);
});

test('it increments the item quantity', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->incrementQuantity(2);

    expect($item->getQuantity())->toEqual(3);
});

test('it decrements the item quantity', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 5,
        options: ['size' => 'L'],
    );

    $item->decrementQuantity(2);

    expect($item->getQuantity())->toEqual(3);
});

test('it throws if the new quantity after decrement is less than 1', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 5,
        options: ['size' => 'L'],
    );

    $item->decrementQuantity(10);
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity::class);

test('it replaces the item quantity', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setQuantity(2);

    expect($item->getQuantity())->toEqual(2);
});

test('it throws if the quantity is less than 1', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setQuantity(0);
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity::class);

test('it returns the item options', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    expect($item->getOptions())
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('size', 'L');
});

test('it merges the item options', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setOptions([
        'color' => 'red',
        'size' => 'M'
    ]);

    expect($item->getOptions())
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKey('color', 'red')
        ->toHaveKey('size', 'M');
});

test('it overwrites the item options', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setOptions([
        'color' => 'red'
    ], true);

    expect($item->getOptions())
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('color', 'red');
});

test('it recalculates the item hash when the options are updated', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        options: ['size' => 'L'],
    );

    $item->setOptions([
        'color' => 'red',
        'size' => 'M'
    ]);

    expect($item->getHash())->toEqual(md5('product-1'.serialize(['size' => 'M', 'color' => 'red'])));
});

test('it returns the item meta', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        meta: ['category' => 'clothing']
    );

    expect($item->getMeta())
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('category', 'clothing');
});

test('it merges the item meta', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        meta: ['category' => 'clothing']
    );

    $item->setMeta([
        'image' => 'image-100',
        'category' => 'hats'
    ]);

    expect($item->getMeta())
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKey('image', 'image-100')
        ->toHaveKey('category', 'hats');
});

test('it overwrites the item meta', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        meta: ['category' => 'clothing'],
    );

    $item->setMeta([
        'image' => 'image-100'
    ], true);

    expect($item->getMeta())
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('image', 'image-100');
});

test('it sets the default vat rate on item', function () {
    \Illuminate\Support\Facades\Config::set('laravel-simple-cart.default_vat_rate', 20);

    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 3,
    );

    expect($item->getVatRate())->toEqual(20);
});

test('it overwrites the default vat rate', function () {
    \Illuminate\Support\Facades\Config::set('laravel-simple-cart.default_vat_rate', 20);

    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 3,
        vat_rate: 10
    );

    expect($item->getVatRate())->toEqual(10);
});

test('vat rate cannot be less than 0', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 3,
        vat_rate: -10
    );
})->throws(InvalidArgumentException::class);

test('vat rate cannot be greater than 100', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 3,
        vat_rate: 110
    );
})->throws(InvalidArgumentException::class);

test('it throws when id is empty string', function () {
    new CartItem(
        id: '',
        name: 'Test Product',
        price: 100,
        quantity: 1
    );
})->throws(InvalidArgumentException::class, 'Please provide an id for the item.');

test('it throws when name is empty string', function () {
    new CartItem(
        id: 'product-1',
        name: '',
        price: 100,
        quantity: 1
    );
})->throws(InvalidArgumentException::class, 'Please provide a name for the item.');

test('it throws when price is empty (zero)', function () {
    new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 0,
        quantity: 1
    );
})->throws(InvalidArgumentException::class, 'Please provide a price for the item.');

test('it throws when incrementing quantity with invalid value', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1
    );
    
    $item->incrementQuantity(0);
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity::class, 'Quantity cannot be less than 1.');

test('it throws when incrementing quantity with negative value', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1
    );
    
    $item->incrementQuantity(-1);
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity::class, 'Quantity cannot be less than 1.');

test('it throws when decrementing quantity with invalid value', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 5
    );
    
    $item->decrementQuantity(0);
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity::class, 'Quantity cannot be less than 1.');

test('it throws when decrementing quantity with negative value', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 5
    );
    
    $item->decrementQuantity(-1);
})->throws(\Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity::class, 'Quantity cannot be less than 1.');

test('it handles null vat rate correctly', function () {
    \Illuminate\Support\Facades\Config::set('laravel-simple-cart.default_vat_rate', null);
    
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        vat_rate: null
    );
    
    expect($item->getVatRate())->toBeNull();
});

test('it properly stores price in cents internally', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 99.99,
        quantity: 1
    );
    
    expect($item->getPrice())->toEqual(99.99);
});

test('it calculates correct vat with null vat rate', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        vat_rate: null
    );
    
    expect($item->vat())->toEqual(0)
        ->and($item->vatTotal())->toEqual(0);
});

test('it calculates unit price correctly with zero vat rate', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1,
        vat_rate: 0
    );
    
    expect($item->unitPriceWithoutVat())->toEqual(100)
        ->and($item->unitPrice())->toEqual(100)
        ->and($item->vat())->toEqual(0);
});



test('it handles large quantities correctly', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 10,
        quantity: 1000,
        vat_rate: 20
    );
    
    expect($item->totalWithoutVat())->toEqual(10000)
        ->and($item->total())->toEqual(12000)
        ->and($item->vatTotal())->toEqual(2000);
});

test('it calculates the vat for one item', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 120,
        quantity: 3,
        vat_rate: 10
    );

    expect($item->vat())->toEqual(12);
});

test('it calculates the vat for all items', function () {
    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 120,
        quantity: 3,
        vat_rate: 10
    );

    expect($item->vatTotal())->toEqual(36);
});

test('it returns 0 vat when default vat rate is null', function () {
    \Illuminate\Support\Facades\Config::set('laravel-simple-cart.default_vat_rate', null);

    $item = new CartItem(
        id: 'product-1',
        name: 'Test Product',
        price: 100,
        quantity: 1
    );

    expect($item->vat())->toEqual(0);
});
