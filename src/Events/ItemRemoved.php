<?php

namespace Ashraam\LaravelSimpleCart\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ItemRemoved
{
    use Dispatchable;

    /**
     * @var string
     */
    public string $instance;

    /**
     * @param  string  $instance
     */
    public function __construct(string $instance)
    {
        $this->instance = $instance;
    }
}
