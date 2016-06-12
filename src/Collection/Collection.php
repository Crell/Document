<?php

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Ramsey\Uuid\Uuid;

class Collection
{
    /**
     *
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     *
     *
     * @var string
     */
    protected $name;

    public function __construct(string $name, Connection $conn) {
        $this->name = $name;
        $this->conn = $conn;
    }

    public function initializeSchema()
    {
        $schemaManager = $this->conn->getSchemaManager();

        if (! $schemaManager->tablesExist($this->tableName())) {

            $table = new Table($this->tableName());
            $table->addColumn('revision', 'string', [
                'length' => 36
            ]);
            $table->addColumn('uuid', 'string', [
                'length' => 36
            ]);
            $table->addColumn('latest', 'boolean');
            $table->setPrimaryKey(['revision']);
            $table->addIndex(['uuid']);

            $schemaManager->createTable($table);
        }
    }

    protected function tableName() : string {
        return 'collection_' . $this->name;
    }

    public function createDocument() : Document {
        $uuid = Uuid::uuid4()->toString();
        $revision = Uuid::uuid4()->toString();

        return new Document($uuid, $revision);
    }

    public function load(string $uuid) : Document {

        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery("SELECT * FROM " . $this->tableName() . " WHERE uuid = :uuid AND latest = :latest", [
            ':uuid' => $uuid,
            ':latest' => 1,
        ]);

        $data = $statement->fetch();

        return new Document($data['uuid'], $data['revision']);
    }

    public function save(Document $document) {
        $this->conn->transactional(function () use ($document) {

            // @todo Figure out how to use this properly.
            $revision = Uuid::uuid4()->toString();
            $this->conn->insert($this->tableName(), [
                'uuid' => $document->uuid(),
                'revision' => $revision,
                'latest' => TRUE,
            ]);
            $this->conn->executeUpdate("UPDATE " . $this->tableName() . " SET latest = :latest WHERE uuid = :uuid AND NOT revision = :revision ", [
                ':latest' => 0,
                ':uuid' => $document->uuid(),
                ':revision' => $revision,
            ]);
        });
    }

}
