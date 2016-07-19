<?php

declare (strict_types = 1);

namespace Crell\Document\Test;

use Crell\Document\Collection\Collection;
use Crell\Document\Collection\CollectionDriverInterface;
use Crell\Document\Collection\DoctrineMySQLCollectionDriver;
use Crell\Document\Document\MutableDocumentInterface;

class LiveCollectionTest extends DocumentTestBase
{

    /**
     * @var CollectionDriverInterface
     */
    protected $driver;

    public function setUp()
    {
        parent::setUp();

        $this->driver = new DoctrineMySQLCollectionDriver($this->conn);
    }

    public function testInitializeCollection()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();
    }

    public function testSaveAndLoad()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();

        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc2 = $collection->newRevision($uuid);

        $collection->save($doc2);

        $doc3 = $collection->newRevision($uuid);

        $this->assertEquals($doc1->uuid(), $doc2->uuid());
        $this->assertEquals($doc1->uuid(), $doc3->uuid());
        $this->assertNotEquals($doc1->revision(), $doc2->revision());
        $this->assertNotEquals($doc1->revision(), $doc3->revision());
        $this->assertNotEquals($doc2->revision(), $doc3->revision());
    }

    public function testLoadOldRevision()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();

        // Save one revision.
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        // Save a second revions.
        $doc2_mut = $collection->newRevision($uuid);
        $collection->save($doc2_mut);

        // Now try to reload the first revision.
        $old_revision = $collection->loadRevision($uuid, $doc1->revision());

        // Old revisions should never be mutable.
        // @todo This may be a wrong assumption.
        $this->assertNotTrue($old_revision instanceof MutableDocumentInterface);
        $this->assertEquals($doc1->uuid(), $old_revision->uuid());
        $this->assertEquals($doc1->revision(), $old_revision->revision());
    }

    public function testLanguage()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc1_en = $collection->newRevision($uuid);

        $doc1_fr = $doc1_en->asLanguage('fr');

        $collection->save($doc1_fr);

        $collection_fr = $collection->forLanguage('fr');
        $doc1_fr = $collection_fr->load($uuid);

        $this->assertEquals($doc1_en->uuid(), $doc1_fr->uuid());
        $this->assertNotEquals($doc1_en->revision(), $doc1_fr->revision());
        $this->assertEquals('en', $doc1_en->language());
        $this->assertEquals('fr', $doc1_fr->language());
    }

    public function testDefault()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        // Now make a new non-default revision, aka a forward revision.
        $doc2 = $collection->newRevision($uuid);
        $collection->save($doc2, false);

        // This should get the default revision, aka be the same as $doc1.
        $default = $collection->load($uuid);
        $latest = $collection->loadLatestRevision($uuid);

        $this->assertEquals($doc1->uuid(), $default->uuid());
        $this->assertEquals($doc1->revision(), $default->revision());
    }

    public function testLatest()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        // Now make a new non-default revision, aka a forward revision.
        $doc2 = $collection->newRevision($uuid);
        $collection->save($doc2, false);

        // This should get the most recent revision, aka be the same as $doc2.
        $latest = $collection->loadLatestRevision($uuid);

        $this->assertEquals($doc2->uuid(), $latest->uuid());
        $this->assertEquals($doc2->revision(), $latest->revision());
    }

    public function testTimestampIsSaved()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        // I hate this, but I don't know how else to compare the timestamps.
        sleep(1);

        $doc2 = $collection->newRevision($uuid);

        $collection->save($doc2);

        $load1 = $collection->loadRevision($uuid, $doc1->revision());
        $load2 = $collection->loadRevision($uuid, $doc2->revision());

        $this->assertEquals($load1->uuid(), $load2->uuid());
        $this->assertNotEquals($load1->revision(), $load2->revision());
        $this->assertNotEquals($load1->timestamp()->format('c'), $load2->timestamp()->format('c'));
    }

    public function testLoadMultiple()
    {
        $collection = new Collection('coll', $this->driver);
        $collection->initializeSchema();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid1 = $doc1->uuid();
        $collection->save($doc1);

        // Save a second new Document.
        $doc2 = $collection->createDocument();
        $uuid2 = $doc2->uuid();
        $collection->save($doc2);

        // Save a second new Document.
        $doc3 = $collection->createDocument();
        $uuid3 = $doc3->uuid();
        $collection->save($doc3);

        $docs = $collection->loadMultiple([$uuid1, $uuid2]);

        $this->assertCount(2, $docs);

        $docs_array = iterator_to_array($docs);

        $keys = array_keys($docs_array);
        $this->assertContains($uuid1, $keys);
        $this->assertContains($uuid2, $keys);
        $this->assertNotContains($uuid3, $keys);

        $this->assertEquals($uuid1, $docs_array[$uuid1]->uuid());
        $this->assertEquals($uuid2, $docs_array[$uuid2]->uuid());
    }

}
