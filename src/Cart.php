<?php

namespace Ashraam\LaravelSimpleCart;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class Cart
{
    protected Collection $items;
    protected Collection $fees;
    protected Collection $discounts;
    protected string $sessionKey;

    public function __construct()
    {
        $this->sessionKey = Config::get('laravelsimplecart.session_key');
        $this->items = collect(Session::get($this->sessionKey . '.items', []));
        $this->fees = collect(Session::get($this->sessionKey . '.fees', []));
        $this->discounts = collect(Session::get($this->sessionKey . '.discounts', []));
    }

    /**
     * Add an item to the cart
     */
    public function add(string $id, string $name, float $price, int $quantity = 1, array $options = [], array $meta = []): void
    {
        $cartItem = [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'options' => $options,
            'meta' => $meta,
        ];

        $itemId = $this->generateItemId($id, $options);
        
        if ($this->items->has($itemId)) {
            $existingItem = $this->items->get($itemId);
            $cartItem['quantity'] += $existingItem['quantity'];
        }

        $this->items->put($itemId, $cartItem);
        $this->save();
    }

    /**
     * Update the quantity of an item in the cart
     */
    public function update(string $itemId, int $quantity): void
    {
        if ($this->items->has($itemId)) {
            if ($quantity <= 0) {
                $this->remove($itemId);
                return;
            }

            $item = $this->items->get($itemId);
            $item['quantity'] = $quantity;
            $this->items->put($itemId, $item);
            $this->save();
        }
    }

    /**
     * Remove an item from the cart
     */
    public function remove(string $itemId): void
    {
        $this->items->forget($itemId);
        $this->save();
    }

    /**
     * Get cart contents
     */
    public function content(): Collection
    {
        return $this->items;
    }

    /**
     * Get cart subtotal (sum of items without fees and discounts)
     */
    public function subtotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    /**
     * Get total fees
     */
    public function totalFees(): float
    {
        return $this->fees->sum('amount');
    }

    /**
     * Get total discounts
     */
    public function totalDiscounts(): float
    {
        return $this->discounts->sum('amount');
    }

    /**
     * Get cart total including fees and discounts
     */
    public function total(): float
    {
        return $this->subtotal() + $this->totalFees() - $this->totalDiscounts();
    }

    /**
     * Add a fee to the cart
     */
    public function addFee(string $name, float $amount, ?string $description = null): void
    {
        $this->fees->put(md5($name), [
            'name' => $name,
            'description' => $description,
            'amount' => $amount
        ]);
        $this->save();
    }

    /**
     * Remove a fee from the cart
     */
    public function removeFee(string $name): void
    {
        $this->fees->forget(md5($name));
        $this->save();
    }

    /**
     * Add a discount to the cart
     */
    public function addDiscount(string $name, float $amount, ?string $description = null): void
    {
        $this->discounts->put(md5($name), [
            'name' => $name,
            'description' => $description,
            'amount' => $amount
        ]);
        $this->save();
    }

    /**
     * Remove a discount from the cart
     */
    public function removeDiscount(string $name): void
    {
        $this->discounts->forget(md5($name));
        $this->save();
    }

    /**
     * Get all fees
     */
    public function getFees(): Collection
    {
        return $this->fees;
    }

    /**
     * Get all discounts
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    /**
     * Check if cart has a specific item
     */
    public function has(string $itemId): bool
    {
        return $this->items->has($itemId);
    }

    /**
     * Check if the fee exists
     */
    public function hasFee(string $name): bool
    {
        return $this->fees->has(md5($name));
    }

    /**
     * Check if the discount exists
     */
    public function hasDiscount(string $name): bool
    {
        return $this->discounts->has(md5($name));
    }

    /**
     * Get a specific item from the cart
     *
     * @param string $itemId The ID of the item to retrieve
     * @return array|null The item if found, null otherwise
     */
    public function get(string $itemId): ?array
    {
        return $this->items->get($itemId);
    }

    /**
     * Clear the cart
     */
    public function clear(): void
    {
        $this->items = collect();
        $this->fees = collect();
        $this->discounts = collect();
        $this->save();
    }

    /**
     * Get total number of items in cart
     */
    public function count(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Generate a unique ID for cart item
     */
    protected function generateItemId(string $id, array $options): string
    {
        return md5($id . serialize($options));
    }

    /**
     * Save cart to session
     */
    protected function save(): void
    {
        Session::put($this->sessionKey . '.items', $this->items->toArray());
        Session::put($this->sessionKey . '.fees', $this->fees->toArray());
        Session::put($this->sessionKey . '.discounts', $this->discounts->toArray());
    }
}
