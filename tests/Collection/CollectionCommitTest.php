<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;

use Crell\Document\Collection\Collection;
use Crell\Document\Collection\CollectionInterface;
use Crell\Document\Document\DocumentNotFoundException;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Driver\Memory\MemoryCollectionDriver;


class CollectionCommitTest extends \PHPUnit_Framework_TestCase
{

    protected function getCollection() : CollectionInterface
    {
        $driver = new MemoryCollectionDriver();
        $collection = new Collection('test', $driver);
        $collection->initializeSchema();

        return $collection;
    }

    public function testThing()
    {
        $collection = $this->getCollection();

        $id = '123';

        $collection = $collection->atCommit($id);

        $collection = $collection->atBranch($id);

        $collection->createBranch($name);

        $commit = new Commit($revisions, $author, $message);

        $commit->addRevision()->addRevision()->setAuthor()->setMessage();

        $collection->addCommit($commit);
    }

}
