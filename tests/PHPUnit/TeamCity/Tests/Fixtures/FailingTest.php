<?php

namespace PHPUnit\TeamCity\Tests\Fixtures;

class FailingTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldFailSame()
    {
        $actualValue = array('first' => 0, 'second' => 2, 'third' => 3);

        $expectedValue = array('first' => 1, 'second' => 2, 'third' => 3);

        $this->assertSame($expectedValue, $actualValue, 'Two array does not match');
    }

    public function testShouldFailEquals()
    {
        $actualValue = array('first' => 0, 'second' => 2, 'third' => 3);

        $expectedValue = array('first' => 1, 'second' => 2, 'third' => 3);

        $this->assertEquals($expectedValue, $actualValue, 'Two array does not match');
    }
}
