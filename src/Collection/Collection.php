<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\LoadableDocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Ramsey\Uuid\Uuid;

/**
 * A Collection represents a set of documents with similar characteristics.
 *
 *
 * For some definition of similar that is largely open to interpretation.
 */
class Collection
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var CollectionDriverInterface
     */
    protected $driver;

    public function __construct(string $name, CollectionDriverInterface $driver, $language = 'en')
    {
        $this->name = $name;
        $this->driver = $driver;
        $this->language = $language;
    }

    /**
     * Returns the name of this collection.
     *
     * @return string
     *   The name of this collection.
     */
    public function name() : string
    {
        return $this->name;
    }

    public function language() : string
    {
        return $this->language;
    }

    /**
     * Creates the schema for this collection if necessary.
     */
    public function initializeSchema()
    {
        $this->driver->initializeSchema($this);
    }

    /**
     * Returns a new collection targeted at ths specified language.
     *
     * @param string $language
     *   The language for which we want a collection.
     * @return Collection
     */
    public function forLanguage(string $language) : self
    {
        $new = clone $this;
        $new->language = $language;

        return $new;
    }

    /**
     * Returns a new, empty document.
     *
     * @return Document
     *   A new document with just the appropriate IDs.
     */
    public function createDocument() : MutableDocumentInterface
    {
        $uuid = Uuid::uuid4()->toString();
        $revision = Uuid::uuid4()->toString();

        $document = $this->createMutableDocument();

        // A newly created, unsaved revision has no Revision ID.
        $document->loadFrom([
            'uuid' => $uuid,
            'language' => $this->language,
            'revision' => $revision,
        ]);

        return $document;
    }

    /**
     * Creates a new mutable document object, ready to be populated.
     *
     * @return MutableDocumentInterface
     */
    protected function createMutableDocument() : MutableDocumentInterface
    {
        $document = new class extends Document implements MutableDocumentInterface {
            use MutableDocumentTrait;
            use LoadableDocumentTrait;
        };
        return $document;
    }

    /**
     * Creates a new immutable document object, ready to be populated.
     *
     * @return Document
     */
    protected function createLoadableDocument() : Document
    {
        $document = new class extends Document {
            use LoadableDocumentTrait;
        };
        return $document;
    }

    /**
     * Retrieves a specified document from the collection.
     *
     * Specifically, the default revision will be returned for the language
     * of this collection.
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     * @return Document
     *   The corresponding document.
     */
    public function load(string $uuid) : Document
    {
        $data = $this->driver->loadDefaultRevisionData($this, $uuid);
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    /**
     * Retrieves a specified document, with special Mutable methods.
     *
     * Note that a mutable object will have a *new* revision ID already set,
     * so that it can later be serialized properly. If you need the original
     * revision ID, you should use load() instead.
     *
     * @param string $uuid
     *
     * @return Document
     */
    public function loadMutable(string $uuid) : MutableDocumentInterface
    {
        $revision = Uuid::uuid4()->toString();

        $data = $this->driver->loadDefaultRevisionData($this, $uuid);
        $document = $this->createMutableDocument()->loadFrom($data);
        $document->setRevisionId($revision);

        return $document;
    }

    /**
     * Retrieves a specific revision of a specified Document.
     *
     * @todo Should this be language-sensitive?
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     * @param string $revision
     *   The revision ID of the Document to load.
     * @return Document
     *   The corresponding document.
     * @throws \Doctrine\DBAL\DBALException
     *
     * @throws \Exception
     */
    public function loadRevision(string $uuid, string $revision) : Document
    {
        $data = $this->driver->loadRevisionData($this, $uuid, $revision);
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    /**
     * Retrieves the latest revision of the specified Document.
     *
     * @param string $uuid
     *   The UUID of the Document to load.
     * @return Document
     */
    public function loadLatestRevision(string $uuid) : Document {
        $data = $this->driver->loadLatestRevisionData($this, $uuid);
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    /**
     * Creates a new revision of the specified Document.
     *
     * @todo Track parantage of entities.
     *
     * @todo We need to switch this to an explicitly mutable object, or a command,
     * or something.
     *
     * @param MutableDocumentInterface $document
     *   The document to be persisted.
     * @param bool $setDefault
     *   True if this should become the default revision of this Document in its
     *   language, False otherwise.
     * @throws \Exception
     */
    public function save(MutableDocumentInterface $document, bool $setDefault = true)
    {
        $this->driver->persist($this, $document, $setDefault);

    }
}
