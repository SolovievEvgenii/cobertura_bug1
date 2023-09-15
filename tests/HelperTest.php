<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once('src/Helpers.php');

class HelperTest extends TestCase
{

    /**
     * @covers isAjaxRequest
     */

    public function testIsAjax()
    {
        $mm = isAjaxRequest();
        $this->assertFalse($mm);
    }
}
