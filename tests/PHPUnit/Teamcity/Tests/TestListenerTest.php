<?php

namespace PHPUnit\TeamCity\Tests;

use PHPUnit\TeamCity\TestListener;
use AspectMock\Test as test;
use PHPUnit\Teamcity\Tests\Fixture\TestWithDataProvider;
use SebastianBergmann\Comparator\ComparisonFailure;

class TestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestListener
     */
    private $listener;

    /**
     * Memory stream to write teamcity messages
     * @var resource
     */
    private $out;

    protected function setUp()
    {
        $this->out = fopen('php://memory', 'w');
        $this->listener = new TestListener($this->out);

        // mock standard php functions output
        test::func('PHPUnit\TeamCity', 'date', '2015-05-28T16:14:12.17+0700');
        test::func('PHPUnit\TeamCity', 'getmypid', 24107);
    }

    protected function tearDown()
    {
        fclose($this->out);
        $this->listener = null;
        test::clean();
    }

    public function testStartTest()
    {
        $test = $this->createTestMock('UnitTest');

        $this->listener->startTest($test);
        $expected = <<<EOS
##teamcity[testStarted captureStandardOutput='true' name='UnitTest' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expected);
    }

    public function testEndTest()
    {
        $test = $this->createTestMock('UnitTest');

        $time = 5;

        $this->listener->endTest($test, $time);
        $expected = <<<EOS
##teamcity[testFinished duration='5' name='UnitTest' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expected);
    }

    public function testStartTestSuite()
    {
        $testSuite = new \PHPUnit_Framework_TestSuite('TestSuite');

        $this->listener->startTestSuite($testSuite);
        $expected = <<<EOS
##teamcity[testSuiteStarted name='TestSuite' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expected);
    }

    public function testEndTestSuite()
    {
        $testSuite = new \PHPUnit_Framework_TestSuite('TestSuite');

        $this->listener->endTestSuite($testSuite);
        $expected = <<<EOS
##teamcity[testSuiteFinished name='TestSuite' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expected);
    }

    public function testAddSkippedTest()
    {
        $test = $this->createTestMock('SkippedTest');
        $exception = new \Exception('Skip message');
        $time = 5;

        $this->listener->addSkippedTest($test, $exception, $time);
        $expected = <<<EOS
##teamcity[testIgnored message='Skip message' name='SkippedTest' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expected);
    }

    public function testAddIncompleteTest()
    {
        $test = $this->createTestMock('IncompleteTest');
        $exception = new \Exception('Incomplete message');
        $time = 5;

        $this->listener->addIncompleteTest($test, $exception, $time);
        $expected = <<<EOS
##teamcity[testIgnored message='Incomplete message' name='IncompleteTest' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expected);
    }

    public function testAddRiskyTest()
    {
        $test = $this->createTestMock('RiskyTest');
        $exception = new \Exception('Ricky message');
        $time = 5;

        $this->listener->addRiskyTest($test, $exception, $time);
        $expected = <<<EOS
##teamcity[testIgnored message='Ricky message' name='RiskyTest' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expected);
    }

    public function testAddError()
    {
        $test = $this->createTestMock('UnitTest');
        $exception = new \Exception('ErrorMessage');
        $time = 5;

        $this->listener->addError($test, $exception, $time);

        $expectedOutputStart = <<<EOS
##teamcity[testFailed message='ErrorMessage' details='
EOS;
        $this->assertStringStartsWith($expectedOutputStart, $this->readOut());

        $expectedOutputEnd = <<<EOS
 name='UnitTest' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertStringEndsWith($expectedOutputEnd, $this->readOut());
    }

    public function testAddFailure()
    {
        $test = $this->createTestMock('FailedTest');
        $exception = new \PHPUnit_Framework_AssertionFailedError('Assertion error');
        $time = 5;

        $this->listener->addFailure($test, $exception, $time);

        $expectedOutputStart = <<<EOS
##teamcity[testFailed message='Assertion error' details='
EOS;
        $this->assertStringStartsWith($expectedOutputStart, $this->readOut());

        $expectedOutputEnd = <<<EOS
 name='FailedTest' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertStringEndsWith($expectedOutputEnd, $this->readOut());
    }

    public function testAddFailureTestCase()
    {
        /* @var \PHPUnit_Framework_TestCase $testCaseMock */
        $testCaseMock = $this->getMockForAbstractClass('\PHPUnit_Framework_TestCase', array('testMethod'), 'TestCase');

        $test = $this->createTestMock('FailedTest');

        $comparisonFailure = new ComparisonFailure(
            'expected',
            'actual',
            'expectedAsString',
            'actualAsString'
        );

        $thrownException = new \PHPUnit_Framework_ExpectationFailedException('ExpectationFailed', $comparisonFailure);
        $result = new \PHPUnit_Framework_TestResult();
        $result->addFailure($test, $thrownException, 2);
        $result->addFailure($test, $thrownException, 3);

        $testCaseMock->setTestResultObject($result);

        $exception = new \PHPUnit_Framework_AssertionFailedError('Assertion error');
        $time = 5;

        $this->listener->addFailure($testCaseMock, $exception, $time);

        $message = $this->readOut();

        $expectedOutputStart = <<<EOS
##teamcity[testFailed type='comparisonFailure' message='Assertion error' details=
EOS;
        $this->assertStringStartsWith($expectedOutputStart, $message);


        $expectedOutputEnd = <<<EOS
 expected='expectedAsString' actual='actualAsString' name='TestCase.testMethod' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertStringEndsWith($expectedOutputEnd, $message);
    }

    public function testMessageNameForTestWithDataProvider()
    {
        $theClass = new \ReflectionClass('\PHPUnit\Teamcity\Tests\Fixture\TestWithDataProvider');
        $testSuite = new \PHPUnit_Framework_TestSuite($theClass);

        $tests = $testSuite->tests();

        $this->assertArrayHasKey(0, $tests);
        $this->assertInstanceOf('PHPUnit_Framework_TestSuite_DataProvider', $tests[0]);
        /* @var \PHPUnit_Framework_TestSuite_DataProvider $dataProviderTestSuite */
        $dataProviderTestSuite = $tests[0];

        $this->assertArrayHasKey(1, $tests);
        $this->assertInstanceOf('\PHPUnit\Teamcity\Tests\Fixture\TestWithDataProvider', $tests[1]);
        /* @var TestWithDataProvider $simpleMethodTest*/
        $simpleMethodTest = $tests[1];

        $this->listener->startTestSuite($testSuite);
        $this->listener->startTestSuite($dataProviderTestSuite);

        foreach ($dataProviderTestSuite as $test) {
            $this->listener->startTest($test);
            $this->listener->endTest($test, 5);
        }

        $this->listener->endTestSuite($dataProviderTestSuite);

        $this->listener->startTest($simpleMethodTest);
        $this->listener->endTest($simpleMethodTest, 6);

        $this->listener->endTestSuite($testSuite);

        $expectedOutput = <<<EOS
##teamcity[testSuiteStarted name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testSuiteStarted name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testStarted captureStandardOutput='true' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "one" (|'data #1|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testFinished duration='5' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "one" (|'data #1|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testStarted captureStandardOutput='true' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "two" (|'data #2|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testFinished duration='5' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "two" (|'data #2|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testStarted captureStandardOutput='true' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "three" (|'data_with_dots|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testFinished duration='5' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "three" (|'data_with_dots|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testStarted captureStandardOutput='true' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "four" (|'.u0085|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testFinished duration='5' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider.with data set "four" (|'.u0085|')' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testSuiteFinished name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testMethodWithDataProvider' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testStarted captureStandardOutput='true' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testSimpleMethod' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testFinished duration='6' name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider.testSimpleMethod' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']
##teamcity[testSuiteFinished name='PHPUnit.Teamcity.Tests.Fixture.TestWithDataProvider' timestamp='2015-05-28T16:14:12.17+0700' flowId='24107']

EOS;

        $this->assertOutputEquals($expectedOutput);
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject|\PHPUnit_Framework_Test
     */
    private function createTestMock($className)
    {
        return $this->getMockBuilder('\PHPUnit_Framework_Test')
            ->setMockClassName($className)
            ->getMock();
    }

    /**
     * @param string $expectedOutput
     * @param string $message
     */
    private function assertOutputEquals($expectedOutput, $message = '')
    {
        $this->assertEquals($expectedOutput, $this->readOut(), $message);
    }

    private function readOut()
    {
        return stream_get_contents($this->out, -1, 0);
    }
}
