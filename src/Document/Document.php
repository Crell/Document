<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * A document is the basic unit of data.
 */
class Document implements \JsonSerializable, DocumentInterface {
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
     * @param bool $mutable
     *   True to return a mutable variant of the specified document. Defaults to false.
     * @return Document
     *   A loaded document, which may be a subclass.
     *
     * @throws \InvalidArgumentException
     *   Thrown if the data structure is missing a required key.
     */
    public static function hydrate(array $data, bool $mutable = false) : self
    {
        $required = ['class', 'uuid', 'language', 'title', 'timestamp', 'fields'];

        // Allow a missing class specification, in which case fall back to Document.
        $data += ['class' => static::class];

        foreach ($required as $name) {
            if (!isset($data[$name])) {
                throw new \InvalidArgumentException(sprintf('Incomplete data. Property \'%s\' is missing.', $name));
            }
        }

        $doc = $mutable ? static::createMutableDocument($data['class']) : new $data['class'];

        foreach (['uuid', 'language', 'title'] as $key) {
            $doc->$key = $data[$key];
        }

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
                $items[] = $definition['class']::hydrate($item, $mutable);
            }

            $doc->fields[$name] = new FieldSet($items);
        }

        return $doc;
    }


    /**
     * Creates a new mutable document object, ready to be populated.
     *
     * @todo Currently ignores the $class, because anonymous classes are not dynamic. Figure out how to fix.
     *
     * @param string $class
     *   The class to instantiate. The actual returned class will be a subclass of this one.
     *
     * @return MutableDocumentInterface
     */
    protected static function createMutableDocument(string $class) : MutableDocumentInterface
    {
        $document = new class extends Document implements MutableDocumentInterface {
            use DocumentTrait;
            use MutableDocumentTrait;
        };
        return $document;
    }
}
