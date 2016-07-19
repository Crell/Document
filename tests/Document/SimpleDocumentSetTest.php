<?php

declare (strict_types = 1);

namespace Crell\Document\Document\Test;

use Crell\Document\Document\SimpleDocumentSet;

class SimpleDocumentSetTest extends \PHPUnit_Framework_TestCase
{

    protected function sampleData()
    {
        return new \ArrayObject([
            'a' => 'A',
            'b' => 'B',
            'c' => 'C',
            'd' => 'D',
        ]);
    }

    public function testCanReadElements()
    {
        $set = new SimpleDocumentSet($this->sampleData());

        $this->assertEquals('A', $set['a']);
        $this->assertEquals('C', $set['c']);
    }

    public function testCanCheckForElements()
    {
        $set = new SimpleDocumentSet($this->sampleData());

        $this->assertTrue(isset($set['a']));
        $this->assertFalse(isset($set['e']));
    }

    public function testCount()
    {
        $set = new SimpleDocumentSet($this->sampleData());

        $this->assertEquals(4, count($set));
    }

    public function testCannotSetValues()
    {
        $set = new SimpleDocumentSet($this->sampleData());

        $this->expectException(\LogicException::class);

        $set['e'] = 'E';
    }

    public function testCannotUnsetValues()
    {
        $set = new SimpleDocumentSet($this->sampleData());

        $this->expectException(\LogicException::class);

        unset($set['a']);
    }

    public function testCanIterate()
    {
        $set = new SimpleDocumentSet($this->sampleData());

        // This function works by running through all iterations, so if it
        // works then iteration must work.
        $set_array = iterator_to_array($set);

        $this->assertCount(4, $set_array);
    }
}
