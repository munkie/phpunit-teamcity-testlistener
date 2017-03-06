<?php

namespace PHPUnit\TeamCity\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @dataProvider dataProviderWithKeys
     *
     * @param string $param
     */
    public function testMethodWithDataProvider($param)
    {
        $this->assertNotNull($param);
    }

    public function testSimpleMethod()
    {
        $this->assertTrue(true);
    }

    public function testDuration()
    {
        usleep(2500000);
        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public static function dataProviderWithKeys()
    {
        return array(
            'one' => array('data #1'),
            'two' => array('data #2'),
            'three' => array('data.with.dots'),
            'four' => array("\u0085"),
            'five.one' => array('5.0')
        );
    }
}
