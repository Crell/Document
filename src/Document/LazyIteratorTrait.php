<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Wrapper for iterators to simulate a lazy-loading array.
 *
 * Useful only for large datasets, for some definition of large.
 *
 * Incomplete. Finish later.
 *
 * @implements \Countable, \ArrayAccess, \Iterator
 */
trait LazyIteratorTrait
{
    /**
     * The cached values pulled from the Generator.
     *
     * @var array
     */
    protected $values = [];

    /**
     *
     *
     * @var \Generator
     */
    protected $generator;

    /**
     * Default constructor
     *
     * This can likely be overwritten by implementing classes.
     *
     * @param \Traversable $iterator
     */
    public function __construct(\Traversable $iterator)
    {
        $this->generator = $iterator;
    }

    public function current() {
        // TODO: Implement current() method.
    }

    public function next() {
        // TODO: Implement next() method.
    }

    public function key() {
        // TODO: Implement key() method.
    }

    public function valid() {
        // TODO: Implement valid() method.
    }

    public function rewind() {
        // TODO: Implement rewind() method.
    }


    public function count()
    {
        foreach ($this->generator as $offset => $item) {
            $this->values[$offset] = $item;
        }
        return count($this->values);
    }

    public function offsetExists($offset) {
        while (!isset($this->values[$offset])) {
            $this->generator->next();
            $this->values[$this->generator->key()] = $this->generator->current();
        }

        return isset($this->values['offset']);

        /*
        foreach ($this->generator as $offset => $item) {
            $this->values[$offset] = $item;
            if (isset($this->values[$offset])) {
                return true;
            }
        }

        return false;
        */
    }

    public function offsetGet($offset) {
        while (!isset($this->values[$offset])) {
            $this->generator->next();
            $this->values[$this->generator->key()] = $this->generator->current();
        }

        return $this->values[$offset];
    }

    public function offsetSet($offset, $value) {
        throw new \LogicException('Cannot set documents in a read-only document set.');
    }

    public function offsetUnset($offset) {
        throw new \LogicException('Cannot unset documents in a read-only document set.');
    }

}
