<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\MutableDocumentInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

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
            $table = new Table($table);
            $table->addColumn('revision', 'string', [
                'length' => 36,
            ]);
            $table->addColumn('uuid', 'string', [
                'length' => 36,
            ]);
            $table->addColumn('latest', 'boolean');
            // default_rev is named differently because "default" is a reserved word.
            $table->addColumn('default_rev', 'boolean');
            $table->addColumn('language', 'string', [
                'length' => 12,
            ]);
            $table->addColumn('created', 'datetime');

            $table->addColumn('document', 'json_array', [
                'length' => 16777215, // This size triggers a MEDIUMTEXT field on MySQL. Postgres will use native JSON.
            ]);
            $table->setPrimaryKey(['revision']);
            $table->addIndex(['uuid']);

            $schemaManager->createTable($table);
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
        return $this->loadMultipleDefaultRevisionData($collection, [$uuid])->current();
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
     *
     *
     * @todo Get rid of the single load method in favor of this one.
     *
     * @param \Crell\Document\Collection\Collection $collection
     * @param array $uuids
     *
     * @return \Iterator
     */
    public function loadMultipleDefaultRevisionData(Collection $collection, array $uuids) : \Iterator
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
    public function persist(CollectionInterface $collection, MutableDocumentInterface $document, bool $setDefault)
    {
        $this->conn->transactional(function (Connection $conn) use ($collection, $document, $setDefault) {

            $table = $this->tableName($collection->name());

            $conn->insert($table, [
                'uuid' => $document->uuid(),
                'revision' => $document->revision(),
                'latest' => true,
                'default_rev' => (int)$setDefault,
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
