# Laravel Simple Cart

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashraam/laravel-simple-cart.svg?style=flat-square)](https://packagist.org/packages/ashraam/laravel-simple-cart)
[![Total Downloads](https://img.shields.io/packagist/dt/ashraam/laravel-simple-cart.svg?style=flat-square)](https://packagist.org/packages/ashraam/laravel-simple-cart)

Laravel Simple Cart is a lightweight, session-based shopping cart implementation designed specifically for Laravel 12 applications. It provides a simple yet comprehensive solution for adding e-commerce cart functionality without requiring database storage.

## Features

- **Session-based Storage**: No database required - uses Laravel sessions
- **Item Management**: Add, remove, and update cart items with ease
- **Product Variants**: Support for product options (size, color, etc.)
- **Meta Data Support**: Additional item information (images, categories, descriptions)
- **VAT Handling**: Built-in VAT calculation and management with configurable rates
- **Modifiers System**: Fees and discounts can be applied to items or cart
- **Multiple Cart Instances**: Support for different cart instances
- **Price Calculations**: Automatic subtotal, VAT, fees, discounts, and total
- **Exception Handling**: Custom exceptions for validation
- **Well Tested**: Comprehensive test suite with Pest PHP
- **Laravel 12 Compatible**: Built for the latest Laravel version

## Installation

You can install the package via composer:

```bash
composer require ashraam/laravel-simple-cart
```

### Service Provider Auto-Discovery

This package supports Laravel's auto-discovery feature. The service provider and facade will be automatically registered.

### Publishing Configuration

You can publish the configuration file with:

```bash
php artisan vendor:publish --provider="Ashraam\LaravelSimpleCart\LaravelSimpleCartServiceProvider"
```

This will create a `config/laravel-simple-cart.php` file in your application.

## Usage

The package provides a simple API to manage your shopping cart:

```php
use Ashraam\LaravelSimpleCart\Facades\Cart;
use Ashraam\LaravelSimpleCart\CartItem;
use Ashraam\LaravelSimpleCart\CartModifier;

// Creating an item
$item = new CartItem(
    id: 'product-1',
    name: 'Product Name',
    price: 30,
    quantity: 2,
    vat_rate: 20,
    options: [
        'size' => 'SM'
    ],
    meta: [
        'image' => 'products/my-image.jpg'
    ]
);

// Creating a modifier that will remove 10% to an item
$itemModifier = new CartModifier(
    id: 'summer10',
    name: 'Summer discount 10%',
    description: 'My discount description',
    value: -10,
    type: CartModifier::PERCENT
);

// Creating a modifier that will add 10 to the cart
$cartModifier = new CartModifier(
    id: 'shipping',
    name: 'Shipping Fees',
    value: 10,
    type: CartModifier::VALUE
);

// Applying a modifier to an item
$item->addModifier($itemModifier);

// Add an item to the cart
Cart::add($item);

// Applying a modifier to the cart
Cart::modifiers()->add($cartModifier);

// Get a collection of CartItem
$items = Cart::content();

// Get subtotal (items with item modifier and vat only)
$subtotal = Cart::subtotal(); // 64.8, (((30 - 10%) * 20%) * 2)

// Get the sum of all items in the cart without vat
$subtotalWithoutVat = Cart::subtotalWithoutVat(); // 54, (30 - 10%) * 2

// Get the sum of the vat of all items in the cart
$vat = Cart::vat(); // 10.8, ((30 - 10%) * 0.2) * 2

// Get the total of the cart (items with vat and modifiers + cart modifiers)
$total = Cart::total(); // 74.8
```

## Classes and methods

### Cart

You may have multiple instances of the cart by using the ``instance()`` method of the cart.
```php
// Get the instance name of the default cart, laravel-simple-cart.default_cart
Cart::getInstance();

// Get the instance name of the wishlist cart, laravel-simple-cart.wishlist
Cart::instance('wishlist')->getInstance();

// Get the instance name of the default cart, laravel-simple-cart.default_cart
Cart::instance()->getInstance();

// Add an item to the cart
Cart::add(new \Ashraam\LaravelSimpleCart\CartItem());

// Returns the item or null by its hash.
// You can use an instance of CartItem as a parameter.
// You can find how to generate the hash in the Cart Item chapter of this doc
Cart::get('13b1fc03bd99f6eac794faef45fbc057');

// Checks if the item is in the cart.
// You can use an instance of CartItem
Cart::has('13b1fc03bd99f6eac794faef45fbc057');

// Returns a collection of filtered items
// You have access to all the CartItem methods available
Cart::search(function($item) {
    $options = $item->getOptions();
    return (array_key_exists("size", $options) && $options["size"] === "sm") || $item->getPrice() >= 15;
});

// Replaces the quantity of the item by the specified value
Cart::update(item: '13b1fc03bd99f6eac794faef45fbc057', quantity: 3);

// Removes the item from the cart
Cart::remove('13b1fc03bd99f6eac794faef45fbc057');

// Returns a collection of all items in the cart
Cart::content();

// Clears the cart from its items and modifiers
Cart::clear();

// Returns the sum of all items without vat (item's modifiers will be included)
Cart::subtotalWithoutVat();

// Returns the sum of all items with vat
Cart::subtotal();

// Returns the sum of all item's vat
Cart::vat();

// Returns the sum of all items with vat and modifiers applied
Cart::total();

// Checks if the cart is empty or not, returns boolean
Cart::empty();

// Returns the total quantity of items in the cart
Cart::count();
```
You can apply modifiers to the cart using this api:

```php
$mod = new \Ashraam\LaravelSimpleCart\CartModifier(
    id: 'modifier-id',
    name: 'modifier name',
    value: 10,
    type: 'value', // values are 'value' or 'percent'
    description: 'modifier description'
);

// Adds a modifier to the cart
Cart::modifiers()->add($mod);

// Returns a collection of modifiers applied to the cart
Cart::modifiers()->content();

// Checks if the modifier is applied to the cart
Cart::modifiers()->has($mod);
Cart::modifiers()->has('modifier-id');

// Returns an instance of CartModifier or null
Cart::modifiers()->get($mod);
Cart::modifiers()->get('modifier-id');

// Removes a modifier from the cart
Cart::modifiers()->remove($mod);
Cart::modifiers()->remove('modifier-id');

// Clears all the modifiers from the cart
Cart::modifiers()->clear();

// Returns the sum of all modifiers applied to the cart
// For the one in percentage, the base price for calculation is Cart::subtotal()
Cart::modifiers()->total();
```


### Cart Item

An item has a unique hash that serves as ID.
This hash is composed of the item id and options.
If you update the id or the options, the hash will be regenerated, so it's recommended to remove the item from the cart first, update the item, and then add it back.
```php
$item = new \Ashraam\LaravelSimpleCart\CartItem(
    id: 'product-id',
    ...,
    options: ['size' => 'L']
)

$hash = md5($item->getId().serialize($item->getOptions()));
```

CartItem available methods
```php
$item = new \Ashraam\LaravelSimpleCart\CartItem(
    id: 'product-1',      // Original product ID
    name: 'Product Name', // Product name
    price: 30,        // Product price
    quantity: 2,         // Quantity
    vat_rate: 10        // VAT Rate (it overwrites the defautl vat rate if set in config)
    options: [           // Options (used with the product id to generate the unique item hash id)
        'size' => 'L'
    ],
    meta: [              // Additional meta data
        'image' => 'https://example.com/product-1.jpg',
        'category' => 'T-shirt'
    ]
);

$item->getHash(); // 13b1fc03bd99f6eac794faef45fbc057
$item->getId(); // 'product-1
$item->setId('new-product-id'); // Updates the product id and regenerate a new hash
$item->getName(); // Product Name
$item->setName('New product name'); // Update the product name
$item->getPrice(); // 30
$item->setPrice(50); // Updates the product base price
$item->getVatRate(); // returns the vat rate or null
$item->setVatRate(21); // Updates the VAT rate for this item
$item->getQuantity(); // returns the quantity
$item->setQuantity(3); // Replaces quantity with the value
$item->incrementQuantity(2); // Increments quantity (default value 1)
$item->decrementQuantity(2); // Decrements quantity (default value 1)
$item->getOptions(); // Returns the option array
$item->setOptions(['color' => 'red'], overwrite: false); // Merges or overwrites the option
$item->getMeta(); // Returns the meta array
$item->setMeta(['desc' => 'y desc'], overwrite: false); // Merges or overwrites the meta
$item->unitPriceWithoutVat(); // 30, base price with modifiers but without vat
$item->vat(); // 3
$item->unitPrice(); // 33, base price with modifiers and vat
$item->totalWithoutVat() // 60
$item->vatTotal() // 6
$item->total() // 66
$item->addModifier(new \Ashraam\LaravelSimpleCart\CartModifier()); // Adds a modifier to the item. You can use the modifier instance or modifier id
$item->removeModifier(new \Ashraam\LaravelSimpleCart\CartModifier()); // Removes a modifier to the item. You can use the modifier instance or modifier id
$item->hasModifier(new \Ashraam\LaravelSimpleCart\CartModifier()); // Checks if a modifier is applied to the item. You can use the modifier instance or modifier id
$item->getModifier(new \Ashraam\LaravelSimpleCart\CartModifier()); // Returns a CartModifier instance or null. You can use the modifier instance or modifier id
$item->getModifiers(); // Returns an array of all modifiers applied to the item
$item->clearModifiers(); // Remove all modifiers from the item
```

### Price calculations order

1. first we calculate each item's price with item's modifiers applied
2. Then we add for each item its vat (if any)
3. Then we apply all cart modifiers

## Configuration

After publishing the configuration file, you'll find it at `config/laravel-simple-cart.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Shopping Cart Session Key
    |--------------------------------------------------------------------------
    |
    | This value is the default key used to store the cart in the session.
    | When using multiple cart instances, this serves as the default instance.
    |
    */
    'default_session_key' => 'default_cart',

    /*
    |--------------------------------------------------------------------------
    | Default VAT Rate
    |--------------------------------------------------------------------------
    |
    | This value is the default VAT Rate to be applied on items when no
    | specific VAT rate is provided. Value should be a float between 0 and 100.
    | Set to null to disable default VAT calculations.
    |
    */
    'default_vat_rate' => null, // Example: 20 for 20% VAT
];
```

### Configuration Options

- **`default_session_key`**: The session key used for the default cart instance. When you call `Cart::add()` without specifying an instance, this key is used.
- **`default_vat_rate`**: Global VAT rate applied to all items unless overridden at the item level. Useful for single-country stores with consistent VAT rates.

### Session Management

The cart uses Laravel's session system to store cart data. Here's what you need to know:

#### Session Storage Structure
```php
// Session storage format
'laravel-simple-cart.default_cart' => [
    'items' => Collection, // CartItem instances
    'modifiers' => Collection, // CartModifier instances
]

'laravel-simple-cart.wishlist' => [
    'items' => Collection,
    'modifiers' => Collection,
]
```

#### Session Persistence
- Cart data persists as long as the user's session is active
- Session lifetime follows Laravel's `config/session.php` settings
- Cart is automatically cleared when session expires
- Multiple browser tabs share the same cart data

#### Session Access
```php
// Get the current session manager
$sessionManager = Cart::getSession();

// Manually clear session data (not recommended)
$sessionManager->forget('laravel-simple-cart.default_cart');
```

### Performance Considerations

#### Session Storage Limitations
- **Size Limits**: Keep cart reasonable in size (recommended: <100 items)
- **Serialization**: Complex objects in meta data may impact performance
- **Memory Usage**: Large carts consume more session storage

#### Best Practices
```php
// ✅ Good: Lightweight meta data
$item = new CartItem(
    id: 'product-1',
    name: 'T-Shirt',
    price: 29.99,
    quantity: 1,
    meta: [
        'image_url' => 'https://example.com/image.jpg',
        'sku' => 'TSHIRT-001'
    ]
);

// ❌ Avoid: Heavy objects in meta
$item = new CartItem(
    id: 'product-1',
    name: 'T-Shirt',
    price: 29.99,
    quantity: 1,
    meta: [
        'full_product_model' => $heavyEloquentModel, // Avoid this
        'large_image_data' => base64_encode($imageData) // Avoid this
    ]
);
```

#### When to Use Multiple Cart Instances
- **User Types**: Separate carts for guests vs authenticated users
- **Cart Purposes**: Main cart, wishlist, comparison list
- **Multi-tenant**: Different carts per tenant/organization
- **A/B Testing**: Different cart behaviors for user segments

```php
// Example: User-specific carts
Cart::instance('user_' . auth()->id())->add($item);
Cart::instance('guest_' . session()->getId())->add($item);

// Example: Purpose-specific carts
Cart::instance('main_cart')->add($item);
Cart::instance('wishlist')->add($item);
Cart::instance('compare')->add($item);
```

## Real-World Examples

For comprehensive real-world examples including e-commerce checkout workflows, Laravel model integration, and practical implementations, see [EXAMPLES.md](EXAMPLES.md).

## API Reference

### Complete Method Signatures

```php
// Cart Class Methods
Cart::instance(?string $instance = null): Cart
Cart::getInstance(): string
Cart::getSession(): SessionManager
Cart::add(CartItem $item): void
Cart::get(CartItem|string $item): ?CartItem
Cart::has(CartItem|string $item): bool
Cart::search(callable $callback): Collection
Cart::update(CartItem|string $item, int $quantity): void
Cart::remove(CartItem|string $item): void
Cart::content(): Collection
Cart::clear(): void
Cart::subtotalWithoutVat(): float
Cart::subtotal(): float
Cart::vat(): float
Cart::total(): float
Cart::empty(): bool
Cart::count(): int
Cart::modifiers(): Modifiers

// CartItem Class Methods
new CartItem(string $id, string $name, float $price, int $quantity = 1, ?float $vat_rate = null, array $options = [], array $meta = [])
$item->getHash(): string
$item->getId(): string
$item->setId(string $id): void
$item->getName(): string
$item->setName(string $name): void
$item->getPrice(): float
$item->setPrice(float $price): void
$item->getVatRate(): ?float
$item->setVatRate(?float $vat_rate): void
$item->getQuantity(): int
$item->setQuantity(int $quantity): void
$item->incrementQuantity(int $quantity = 1): void
$item->decrementQuantity(int $quantity = 1): void
$item->getOptions(): array
$item->setOptions(array $options, bool $overwrite = false): void
$item->getMeta(): array
$item->setMeta(array $meta, bool $overwrite = false): void
$item->unitPriceWithoutVat(): float
$item->vat(): float
$item->unitPrice(): float
$item->totalWithoutVat(): float
$item->vatTotal(): float
$item->total(): float
$item->addModifier(CartModifier $modifier): void
$item->removeModifier(CartModifier|string $modifier): void
$item->hasModifier(CartModifier|string $modifier): bool
$item->getModifier(CartModifier|string $modifier): ?CartModifier
$item->getModifiers(): Collection
$item->clearModifiers(): void

// CartModifier Class Methods
new CartModifier(string $id, string $name, float $value, string $type = CartModifier::VALUE, ?string $description = null)
$modifier->getId(): string
$modifier->getName(): string
$modifier->setName(string $name): void
$modifier->getValue(): float
$modifier->getRawValue(): int
$modifier->setValue(float $value): void
$modifier->getType(): string
$modifier->setType(string $type): void
$modifier->getDescription(): ?string
$modifier->setDescription(string $description): void

// Modifiers Class Methods
$modifiers->content(): Collection
$modifiers->get(CartModifier|string $modifier): ?CartModifier
$modifiers->has(CartModifier|string $modifier): bool
$modifiers->add(CartModifier $modifier): void
$modifiers->remove(CartModifier|string $modifier): void
$modifiers->clear(): void
$modifiers->total(): float
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
