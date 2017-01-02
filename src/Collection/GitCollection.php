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
    use DocumentFileNameTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $baseLanguage;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var Branch
     */
    protected $branch;

    public function __construct(string $name, Repository $repository, string $language = 'en', string $baseLanguage = 'en')
    {
        $this->name = $name;
        $this->repository = $repository;
        $this->language = $language;
        $this->baseLanguage = $baseLanguage;

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
            $data = $this->branch->load($this->documentFileNameFromIds($uuid, $this->language));
            return Document::hydrate($data);
        }
        catch (RecordNotFoundException $e) {
            $e = new DocumentNotFoundException(sprintf('No document found with id %s for language %s.', $uuid, $this->language()), $e->getCode(), $e);
            $e->setCollectionName($this->name())
                ->setUuid($uuid)
                ->setLanguage($this->language());
            throw $e;
        }
    }

    public function newRevision(string $uuid, string $parentRevision = null): MutableDocumentInterface
    {
        try {
            $data = $this->branch->load($this->documentFileNameFromIds($uuid, $this->language));
        } catch (RecordNotFoundException $e) {
            // If there is no existing revision in the current language, perhaps there's one in the base
            // language we can start from? If so, do so. If not, throw the original exception.
            try {
                $data = $this->branch->load($this->documentFileNameFromIds($uuid, $this->baseLanguage));
                $data['language'] = $this->language();
            } catch (RecordNotFoundException $e2) {
                $e = new DocumentNotFoundException(sprintf('No document found with id %s. Cannot create new revision.', $uuid), $e->getCode(), $e);
                $e->setCollectionName($this->name())
                    ->setUuid($uuid)
                    ->setLanguage($this->language());
                throw $e;
            }
        }

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
    protected function loadMultipleGenerator($uuids, bool $includeArchived = false) : \Generator
    {
        $names = [];
        foreach ($uuids as $uuid) {
            $names[] = $this->documentFileNameFromIds($uuid, $this->language);
        }

        foreach ($this->branch->loadMultiple($names) as $uuid => $record) {
            yield $record['uuid'] => Document::hydrate($record);
        }
    }

    public function setDefaultRevision(string $uuid, string $language, string $revision): CollectionInterface
    {
        // NA
    }

    public function save(MutableDocumentInterface $document, bool $setDefault = TRUE) : string
    {
        $commit = $this->createCommit()->withRevision($document);
        return $this->saveCommit($commit, $setDefault);
    }

    public function createCommit(string $message = 'No message', string $author = 'Anonymous <>'): Commit
    {
        return new Commit($message, $author);
    }

    public function saveCommit(Commit $commit, bool $setDefault = true): string
    {
        // Disallow empty commits at this level.
        if (!count($commit)) {
            throw new \InvalidArgumentException('Empty commits are not allowed.');
        }

        return $this->branch->commit($commit, $commit->author(), $commit->message());
    }

    public function loadRevision(string $uuid, string $revision): DocumentInterface
    {
        try {
            $data = $this->repository->load($this->documentFileNameFromIds($uuid, $this->language), $revision);
            return Document::hydrate($data);
        }
        catch (RecordNotFoundException $e) {
            $e = new DocumentNotFoundException(sprintf('No document found with id %s for language %s.', $uuid, $this->language()), $e->getCode(), $e);
            $e->setCollectionName($this->name())
                ->setUuid($uuid)
                ->setLanguage($this->language());
            throw $e;
        }
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
