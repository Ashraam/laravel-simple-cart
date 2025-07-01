# Laravel Simple Cart - Real-World Examples

This document provides comprehensive real-world examples showing how to integrate Laravel Simple Cart into your applications.

## Table of Contents

- [E-commerce Checkout Workflow](#e-commerce-checkout-workflow)
- [Integration with Laravel Models](#integration-with-laravel-models)
- [Effective Meta Data Usage](#effective-meta-data-usage)
- [Testing Examples](#testing-examples)
- [API Reference with Examples](#api-reference-with-examples)

## E-commerce Checkout Workflow

### 1. Adding Products to Cart

```php
// app/Http/Controllers/ProductController.php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Ashraam\LaravelSimpleCart\Facades\Cart;
use Ashraam\LaravelSimpleCart\CartItem;
use Ashraam\LaravelSimpleCart\CartModifier;

class ProductController extends Controller
{
    public function addToCart(Request $request, Product $product)
    {
        $item = new CartItem(
            id: (string) $product->id,
            name: $product->name,
            price: $product->price,
            quantity: $request->quantity,
            vat_rate: $product->vat_rate ?? config('laravel-simple-cart.default_vat_rate'),
            options: [
                'size' => $request->size,
                'color' => $request->color,
            ],
            meta: [
                'image' => $product->featured_image,
                'sku' => $product->sku,
                'category' => $product->category->name,
                'slug' => $product->slug,
                'weight' => $product->weight,
            ]
        );

        // Apply product-specific discounts
        if ($product->hasActiveDiscount()) {
            $discount = new CartModifier(
                id: 'product_discount_' . $product->id,
                name: $product->discount_name,
                value: -$product->discount_percentage,
                type: CartModifier::PERCENT
            );
            $item->addModifier($discount);
        }

        Cart::add($item);

        return response()->json([
            'message' => 'Product added to cart',
            'cart_count' => Cart::count(),
            'cart_total' => number_format(Cart::total(), 2)
        ]);
    }

    public function updateCartItem(Request $request, Product $product)
    {
        $options = [
            'size' => $request->size,
            'color' => $request->color,
        ];

        // Create temporary item to get hash
        $tempItem = new CartItem(
            id: (string) $product->id,
            name: $product->name,
            price: $product->price,
            quantity: 1,
            options: $options
        );

        Cart::update($tempItem->getHash(), $request->quantity);

        return response()->json([
            'cart_count' => Cart::count(),
            'cart_total' => number_format(Cart::total(), 2)
        ]);
    }
}
```

### 2. Coupon Management

```php
// app/Http/Controllers/CouponController.php
<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Ashraam\LaravelSimpleCart\Facades\Cart;
use Ashraam\LaravelSimpleCart\CartModifier;

class CouponController extends Controller
{
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $coupon = Coupon::where('code', $request->code)
            ->where('expires_at', '>', now())
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid coupon code'], 422);
        }

        // Check if coupon already applied
        if (Cart::modifiers()->has('coupon_' . $coupon->code)) {
            return response()->json(['error' => 'Coupon already applied'], 422);
        }

        // Check minimum order amount
        if ($coupon->minimum_amount && Cart::subtotal() < $coupon->minimum_amount) {
            return response()->json([
                'error' => "Minimum order amount of $" . $coupon->minimum_amount . " required"
            ], 422);
        }

        // Check usage limits
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['error' => 'Coupon usage limit exceeded'], 422);
        }

        $modifier = new CartModifier(
            id: 'coupon_' . $coupon->code,
            name: $coupon->name,
            value: $coupon->type === 'percentage' ? -$coupon->value : -$coupon->value,
            type: $coupon->type === 'percentage' ? CartModifier::PERCENT : CartModifier::VALUE,
            description: $coupon->description
        );

        Cart::modifiers()->add($modifier);

        return response()->json([
            'message' => 'Coupon applied successfully',
            'discount' => abs($modifier->getValue()),
            'new_total' => number_format(Cart::total(), 2)
        ]);
    }

    public function removeCoupon(Request $request)
    {
        $couponId = 'coupon_' . $request->code;
        
        if (Cart::modifiers()->has($couponId)) {
            Cart::modifiers()->remove($couponId);
            
            return response()->json([
                'message' => 'Coupon removed successfully',
                'new_total' => number_format(Cart::total(), 2)
            ]);
        }

        return response()->json(['error' => 'Coupon not found'], 404);
    }
}
```

### 3. Shipping Calculation

```php
// app/Http/Controllers/ShippingController.php
<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Ashraam\LaravelSimpleCart\Facades\Cart;
use Ashraam\LaravelSimpleCart\CartModifier;

class ShippingController extends Controller
{
    public function calculateShipping(Request $request)
    {
        $request->validate([
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'postal_code' => 'required|string',
            'country' => 'required|string'
        ]);

        $shippingMethod = ShippingMethod::find($request->shipping_method_id);
        
        // Remove existing shipping
        Cart::modifiers()->remove('shipping');
        
        // Calculate shipping cost based on cart weight and destination
        $totalWeight = Cart::content()->sum(function($item) {
            return $item->getMeta()['weight'] ?? 0 * $item->getQuantity();
        });

        $shippingCost = $shippingMethod->calculateCost([
            'weight' => $totalWeight,
            'subtotal' => Cart::subtotal(),
            'postal_code' => $request->postal_code,
            'country' => $request->country
        ]);

        // Add new shipping
        if ($shippingCost > 0) {
            $shipping = new CartModifier(
                id: 'shipping',
                name: $shippingMethod->name,
                value: $shippingCost,
                type: CartModifier::VALUE,
                description: "Shipping to {$request->postal_code}, {$request->country}"
            );
            
            Cart::modifiers()->add($shipping);
        }

        return response()->json([
            'shipping_cost' => number_format($shippingCost, 2),
            'shipping_method' => $shippingMethod->name,
            'cart_total' => number_format(Cart::total(), 2),
            'estimated_delivery' => $shippingMethod->estimated_delivery_days . ' business days'
        ]);
    }

    public function getAvailableShippingMethods(Request $request)
    {
        $methods = ShippingMethod::where('is_active', true)
            ->where('country_code', $request->country)
            ->get()
            ->map(function($method) use ($request) {
                $cost = $method->calculateCost([
                    'subtotal' => Cart::subtotal(),
                    'postal_code' => $request->postal_code
                ]);

                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'cost' => number_format($cost, 2),
                    'delivery_days' => $method->estimated_delivery_days
                ];
            });

        return response()->json($methods);
    }
}
```

### 4. Checkout Process

```php
// app/Http/Controllers/CheckoutController.php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderModifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ashraam\LaravelSimpleCart\Facades\Cart;

class CheckoutController extends Controller
{
    public function show()
    {
        if (Cart::empty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty');
        }

        return view('checkout.show', [
            'cartItems' => Cart::content(),
            'cartSummary' => $this->getCartSummary(),
            'appliedModifiers' => Cart::modifiers()->content()
        ]);
    }

    public function processCheckout(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:credit_card,paypal,bank_transfer',
            'billing_address' => 'required|array',
            'shipping_address' => 'required|array',
            'terms_accepted' => 'required|accepted'
        ]);

        if (Cart::empty()) {
            return back()->with('error', 'Your cart is empty');
        }

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => $this->generateOrderNumber(),
                'subtotal_without_vat' => Cart::subtotalWithoutVat(),
                'vat_amount' => Cart::vat(),
                'subtotal' => Cart::subtotal(),
                'modifier_total' => Cart::modifiers()->total(),
                'total' => Cart::total(),
                'status' => 'pending',
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method
            ]);

            // Create order items
            foreach (Cart::content() as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->getId(),
                    'name' => $cartItem->getName(),
                    'price' => $cartItem->getPrice(),
                    'quantity' => $cartItem->getQuantity(),
                    'vat_rate' => $cartItem->getVatRate(),
                    'options' => $cartItem->getOptions(),
                    'meta' => $cartItem->getMeta(),
                    'unit_total' => $cartItem->unitPrice(),
                    'line_total' => $cartItem->total()
                ]);
            }

            // Save applied modifiers
            foreach (Cart::modifiers()->content() as $modifier) {
                OrderModifier::create([
                    'order_id' => $order->id,
                    'modifier_id' => $modifier->getId(),
                    'name' => $modifier->getName(),
                    'value' => $modifier->getValue(),
                    'type' => $modifier->getType(),
                    'description' => $modifier->getDescription()
                ]);
            }

            // Process payment (integrate with your payment provider)
            $paymentResult = $this->processPayment($order, $request->payment_method);

            if ($paymentResult['success']) {
                $order->update([
                    'status' => 'paid',
                    'payment_reference' => $paymentResult['reference']
                ]);

                // Clear cart after successful order
                Cart::clear();

                DB::commit();

                return redirect()->route('order.confirmation', $order)
                    ->with('success', 'Order placed successfully!');
            } else {
                throw new \Exception('Payment failed: ' . $paymentResult['error']);
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->with('error', 'Checkout failed: ' . $e->getMessage());
        }
    }

    private function getCartSummary(): array
    {
        return [
            'items_count' => Cart::count(),
            'subtotal_without_vat' => Cart::subtotalWithoutVat(),
            'vat_amount' => Cart::vat(),
            'subtotal' => Cart::subtotal(),
            'modifiers_total' => Cart::modifiers()->total(),
            'final_total' => Cart::total(),
            'currency' => config('app.currency', 'USD')
        ];
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Y') . '-' . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    private function processPayment(Order $order, string $method): array
    {
        // Implement your payment processing logic here
        // This is just a placeholder
        return [
            'success' => true,
            'reference' => 'PAY-' . uniqid()
        ];
    }
}
```

## Integration with Laravel Models

### Product Model Integration

```php
// app/Models/Product.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ashraam\LaravelSimpleCart\CartItem;
use Ashraam\LaravelSimpleCart\Facades\Cart;

class Product extends Model
{
    protected $fillable = [
        'name', 'price', 'sku', 'weight', 'vat_rate', 
        'featured_image', 'category_id', 'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function addToCart(int $quantity = 1, array $options = []): CartItem
    {
        return new CartItem(
            id: (string) $this->id,
            name: $this->name,
            price: $this->price,
            quantity: $quantity,
            vat_rate: $this->vat_rate ?? config('laravel-simple-cart.default_vat_rate'),
            options: $options,
            meta: [
                'image' => $this->featured_image_url,
                'sku' => $this->sku,
                'category' => $this->category->name,
                'weight' => $this->weight,
                'slug' => $this->slug,
                'brand' => $this->brand,
                'description' => $this->short_description,
            ]
        );
    }

    public function getCartItem(array $options = []): ?CartItem
    {
        $tempItem = $this->addToCart(1, $options);
        return Cart::get($tempItem->getHash());
    }

    public function isInCart(array $options = []): bool
    {
        $tempItem = $this->addToCart(1, $options);
        return Cart::has($tempItem->getHash());
    }

    public function getCartQuantity(array $options = []): int
    {
        $cartItem = $this->getCartItem($options);
        return $cartItem ? $cartItem->getQuantity() : 0;
    }

    public function hasActiveDiscount(): bool
    {
        return $this->discounts()->exists();
    }

    public function getDiscountPercentage(): float
    {
        $discount = $this->discounts()->first();
        return $discount ? $discount->percentage : 0;
    }

    public function getDiscountName(): string
    {
        $discount = $this->discounts()->first();
        return $discount ? $discount->name : '';
    }

    // Accessor for featured image URL
    public function getFeaturedImageUrlAttribute(): string
    {
        return $this->featured_image 
            ? asset('storage/' . $this->featured_image)
            : asset('images/placeholder-product.jpg');
    }
}
```

### Cart Controller with Model Integration

```php
// app/Http/Controllers/CartController.php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Ashraam\LaravelSimpleCart\Facades\Cart;

class CartController extends Controller
{
    public function index()
    {
        return view('cart.index', [
            'cartItems' => Cart::content(),
            'cartSummary' => $this->getCartSummary()
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
            'size' => 'nullable|string',
            'color' => 'nullable|string'
        ]);

        if (!$product->is_active) {
            return back()->with('error', 'This product is not available');
        }

        $options = array_filter([
            'size' => $request->size,
            'color' => $request->color,
        ]);

        $item = $product->addToCart($request->quantity, $options);
        
        // Apply product discounts
        if ($product->hasActiveDiscount()) {
            $discount = new \Ashraam\LaravelSimpleCart\CartModifier(
                id: 'product_discount_' . $product->id,
                name: $product->getDiscountName(),
                value: -$product->getDiscountPercentage(),
                type: \Ashraam\LaravelSimpleCart\CartModifier::PERCENT
            );
            $item->addModifier($discount);
        }

        Cart::add($item);

        return back()->with('success', 'Product added to cart successfully');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:10',
            'size' => 'nullable|string',
            'color' => 'nullable|string'
        ]);

        $options = array_filter([
            'size' => $request->size,
            'color' => $request->color,
        ]);

        $item = $product->addToCart(1, $options);
        
        if ($request->quantity == 0) {
            Cart::remove($item);
        } else {
            Cart::update($item, $request->quantity);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'cart_count' => Cart::count(),
                'cart_total' => number_format(Cart::total(), 2)
            ]);
        }

        return back()->with('success', 'Cart updated successfully');
    }

    public function remove(Product $product, Request $request)
    {
        $options = array_filter([
            'size' => $request->size,
            'color' => $request->color,
        ]);

        $item = $product->addToCart(1, $options);
        Cart::remove($item);

        return back()->with('success', 'Item removed from cart');
    }

    public function clear()
    {
        Cart::clear();
        return back()->with('success', 'Cart cleared successfully');
    }

    private function getCartSummary(): array
    {
        return [
            'items_count' => Cart::count(),
            'subtotal_without_vat' => Cart::subtotalWithoutVat(),
            'vat_amount' => Cart::vat(),
            'subtotal' => Cart::subtotal(),
            'modifiers_total' => Cart::modifiers()->total(),
            'final_total' => Cart::total(),
        ];
    }
}
```

## Effective Meta Data Usage

### Examples of Good Meta Data Structure

```php
// ✅ Good examples of meta data usage
$electronicsItem = new CartItem(
    id: 'laptop-001',
    name: 'Gaming Laptop',
    price: 1299.99,
    quantity: 1,
    meta: [
        // Display information
        'image_url' => 'https://cdn.example.com/laptop.jpg',
        'thumbnail_url' => 'https://cdn.example.com/laptop-thumb.jpg',
        'gallery' => [
            'https://cdn.example.com/laptop-1.jpg',
            'https://cdn.example.com/laptop-2.jpg'
        ],
        
        // Product identification
        'sku' => 'LAPTOP-GAMING-001',
        'barcode' => '1234567890123',
        'brand' => 'TechCorp',
        'model' => 'GamingPro X1',
        
        // Categorization
        'category' => 'Electronics',
        'subcategory' => 'Laptops',
        'tags' => ['gaming', 'high-performance', 'portable'],
        
        // Physical properties for shipping
        'weight' => 2.5, // in kg
        'dimensions' => [
            'length' => 35,
            'width' => 25,
            'height' => 2
        ],
        'fragile' => true,
        'requires_signature' => true,
        
        // URLs for navigation
        'product_url' => '/products/gaming-laptop-x1',
        'review_url' => '/products/gaming-laptop-x1/reviews',
        'manual_url' => '/manuals/laptop-gaming-001.pdf',
        
        // Additional business logic
        'warranty_months' => 24,
        'is_digital' => false,
        'tax_class' => 'standard',
        'availability_status' => 'in_stock',
        'stock_quantity' => 15,
        
        // Vendor information
        'vendor_id' => 'vendor_123',
        'vendor_name' => 'Tech Supplier Co.',
        'cost_price' => 900.00, // For margin calculations
    ]
);

$clothingItem = new CartItem(
    id: 'tshirt-002',
    name: 'Premium Cotton T-Shirt',
    price: 29.99,
    quantity: 2,
    options: [
        'size' => 'L',
        'color' => 'navy-blue'
    ],
    meta: [
        // Product display
        'image_url' => 'https://cdn.example.com/tshirt-navy.jpg',
        'color_swatch' => '#1a237e',
        
        // Product details
        'sku' => 'TSHIRT-COTTON-002-L-NAVY',
        'material' => '100% Organic Cotton',
        'care_instructions' => 'Machine wash cold, tumble dry low',
        'origin_country' => 'Portugal',
        
        // Size and fit
        'size_chart_url' => '/size-charts/tshirts',
        'fit_type' => 'regular',
        'measurements' => [
            'chest' => 52, // in cm
            'length' => 72
        ],
        
        // Sustainability
        'is_organic' => true,
        'is_recycled' => false,
        'carbon_footprint' => 'low',
        'certifications' => ['GOTS', 'Fair Trade'],
        
        // Marketing
        'seasonal_collection' => 'summer_2024',
        'featured_in' => ['new_arrivals', 'best_sellers'],
        'recommendation_tags' => ['casual', 'everyday', 'comfort']
    ]
);

// Access meta data in views and controllers
$productImage = $electronicsItem->getMeta()['image_url'];
$isFragile = $electronicsItem->getMeta()['fragile'] ?? false;
$careInstructions = $clothingItem->getMeta()['care_instructions'];
```

### Using Meta Data in Blade Templates

```blade
{{-- resources/views/cart/items.blade.php --}}
@foreach(Cart::content() as $item)
    @php
        $meta = $item->getMeta();
        $options = $item->getOptions();
    @endphp
    
    <div class="cart-item" data-item-id="{{ $item->getHash() }}">
        <div class="item-image">
            <img src="{{ $meta['image_url'] ?? '/images/placeholder.jpg' }}" 
                 alt="{{ $item->getName() }}"
                 class="w-20 h-20 object-cover rounded">
        </div>
        
        <div class="item-details">
            <h3 class="font-semibold">{{ $item->getName() }}</h3>
            
            @if(isset($meta['sku']))
                <p class="text-sm text-gray-600">SKU: {{ $meta['sku'] }}</p>
            @endif
            
            @if(isset($meta['brand']))
                <p class="text-sm">Brand: {{ $meta['brand'] }}</p>
            @endif
            
            {{-- Display options --}}
            @if(!empty($options))
                <div class="text-sm text-gray-600">
                    @foreach($options as $key => $value)
                        <span class="mr-2">{{ ucfirst($key) }}: {{ $value }}</span>
                    @endforeach
                </div>
            @endif
            
            {{-- Special indicators --}}
            @if(isset($meta['fragile']) && $meta['fragile'])
                <span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">
                    Handle with Care
                </span>
            @endif
            
            @if(isset($meta['is_organic']) && $meta['is_organic'])
                <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                    Organic
                </span>
            @endif
        </div>
        
        <div class="item-price">
            <div class="text-lg font-semibold">${{ number_format($item->total(), 2) }}</div>
            <div class="text-sm text-gray-600">{{ $item->getQuantity() }} × ${{ number_format($item->unitPrice(), 2) }}</div>
            
            @if($item->getVatRate())
                <div class="text-xs text-gray-500">
                    (incl. {{ $item->getVatRate() }}% VAT)
                </div>
            @endif
        </div>
        
        <div class="item-actions">
            {{-- Quantity controls --}}
            <div class="flex items-center space-x-2">
                <button onclick="updateQuantity('{{ $item->getHash() }}', {{ $item->getQuantity() - 1 }})"
                        class="btn btn-sm">-</button>
                <span>{{ $item->getQuantity() }}</span>
                <button onclick="updateQuantity('{{ $item->getHash() }}', {{ $item->getQuantity() + 1 }})"
                        class="btn btn-sm">+</button>
            </div>
            
            {{-- Remove button --}}
            <button onclick="removeItem('{{ $item->getHash() }}')" 
                    class="text-red-600 hover:text-red-800">
                Remove
            </button>
            
            {{-- Product link --}}
            @if(isset($meta['product_url']))
                <a href="{{ $meta['product_url'] }}" class="text-blue-600 hover:text-blue-800">
                    View Product
                </a>
            @endif
        </div>
    </div>
@endforeach
```

### JavaScript Integration

```javascript
// public/js/cart.js
class CartManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateCartDisplay();
    }

    bindEvents() {
        // Update quantity
        document.addEventListener('click', (e) => {
            if (e.target.matches('.quantity-btn')) {
                const action = e.target.dataset.action;
                const itemId = e.target.dataset.itemId;
                const currentQty = parseInt(e.target.dataset.currentQty);
                
                const newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;
                this.updateQuantity(itemId, newQty);
            }
        });

        // Remove item
        document.addEventListener('click', (e) => {
            if (e.target.matches('.remove-item-btn')) {
                const itemId = e.target.dataset.itemId;
                this.removeItem(itemId);
            }
        });
    }

    async updateQuantity(itemId, quantity) {
        try {
            const response = await fetch('/cart/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ item_id: itemId, quantity: quantity })
            });

            const data = await response.json();
            
            if (response.ok) {
                this.updateCartDisplay();
                this.showNotification('Cart updated successfully', 'success');
            } else {
                this.showNotification(data.error || 'Error updating cart', 'error');
            }
        } catch (error) {
            this.showNotification('Network error', 'error');
        }
    }

    async removeItem(itemId) {
        if (!confirm('Remove this item from your cart?')) return;

        try {
            const response = await fetch('/cart/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ item_id: itemId })
            });

            if (response.ok) {
                document.querySelector(`[data-item-id="${itemId}"]`).remove();
                this.updateCartDisplay();
                this.showNotification('Item removed from cart', 'success');
            }
        } catch (error) {
            this.showNotification('Network error', 'error');
        }
    }

    async updateCartDisplay() {
        try {
            const response = await fetch('/cart/summary');
            const data = await response.json();
            
            document.querySelector('.cart-count').textContent = data.items_count;
            document.querySelector('.cart-total').textContent = '$' + data.final_total;
            document.querySelector('.cart-subtotal').textContent = '$' + data.subtotal;
            
            if (data.vat_amount > 0) {
                document.querySelector('.cart-vat').textContent = '$' + data.vat_amount;
            }
        } catch (error) {
            console.error('Error updating cart display:', error);
        }
    }

    showNotification(message, type) {
        // Implement your notification system here
        console.log(`${type}: ${message}`);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CartManager();
});
```

This comprehensive examples file shows practical, real-world implementations that developers can directly use or adapt for their Laravel applications using your Simple Cart package.
