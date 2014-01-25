<?php

namespace JDesrosiers\Tests\Silex\Provider\Fixtures;

class Foo
{
    protected $foo;

    public function getFoo()
    {
        return $this->foo;
    }

    public function setFoo($foo)
    {
        return $this->foo = $foo;
    }
}