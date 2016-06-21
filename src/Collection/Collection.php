<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentInterface;
use Crell\Document\Document\DocumentTrait;
use Crell\Document\Document\LoadableDocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
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
            'timestamp' => new \DateTimeImmutable(),
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
        $document = $this->createLoadableDocument()->loadFrom($data);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMutable(string $uuid) : MutableDocumentInterface
    {
        $revision = Uuid::uuid4()->toString();

        $data = $this->driver->loadDefaultRevisionData($this, $uuid);
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

    public function archive(string $uuid)
    {
        $doc = $this->loadMutable($uuid);
        $doc->setArchived(true);

        $this->save($doc);
    }

    /**
     * {@inheritdoc}
     */
    public function save(MutableDocumentInterface $document, bool $setDefault = true)
    {
        $this->driver->persist($this, $document, $setDefault);
    }
}
