<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Soloevgen\Testcover\ClassTwo;

/**
 * @covers Soloevgen\Testcover\ClassTwo
 */
class ClassTwoTest extends TestCase
{

    public function testMethodAdd()
    {
        $mm = new ClassTwo();
        $this->assertEquals(4, $mm->methodAdd(3,1));
    }

    public function testMethodSub()
    {
        $mm = new ClassTwo();
        $this->assertEquals(2, $mm->methodSub(3,1));
    }

    public function testMethodMul()
    {
        $mm = new ClassTwo();
        $this->assertEquals(9, $mm->methodMul(3,3));
    }
}
