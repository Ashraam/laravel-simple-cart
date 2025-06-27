<?php

namespace Ashraam\LaravelSimpleCart;

class CartModifier
{
    private string $id;
    private string $name;
    private float $value;

    private ?string $description;

    public function __construct(string $id, string $name, float $value, ?string $description = null)
    {
        if(empty($id)) {
            throw new \InvalidArgumentException("Please provide an id for the modifier.");
        }

        if(empty($name)) {
            throw new \InvalidArgumentException("Please provide a name for the modifier.");
        }

        if(empty($value)) {
            throw new \InvalidArgumentException("Please provide a value for the modifier.");
        }

        $this->id = $id;
        $this->name = $name;
        $this->value = $value * 100;
        $this->description = $description;
    }

    /**
     * It returns the ID of the modifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * It returns the name of the modifier
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * It updates the name of the modifier
     *
     * @param  string  $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * It returns the value of the modifier
     *
     * @return float
     */
    public function getValue(): float
    {
        return $this->value / 100;
    }

    /**
     * It updates the value of the modifier
     *
     * @param  float  $value
     * @return void
     */
    public function setValue(float $value): void
    {
        $this->value = $value * 100;
    }

    /**
     * It returns the description of the modifier
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * It updates the description of the modifier
     *
     * @param  string  $description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
