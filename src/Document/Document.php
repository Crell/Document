<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * A document is the basic unit of data.
 */
class Document {

    protected $fields = [];

    /**
     * Returns a list of field names on this document.
     *
     * @return string[]
     */
    public function fieldNames() : array
    {
        return array_keys($this->fields);
    }

    /**
     * Passes through property requests to an enclosed field.
     *
     * @param string $name
     * @return FieldSet
     */
    public function __get(string $name) : FieldSet
    {
        // @todo This may need to return by reference. Drupal does but
        // I don't know if that's for any good reason.
        return $this->fields[$name];
    }


}
