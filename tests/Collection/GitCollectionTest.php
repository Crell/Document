<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;

use Crell\Document\Collection\Collection;
use Crell\Document\Collection\CollectionInterface;
use Crell\Document\Collection\GitCollection;
use Crell\Document\Document\DocumentNotFoundException;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Driver\Memory\MemoryCollectionDriver;
use Crell\Document\GitStore\Repository;
use Crell\Document\Test\GitStore\GitRepositoryTestUtils;


class GitCollectionTest extends \PHPUnit_Framework_TestCase
{
    use GitRepositoryTestUtils;

    protected function getCollection($debug = false) : CollectionInterface
    {

        $driver = $this->getRepository($debug);

        $collection = new GitCollection('test', $driver);
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
        $collection = $this->getCollection(true);

        $doc1 = $collection->createDocument()->setTitle('Doc 1');

        $uuid = $doc1->uuid();

        $collection->save($doc1);
        $doc1 = $collection->load($uuid);

        $doc2 = $collection->newRevision($uuid)->setTitle('Doc 2');

        $collection->save($doc2);
        $doc2 = $collection->load($uuid);

        $doc3 = $collection->newRevision($uuid)->setTitle('Doc 3');
        $collection->save($doc3);
        $doc3 = $collection->load($uuid);

        $this->assertEquals($doc1->uuid(), $doc2->uuid());
        $this->assertEquals($doc1->uuid(), $doc3->uuid());
        $this->assertEquals('Doc 1', $doc1->title());
        $this->assertEquals('Doc 2', $doc2->title());
        $this->assertEquals('Doc 3', $doc3->title());
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

        $doc1 = $collection->createDocument()->setTitle('Draft 1');

        // Save one revision.
        $uuid = $doc1->uuid();
        $commit1 = $collection->save($doc1);

        // Save a second revision.
        $doc2_mut = $collection->newRevision($uuid)->setTitle('Draft 2');;
        $commit2 = $collection->save($doc2_mut);

        // Now try to reload the first revision.
        $old_revision = $collection->loadRevision($uuid, $commit1);

        // Old revisions should never be mutable.
        $this->assertNotTrue($old_revision instanceof MutableDocumentInterface);
        $this->assertEquals($doc1->uuid(), $old_revision->uuid());
        $this->assertEquals('Draft 1', $old_revision->title());
    }

    public function testLanguage()
    {
        $collection = $this->getCollection();

        $doc1 = $collection->createDocument()->setTitle('Doc 1 EN');
        $uuid = $doc1->uuid();

        $collection->save($doc1);
        $doc1_en = $collection->load($uuid);

        $collection_fr = $collection->forLanguage('fr');

        $doc_fr = $collection_fr->newRevision($uuid)->setTitle('Doc 1 FR');
        $collection_fr->save($doc_fr);
        $doc1_fr = $collection_fr->load($uuid);

        $this->assertEquals($doc1_en->uuid(), $doc1_fr->uuid());
        $this->assertNotEquals($doc1_en->title(), $doc1_fr->title());
        $this->assertEquals('en', $doc1_en->language());
        $this->assertEquals('fr', $doc1_fr->language());
    }

    /*
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
    */

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

    public function testSingleDocumentCommit()
    {
        $collection = $this->getCollection();

        $doc1 = $collection->createDocument()->setTitle('Doc 1');
        $uuid = $doc1->uuid();

        $commit = $collection->createCommit()->withRevision($doc1);
        $collection->saveCommit($commit);
        $doc1 = $collection->load($uuid);

        $doc2 = $collection->newRevision($uuid)->setTitle('Doc 2');

        $commit = $collection->createCommit('Commit message', 'Arthur the Author <>')->withRevision($doc2);
        $collection->saveCommit($commit);
        $doc2 = $collection->load($uuid);

        $doc3 = $collection->newRevision($uuid);

        $this->assertEquals($doc1->uuid(), $doc2->uuid());
        $this->assertEquals($doc1->uuid(), $doc3->uuid());
        $this->assertEquals('Doc 1', $doc1->title());
        $this->assertEquals('Doc 2', $doc2->title());
    }

    public function testMultiDocumentCommit()
    {
        $collection = $this->getCollection();

        $doc1 = $collection->createDocument();
        $uuid1 = $doc1->uuid();

        $doc2 = $collection->createDocument();
        $uuid2 = $doc2->uuid();

        $doc3 = $collection->createDocument();
        $uuid3 = $doc3->uuid();

        $commit = $collection->createCommit()
            ->withRevision($doc1)
            ->withRevision($doc2)
            ->withRevision($doc3);

        // Save all 3 at once.
        $collection->saveCommit($commit);

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


    public function testCommitHistory()
    {
        $collection = $this->getCollection();

        $doc1 = $collection->createDocument()->setTitle('Doc 1');
        $commit1 = $collection->save($doc1);

        $uuid1 = $doc1->uuid();

        // This is a different document so should not show up in the history for doc1.
        $doc2 = $collection->createDocument()->setTitle('Doc 2');
        $commit2 = $collection->save($doc2);

        $doc1Rev2 = $collection->newRevision($uuid1)->setTitle('Doc 1 Rev 2');
        $commit3 = $collection->save($doc1Rev2);

        $commits = $collection->history($uuid1);

        $commitsArray = iterator_to_array($commits);

        $this->assertEquals($commit3, $commitsArray[0]);
        $this->assertEquals($commit1, $commitsArray[1]);
        $this->assertEquals(2, count($commitsArray));
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
        $collection->archive([$doc1]);

        // Add a second for good measure.
        $doc2 = $collection->createDocument();
        $uuid2 = $doc2->uuid();
        $collection->save($doc2);

        // It should now behave as if it doesn't exist.
        try {
            $collection->load($uuid1);
            $this->fail('No exception thrown or wrong exception thrown.');
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
        $collection->archive([$doc1]);

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
        $collection->archive([$doc1]);

        // Load it, allowing for archived.  This will throw an exception if
        // not found.
        $collection->loadArchived($uuid1);
    }

    /*

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
    */
}
