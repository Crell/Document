<?php

declare(strict_types = 1);

namespace Crell\Document\Document\Field;

/**
 * A basic text field.
 *
 * @todo This probably could get fancier, but it's mainly for testing for now.
 */
class TextField
{
    /**
     * The stored value in this TextField.
     *
     * @var string
     */
    protected $value;

    /**
     * TextField constructor.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Passes property accesses on to the internal value.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->$name;
    }

    /**
     * Returns the ROT13 of this text field.
     *
     * @todo This is purely for example and testing purposes.
     *
     * @return string
     */
    public function rot13() : string
    {
        return str_rot13($this->value);
    }
}
