<?php

declare(strict_types = 1);

namespace Crell\Document\Test\Collection;


use Crell\Document\Collection\Collection;
use Crell\Document\Collection\CollectionInterface;
use Crell\Document\Driver\Memory\MemoryCollectionDriver;

class CollectionBranchTest extends \PHPUnit_Framework_TestCase
{

    protected function getCollection() : CollectionInterface
    {
        $driver = new MemoryCollectionDriver();
        $collection = new Collection('test', $driver);
        $collection->initializeSchema();

        return $collection;
    }

    public function testCreateBranch()
    {
        $collection_master = $this->getCollection();

        $collection_newbranch = $collection_master->makeBranch('newbranch');

        $this->assertEquals('master', $collection_master->branch());
        $this->assertEquals('newbranch', $collection_newbranch->branch());
    }

    public function testSaveDocumentOnNonMasterBranch()
    {
        $collection_master = $this->getCollection();

        $doc1 = $collection_master->createDocument();
        $uuid = $doc1->uuid();
        $collection_master->save($doc1);

        $collection_newbranch = $collection_master->makeBranch('newbranch');

        $doc2 = $collection_master->newRevision($uuid);

        $collection_newbranch->save($doc2);

        $collection_master->load($uuid);

        $this->assertNotEquals($collection_master->load($uuid)->revision(), $collection_newbranch->load($uuid)->revision());
    }

}
