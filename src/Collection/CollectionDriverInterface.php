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
     * @param Collection $collection
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
    public function loadMultipleDefaultRevisionData(Collection $collection, array $uuids) : \Iterator;

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
