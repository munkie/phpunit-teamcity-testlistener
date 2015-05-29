<?php

namespace PHPUnit\Teamcity\Tests\Fixture;

class TestWithDataProvider extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderWithKeys
     *
     * @param string $param
     */
    public function testMethodWithDataProvider($param)
    {

    }

    public function testSimpleMethod()
    {

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
            'four' => array("\u0085")
        );
    }
}
