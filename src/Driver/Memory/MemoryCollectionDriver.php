<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\Memory;

use Crell\Document\Collection\CollectionInterface;
use Crell\Document\Collection\DocumentRecordNotFoundException;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Driver\CollectionDriverInterface;

class MemoryCollectionDriver implements CollectionDriverInterface {

    /**
     *
     *
     * @var MemoryTable
     */
    protected $storage;

    public function __construct()
    {
        $this->storage = new MemoryTable();
    }

    /**
     * {@inheritdoc}
     */
    public function initializeSchema(CollectionInterface $collection)
    {
        $this->storage = new MemoryTable();
    }

    /**
     * {@inheritdoc}
     */
    public function loadLatestRevisionData(CollectionInterface $collection, string $uuid) : array
    {
        $result = $this->storage->find(function(array $item) use ($collection, $uuid) {
            return $item['uuid'] == $uuid && $item['latest'] == true && $item['language'] == $collection->language();
        });
        return current(iterator_to_array($result));
    }

    /**
     * {@inheritdoc}
     */
    public function loadDefaultRevisionData(CollectionInterface $collection, string $uuid, bool $includeArchived = false) : array
    {
        $result = $this->storage->find(function(array $item) use ($collection, $uuid, $includeArchived) {
            return $item['uuid'] == $uuid
                && $item['default_rev'] == true
                && $item['language'] == $collection->language()
                // If archived is allowed, turn this line into a noop.
                && $item['archived'] == ($includeArchived ? $item['archived'] : 0);
        });
        $value = current(iterator_to_array($result));

        if (!$value) {
            $e = new DocumentRecordNotFoundException();
            $e->setCollectionName($collection->name())
                ->setUuid($uuid)
                ->setLanguage($collection->language());
            throw $e;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function loadRevisionData(CollectionInterface $collection, string $uuid, string $revision) : array
    {
        $result = $this->storage->find(function(array $item) use ($uuid, $revision) {
            return $item['uuid'] == $uuid && $item['revision'] == $revision;
        });
        return current(iterator_to_array($result));
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultipleDefaultRevisionData(CollectionInterface $collection, array $uuids, bool $includeArchived = false) : \Iterator
    {
        foreach ($uuids as $uuid) {
            try {
                $record = $this->loadDefaultRevisionData($collection, $uuid, $includeArchived);
                yield $uuid => $record;
            }
            catch (DocumentRecordNotFoundException $e) {
                // The API expects us to NOT throw an exception if one of the
                // items is missing. However, because the memory driver has
                // the multi-call wrap the single-call, rather than vice-versa,
                // we need to catch and swallow the exception that the single-call
                // throws.
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultRevision(CollectionInterface $collection, string $uuid, string $language, string $revision)
    {

        $is_related_revision = function($item) use ($uuid, $language, $revision) {
            return $item['uuid'] == $uuid
            && $item['language'] == $language;
        };

        $this->storage->update($is_related_revision, function(&$item) use($revision) {
            $item['default_rev'] = $item['revision'] == $revision;
        });
    }


    /**
     * {@inheritdoc}
     */
    public function persist(CollectionInterface $collection, MutableDocumentInterface $document, bool $setDefault)
    {
        $this->storage->insert([
            'uuid' => $document->uuid(),
            'revision' => $document->revision(),
            'parent_rev' => $document->parent(),
            'language' => $document->language(),
            'document' => $document,
            'title' => $document->title(),
            'latest' => true,
            'archived' => false,
            'timestamp' => new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            'default_rev' => (int)$setDefault,
        ]);

        $is_related_doc = function($item) use ($document) {
            return $item['uuid'] == $document->uuid()
                && $item['language'] == $document->language()
                && $item['revision'] != $document->revision();
        };

        // Set all revisions of this Document of the same language to not be
        // the latest, except the one we just saved as the latest.
        $this->storage->update($is_related_doc, function (&$item) {
            $item['latest'] = false;
        });

        if ($setDefault) {
            // If the Document we just saved was flagged as the default, set
            // all other revisions to not be the default (for the same document
            // and language).
            $this->storage->update($is_related_doc, function(&$item) {
                $item['default_rev'] = false;
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setArchived(CollectionInterface $collection, string $revision) {
        $this->storage->update(function ($item) use ($revision) {
            return $item['revision'] == $revision;
        }, function(&$item) {
            $item['archived'] = true;
        });
    }
}
