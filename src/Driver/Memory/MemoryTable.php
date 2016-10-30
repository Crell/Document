<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\Memory;

/**
 * An in-memory table-esque collection, suitable for mocking SQL drivers.
 */
class MemoryTable
{

    /**
     * The actual data storage, as an array of associative arrays.
     *
     * @var array
     */
    protected $storage;

    /**
     * Adds a record to the storage.
     *
     * @param array $record
     *   The record to add.
     *
     * @return static
     */
    public function insert(array $record)
    {
        $this->storage[] = $record;
        return $this;
    }

    /**
     * Modifies selected records in the dataset.
     *
     * @param callable $filter
     *   A callable taking a single array parameter. It must return True if
     *   that passed record should be modified, False otherwise.
     * @param callable $change
     *   A callable taking a single array parameter by reference. It should
     *   modify the record in-place as desired.
     *
     * @return static
     */
    public function update(callable $filter, callable $change)
    {
        array_walk($this->storage, function(&$item) use ($filter, $change) {
            if ($filter($item)) {
                $change($item);
            }
        });
        return $this;
    }

    /**
     * Returns selected records in the dataset by a specified callable filter.
     *
     * @param callable $filter
     *   A callable taking a single array parameter. It must return True if
     *   that passed record should be returned, False otherwise.
     *
     * @return \Generator
     */
    public function find(callable $filter)
    {
        foreach ($this->storage as $item) {
            if ($filter($item)) {
                yield $item;
            }
        }
    }
}
