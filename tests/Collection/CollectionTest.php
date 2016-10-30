<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;

use Crell\Document\Collection\Collection;
use Crell\Document\Collection\CollectionInterface;
use Crell\Document\Document\DocumentNotFoundException;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Driver\Memory\MemoryCollectionDriver;


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

        // Save a third new Document.
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


    public function testSingleDocumentNotFound()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        try {
            // There is clearly no such UUID.
            $collection->load('123');
        }
        catch (DocumentNotFoundException $e) {
            $this->assertEquals($collection->name(), $e->getCollectionName());
            $this->assertEquals('123', $e->getUuid());
            $this->assertEquals($collection->language(), $e->getLanguage());
            return;
        }

        $this->fail('No exception thrown or wrong exception thrown');
    }

    public function testSomeDocumentsFound()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        $docs = $collection->loadMultiple([$uuid, '123']);
        $doc_array = iterator_to_array($docs);

        $this->assertCount(1, $doc_array);
        $this->assertEquals($uuid, key($doc_array));
    }

    public function testNoDocumentsFound()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        $docs = $collection->loadMultiple(['123', '456']);
        $doc_array = iterator_to_array($docs);

        $this->assertCount(0, $doc_array);
    }

    public function testOrderedSet()
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

        // Save a third new Document.
        $doc3 = $collection->createDocument();
        $uuid3 = $doc3->uuid();
        $collection->save($doc3);

        $docs = $collection->loadMultiple([$uuid3, $uuid1, $uuid2]);

        $this->assertCount(3, $docs);

        $docs_array = iterator_to_array($docs);

        $keys = array_keys($docs_array);
        $this->assertEquals([$uuid3, $uuid1, $uuid2], $keys);
    }

    public function testArchiveOne()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid1 = $doc1->uuid();
        $collection->save($doc1);

        // Now archive it.
        $collection->archive($doc1);

        // Add a second for good measure.
        $doc2 = $collection->createDocument();
        $uuid2 = $doc2->uuid();
        $collection->save($doc2);

        // It should now behave as if it doesn't exist.
        try {
            $collection->load($uuid1);
            $this->fail('No exception thrown or wrong exception thrown');
        }
        catch (DocumentNotFoundException $e) {
            $this->assertEquals($collection->name(), $e->getCollectionName());
            $this->assertEquals($uuid1, $e->getUuid());
            $this->assertEquals($collection->language(), $e->getLanguage());
        }

        // But the other document should still be available.
        $collection->load($uuid2);
    }

    public function testArchiveLoadMultiple()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid1 = $doc1->uuid();
        $collection->save($doc1);

        // Now archive it.
        $collection->archive($doc1);

        // Add a second for good measure.
        $doc2 = $collection->createDocument();
        $uuid2 = $doc2->uuid();
        $collection->save($doc2);

        $docs = $collection->loadMultiple([$uuid1, $uuid2]);
        $doc_array = iterator_to_array($docs);

        $this->assertCount(1, $doc_array);
        $this->assertEquals($uuid2, key($doc_array));
    }

    public function testLoadArchived()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid1 = $doc1->uuid();
        $collection->save($doc1);

        // Now archive it.
        $collection->archive($doc1);

        // Load it, allowing for achived.  This will throw an exception if
        // not found.
        $collection->load($uuid1, true);
    }

    public function testLoadArchivedMultiple()
    {
        $collection = $this->getCollection();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid1 = $doc1->uuid();
        $collection->save($doc1);

        // Now archive it.
        $collection->archive($doc1);

        // Add a second for good measure.
        $doc2 = $collection->createDocument();
        $uuid2 = $doc2->uuid();
        $collection->save($doc2);

        $docs = $collection->loadMultiple([$uuid1, $uuid2], true);
        $doc_array = iterator_to_array($docs);

        $this->assertCount(2, $doc_array);
    }
}
