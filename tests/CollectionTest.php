<?php

declare (strict_types = 1);

namespace Crell\Document\Test;

use Crell\Document\Collection\Collection;
use Crell\Document\Document\MutableDocumentInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class CollectionTest extends DocumentTestBase
{

    public function testInitializeCollection()
    {
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();
    }

    public function testSaveAndLoad()
    {
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();

        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc2 = $collection->loadMutable($uuid);

        $collection->save($doc2);

        $doc3 = $collection->loadMutable($uuid);

        $this->assertEquals($doc1->uuid(), $doc2->uuid());
        $this->assertEquals($doc1->uuid(), $doc3->uuid());
        $this->assertNotEquals($doc1->revision(), $doc2->revision());
        $this->assertNotEquals($doc1->revision(), $doc3->revision());
        $this->assertNotEquals($doc2->revision(), $doc3->revision());
    }

    public function testNoImmutableSave()
    {
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();

        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc2 = $collection->load($uuid);

        $this->expectException(\TypeError::class);

        $collection->save($doc2);
    }

    public function testLoadOldRevision()
    {
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();

        // Save one revision.
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        // Save a second revions.
        $doc2_mut = $collection->loadMutable($uuid);
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
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc1_en = $collection->loadMutable($uuid);

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
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();

        // Save a new Document.
        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();
        $collection->save($doc1);

        // Now make a new non-default revision, aka a forward revision.
        $doc2 = $collection->loadMutable($uuid);
        $collection->save($doc2, false);

        // This should get the default revision, aka be the same as $doc1.
        $doc3 = $collection->load($uuid);

        $this->assertEquals($doc1->uuid(), $doc3->uuid());
        $this->assertEquals($doc1->revision(), $doc3->revision());
    }
}
