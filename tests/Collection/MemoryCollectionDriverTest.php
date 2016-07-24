<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;


use Crell\Document\Collection\Collection;
use Crell\Document\Collection\CollectionDriverInterface;
use Crell\Document\Collection\MemoryCollectionDriver;
use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;

class MemoryCollectionDriverTest extends DriverTestBase
{
    /**
     * {@inheritdoc}
     */
    protected function getDriver() : CollectionDriverInterface {
        return new MemoryCollectionDriver();
    }


}
