<?php

namespace Ashraam\LaravelSimpleCart;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    protected Collection $items;

    public function __construct()
    {
        $this->items = collect(Session::get(config('laravelsimplecart.session_key'), []));
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
     * Clear the cart
     */
    public function clear(): void
    {
        $this->items = collect();
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
     * Get cart total
     */
    public function total(): float
    {
        return $this->items->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    /**
     * Get total number of items in cart
     */
    public function count(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if cart has a specific item
     */
    public function has(string $itemId): bool
    {
        return $this->items->has($itemId);
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
        Session::put(config('laravelsimplecart.session_key'), $this->items->toArray());
    }
}
