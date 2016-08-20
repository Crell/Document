<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\MutableDocumentInterface;
use Doctrine\DBAL\Connection;

class DoctrineMySQLCollectionDriver implements CollectionDriverInterface
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeSchema(CollectionInterface $collection)
    {
        $schemaManager = $this->conn->getSchemaManager();

        $table = $this->tableName($collection->name());

        if (!$schemaManager->tablesExist($table)) {
            $schemaManager->createTable(new DoctrineCollectionTable($table));
        }
    }

    /**
     *  Returns the name of the main table for a collection.
     *
     * @param string $collection
     *   The name of the collection
     * @return string
     *   The name of the table.
     */
    protected function tableName(string $collection) : string
    {
        return 'collection_' . $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function loadDefaultRevisionData(CollectionInterface $collection, string $uuid) : array
    {
        $value = $this->loadMultipleDefaultRevisionData($collection, [$uuid])->current();

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
    public function loadLatestRevisionData(CollectionInterface $collection, string $uuid) : array
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName($collection->name()) . ' WHERE uuid = :uuid AND latest = :latest AND language = :language', [
            ':uuid' => $uuid,
            ':latest' => 1,
            ':language' => $collection->language(),
        ]);

        return $this->decodeSerializedDocument($statement->fetchColumn());
    }

    /**
     * {@inheritdoc}
     */
    public function loadRevisionData(CollectionInterface $collection, string $uuid, string $revision) : array
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName($collection->name()) . ' WHERE uuid = :uuid AND revision = :revision', [
            ':uuid' => $uuid,
            ':revision' => $revision,
        ]);

        $json = $statement->fetchColumn();
        if ($json === false) {
            // @todo Figure out what to do with this.
            throw new \Exception();
        }

        return $this->decodeSerializedDocument($json);
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultipleDefaultRevisionData(CollectionInterface $collection, array $uuids) : \Iterator
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT document FROM ' . $this->tableName($collection->name()) . ' WHERE uuid IN (?) AND default_rev = ? AND language = ?', [
            $uuids,
            1,
            $collection->language(),
        ], [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_STR]);

        foreach ($statement as $record) {
            $data = json_decode($record['document'], true);
            $data['timestamp'] = new \DateTimeImmutable($data['timestamp']);
            unset($data['created']);
            yield $data['uuid'] => $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultRevision(CollectionInterface $collection, string $uuid, string $language, string $revision)
    {
        $this->conn->transactional(function (Connection $conn) use ($collection, $uuid, $language, $revision) {
            $table = $this->tableName($collection->name());

            // If the Document we just saved was flagged as the default, set
            // all other revisions to not be the default (for the same document
            // and language).

            // @todo Fold this into a single query.
            $this->conn->executeUpdate('UPDATE '.$table.' SET default_rev = :default WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                ':default' => 0,
                ':uuid' => $uuid,
                ':language' => $language,
                ':revision' => $revision,
            ]);
            $this->conn->executeUpdate('UPDATE '.$table.' SET default_rev = :default WHERE uuid = :uuid AND language = :language AND revision = :revision ', [
                ':default' => 1,
                ':uuid' => $uuid,
                ':language' => $language,
                ':revision' => $revision,
            ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function persist(CollectionInterface $collection, MutableDocumentInterface $document, bool $setDefault)
    {
        $this->conn->transactional(function (Connection $conn) use ($collection, $document, $setDefault) {

            $table = $this->tableName($collection->name());

            $conn->insert($table, [
                'uuid' => $document->uuid(),
                'revision' => $document->revision(),
                'parent_rev' => $document->parent(),
                'latest' => true,
                'default_rev' => (int)$setDefault,
                'title' => $document->title(),
                'language' => $document->language(),
                'created' => $document->timestamp()->format('Y-m-d H:i:s'),
                'document' => json_encode($document),
            ]);

            // Set all revisions of this Document of the same language to not be
            // the latest, except the one we just saved as the latest.
            $conn->executeUpdate('UPDATE '.$table.' SET latest = :latest WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                ':latest' => 0,
                ':uuid' => $document->uuid(),
                ':language' => $document->language(),
                ':revision' => $document->revision(),
            ]);

            if ($setDefault) {
                // If the Document we just saved was flagged as the default, set
                // all other revisions to not be the default (for the same document
                // and language).
                $conn->executeUpdate('UPDATE '.$table.' SET default_rev = :default WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                    ':default' => 0,
                    ':uuid' => $document->uuid(),
                    ':language' => $document->language(),
                    ':revision' => $document->revision(),
                ]);
            }
        });
    }

    /**
     * Decodes a JSON serialized document back to an array.
     *
     * @param string $json
     *   The serialized JSON document to decode.
     *
     * @return array
     */
    protected function decodeSerializedDocument(string $json) : array
    {
        $data = json_decode($json, true);
        $data['timestamp'] = new \DateTimeImmutable($data['timestamp']);

        return $data;
    }
}
