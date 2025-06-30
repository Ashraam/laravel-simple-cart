<?php

namespace Ashraam\LaravelSimpleCart;

use Ashraam\LaravelSimpleCart\Exceptions\InvalidPrice;
use Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity;
use Illuminate\Support\Collection;

class CartItem
{
    private string $hash;
    private string $id;
    private string $name;
    private int $price;
    private int $quantity;
    private array $options;
    private array $meta;
    private array $modifiers;

    public function __construct(string $id, string $name, float $price, int $quantity = 1, array $options = [], array $meta = [])
    {
        if(empty($id)) {
            throw new \InvalidArgumentException("Please provide an id for the item.");
        }

        if(empty($name)) {
            throw new \InvalidArgumentException("Please provide a name for the item.");
        }

        if(empty($price)) {
            throw new \InvalidArgumentException("Please provide a price for the item.");
        }

        if($price < 0) {
            throw new InvalidPrice("Price cannot be less than 0.");
        }

        if($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }


        $this->id = $id;
        $this->name = $name;
        $this->price = $price * 100;
        $this->quantity = $quantity;
        $this->options = $options;
        $this->meta = $meta;
        $this->modifiers = [];
        $this->hash = $this->generateHash();
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
        $this->hash = $this->generateHash();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPrice(): float
    {
        return $this->price / 100;
    }

    public function setPrice(float $price): void
    {
        if($price < 0) {
            throw new InvalidPrice("Price cannot be less than 0.");
        }

        $this->price = $price * 100;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        if($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }

        $this->quantity = $quantity;
    }

    public function incrementQuantity(int $quantity = 1): void
    {
        if($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }

        $this->quantity += $quantity;
    }

    public function decrementQuantity(int $quantity = 1): void
    {
        if($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }

        if($this->quantity - $quantity < 1) {
            throw new InvalidQuantity("Calculated quantity must be superior or equal to 1.");
        }

        $this->quantity -= $quantity;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options, bool $overwrite = false): void
    {
        if($overwrite) {
            $this->options = $options;
        } else {
            $this->options = array_merge($this->options, $options);
        }
        $this->hash = $this->generateHash();
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function setMeta(array $meta, bool $overwrite = false): void
    {
        if($overwrite) {
            $this->meta = $meta;
        } else {
            $this->meta = array_merge($this->meta, $meta);
        }
    }

    public function getTotal(): float
    {
        $total = $this->price * $this->quantity;
        $modifiers = $this->getModifiers()->sum(function($modifier) use ($total) {
            if($modifier->getType() === CartModifier::PERCENT) {
                return $total * ($modifier->getRawValue() / 100);
            }

            return $modifier->getRawValue();
        });

        return ($total + $modifiers) / 100;
    }

    /**
     * Add a modifier to this cart item
     *
     * @param CartModifier $modifier
     * @return void
     */
    public function addModifier(CartModifier $modifier): void
    {
        $this->modifiers[$modifier->getId()] = $modifier;
    }

    /**
     * Remove a modifier from this cart item
     *
     * @param CartModifier|string $modifier
     * @return void
     */
    public function removeModifier(CartModifier|string $modifier): void
    {
        if ($modifier instanceof CartModifier) {
            $id = $modifier->getId();
        } else {
            $id = $modifier;
        }

        unset($this->modifiers[$id]);
    }

    /**
     * Check if this cart item has a specific modifier
     *
     * @param CartModifier|string $modifier
     * @return bool
     */
    public function hasModifier(CartModifier|string $modifier): bool
    {
        if ($modifier instanceof CartModifier) {
            $id = $modifier->getId();
        } else {
            $id = $modifier;
        }

        return array_key_exists($id, $this->modifiers);
    }

    /**
     * Get a specific modifier from this cart item
     *
     * @param CartModifier|string $modifier
     * @return CartModifier|null
     */
    public function getModifier(CartModifier|string $modifier): ?CartModifier
    {
        if ($modifier instanceof CartModifier) {
            $id = $modifier->getId();
        } else {
            $id = $modifier;
        }

        return $this->modifiers[$id] ?? null;
    }

    /**
     * Get all modifiers for this cart item
     *
     * @return Collection
     */
    public function getModifiers(): Collection
    {
        return collect($this->modifiers);
    }

    /**
     * Clear all modifiers from this cart item
     *
     * @return void
     */
    public function clearModifiers(): void
    {
        $this->modifiers = [];
    }

    private function generateHash(): string
    {
        return md5($this->id . serialize($this->options));
    }
}
