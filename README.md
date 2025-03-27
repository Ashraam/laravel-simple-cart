# Laravel Simple Cart

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashraam/laravelsimplecart.svg?style=flat-square)](https://packagist.org/packages/ashraam/laravelsimplecart)
[![Total Downloads](https://img.shields.io/packagist/dt/ashraam/laravelsimplecart.svg?style=flat-square)](https://packagist.org/packages/ashraam/laravelsimplecart)

A simple session-based shopping cart implementation for Laravel 12. This package provides an easy way to add shopping cart functionality to your Laravel application without the need for a database.

## Installation

You can install the package via composer:

```bash
composer require ashraam/laravel-simple-cart
```

## Usage

The package provides a simple API to manage your shopping cart:

```php
use Ashraam\LaravelSimpleCart\Facades\Cart;

// Add an item to cart
Cart::add(
    id: 'product-1',
    name:'Product Name',
    price: 29.99,
    quantity:2,
    options: ['size' => 'L'],
    meta: ['image' => 'https://example.com/product-1.jpg', 'category' => 'T-shirt']
);

// Update quantity
Cart::update('item-id', 3);

// Remove item
Cart::remove('item-id');

// Clear cart
Cart::clear();

// Get cart contents
$items = Cart::content();

// Get cart total
$total = Cart::total();

// Get number of items in cart
$count = Cart::count();

// Check if item exists in cart
if (Cart::has('item-id')) {
    // Item exists
}

// Get a specific item from cart
$itemId = md5($productId . serialize($options)); // Generate item ID
$item = Cart::get($itemId); // Returns the item or null if not found
```

### Item Structure
When retrieving an item using `Cart::get()`, the returned array will have this structure:
```php
[
    'id' => 'product-1',      // Original product ID
    'name' => 'Product Name', // Product name
    'price' => 29.99,        // Product price
    'quantity' => 2,         // Quantity in cart©©
    'options' => [           // Options (used with the product id to generate the unique item hash id)
        'size' => 'L'
    ],
    'meta' => [              // Additional meta data
        'image' => 'https://example.com/product-1.jpg',
        'category' => 'T-shirt'
    ]
]
```

### Configuration

You can publish the configuration file with:

```bash
php artisan vendor:publish --provider="Ashraam\LaravelSimpleCart\LaravelSimpleCartServiceProvider"
```

This will create a `config/laravelsimplecart.php` file where you can modify the cart settings:

```php
return [
    'session_key' => 'laravel_simple_cart'
];
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email romain.bertolucci@gmail.com instead of using the issue tracker.

## Credits

- [Romain BERTOLUCCI](https://github.com/ashraam)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
