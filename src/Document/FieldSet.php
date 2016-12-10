<?php

declare(strict_types = 1);

namespace Crell\Document\Document;

/**
 * Generic wrapper for a set of fields that allows transparent access to the first field.
 *
 * @todo Might this be easier if it extended ArrayObject? Can we even do that?
 */
class FieldSet implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Array of field objects.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * FieldSet constructor.
     *
     * @todo This is probably temporary until a better creation process is found.
     *
     * @param array $fields
     */
    public function __construct(array $fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * Pass through property requests to the first object.
     *
     * Note that the property request should almost always get caught by another
     * __get on the field.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->fields[0]->$name;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($fields[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('No setting of fields.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('No unsetting of fields');
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->fields);
    }
}
