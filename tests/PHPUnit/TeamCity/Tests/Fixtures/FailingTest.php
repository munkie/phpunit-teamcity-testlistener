<?php

namespace PHPUnit\TeamCity\Tests\Fixtures;

class FailingTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldFail()
    {
        $actualValue = array('first' => 0, 'second' => 2, 'third' => 3);

        $expectedValue = array('first' => 1, 'second' => 2, 'third' => 3);

        $this->assertSame($expectedValue, $actualValue, 'Two array does not match');
    }
}
