<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;

use Crell\Document\Collection\CollectionDriverInterface;
use Crell\Document\Collection\DoctrineMySQLCollectionDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DoctrineMySQLDriverTest extends DriverTestBase
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var string
     */
    protected $databaseName = 'document_experiment';

    /**
     * @var string
     */
    protected $databaseUser = 'test';

    /**
     * @var string
     */
    protected $databasePass = 'test';

    public function setUp()
    {
        parent::setUp();

        $this->resetDatabase();

        $this->getDriver()->initializeSchema($this->collection);
    }

    /**
     * Ensures that the test database exists and is empty.
     *
     * @return bool
     *   True if the database didn't exist yet, False if we had to drop and
     *   recreate it.
     */
    protected function resetDatabase()
    {
        $connectionParams = [
            'user' => $this->databaseUser,
            'password' => $this->databasePass,
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        ];
        $conn = DriverManager::getConnection($connectionParams);

        $schemaManager = $conn->getSchemaManager();
        $databases = $schemaManager->listDatabases();
        if (!in_array($this->databaseName, $databases)) {
            $schemaManager->createDatabase($this->databaseName);
            return true;
        } else {
            $schemaManager->dropDatabase($this->databaseName);
            $schemaManager->createDatabase($this->databaseName);
            return false;
        }
    }

    /**
     * Returns the live database connection.
     *
     * This method assumes that resetDatabase() has already been called to
     * ensure the database itself exists. It will also lazy-instantiate the
     * connection as needed.
     *
     * @return Connection
     *   The live database connection.
     */
    protected function getConnection() : Connection
    {
        if (empty($this->conn)) {
            $connectionParams = [
                'dbname' => $this->databaseName,
                'user' => $this->databaseUser,
                'password' => $this->databasePass,
                'host' => 'localhost',
                'driver' => 'pdo_mysql',
            ];

            $this->conn = DriverManager::getConnection($connectionParams);
        }

        return $this->conn;
    }

    public function tearDown()
    {
        $schemaManager = $this->getConnection()->getSchemaManager();

        $databases = $schemaManager->listDatabases();
        if (in_array($this->databaseName, $databases)) {
            // @todo For now, don't clean up so that we can debug the DB afterward.
            // This should be restored eventually.
            //$schemaManager->dropDatabase($this->databaseName);
        }

        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver() : CollectionDriverInterface
    {
        return new DoctrineMySQLCollectionDriver($this->getConnection());
    }


}
