<?php

declare(strict_types = 1);

namespace Crell\Document\Document\Field;

/**
 * A basic text field.
 *
 * @todo This probably could get fancier, but it's mainly for testing for now.
 */
class TextField implements \JsonSerializable
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
    public function __construct(string $value = '')
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

    /**
     * Loads a new field using the provided data.
     *
     * @param array $data
     *   An array of raw data to repopulate this object. Generally it is a direct
     *   load from JSON data.
     * @return static
     *   A loaded document, which may be a subclass.
     *
     * @throws \InvalidArgumentException
     *   Thrown if the data structure is missing a required key.
     */
    public static function hydrate(array $data) : self
    {
        $field = new static();

        foreach ($data as $key => $value) {
            $field->$key = $value;
        }

        return $field;
    }

    public function jsonSerialize()
    {
        return [
            'value' => $this->value,
        ];
    }


}
