<?php

declare (strict_types = 1);

namespace Crell\Document\Document\Test;

use Crell\Document\Document\Document;
use Crell\Document\Document\Field\TextField;

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
            'timestamp' => (new \DateTimeImmutable('2016-12-04'))->format('c'),
            'title' => 'A title',
            'fields' => []
        ];

        $doc = Document::hydrate($data);

        $this->assertEquals('abc123', $doc->uuid());
        $this->assertEquals('rev', $doc->revision());
        $this->assertEquals('A title', $doc->title());
        $this->assertEquals(new \DateTimeImmutable('2016-12-04'), $doc->timestamp());
    }

    public function testHydrateWithFields()
    {
        $data = [
            'class' => Document::class,
            'uuid' => 'abc123',
            'language' => 'en',
            'revision' => 'rev',
            'parent_rev' => '',
            'timestamp' => (new \DateTimeImmutable('2016-12-04'))->format('c'),
            'title' => 'A title',
            'fields' => [
                'text' => [
                    'class' => TextField::class,
                    'items' => [
                        ['value' => 'A textfield'],
                    ],
                ]
            ],
        ];

        $doc = Document::hydrate($data);

        $this->assertEquals('abc123', $doc->uuid());
        $this->assertEquals('rev', $doc->revision());
        $this->assertEquals('A title', $doc->title());
        $this->assertEquals(new \DateTimeImmutable('2016-12-04'), $doc->timestamp());

        $this->assertEquals('A textfield', $doc->text->value);
    }

    public function testSerialize()
    {
        $data = [
            'class' => Document::class,
            'uuid' => 'abc123',
            'language' => 'en',
            'revision' => 'rev',
            'parent_rev' => '',
            'timestamp' => (new \DateTimeImmutable('2016-12-04'))->format('c'),
            'title' => 'A title',
            'fields' => [
                'text' => [
                    'class' => TextField::class,
                    'items' => [
                        ['value' => 'A textfield'],
                    ],
                ]
            ],
        ];

        $doc = Document::hydrate($data);

        $json = json_encode($doc);

        $deserialized = json_decode($json, TRUE);

        $this->assertEquals($data, $deserialized);
    }

}
