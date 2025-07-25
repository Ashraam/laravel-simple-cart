<?php

namespace Ashraam\LaravelSimpleCart;

use Ashraam\LaravelSimpleCart\Exceptions\InvalidPrice;
use Ashraam\LaravelSimpleCart\Exceptions\InvalidQuantity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class CartItem
{
    private string $hash;
    private string $id;
    private string $name;
    private int $price;
    private int $quantity;
    private ?float $vat_rate;
    private array $options;
    private array $meta;

    public function __construct(
        string $id,
        string $name,
        float $price,
        int $quantity = 1,
        ?float $vat_rate = null,
        array $options = [],
        array $meta = []
    ) {
        if (empty($id)) {
            throw new \InvalidArgumentException("Please provide an id for the item.");
        }

        if (empty($name)) {
            throw new \InvalidArgumentException("Please provide a name for the item.");
        }

        if (empty($price)) {
            throw new \InvalidArgumentException("Please provide a price for the item.");
        }

        if ($price < 0) {
            throw new InvalidPrice("Price cannot be less than 0.");
        }

        if ($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }

        if (is_numeric($vat_rate) && ($vat_rate < 0 || $vat_rate > 100)) {
            throw new \InvalidArgumentException('VAT rate must be between 0 and 100.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = $price * 100;
        $this->quantity = $quantity;
        $this->vat_rate = $vat_rate ?? Config::get('laravel-simple-cart.default_vat_rate');
        $this->options = $options;
        $this->meta = $meta;
        $this->hash = $this->generateHash();
    }

    /**
     * Returns the hash value of the item.
     * The hash is a md5 string from the combinaison of the item id and the options.
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Returns the item's ID
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Update the item's ID
     * Recalculates the hash
     *
     * @param  string  $id
     * @return void
     */
    public function setId(string $id): void
    {
        $this->id = $id;
        $this->hash = $this->generateHash();
    }

    /**
     * Returns the item's name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Updates the item's name
     *
     * @param  string  $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the item's base price (without VAT)
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price / 100;
    }

    /**
     * Updates the base item's price.
     * Price cannot be less than 0.
     *
     *
     * @param  float  $price
     * @return void
     */
    public function setPrice(float $price): void
    {
        if ($price < 0) {
            throw new InvalidPrice("Price cannot be less than 0.");
        }

        $this->price = $price * 100;
    }

    /**
     * Returns the VAT Rate applied to the item.
     *
     * @return float|null
     */
    public function getVatRate(): ?float
    {
        return $this->vat_rate;
    }

    /**
     * Returns the item's quantity
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Updates the item's quantity
     *
     * @param  int  $quantity
     * @return void
     */
    public function setQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }

        $this->quantity = $quantity;
    }

    /**
     * Increment the item's quantity
     *
     * @param  int  $quantity
     * @return void
     */
    public function incrementQuantity(int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }

        $this->quantity += $quantity;
    }

    /**
     * Decrement the item's quantity
     * Calculated quantity cannot be less than 1
     *
     * @param  int  $quantity
     * @return void
     */
    public function decrementQuantity(int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new InvalidQuantity("Quantity cannot be less than 1.");
        }

        if ($this->quantity - $quantity < 1) {
            throw new InvalidQuantity("Calculated quantity must be superior or equal to 1.");
        }

        $this->quantity -= $quantity;
    }

    /**
     * Returns the option array
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Updates or overwrites the option array
     *
     * @param  array  $options
     * @param  bool  $overwrite
     * @return void
     */
    public function setOptions(array $options, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->options = $options;
        } else {
            $this->options = array_merge($this->options, $options);
        }
        $this->hash = $this->generateHash();
    }

    /**
     * Return the meta array
     *
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Updates or overwrite the meta array
     *
     * @param  array  $meta
     * @param  bool  $overwrite
     * @return void
     */
    public function setMeta(array $meta, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->meta = $meta;
        } else {
            $this->meta = array_merge($this->meta, $meta);
        }
    }

    /**
     * Returns the unit price without vat
     *
     * @return float
     */
    public function unitPriceWithoutVat(): float
    {
        return $this->price / 100;
    }

    /**
     * Returns the vat amount for a single item
     *
     * @return float
     */
    public function vat(): float
    {
        return ($this->price * ($this->vat_rate / 100)) / 100;
    }

    /**
     * Returns the unit price of the item with vat
     *
     * @return float
     */
    public function unitPrice(): float
    {
        return $this->unitPriceWithoutVat() + $this->vat();
    }

    /**
     * Returns the total price of the item without vat
     *
     * @return float
     */
    public function totalWithoutVat(): float
    {
        return $this->unitPriceWithoutVat() * $this->quantity;
    }

    /**
     * Returns the total vat amount of this item
     *
     * @return float
     */
    public function vatTotal(): float
    {
        return $this->vat() * $this->quantity;
    }

    /**
     * Returns the total price of this item (with vat included)
     *
     * @return float
     */
    public function total(): float
    {
        return $this->unitPrice() * $this->quantity;
    }


    /**
     * Generates the item's hash
     *
     * @return string
     */
    private function generateHash(): string
    {
        return md5($this->id.serialize($this->options));
    }
}
