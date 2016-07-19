<?php

declare (strict_types = 1);

namespace Crell\Document\Test\Collection;


use Crell\Document\Collection\Collection;
use Crell\Document\Collection\MemoryCollectionDriver;
use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;

class MemoryCollectionDriverTest extends \PHPUnit_Framework_TestCase
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
            }
        };
    }

    public function testPersistType()
    {
        $driver = new MemoryCollectionDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, $doc, true);
    }

    public function testPersistAndLoadByUuid()
    {
        $driver = new MemoryCollectionDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, $doc, true);

        $loaded = $driver->loadDefaultRevisionData($this->collection, '123');
        $this->assertEquals($doc->uuid(), $loaded['uuid']);
        $this->assertEquals($doc->revision(), $loaded['revision']);
        $this->assertEquals($doc->language(), $loaded['language']);
    }

    public function testPersistAndLoadByRevision()
    {
        $driver = new MemoryCollectionDriver();

        $doc = $this->getMutableMockDocument();

        $driver->persist($this->collection, $doc, true);

        $loaded = $driver->loadRevisionData($this->collection, '123', '456');
        $this->assertEquals($doc->uuid(), $loaded['uuid']);
        $this->assertEquals($doc->revision(), $loaded['revision']);
        $this->assertEquals($doc->language(), $loaded['language']);
    }

    /**
     * Returns a mocked mutable document object.
     *
     * @return MutableDocumentInterface
     */
    protected function getMutableMockDocument() : MutableDocumentInterface
    {
        $doc = new class extends Document implements MutableDocumentInterface {
            use DocumentTrait, MutableDocumentTrait;

            public function __construct() {
                $this->uuid = '123';
                $this->revision = '456';
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
