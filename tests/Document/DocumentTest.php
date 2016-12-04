<?php

declare (strict_types = 1);

namespace Crell\Document\Document\Test;

use Crell\Document\Document\Document;
use Crell\Document\Document\DocumentTrait;
use Crell\Document\Document\Field\TextField;
use Crell\Document\Document\FieldSet;
use Crell\Document\Document\LoadableDocumentTrait;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Document\MutableDocumentTrait;
use Crell\Document\Document\SimpleDocumentSet;

class DocumentTest extends \PHPUnit_Framework_TestCase
{

    public function testHydrateWithNoFields()
    {
        $data = [
            'class' => Document::class,
            'uuid' => 'abc123',
            'language' => 'en',
            'revision' => 'rev',
            'parent_rev' => '',
            'timestamp' => new \DateTimeImmutable('2016-12-04'),
            'title' => 'A title',
        ];

        $doc = Document::hydrate($data);

        $this->assertEquals('abc123', $doc->uuid());
        $this->assertEquals('rev', $doc->revision());
        $this->assertEquals('A title', $doc->title());
        $this->assertEquals(new \DateTimeImmutable('2016-12-04'), $doc->timestamp());
    }

}
