<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Driver\Git;

use Crell\Document\Driver\CollectionDriverInterface;
use Crell\Document\Driver\Git\GitCollectionDriver;
use Crell\Document\Test\Driver\DriverTestBase;


class GitCollectionDriverTest extends DriverTestBase
{
    /**
     * {@inheritdoc}
     */
    protected function getDriver() : CollectionDriverInterface
    {
        $path = dirname(realpath(__FILE__)) . '/repository.git';
        $driver = new GitCollectionDriver($path);

        $driver->initializeSchema($this->collection);

        return $driver;
    }

}
