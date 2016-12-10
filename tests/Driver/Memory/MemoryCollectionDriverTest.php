<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Driver\Memory;


use Crell\Document\Driver\CollectionDriverInterface;
use Crell\Document\Driver\Memory\MemoryCollectionDriver;
use Crell\Document\Test\Driver\DriverTestBase;

class MemoryCollectionDriverTest extends DriverTestBase
{
    /**
     * {@inheritdoc}
     */
    protected function getDriver() : CollectionDriverInterface {
        return new MemoryCollectionDriver();
    }


}
