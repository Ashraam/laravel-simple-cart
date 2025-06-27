<?php

namespace Ashraam\LaravelSimpleCart;

class CartModifier
{
    public const VALUE = 'value';
    public const PERCENT = 'percent';

    private string $id;
    private string $name;
    private int $value;
    private string $type;
    private ?string $description;

    public function __construct(string $id, string $name, float $value, string $type = self::VALUE, ?string $description = null)
    {
        if(empty($id)) {
            throw new \InvalidArgumentException("Please provide an id for the modifier.");
        }

        if(empty($name)) {
            throw new \InvalidArgumentException("Please provide a name for the modifier.");
        }

        if(!is_numeric($value)) {
            throw new \InvalidArgumentException("Please provide a numeric value for the modifier.");
        }

        if(empty($type) || !in_array($type, [self::VALUE, self::PERCENT])) {
            throw new \InvalidArgumentException("Please provide a valid type for the modifier (value or percent).");
        }

        if($type === self::PERCENT && ($value < -100 || $value > 100)) {
            throw new \InvalidArgumentException("Please provide a valid value for the modifier between -100 and 100.");
        }

        $this->id = $id;
        $this->name = $name;
        $this->value = $type === self::VALUE ? $value * 100 : $value;
        $this->type = $type;
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
        return $this->type === self::VALUE ? $this->value / 100 : $this->value;
    }

    /**
     * Returns the raw value (in cents for type Value)
     *
     * @return int
     */
    public function getRawValue(): int
    {
        return $this->value;
    }

    /**
     * It updates the value of the modifier
     *
     * @param  float  $value
     * @return void
     */
    public function setValue(float $value): void
    {
        $this->value = $this->type === self::VALUE ? $value * 100 : $value;
    }

    /**
     * Returns the type of the modifier
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * It updates the type of the modifier
     * It recalculates the raw value when changing type
     *
     * @param  string  $type
     * @return void
     */
    public function setType(string $type): void
    {
        if(!in_array($type, [self::VALUE, self::PERCENT])) {
            throw new \InvalidArgumentException("Please provide a valid type for the modifier (value or percent).");
        }

        if($type === $this->type) {
            return;
        }

        $value = $type === self::VALUE ? $this->value * 100 : $this->value / 100;

        if($type === self::PERCENT && ($value < -100 || $value > 100)) {
            throw new \InvalidArgumentException("Cannot change the type to percent if the value is not between -100 and 100.");
        }

        $this->type = $type;
        $this->value = $value;
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
