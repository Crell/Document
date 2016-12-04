<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * A document is the basic unit of data.
 */
class Document {
    use DocumentTrait;

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

    public static function hydrate(array $data) : self
    {
        $doc = new $data['class'];

        foreach (['uuid', 'revision', 'language', 'title'] as $key) {
            $doc->$key = $data[$key];
        }

        // Named differently because coding standards.
        // @todo Do something about this.
        $doc->parentRev = $data['parent_rev'];

        $doc->timestamp = $data['timestamp'];

        return $doc;
    }


}
