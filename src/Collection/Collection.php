<?php

namespace Crell\Document\Collection;

use Crell\Document\Document\Document;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

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
            $table->addColumn('uuid', 'string', [
                'length' => 36
            ]);

            $schemaManager->createTable($table);
        }
    }

    protected function tableName() : string {
        return 'collection_' . $this->name;
    }

    public function load(string $id) : Document {

        return new Document($id);

    }

}
