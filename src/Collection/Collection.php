<?php

declare (strict_types = 1);

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Ramsey\Uuid\Uuid;

class Collection
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $language;

    public function __construct(string $name, Connection $conn, $language = 'en')
    {
        $this->name = $name;
        $this->conn = $conn;
        $this->language = $language;
    }

    public function initializeSchema()
    {
        $schemaManager = $this->conn->getSchemaManager();

        if (!$schemaManager->tablesExist($this->tableName())) {
            $table = new Table($this->tableName());
            $table->addColumn('revision', 'string', [
                'length' => 36,
            ]);
            $table->addColumn('uuid', 'string', [
                'length' => 36,
            ]);
            $table->addColumn('latest', 'boolean');
            $table->addColumn('language', 'string', [
                'length' => 12,
            ]);
            $table->setPrimaryKey(['revision']);
            $table->addIndex(['uuid']);

            $schemaManager->createTable($table);
        }
    }

    public function forLanguage(string $language) : self
    {
        $new = clone $this;
        $new->language = $language;

        return $new;
    }

    protected function tableName() : string
    {
        return 'collection_'.$this->name;
    }

    public function createDocument() : Document
    {
        $uuid = Uuid::uuid4()->toString();
        $revision = Uuid::uuid4()->toString();

        return new Document($uuid, $revision, $this->language);
    }

    public function load(string $uuid) : Document
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT * FROM '.$this->tableName().' WHERE uuid = :uuid AND latest = :latest AND language = :language', [
            ':uuid' => $uuid,
            ':latest' => 1,
            ':language' => $this->language,
        ]);

        $data = $statement->fetch();

        return new Document($data['uuid'], $data['revision'], $data['language']);
    }

    public function loadRevision(string $uuid, string $revision) : Document
    {
        // @todo There's probably a better/safer way to do this.
        $statement = $this->conn->executeQuery('SELECT * FROM '.$this->tableName().' WHERE uuid = :uuid AND revision = :revision', [
            ':uuid' => $uuid,
            ':revision' => $revision,
        ]);

        $data = $statement->fetch();

        return new Document($data['uuid'], $data['revision'], $data['language']);
    }

    public function save(Document $document)
    {
        $this->conn->transactional(function () use ($document) {

            // @todo Figure out how to use this properly.
            $revision = Uuid::uuid4()->toString();
            $this->conn->insert($this->tableName(), [
                'uuid' => $document->uuid(),
                'revision' => $revision,
                'latest' => true,
                'language' => $document->language(),
            ]);
            $this->conn->executeUpdate('UPDATE '.$this->tableName().' SET latest = :latest WHERE uuid = :uuid AND language = :language AND NOT revision = :revision ', [
                ':latest' => 0,
                ':uuid' => $document->uuid(),
                ':language' => $document->language(),
                ':revision' => $revision,
            ]);
        });
    }
}
