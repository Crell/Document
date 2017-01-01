<?php

declare(strict_types = 1);

namespace Crell\Document\Collection;


use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentInterface;
use Crell\Document\Document\DocumentNotFoundException;
use Crell\Document\Document\DocumentSetInterface;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\SimpleDocumentSet;
use Crell\Document\GitStore\Branch;
use Crell\Document\GitStore\RecordNotFoundException;
use Crell\Document\GitStore\Repository;
use Ramsey\Uuid\Uuid;

/**
 * An implementation of Collection intended for a Git backend.
 *
 * @todo This is temporary. It will fold back into Collection later once
 * I figure out how Git's assumptions change the interface/model.
 */
class GitCollection implements CollectionInterface
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
     * @var Repository
     */
    protected $repository;

    /**
     * @var Branch
     */
    protected $branch;

    public function __construct(string $name, Repository $repository, $language = 'en')
    {
        $this->name = $name;
        $this->repository = $repository;
        $this->language = $language;

        $this->branch = $this->repository->getBranchPointer('master');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function initializeSchema()
    {
        $this->repository->init();
    }

    public function forLanguage(string $language): CollectionInterface
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

        /** @var MutableDocumentInterface $document */
        $document = Document::hydrate([
            'uuid' => $uuid,
            'language' => $this->language,
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c'),
            'title' => '',
            'fields' => [],
        ], true);

        return $document;
    }

    public function load(string $uuid, bool $includeArchived = false): DocumentInterface
    {
        try {
            $data = $this->branch->load($uuid);
            return Document::hydrate($data);
        }
        catch (RecordNotFoundException $e) {
            $e = new DocumentNotFoundException($e->getMessage(), $e->getCode(), $e);
            $e->setCollectionName($this->name())
                ->setUuid($uuid)
                ->setLanguage($this->language());
            throw $e;
        }
    }

    public function newRevision(string $uuid, string $parentRevision = null): MutableDocumentInterface
    {
        $data = $this->branch->load($uuid);

        /** @var MutableDocumentInterface $document */
        $document = Document::hydrate($data, true);
        $document->setTimestamp(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        return $document;
    }

    public function loadMultiple(array $uuids, bool $includeArchived = false): DocumentSetInterface
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
        foreach ($this->branch->loadMultiple($uuids) as $uuid => $record) {
            yield $uuid => Document::hydrate($record);
        }
    }

    public function setDefaultRevision(string $uuid, string $language, string $revision): CollectionInterface
    {
        // NA
    }

    public function save(MutableDocumentInterface $document, bool $setDefault = TRUE)
    {
        $commit = $this->createCommit()->withRevision($document);
        $this->saveCommit($commit, $setDefault);
    }

    public function createCommit(string $message = 'No message', string $author = 'Anonymous <>'): Commit
    {
        return new Commit($message, $author);
    }

    public function saveCommit(Commit $commit, bool $setDefault = true): CollectionInterface
    {
        // If there are no commits, there is nothing to do. Viz, no
        // empty commits allowed.
        if (!count($commit)) {
            return $this;
        }

        $this->branch->commit($commit, $commit->author(), $commit->message());

        return $this;
    }


    public function loadRevision(string $uuid, string $revision): DocumentInterface
    {
        // NA
    }

    public function loadLatestRevision(string $uuid): DocumentInterface
    {
        // NA
    }

    public function archive(DocumentInterface $document)
    {
        // NA
    }

    public function commit(): string
    {
        // NA
    }

    public function atCommit(string $commit): CollectionInterface
    {
        // NA?
    }

    public function atBranch(string $name): CollectionInterface
    {
        // NA?
    }

}
