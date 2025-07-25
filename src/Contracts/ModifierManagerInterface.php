<?php

namespace Ashraam\LaravelSimpleCart\Contracts;

use Ashraam\LaravelSimpleCart\CartModifier;
use Illuminate\Support\Collection;

interface ModifierManagerInterface
{
    /**
     * Add a modifier
     *
     * @param CartModifier $modifier
     * @return void
     */
    public function add(CartModifier $modifier): void;

    /**
     * Remove a modifier by instance or ID
     *
     * @param CartModifier|string $modifier
     * @return void
     */
    public function remove(CartModifier|string $modifier): void;

    /**
     * Check if a modifier exists
     *
     * @param CartModifier|string $modifier
     * @return bool
     */
    public function has(CartModifier|string $modifier): bool;

    /**
     * Get a modifier by instance or ID
     *
     * @param CartModifier|string $modifier
     * @return CartModifier|null
     */
    public function get(CartModifier|string $modifier): ?CartModifier;

    /**
     * Get all modifiers
     *
     * @return Collection
     */
    public function content(): Collection;

    /**
     * Clear all modifiers
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Get the total value of all modifiers
     *
     * @return float
     */
    public function total(): float;
}