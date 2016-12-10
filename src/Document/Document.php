<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * A document is the basic unit of data.
 */
class Document implements \JsonSerializable {
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

    /**
     * Loads a new document using the provided data.
     *
     * @param array $data
     *   An array of raw data to repopulate this object. Generally it is a direct
     *   load from JSON data.
     * @return Document
     *   A loaded document, which may be a subclass.
     *
     * @throws \InvalidArgumentException
     *   Thrown if the data structure is missing a required key.
     */
    public static function hydrate(array $data) : self
    {
        $required = ['class', 'uuid', 'revision', 'language', 'title', 'parent_rev', 'timestamp', 'fields'];

        foreach ($required as $name) {
            if (!isset($data[$name])) {
                throw new \InvalidArgumentException(sprintf('Incomplete data. Property \'%s\' is missing.', $name));
            }
        }

        $doc = new $data['class'];

        foreach (['uuid', 'revision', 'language', 'title'] as $key) {
            $doc->$key = $data[$key];
        }

        // Named differently because coding standards.
        // @todo Do something about this.
        $doc->parentRev = $data['parent_rev'];

        $doc->timestamp = new \DateTimeImmutable($data['timestamp'], new \DateTimeZone('UTC'));

        foreach ($data['fields'] as $name => $definition) {
            if (!isset($definition['class'])) {
                throw new \InvalidArgumentException(sprintf('No class defined for field \'%s\'.', $name));
            }

            if (!isset($definition['items'])) {
                throw new \InvalidArgumentException(sprintf('No items defined for field \'%s\'.', $name));
            }

            $items = [];
            foreach ($definition['items'] as $item) {
                $items[] = $definition['class']::hydrate($item);
            }

            $doc->fields[$name] = new FieldSet($items);
        }

        return $doc;
    }


}
