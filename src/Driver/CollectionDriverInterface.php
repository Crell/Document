<?php

declare (strict_types = 1);

namespace Crell\Document\Driver;

use Crell\Document\Collection\CollectionInterface;
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
     * @param CollectionInterface $collection
     *   The collection for which to run this driver.
     * @param string $uuid
     *
     * @return array
     */
    public function loadLatestRevisionData(CollectionInterface $collection, string $uuid) : array;

    /**
     *
     * @param CollectionInterface $collection
     *   The collection for which to run this driver.
     * @param string $uuid
     *   The UUID of the document's record to load.
     * @param bool $includeArchived
     *   True to allow an archived revision to be loaded, False otherwise.
     *
     * @return array
     */
    public function loadDefaultRevisionData(CollectionInterface $collection, string $uuid, bool $includeArchived = false) : array;

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
     * Note: The returned order of IDs is NOT guaranteed. If a particular ID was
     * not present, it will be omitted.
     *
     * @param CollectionInterface $collection
     *   The collection for which to run this driver.
     * @param array $uuids
     *   An array of UUIDs to load.
     * @param bool $includeArchived
     *   True to allow an archived revision to be loaded, False otherwise.
     *
     * @return \Iterator
     *   An iterator of the specifed document records. It may be empty if no
     *   records were found.
     */
    public function loadMultipleDefaultRevisionData(CollectionInterface $collection, array $uuids, bool $includeArchived = false) : \Iterator;

    /**
     * Sets the revision of an entity that should be considered the default to load.
     *
     * @param CollectionInterface $collection
     *   The collection for which to run this driver.
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
     *   The collection for which to run this driver.
     * @param MutableDocumentInterface[] $documents
     *   The documents to persist.
     * @param bool $setDefault
     *   True to set this revision as the default revision. False if not.
     *
     * @return mixed
     */
    public function persist(CollectionInterface $collection, array $documents, bool $setDefault);

    /**
     * Marks the specified revision of a document as archived.
     *
     * It is a business error to ever call this on a method that is not the
     * default revision, but that error handling is the responsibility of
     * the Collection.
     *
     * @param CollectionInterface $collection
     *   The collection for which to run this driver.
     * @param string $revision
     *   The document to archive.
     */
    public function setArchived(CollectionInterface $collection, string $revision);

    /**
     * Creates a new branch, from a specified existing branch.
     *
     * @param CollectionInterface $collection
     *   The collection for which to run this driver.
     * @param string $branch
     *   The branch to create.
     * @return void
     */
    public function makeBranch(CollectionInterface $collection, string $branch, string $parent = 'master');
}
