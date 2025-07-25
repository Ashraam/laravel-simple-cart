<?php

namespace Ashraam\LaravelSimpleCart\Events;

use Ashraam\LaravelSimpleCart\CartItem;
use Illuminate\Foundation\Events\Dispatchable;

class ItemAdded
{
    use Dispatchable;

    /**
     * @var string
     */
    public string $instance;

    /**
     * @var \Ashraam\LaravelSimpleCart\CartItem
     */
    public CartItem $item;

    /**
     * @param  string  $instance
     * @param  CartItem  $item
     */
    public function __construct(string $instance, CartItem $item)
    {
        $this->instance = $instance;
        $this->item = $item;
    }
}
