<?php


namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Crell\Document\Document\LoadableDocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Ramsey\Uuid\Uuid;

interface CollectionDriverInterface
{
    /**
     * Creates the schema for this collection if necessary.
     *
     * @param Collection $collection
     */
    public function initializeSchema(Collection $collection);

    /**
     *
     * @param Collection $collection
     * @param string $uuid
     *
     * @return array
     */
    public function loadLatestRevisionData(Collection $collection, string $uuid) : array;

    /**
     *
     * @param Collection $collection
     * @param $uuid
     *
     * @return array
     */
    public function loadDefaultRevisionData(Collection $collection, string $uuid) : array;

    /**
     *
     * @param Collection $collection
     * @param string $uuid
     * @param string $revision
     *
     * @return array
     */
    public function loadRevisionData(Collection $collection, string $uuid, string $revision) : array;

    /**
     *
     *
     * @param Collection $collection
     * @param MutableDocumentInterface $document
     * @param bool $setDefault
     *
     * @return mixed
     */
    public function persist(Collection $collection, MutableDocumentInterface $document, bool $setDefault);
}
