<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\DocumentInterface;
use Crell\Document\Document\MutableDocumentInterface;

class MemoryCollectionDriver implements CollectionDriverInterface {

    protected $storage = [];

    /**
     * {@inheritdoc}
     */
    public function initializeSchema(CollectionInterface $collection)
    {
        $this->storage = [];
    }

    /**
     * {@inheritdoc}
     */
    public function loadLatestRevisionData(CollectionInterface $collection, string $uuid) : array
    {
        $result = $this->find($this->storage, function(array $item) use ($collection, $uuid) {
            return $item['uuid'] == $uuid && $item['latest'] == true && $item['language'] == $collection->language();
        });
        return current(iterator_to_array($result));
    }

    /**
     * {@inheritdoc}
     */
    public function loadDefaultRevisionData(CollectionInterface $collection, string $uuid) : array
    {
        $result = $this->find($this->storage, function(array $item) use ($collection, $uuid) {
            return $item['uuid'] == $uuid
                && $item['default_rev'] == true
                && $item['language'] == $collection->language()
                && $item['archived'] == false;
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
        $result = $this->find($this->storage, function(array $item) use ($uuid, $revision) {
            return $item['uuid'] == $uuid && $item['revision'] == $revision;
        });
        return current(iterator_to_array($result));
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultipleDefaultRevisionData(CollectionInterface $collection, array $uuids) : \Iterator
    {
        foreach ($uuids as $uuid) {
            try {
                $record = $this->loadDefaultRevisionData($collection, $uuid);
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

        array_walk($this->storage, function(&$item) use($is_related_revision, $revision) {
            if ($is_related_revision($item)) {
                $item['default_rev'] = $item['revision'] == $revision;
            }
        });
    }


    /**
     * {@inheritdoc}
     */
    public function persist(CollectionInterface $collection, MutableDocumentInterface $document, bool $setDefault)
    {
        $this->storage[] = [
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
        ];

        $is_related_doc = function($item) use ($document) {
            return $item['uuid'] == $document->uuid()
                && $item['language'] == $document->language()
                && $item['revision'] != $document->revision();
        };

        // Set all revisions of this Document of the same language to not be
        // the latest, except the one we just saved as the latest.
        array_walk($this->storage, function(&$item) use ($is_related_doc) {
            if ($is_related_doc($item)) {
                $item['latest'] = false;
            }
        });

        if ($setDefault) {
            // If the Document we just saved was flagged as the default, set
            // all other revisions to not be the default (for the same document
            // and language).
            array_walk($this->storage, function(&$item) use ($is_related_doc) {
                if ($is_related_doc($item)) {
                    $item['default_rev'] = false;
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setArchived(CollectionInterface $collection, string $revision) {
        array_walk($this->storage, function(&$item) use ($collection, $revision) {
            if ($item['revision'] == $revision) {
                $item['archived'] = true;
            }
        });
    }

    /**
     * Filters an iterable by a specified callable filter.
     *
     * @param \Traversable|array $collection
     *   The collection to filter.
     * @param callable $filter
     *   The filter function to apply.
     *
     * @return \Generator
     */
    protected function find($collection, callable $filter)
    {
        foreach ($collection as $item) {
            if ($filter($item)) {
                yield $item;
            }
        }
    }
}
