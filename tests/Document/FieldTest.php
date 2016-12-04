<?php

declare (strict_types = 1);

namespace Crell\Document\Document\Test;

use Crell\Document\Document\Document;
use Crell\Document\Document\Field\TextField;
use Crell\Document\Document\FieldSet;
use Crell\Document\Document\SimpleDocumentSet;

class FieldTest extends \PHPUnit_Framework_TestCase
{

    public function testReadExample()
    {

        $doc = new class extends Document {
            public function __construct()
            {
                $this->fields['text'] = new FieldSet([new TextField('Hello World')]);
            }
        };

        $this->assertCount(1, $doc->fieldNames());

        // Returns Field object for "text" property.
        $field = $doc->text;

        $this->assertInstanceOf(FieldSet::class, $field);
        $this->assertEquals('Hello World', $field->value);
        $this->assertEquals('Hello World', $doc->text->value);
        $this->assertEquals('Hello World', $doc->text[0]->value);

        $doc->text[0]->rot13();
    }

    /*
    public function testWrite()
    {
        $doc->field->setProperty('foo', 'bar');



    }
    */


}
