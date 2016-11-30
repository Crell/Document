<?php

declare (strict_types = 1);

namespace Crell\Document\Document\Test;

use Crell\Document\Document\Document;
use Crell\Document\Document\SimpleDocumentSet;

class FieldTest extends \PHPUnit_Framework_TestCase
{

    public function testReadExample()
    {

        $doc = new Document();

        // Get list of field names. Or objects?
        $fields = $doc->fields();

        // Returns Field object for "field" property.
        $f = $doc->field;

        $doc->field->prop;

        $doc->field[2]->prop;

        $doc->field->method();
    }

    public function testWrite()
    {
        $doc->field->setProperty('foo', 'bar');



    }


}
