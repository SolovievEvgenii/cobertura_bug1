<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Soloevgen\Testcover\ClassOne;

/**
 * @covers Soloevgen\Testcover\ClassOne
 */
class ClassOneTest extends TestCase
{

    public function testMethodAdd()
    {
        $mm = new ClassOne();
        $this->assertEquals(4, $mm->methodAdd(3,1));
    }

    public function testMethodSub()
    {
        $mm = new ClassOne();
        $this->assertEquals(2, $mm->methodSub(3,1));
    }
}
