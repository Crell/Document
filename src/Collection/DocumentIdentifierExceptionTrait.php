<?php


namespace Crell\Document\Collection;


trait DocumentIdentifierExceptionTrait
{
    /**
     * The name of the collection that triggered this exception.
     *
     * @var string
     */
    protected $collectionName;

    /**
     * The language code of the document that was missing.
     *
     * @var string
     */
    protected $language;

    /**
     * The UUID of the document that was missing.
     *
     * @var string
     */
    protected $uuid;

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
    public function setUuid(string $uuid) : self
    {
        $this->uuid = $uuid;
        return $this;
    }

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
    public function setLanguage(string $language) : self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Sets the name of the collection that is missing a document.
     *
     * @param string $name
     *   The name of the collection.
     * @return self
     */
    public function setCollectionName(string $name) : self
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
