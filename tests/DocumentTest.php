<?php

namespace Crell\Document\Test;


use Crell\Document\Repository\Repository;

class DocumentTest extends \PHPUnit_Framework_TestCase {

    public function testStuff()
    {
        $r = new Repository();

        $document = $r->load(123);

        $this->assertEquals(123, $document->id());

    }
}
