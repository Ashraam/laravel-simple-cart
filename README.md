# Laravel Simple Cart

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashraam/laravel-simple-cart.svg?style=flat-square)](https://packagist.org/packages/ashraam/laravel-simple-cart)
[![Total Downloads](https://img.shields.io/packagist/dt/ashraam/laravel-simple-cart.svg?style=flat-square)](https://packagist.org/packages/ashraam/laravel-simple-cart)

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

// Clear cart (including fees and discounts)
Cart::clear();

// Get cart contents
$items = Cart::content();

// Get subtotal (items only)
$subtotal = Cart::subtotal();

// Add fees
Cart::addFee('shipping', 10.00);
Cart::addFee('handling', 5.00);

// Check if fees exists
Cart::hasFee('shipping'); // true
Cart::hasFee('non-existant'); // false

// Remove fees
Cart::removeFee('shipping');

// Get all fees
$fees = Cart::getFees();

// Get a specific fee
$fee =  Cart::getFee('shipping');

// Get total fees
$totalFees = Cart::totalFees();

// Add discounts
Cart::addDiscount('SUMMER10', 10.00);
Cart::addDiscount('WELCOME5', 5.00);

// Check if discount exists
Cart::hasDiscount('SUMMER10'); // true
Cart::hasDiscount('non-existant'); // false

// Remove discounts
Cart::removeDiscount('SUMMER10');

// Get all discounts
$discounts = Cart::getDiscounts();

// Get a specific discount
$discount = Cart::getDiscount('SUMMER10');

// Get total discounts
$totalDiscounts = Cart::totalDiscounts();

// Get cart total (subtotal + fees - discounts)
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
    'quantity' => 2,         // Quantity in cart
    'options' => [           // Options (used with the product id to generate the unique item hash id)
        'size' => 'L'
    ],
    'meta' => [              // Additional meta data
        'image' => 'https://example.com/product-1.jpg',
        'category' => 'T-shirt'
    ]
]
```

### Price Calculations

The cart provides several methods to calculate prices:

1. `subtotal()`: Returns the sum of all items (price × quantity)
2. `totalFees()`: Returns the sum of all added fees
3. `totalDiscounts()`: Returns the sum of all added discounts
4. `total()`: Returns the final total calculated as: subtotal + fees - discounts

### Managing Fees and Discounts

Fees and discounts are stored separately from items and persist until explicitly removed or the cart is cleared.

```php
// Adding fees
Cart::addFee('shipping', 10.00, 'optional description');
Cart::addFee('handling', 5.00);

// Adding discounts
Cart::addDiscount('SUMMER10', 10.00, 'optional description');
Cart::addDiscount('WELCOME5', 5.00);

// Removing fees/discounts
Cart::removeFee('shipping');
Cart::removeDiscount('SUMMER10');

// Getting all fees/discounts
$fees = Cart::getFees(); // Returns Collection of fees
$discounts = Cart::getDiscounts(); // Returns Collection of discounts
```

Each fee and discount is stored with a name and amount. The name is used as a unique identifier when removing the fee or discount.

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
