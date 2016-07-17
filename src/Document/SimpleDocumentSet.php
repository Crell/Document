<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


class SimpleDocumentSet implements DocumentSetInterface, \IteratorAggregate
{

    protected $values = [];

    public function __construct(\Traversable $iterator)
    {
        $this->values = iterator_to_array($iterator);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayObject($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset) {
        return isset($this->values[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset) {
        return $this->values[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value) {
        throw new \LogicException('Cannot set documents in a read-only document set.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset) {
        throw new \LogicException('Cannot unset documents in a read-only document set.');
    }


}
