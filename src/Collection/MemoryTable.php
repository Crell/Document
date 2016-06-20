<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

/**
 * @todo Maybe finish this at some point.
 */
class MemoryTable {
    protected $storage;

    protected function getKeys() {
        return [];
    }

    protected function iterateStorage() {
        foreach ($this->getKeys() as $key_name) {
            $key = key($this->storage);
            $val = current($this->storage);
            $key_array[$key_name] = $key;

        }

        foreach ($this->storage as $uuid => $rev) {
            foreach ($rev as $revision => $lang) {
                foreach ($lang as $language => $data) {
                    $data['uuid'] = $uuid;
                    $data['revision'] = $revision,
                        $data['language'] = $language;
                        yield $data;
                    }
            }
        }
    }

}
