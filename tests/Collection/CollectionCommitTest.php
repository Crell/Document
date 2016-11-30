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

    public function testSingleDocumentCommit()
    {
        $collection = $this->getCollection();

        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();

        $commit = $collection->createCommit()->withRevision($doc1);
        $collection->saveCommit($commit);

        $doc2 = $collection->newRevision($uuid);

        $commit = $collection->createCommit('Commit message', 'Arthur the Author')->withRevision($doc1);
        $collection->saveCommit($commit);

        $doc3 = $collection->newRevision($uuid);

        $this->assertEquals($doc1->uuid(), $doc2->uuid());
        $this->assertEquals($doc1->uuid(), $doc3->uuid());
        $this->assertNotEquals($doc1->revision(), $doc2->revision());
        $this->assertNotEquals($doc1->revision(), $doc3->revision());
        $this->assertNotEquals($doc2->revision(), $doc3->revision());
    }

    /*
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
    */

}
