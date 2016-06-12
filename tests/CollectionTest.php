<?php

declare (strict_types = 1);

namespace Crell\Document\Test;

use Crell\Document\Collection\Collection;
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

        $doc2 = $collection->load($uuid);

        $collection->save($doc2);

        $doc3 = $collection->load($uuid);

        $this->assertEquals($doc1->uuid(), $doc2->uuid());
        $this->assertEquals($doc1->uuid(), $doc3->uuid());
        $this->assertNotEquals($doc1->revision(), $doc2->revision());
        $this->assertNotEquals($doc1->revision(), $doc3->revision());
        $this->assertNotEquals($doc2->revision(), $doc3->revision());
    }

    public function testLoadOldRevision()
    {
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();

        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc2 = $collection->load($uuid);

        $collection->save($doc2);

        $old_revision = $collection->loadRevision($uuid, $doc2->revision());

        $this->assertEquals($doc2->uuid(), $old_revision->uuid());
        $this->assertEquals($doc2->revision(), $old_revision->revision());
    }

    public function testLanguage()
    {
        $collection = new Collection('coll', $this->conn);
        $collection->initializeSchema();

        $doc1 = $collection->createDocument();
        $uuid = $doc1->uuid();

        $collection->save($doc1);

        $doc1_en = $collection->load($uuid);

        $doc1_fr = $doc1_en->asLanguage('fr');

        $collection->save($doc1_fr);

        $collection_fr = $collection->forLanguage('fr');
        $doc1_fr = $collection_fr->load($uuid);

        $this->assertEquals($doc1_en->uuid(), $doc1_fr->uuid());
        $this->assertNotEquals($doc1_en->revision(), $doc1_fr->revision());
        $this->assertEquals('en', $doc1_en->language());
        $this->assertEquals('fr', $doc1_fr->language());
    }
}
