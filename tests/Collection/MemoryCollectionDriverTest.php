<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;


use Crell\Document\Driver\CollectionDriverInterface;
use Crell\Document\Driver\Memory\MemoryCollectionDriver;

class MemoryCollectionDriverTest extends DriverTestBase
{
    /**
     * {@inheritdoc}
     */
    protected function getDriver() : CollectionDriverInterface {
        return new MemoryCollectionDriver();
    }


}
