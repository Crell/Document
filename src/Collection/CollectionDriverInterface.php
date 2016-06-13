<?php


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
     * @param CollectionInterface $collection
     * @param string $uuid
     *
     * @return array
     */
    public function loadLatestRevisionData(CollectionInterface $collection, string $uuid) : array;

    /**
     *
     * @param CollectionInterface $collection
     * @param $uuid
     *
     * @return array
     */
    public function loadDefaultRevisionData(CollectionInterface $collection, string $uuid) : array;

    /**
     *
     * @param CollectionInterface $collection
     * @param string $uuid
     * @param string $revision
     *
     * @return array
     */
    public function loadRevisionData(CollectionInterface $collection, string $uuid, string $revision) : array;

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
