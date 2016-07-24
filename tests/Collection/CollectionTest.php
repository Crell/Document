<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;

use Crell\Document\Collection\Collection;
use Crell\Document\Collection\CollectionInterface;
use Crell\Document\Collection\MemoryCollectionDriver;
use Crell\Document\Document\MutableDocumentInterface;


class CollectionTest extends \PHPUnit_Framework_TestCase
{

    protected function getCollection() : CollectionInterface
    {
        $driver = new MemoryCollectionDriver();
        $collection = new Collection('test', $driver);
        $collection->initializeSchema();

        return $collection;
    }

    public function testInitializeCollection()
    {
        $collection = $this->getCollection();
        $collection->initializeSchema();
    }

    public function testSaveAndLoad()
    {
        $collection = $this->getCollection();

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

    public function testDefaultLanguageIsEnglish()
    {
        $driver = new MemoryCollectionDriver();
        $c = new Collection('test', $driver);

        $this->assertEquals('en', $c->language());
    }

    public function testGettingAlternateLanguageCollection()
    {
        $driver = new MemoryCollectionDriver();
        $en = new Collection('test', $driver);
        $fr = $en->forLanguage('fr');

        $this->assertNotEquals($en, $fr);
        $this->assertEquals('en', $en->language());
        $this->assertEquals('fr', $fr->language());
    }


    public function testNoImmutableSave()
    {
        $collection = $this->getCollection();

        $doc1 = $collection->createDocument();

        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc2 = $collection->load($uuid);

        $this->expectException(\TypeError::class);

        $collection->save($doc2);
    }


    public function testLoadOldRevision()
    {
        $collection = $this->getCollection();

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
        $collection = $this->getCollection();

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
        $collection = $this->getCollection();

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
        $collection = $this->getCollection();

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
        $collection = $this->getCollection();

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
        $collection = $this->getCollection();

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

    public function testCreateFromLatestRevision()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $doc1->setTitle('A');
        $collection->save($doc1);

        // Make some more revisions.
        $doc2 = $collection->newRevision($uuid);
        $doc2->setTitle($doc2->title() . 'B');
        $collection->save($doc2);

        $doc3 = $collection->newRevision($uuid);
        $doc3->setTitle($doc3->title() . 'C');
        $collection->save($doc3);

        // This should get the most recent revision, aka be the same as $doc3.
        $latest = $collection->loadLatestRevision($uuid);

        // We've been concatenating the title all along, so this should show
        // that we've always used the latest revision.
        $this->assertEquals('ABC', $latest->title());
    }

    public function testCreateFromOldRevision()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $doc1->setTitle('A');
        $collection->save($doc1);

        // Make some more revisions.
        $doc2 = $collection->newRevision($uuid);
        $doc2->setTitle($doc2->title() . 'B');
        $collection->save($doc2);

        // Make this revision off of the first revision, not the second.
        $doc3 = $collection->newRevision($uuid, $doc1->revision());
        $doc3->setTitle($doc3->title() . 'C');
        $collection->save($doc3);

        // This should get the most recent revision, aka be the same as $doc3.
        $latest = $collection->loadLatestRevision($uuid);

        // Because this revision was built off of the first, not the latest,
        // its title should reflect only that path.
        $this->assertEquals('AC', $latest->title());
    }

    public function testParentRevisionIsTracked()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $rev1 = $doc1->revision();
        $doc1->setTitle('A');
        $collection->save($doc1);

        // Make some more revisions.
        $doc2 = $collection->newRevision($uuid);
        $doc2->setTitle($doc2->title() . 'B');
        $collection->save($doc2);

        // Make this revision off of the first revision, not the second.
        $doc3 = $collection->newRevision($uuid, $doc1->revision());
        $doc3->setTitle($doc3->title() . 'C');
        $collection->save($doc3);

        // This should get the most recent revision, aka be the same as $doc3.
        $latest = $collection->loadLatestRevision($uuid);

        $this->assertEquals('', $doc1->parent());
        $this->assertEquals($rev1, $doc2->parent());
        $this->assertEquals($rev1, $doc3->parent());
    }

    public function testCanSetDefaultRevision()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        // Now make a new non-default revision, aka a forward revision.
        $doc2 = $collection->newRevision($uuid);
        $collection->save($doc2, false);

        // This should get the most default revision, aka be the same as $doc1.
        $latest = $collection->load($uuid);

        $this->assertEquals($doc1->uuid(), $latest->uuid());
        $this->assertEquals($doc1->revision(), $latest->revision());

        // Now change what the latest revision is and test again.
        $collection->setDefaultRevision($uuid, $doc2->language(), $doc2->revision());

        $latest = $collection->load($uuid);
        $this->assertEquals($doc2->uuid(), $latest->uuid());
        $this->assertEquals($doc2->revision(), $latest->revision());
    }

}
