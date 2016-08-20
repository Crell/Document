<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


class DocumentNotFoundException extends \InvalidArgumentException implements DocumentException
{

    /**
     * The name of the collection that triggered this exception.
     *
     * @var string
     */
    protected $collectionName;

    /**
     * The UUID of the document that was missing.
     *
     * @var string
     */
    protected $uuid;

    /**
     * The language code of the document that was missing.
     *
     * @var string
     */
    protected $language;

    /**
     * Returns the language code of the missing document.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Sets the language code of the missing document.
     *
     * @param string $language
     * @return self
     */
    public function setLanguage(string $language) : DocumentNotFoundException
    {
        $this->language = $language;
        return $this;
    }


    /**
     * Returns the UUID of the missing document.
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Sets the UUID of the missing document
     *
     * @param string $uuid
     * @return self
     */
    public function setUuid(string $uuid) : DocumentNotFoundException
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Sets the name of the collection that is missing a document.
     *
     * @param string $name
     *   The name of the collection.
     * @return self
     */
    public function setCollectionName(string $name) : DocumentNotFoundException
    {
        $this->collectionName = $name;
        return $this;
    }

    /**
     * Returns the name of the collection that is missing a document.
     *
     * @return string
     *   The name of the collection.
     */
    public function getCollectionName() : string
    {
        return $this->collectionName;
    }


}
