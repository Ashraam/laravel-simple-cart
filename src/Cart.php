<?php

namespace Ashraam\LaravelSimpleCart;

use Ashraam\LaravelSimpleCart\Events\CartCleared;
use Ashraam\LaravelSimpleCart\Events\ItemQuantityUpdated;
use Ashraam\LaravelSimpleCart\Events\ItemRemoved;
use Ashraam\LaravelSimpleCart\Events\ItemReplaced;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;
use Ashraam\LaravelSimpleCart\Events\ItemAdded;
use Illuminate\Support\Facades\Config;

class Cart
{
    private string $instance;

    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->instance = "laravel-simple-cart.".Config::get('laravel-simple-cart.default_session_key');
        $this->session = $session;
    }

    /**
     * Set the cart instance. If not provided, the default cart session key will be used (see config file)
     *
     * @param  string|null  $instance
     * @return $this
     */
    public function instance(?string $instance = null): self
    {
        $this->instance = "laravel-simple-cart.".($instance ?? Config::get('laravel-simple-cart.default_session_key'));
        return $this;
    }

    /**
     * Get the current instance name
     *
     * @return string
     */
    public function getInstance(): string
    {
        return str_replace("laravel-simple-cart.", "", $this->instance);
    }


    /**
     * Get the current Session Manager
     *
     * @return SessionManager
     */
    public function getSession(): SessionManager
    {
        return $this->session;
    }

    /**
     * Find an item in the cart by product ID and options
     * This provides a developer-friendly alternative to using hashes
     *
     * @param string $id The product ID
     * @param array $options The product options (size, color, etc.)
     * @return CartItem|null
     */
    public function find(string $id, array $options = []): ?CartItem
    {
        return $this->content()->first(function($item) use ($id, $options) {
            return $item->getId() === $id && $item->getOptions() === $options;
        });
    }


    /**
     * Get a specific item from the cart
     *
     * @param CartItem|string $item The item instance or the ID of the item to retrieve
     * @return CartItem|null The item if found, null otherwise
     */
    public function get(CartItem|string $item): ?CartItem
    {
        if($item instanceof CartItem) {
            $itemId = $item->getHash();
        } else {
            $itemId = $item;
        }

        return $this->content()->get($itemId);
    }

    /**
     * Check if the cart contains a specific item
     *
     * @param  CartItem|string  $item
     * @return bool
     */
    public function has(CartItem|string $item): bool
    {
        if($item instanceof CartItem) {
            $itemId = $item->getHash();
        } else {
            $itemId = $item;
        }

        return $this->content()->has($itemId);
    }

    /**
     * Search for specific items in the cart according to your need
     *
     * @param  callable  $callback
     * @return Collection
     */
    public function search(callable $callback): Collection
    {
        return $this->content()->filter($callback);
    }

    /**
     * Add the item to the cart. If the item exists already, the item's quantity will be incremented
     *
     * @param  CartItem  $item
     * @return void
     */
    public function add(CartItem $item): void
    {
        if($this->has($item)) {
            $existingCartItem = $this->get($item->getHash());
            $item->incrementQuantity($existingCartItem->getQuantity());
        }

        $content = $this->content();

        $content->put($item->getHash(), $item);

        $this->session->put("{$this->instance}.items", $content);

        event(new ItemAdded($this->getInstance(), $item));
    }

    /**
     * Replace an existing item in the cart or add it if it doesn't exist
     *
     * @param  CartItem  $item
     * @return void
     */
    public function replace(CartItem $item): void
    {
        $isExistingItem = $this->has($item);
        
        $content = $this->content();
        $content->put($item->getHash(), $item);
        $this->session->put("{$this->instance}.items", $content);

        if ($isExistingItem) {
            event(new ItemReplaced($this->getInstance(), $item));
        } else {
            event(new ItemAdded($this->getInstance(), $item));
        }
    }

    /**
     * Update the item's quantity, if the quantity is less than 0, the item will be removed from the cart
     *
     * @param  CartItem|string  $item
     * @param  int  $quantity
     * @return void
     */
    public function update(CartItem|string $item, int $quantity): void
    {
        if($item instanceof CartItem) {
            $itemId = $item->getHash();
        } else {
            $itemId = $item;
        }

        if ($this->has($itemId)) {
            if ($quantity <= 0) {
                $this->remove($itemId);
                return;
            }

            $item = $this->get($itemId);
            $item->setQuantity($quantity);

            $content = $this->content()->put($item->getHash(), $item);
            $this->session->put("{$this->instance}.items", $content);

            event(new ItemQuantityUpdated($this->getInstance(), $this->get($itemId)));
        }
    }

    /**
     * Remove the item from the cart
     *
     * @param  CartItem|string  $item
     * @return void
     */
    public function remove(CartItem|string $item): void
    {
        if($item instanceof CartItem) {
            $itemId = $item->getHash();
        } else {
            $itemId = $item;
        }

        $this->session->put("{$this->instance}.items", $this->content()->forget($itemId));

        event(new ItemRemoved($this->getInstance()));
    }

    /**
     * Returns a collection of all items in the cart
     *
     * @return Collection
     */
    public function content(): Collection
    {
        return collect($this->session->get("{$this->instance}.items", []));
    }

    /**
     * Clear the cart
     *
     * @return void
     */
    public function clear(): void
    {
        $this->modifiers()->clear();
        $this->session->forget("{$this->instance}.items");

        event(new CartCleared($this->getInstance()));
    }

    /**
     * It returns the sum of all items without vat
     *
     * @return float
     */
    public function subtotalWithoutVat(): float
    {
        return $this->content()->sum(fn($item) => $item->totalWithoutVat());
    }

    /**
     * It returns the total price of the cart items
     *
     * @return float
     */
    public function subtotal(): float
    {
        return $this->content()->sum(fn($item) => $item->total());
    }

    public function vat(): float
    {
        return $this->content()->sum(fn($item) => $item->vatTotal());
    }

    /**
     * Returns the total price of the cart (subtotal plus total of modifiers)
     * If the sum of all modifiers applied to the cart is superior to the subtotal, returns 0
     *
     * @return float
     */
    public function total(): float
    {
        $totalItems = $this->subtotal();
        $totalModifiers = $this->modifiers()->total();
        $total = $totalItems + $totalModifiers;

        return $total > 0 ? $total : 0;
    }

    /**
     * It checks if the cart is Empty or not
     *
     * @return bool
     */
    public function empty(): bool
    {
        return $this->content()->isEmpty();
    }

    /**
     * Get the total quantity of items in the cart
     *
     * @return int
     */
    public function count(): int
    {
        return $this->content()->sum(fn($item) => $item->getQuantity());
    }

    /**
     * Retrieve the modifiers associated with the current cart instance
     *
     * @return Modifiers
     */
    public function modifiers(): Modifiers
    {
        return new Modifiers($this);
    }
}
