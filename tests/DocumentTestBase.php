<?php

declare (strict_types = 1);

namespace Crell\Document\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DocumentTestBase extends \PHPUnit_Framework_TestCase {

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

        $this->ensureDatabase();

        $connectionParams = [
            'dbname' => $this->databaseName,
            'user' => $this->databaseUser,
            'password' => $this->databasePass,
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        ];

        $this->conn = DriverManager::getConnection($connectionParams);
    }

    protected function ensureDatabase() : bool
    {
        $connectionParams = [
            'user' => $this->databaseUser,
            'password' => $this->databasePass,
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        ];

        $conn = DriverManager::getConnection($connectionParams);

        $databases = $conn->getSchemaManager()->listDatabases();
        if (!in_array($this->databaseName, $databases)) {
            $conn->getSchemaManager()->createDatabase($this->databaseName);

            return true;
        } else {
            $conn->getSchemaManager()->dropDatabase($this->databaseName);
            $conn->getSchemaManager()->createDatabase($this->databaseName);

            return false;
        }
    }

    public function tearDown()
    {
        $databases = $this->conn->getSchemaManager()->listDatabases();
        if (in_array($this->databaseName, $databases)) {
            //$this->conn->getSchemaManager()->dropDatabase($this->databaseName);
        }

        parent::tearDown(); // TODO: Change the autogenerated stub
    }

}