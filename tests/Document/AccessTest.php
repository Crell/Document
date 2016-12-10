<?php

declare (strict_types = 1);

namespace Crell\Document\Document\Test;

class AccessTest extends \PHPUnit_Framework_TestCase
{

    public function testHydrate()
    {
        //$s = Super::create();
    }
}

class Super {
    protected $superProtected = 'Super protected';
    private $superPrivate = 'Super private';

    public static function create() : self {
        $c = new Child();

        print $c->superPrivate . PHP_EOL;
        print $c->superProtected . PHP_EOL;
        print $c->protected . PHP_EOL;
        //print $c->private . PHP_EOL;

        return $c;
    }
}

class Child extends Super {
    protected $protected = 'Child protected';
    private $private = 'Child private';
}
