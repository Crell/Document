<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentInterface;
use Crell\Document\Document\DocumentNotFoundException;
use Crell\Document\Document\DocumentSetInterface;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\SimpleDocumentSet;
use Crell\Document\Driver\CollectionDriverInterface;
use Ramsey\Uuid\Uuid;

class Collection implements CollectionInterface
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

    /**
     *
     *
     * @var string
     */
    protected $commit;

    public function __construct(string $name, CollectionDriverInterface $driver, $language = 'en')
    {
        $this->name = $name;
        $this->driver = $driver;
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     */
    public function commit() : string
    {
        return $this->commit;
    }

    /**
     * {@inheritdoc}
     */
    public function atCommit(string $commit) : CollectionInterface
    {
        $new = clone ($this);
        $new->commit = $commit;
        return $new;
    }


    public function atBranch(string $name) : CollectionInterface
    {
        $commit = '123'; // Determine what commit that branch is.
        return $this->atCommit($commit);
    }

    /**
     * {@inheritdoc}
     */
    public function createCommit(string $message = '', string $author = '') : Commit
    {
        return new Commit($message, $author);
    }

    /**
     * Saves a commit object atomically.
     *
     * @param Commit $commit
     *   The commit object to persist.
     * @param bool $setDefault
     *
     * @return CollectionInterface
     *   The called object.
     */
    public function saveCommit(Commit $commit, bool $setDefault = true) : CollectionInterface
    {
        // If there are no commits, there is nothing to do. Viz, no
        // empty commits allowed.
        if (!count($commit)) {
            return $this;
        }

        foreach ($commit as $revision) {
            $this->driver->persist($this, iterator_to_array($commit), $setDefault);
        }

        return $this;
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

        /** @var MutableDocumentInterface $document */
        $document = Document::hydrate([
            'uuid' => $uuid,
            'language' => $this->language,
            'revision' => $revision,
            'parent_rev' => '',
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c'),
            'title' => '',
            'fields' => [],
        ], true);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $uuid, bool $includeArchived = false) : DocumentInterface
    {
        try {
            $data = $this->driver->loadDefaultRevisionData($this, $uuid, $includeArchived);
            return Document::hydrate($data);
        }
        catch (DocumentRecordNotFoundException $e) {
            $e = new DocumentNotFoundException($e->getMessage(), $e->getCode(), $e);
            $e->setCollectionName($this->name())
                ->setUuid($uuid)
                ->setLanguage($this->language());
            throw $e;
        }
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

        /** @var MutableDocumentInterface $document */
        $document = Document::hydrate($data, true);
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
        $document = Document::hydrate($data);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function loadLatestRevision(string $uuid) : DocumentInterface
    {
        $data = $this->driver->loadLatestRevisionData($this, $uuid);
        $document = Document::hydrate($data);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultiple(array $uuids, bool $includeArchived = false) : DocumentSetInterface
    {
        return new SimpleDocumentSet($this->loadMultipleGenerator($uuids, $includeArchived), $uuids);
    }

    /**
     * Creates a generator for producing lazy-loaded documents.
     *
     * @param array $uuids
     *   An array of UUIDs to load.
     * @param bool $includeArchived
     *   True to return the document even if it is archived in its current
     *  revision. False otherwise.
     *
     * @return \Generator
     *   A generator that produces documents with the specified UUIDs.
     */
    protected function loadMultipleGenerator(array $uuids, bool $includeArchived = false) : \Generator
    {
        $data = $this->driver->loadMultipleDefaultRevisionData($this, $uuids, $includeArchived);
        foreach ($data as $uuid => $record) {
            yield $uuid => Document::hydrate($record);
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
        $commit = $this->createCommit()->withRevision($document);
        $this->saveCommit($commit, $setDefault);
    }

    /**
     * {@inheritdoc}
     */
    public function archive(DocumentInterface $document)
    {
        $defaultRevisionData = $this->driver->loadDefaultRevisionData($this, $document->uuid());
        $this->driver->setArchived($this, $defaultRevisionData['revision']);
    }
}
