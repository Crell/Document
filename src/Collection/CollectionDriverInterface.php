<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\MutableDocumentInterface;

interface CollectionDriverInterface
{
    /**
     * Creates the schema for this collection if necessary.
     *
     * @param CollectionInterface $collection
     */
    public function initializeSchema(CollectionInterface $collection);

    /**
     *
     * @param Collection $collection
     *   The collection for which to run this driver.
     * @param string $uuid
     *
     * @return array
     */
    public function loadLatestRevisionData(CollectionInterface $collection, string $uuid) : array;

    /**
     *
     * @param Collection $collection
     *   The collection for which to run this driver.
     * @param $uuid
     *
     * @return array
     */
    public function loadDefaultRevisionData(CollectionInterface $collection, string $uuid) : array;

    /**
     *
     * @param CollectionInterface $collection
     *   The collection for which to run this driver.
     * @param string $uuid
     * @param string $revision
     *
     * @return array
     */
    public function loadRevisionData(CollectionInterface $collection, string $uuid, string $revision) : array;

    /**
     * Returns an iterable of records with the specified IDs.
     *
     * Note: The returned order of IDs is NOT guaranteed.
     *
     * @todo Get rid of the single load method in favor of this one.
     *
     * @param Collection $collection
     *   The collection for which to run this driver.
     * @param array $uuids
     *   An array of UUIDs to load.
     *
     * @return \Iterator
     *   An iterator of the specifed document records.
     */
    public function loadMultipleDefaultRevisionData(CollectionInterface $collection, array $uuids) : \Iterator;

    /**
     * Sets the revision of an entity that should be considered the default to load.
     *
     * @param CollectionInterface $collection
     * @param string $uuid
     *   The UUID of the document to change.
     * @param string $language
     *   The language within which to set the default.
     * @param string $revision
     *   The revision that should be made default.
     *
     */
    public function setDefaultRevision(CollectionInterface $collection, string $uuid, string $language, string $revision);

    /**
     *
     *
     * @param CollectionInterface $collection
     * @param MutableDocumentInterface $document
     * @param bool $setDefault
     *
     * @return mixed
     */
    public function persist(CollectionInterface $collection, MutableDocumentInterface $document, bool $setDefault);
}
