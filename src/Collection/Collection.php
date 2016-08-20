<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentInterface;
use Crell\Document\Document\DocumentNotFoundException;
use Crell\Document\Document\DocumentSetInterface;
use Crell\Document\Document\DocumentTrait;
use Crell\Document\Document\LoadableDocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
use Crell\Document\Document\SimpleDocumentSet;
use Ramsey\Uuid\Uuid;

class Collection implements CollectionInterface {
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
     * {@inheritdoc}
     */
    public function name() : string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function language() : string
    {
        return $this->language;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeSchema()
    {
        $this->driver->initializeSchema($this);
    }

    /**
     * {@inheritdoc}
     */
    public function forLanguage(string $language) : CollectionInterface
    {
        $new = clone $this;
        $new->language = $language;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function createDocument() : MutableDocumentInterface
    {
        $uuid = Uuid::uuid4()->toString();
        $revision = Uuid::uuid4()->toString();

        $document = $this->createMutableDocument();

        $document->loadFrom([
            'uuid' => $uuid,
            'language' => $this->language,
            'revision' => $revision,
            'parent_rev' => '',
            'timestamp' => new \DateTimeImmutable(),
            'title' => '',
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
            use DocumentTrait;
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
    protected function createLoadableDocument() : DocumentInterface
    {
        $document = new class extends Document implements DocumentInterface {
            use DocumentTrait;
            use LoadableDocumentTrait;
        };
        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $uuid) : DocumentInterface
    {
        $data = $this->driver->loadDefaultRevisionData($this, $uuid);
        if (!$data) {
            $e = new DocumentNotFoundException();
            $e->setCollectionName($this->name())
               ->setUuid($uuid)
                ->setLanguage($this->language());
            throw $e;
        }
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function newRevision(string $uuid, string $parentRevision = null) : MutableDocumentInterface
    {
        $revision = Uuid::uuid4()->toString();

        $data = $parentRevision
            ? $this->driver->loadRevisionData($this, $uuid, $parentRevision)
            : $this->driver->loadLatestRevisionData($this, $uuid);

        $data['parent_rev'] = $parentRevision ?: $data['revision'];

        $document = $this->createMutableDocument()->loadFrom($data);
        $document->setRevisionId($revision);
        $document->setTimestamp(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function loadRevision(string $uuid, string $revision) : DocumentInterface
    {
        $data = $this->driver->loadRevisionData($this, $uuid, $revision);
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function loadLatestRevision(string $uuid) : DocumentInterface
    {
        $data = $this->driver->loadLatestRevisionData($this, $uuid);
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultiple(array $uuids) : DocumentSetInterface
    {
        return new SimpleDocumentSet($this->loadMultipleGenerator($uuids));
    }

    /**
     * Creates a generator for producing lazy-loaded documents.
     *
     * @param array $uuids
     *   An array of UUIDs to load.
     * @return \Generator
     *   A generator that produces documents with the specified UUIDs.
     */
    protected function loadMultipleGenerator(array $uuids) : \Generator
    {
        $data = $this->driver->loadMultipleDefaultRevisionData($this, $uuids);
        foreach ($data as $uuid => $record) {
            yield $uuid => $this->createLoadableDocument()->loadFrom($record);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultRevision(string $uuid, string $language, string $revision) : CollectionInterface
    {
        $this->driver->setDefaultRevision($this, $uuid, $language, $revision);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save(MutableDocumentInterface $document, bool $setDefault = true)
    {
        $this->driver->persist($this, $document, $setDefault);
    }
}
