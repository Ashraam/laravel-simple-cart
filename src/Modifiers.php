<?php

namespace Ashraam\LaravelSimpleCart;

use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

class Modifiers
{
    private Cart $cart;
    private string $instance;
    private SessionManager $session;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
        $this->instance = $cart->getInstance();
        $this->session = $cart->getSession();
    }

    /**
     * Retrieves the modifiers associated with the current instance of the cart.
     *
     * @return Collection
     */
    public function content(): Collection
    {
        return collect($this->session->get("{$this->instance}.modifiers", []));
    }

    /**
     * It returns the modifier by its ID.
     * It returns null if not found
     *
     * @param  CartModifier|string  $modifier
     * @return CartModifier|null
     */
    public function get(CartModifier|string $modifier): ?CartModifier
    {
        if($modifier instanceof CartModifier) {
            $id = $modifier->getId();
        } else {
            $id = $modifier;
        }

        return $this->content()->get($id);
    }

    /**
     * It checks if the modifier exists
     *
     * @param  CartModifier|string  $modifier
     * @return bool
     */
    public function has(CartModifier|string $modifier): bool
    {
        if($modifier instanceof CartModifier) {
            $id = $modifier->getId();
        } else {
            $id = $modifier;
        }

        return $this->content()->has($id);
    }

    /**
     * It adds the modifier to the cart
     *
     * @param  CartModifier  $modifier
     * @return void
     */
    public function add(CartModifier $modifier): void
    {
        $content = $this->content()->put($modifier->getId(), $modifier);

        $this->session->put("{$this->instance}.modifiers", $content);
    }

    /**
     * Removes the specified modifier by its ID.
     *
     * @param  CartModifier|string  $modifier
     * @return void
     */
    public function remove(CartModifier|string $modifier): void
    {
        if($modifier instanceof CartModifier) {
            $id = $modifier->getId();
        } else {
            $id = $modifier;
        }

        $content = $this->content()->forget($id);

        $this->session->put("{$this->instance}.modifiers", $content);
    }

    /**
     * It clears the modifiers from the cart
     *
     * @return void
     */
    public function clear(): void
    {
        $this->session->forget("{$this->instance}.modifiers");
    }

    /**
     * It returns the total value of all modifiers associated to the cart
     * Modifiers of type percent will be calculated base on the cart subtotal
     *
     * @return float
     */
    public function total(): float
    {
        $subtotal = $this->cart->subtotal();

        return $this->content()->sum(function($modifier) use ($subtotal) {
            if($modifier->getType() === CartModifier::PERCENT) {
                return $subtotal * ($modifier->getValue() / 100);
            }

            return $modifier->getValue();
        });
    }
}
