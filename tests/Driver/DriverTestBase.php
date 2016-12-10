<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Driver;

use Crell\Document\Collection\Collection;
use Crell\Document\Collection\DocumentRecordNotFoundException;
use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
use Crell\Document\Driver\CollectionDriverInterface;

abstract class DriverTestBase extends \PHPUnit_Framework_TestCase
{

    /**
     * Dummy collection.
     *
     * @var Collection
     */
    protected $collection;

    public function setUp() {
        // Create a dummy collection, since calls to the driver need one but
        // the memory driver doesn't use it.
        $this->collection = new class extends Collection {
            public function __construct() {
                $this->language = 'en';
                $this->name = 'coll';
            }
        };
    }

    /**
     * Returns a collection driver object to test.
     *
     * @return CollectionDriverInterface
     */
    abstract protected function getDriver() : CollectionDriverInterface;


    public function testPersistType()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, [$doc], true);
    }

    public function testPersistAndLoadByUuid()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, [$doc], true);

        $loaded = $driver->loadDefaultRevisionData($this->collection, '123');
        $this->assertEquals($doc->uuid(), $loaded['uuid']);
        $this->assertEquals($doc->revision(), $loaded['revision']);
        $this->assertEquals($doc->language(), $loaded['language']);
    }

    public function testPersistAndLoadByRevision()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, [$doc], true);

        $loaded = $driver->loadRevisionData($this->collection, '123', '456');
        $this->assertEquals($doc->uuid(), $loaded['uuid']);
        $this->assertEquals($doc->revision(), $loaded['revision']);
        $this->assertEquals($doc->language(), $loaded['language']);
    }

    public function testMissingUuid()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, [$doc], true);

        try {
            // There is clearly no such UUID.
            $loaded = $driver->loadDefaultRevisionData($this->collection, '789');
            $this->fail('No exception thrown or wrong exception thrown');
        }
        catch (DocumentRecordNotFoundException $e) {
            $this->assertEquals($this->collection->name(), $e->getCollectionName());
            $this->assertEquals('789', $e->getUuid());
            $this->assertEquals($this->collection->language(), $e->getLanguage());
        }
    }

    public function testSomeUuidsFound()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, [$doc], true);

        // Only one of these UUIDs exist.
        $loaded = $driver->loadMultipleDefaultRevisionData($this->collection, ['123', 'abc']);

        $records = iterator_to_array($loaded);

        $this->assertCount(1, $records);
        $this->assertEquals('123', key($records));
    }

    public function testNoUuidsFound()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, [$doc], true);

        // None of these UUIDs exist.
        $records = $driver->loadMultipleDefaultRevisionData($this->collection, ['789', 'abc']);

        $found = iterator_to_array($records);
        $this->assertEmpty($found);
    }

    public function testSetArchivedSingle()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument('123');
        $driver->persist($this->collection, [$doc], true);

        $driver->setArchived($this->collection, $doc->revision());

        $doc2 = $this->getMutableMockDocument('abc', 'def');
        $driver->persist($this->collection, [$doc2], true);

        try {
            // This UUID is Archived.
            $loaded = $driver->loadDefaultRevisionData($this->collection, '123');
            $this->fail('No exception thrown or wrong exception thrown');
        }
        catch (DocumentRecordNotFoundException $e) {
            $this->assertEquals($this->collection->name(), $e->getCollectionName());
            $this->assertEquals('123', $e->getUuid());
            $this->assertEquals($this->collection->language(), $e->getLanguage());
        }
    }

    public function testSetArchivedMultiple()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument('123');
        $driver->persist($this->collection, [$doc], true);

        $driver->setArchived($this->collection, $doc->revision());

        $doc2 = $this->getMutableMockDocument('abc', 'def');
        $driver->persist($this->collection, [$doc2], true);

        // Only the second should be found, as the first is archived.
        $records = $driver->loadMultipleDefaultRevisionData($this->collection, ['123', 'abc']);

        $found = iterator_to_array($records);
        $this->assertCount(1, $found);
    }

    public function testLoadArchived()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument('123');
        $driver->persist($this->collection, [$doc], true);

        $driver->setArchived($this->collection, $doc->revision());

        // This UUID is Archived, but since we allow for archived, it should be fine.
        // An error would throw an exception.
        $driver->loadDefaultRevisionData($this->collection, '123', true);
    }

    public function testLoadArchivedMultiple()
    {
        $driver = $this->getDriver();

        $doc = $this->getMutableMockDocument('123');
        $driver->persist($this->collection, [$doc], true);

        $driver->setArchived($this->collection, $doc->revision());

        $doc2 = $this->getMutableMockDocument('abc', 'def');
        $driver->persist($this->collection, [$doc2], true);

        // Only the second should be found, as the first is archived.
        $records = $driver->loadMultipleDefaultRevisionData($this->collection, ['123', 'abc'], true);

        $found = iterator_to_array($records);
        $this->assertCount(2, $found);
    }

    /**
     * Returns a mocked mutable document object.
     *
     * @return MutableDocumentInterface
     */
    protected function getMutableMockDocument(string $uuid = '123', string $revision = '456') : MutableDocumentInterface
    {
        $doc = new class($uuid, $revision) extends Document implements MutableDocumentInterface {
            use DocumentTrait, MutableDocumentTrait;

            public function __construct(string $uuid, string $revision) {
                $this->uuid = $uuid;
                $this->revision = $revision;
                $this->parentRev = '';
                $this->language = 'en';
                $this->title = 'A';
            }

            public function setRevisionId(string $revision) : MutableDocumentInterface
            {
                // Do nothing.
            }
        };

        return $doc;
    }

}
