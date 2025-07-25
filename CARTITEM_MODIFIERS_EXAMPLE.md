# CartItem Modifiers Example

You can now add modifiers directly to individual cart items. This is useful for item-specific discounts, shipping costs, or other modifiers that apply to individual items rather than the entire cart.

## Basic Usage

```php
use Ashraam\LaravelSimpleCart\CartItem;
use Ashraam\LaravelSimpleCart\CartModifier;

// Create a cart item
$item = new CartItem(
    id: 'product-1',
    name: 'T-Shirt',
    price: 25,
    quantity: 2
);

// Create modifiers for this specific item
$expressShipping = new CartModifier(
    id: 'express',
    name: 'Express Shipping',
    value: 5.99,
    type: CartModifier::VALUE
);

$memberDiscount = new CartModifier(
    id: 'member_discount',
    name: 'Member Discount',
    value: -10,
    type: CartModifier::PERCENT
);

// Add modifiers to the item
$item->addModifier($expressShipping);
$item->addModifier($memberDiscount);

// Check if item has specific modifiers
if ($item->hasModifier('express')) {
    echo "Item has express shipping";
}

// Get all modifiers for the item
$modifiers = $item->getModifiers();
echo "Item has " . $modifiers->count() . " modifiers";

// Remove a modifier
$item->removeModifier('member_discount');

// Clear all modifiers
$item->clearModifiers();
```

## Working with Cart

```php
use Ashraam\LaravelSimpleCart\Facades\Cart;

// Add item with modifiers to cart
Cart::add($item);

// When you retrieve the item from cart, modifiers are preserved
$retrievedItem = Cart::get($item);
$itemModifiers = $retrievedItem->getModifiers();

// Item modifiers are automatically saved to session
```

## Available Methods

### Adding Modifiers
- `addModifier(CartModifier $modifier)` - Add a modifier to the item

### Checking Modifiers
- `hasModifier(CartModifier|string $modifier)` - Check if item has a specific modifier
- `getModifier(CartModifier|string $modifier)` - Get a specific modifier from the item
- `getModifiers()` - Get all modifiers as a Collection

### Removing Modifiers
- `removeModifier(CartModifier|string $modifier)` - Remove a specific modifier
- `clearModifiers()` - Remove all modifiers from the item

## Use Cases

1. **Item-specific shipping costs**: Add shipping modifiers to items that require special handling
2. **Product-specific discounts**: Apply discounts that only affect certain items
3. **Gift wrapping**: Add gift wrapping charges to specific items
4. **Express delivery**: Add express delivery fees to items that support it
5. **Volume discounts**: Apply quantity-based discounts to individual items

## Notes

- Modifiers are stored with the item in the session
- Adding a modifier with an existing ID will overwrite the previous modifier
- The cart's total calculation methods don't automatically include item modifiers - you can implement your own logic for that if needed
- Item modifiers are preserved when items are added to/retrieved from the cart
