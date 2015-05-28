<?php

namespace PHPUnit\TeamCity;

class TestListener extends \PHPUnit_Util_Printer implements \PHPUnit_Framework_TestListener
{
    const MESSAGE_SUITE_STARTED = 'testSuiteStarted';
    const MESSAGE_TEST_STARTED = 'testStarted';
    const MESSAGE_TEST_FAILED = 'testFailed';
    const MESSAGE_TEST_IGNORED = 'testIgnored';
    const MESSAGE_TEST_FINISHED = 'testFinished';
    const MESSAGE_COMPARISON_FAILURE = 'comparisonFailure';
    const MESSAGE_SUITE_FINISHED = 'testSuiteFinished';

    /**
     * @var string
     */
    protected $captureStandardOutput = 'true';

    /**
     * @param string $type
     * @param \PHPUnit_Framework_Test $test
     * @param array $params
     * @return string
     */
    protected function getServiceMessage($type, \PHPUnit_Framework_Test $test, array $params = array())
    {
        list($usec, $sec) = explode(' ', microtime());
        $msec = floor($usec * 1000);
        $params += array(
            'name' => $this->getTestName($test),
            'timestamp' => date("Y-m-d\\TH:i:s.{$msec}O", $sec),
            'flowId' => $this->getFlowId($test),
        );
        $message = "##teamcity[{$type}";
        foreach ($params as $name => $value) {
            $message .= ' ' . $name . '=\'' . $this->addSlashes($value) . '\'';
        }
        $message .= "]" . PHP_EOL;
        return $message;
    }

    /**
     * @param \PHPUnit_Framework_Test $test
     * @return string
     */
    protected function getTestName(\PHPUnit_Framework_Test $test)
    {
        if ($test instanceof \PHPUnit_Framework_SelfDescribing) {
            $name = $test->toString();
        } else {
            $name = get_class($test);
        }
        return $this->formatName($name);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function formatName($name)
    {
        return str_replace(
            array('.', '\\', '::', ' with data set', ':'),
            array('_', '.', '.', '.with data set', '_'),
            $name
        );
    }

    /**
     * @param \PHPUnit_Framework_Test $test
     * @return int
     */
    protected function getFlowId(\PHPUnit_Framework_Test $test)
    {
        return getmypid();
    }

    /**
     * @param string $string
     * @return string
     */
    protected function addSlashes($string)
    {
        return str_replace(
            array("|", "'", "\n", "\r", "\u0085", "\u2028", "\u2029", "[","]"),
            array("||", "|'", "|n", "|r", "|x", "|l", "|p", "|[", "|]"),
            $string
        );
    }

    /**
     * An error occurred.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $message = $this->getServiceMessage(
            self::MESSAGE_TEST_FAILED,
            $test,
            array(
                'message' => $e->getMessage(),
                'details' => $e->getTraceAsString(),
            )
        );
        $this->write($message);
    }

    /**
     * A failure occurred.
     *
     * @param \PHPUnit_Framework_Test|\PHPUnit_Framework_TestCase $test
     * @param \PHPUnit_Framework_AssertionFailedError $e
     * @param float $time
     */
    public function addFailure(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $failures = array();
        $testResult = $test->getTestResultObject();
        /* @var $failure \PHPUnit_Framework_TestFailure */
        foreach ($testResult->failures() as $failure) {
            $hash = md5($e->getMessage() . ' ' . $e->getTraceAsString());
            if (isset($failures[$hash])) {
                continue;
            }

            $array = array(
                'type'     => self::MESSAGE_COMPARISON_FAILURE,
                'message'  => $e->getMessage(),
                'details'  => $e->getTraceAsString(),
            );

            $thrownException = $failure->thrownException();
            if ($thrownException instanceof \PHPUnit_Framework_ExpectationFailedException) {
                $comparisonFailure = $thrownException->getComparisonFailure();
                if (null !== $comparisonFailure) {
                    $array += array(
                        'expected' => $comparisonFailure->getExpectedAsString(),
                        'actual' => $comparisonFailure->getActualAsString(),
                    );
                }
            }

            $message = $this->getServiceMessage(self::MESSAGE_TEST_FAILED, $test, $array);
            $this->write($message);
            $failures[$hash] = true;
        }
    }

    /**
     * Incomplete test.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $message = $this->getServiceMessage(
            self::MESSAGE_TEST_IGNORED,
            $test,
            array(
                'message' => $e->getMessage(),
            )
        );
        $this->write($message);
    }

    /**
     * Risky test.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception $e
     * @param float $time
     * @since  Method available since Release 4.0.0
     */
    public function addRiskyTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $this->addIncompleteTest($test, $e, $time);
    }

    /**
     * Skipped test.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $message = $this->getServiceMessage(
            self::MESSAGE_TEST_IGNORED,
            $test,
            array(
                'message' => $e->getMessage(),
            )
        );
        $this->write($message);
    }

    /**
     * A test suite started.
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $message = $this->getServiceMessage(
            self::MESSAGE_SUITE_STARTED,
            $suite
        );
        $this->write($message);
    }

    /**
     * A test suite ended.
     *
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $message = $this->getServiceMessage(
            self::MESSAGE_SUITE_FINISHED,
            $suite
        );
        $this->write($message);
    }

    /**
     * A test started.
     *
     * @param \PHPUnit_Framework_Test $test
     */
    public function startTest(\PHPUnit_Framework_Test $test)
    {
        $message = $this->getServiceMessage(
            self::MESSAGE_TEST_STARTED,
            $test,
            array(
                'captureStandardOutput' => $this->captureStandardOutput,
            )
        );
        $this->write($message);
    }

    /**
     * A test ended.
     *
     * @param \PHPUnit_Framework_Test $test
     * @param float $time
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        $message = $this->getServiceMessage(
            self::MESSAGE_TEST_FINISHED,
            $test,
            array(
                'duration' => $time,
            )
        );
        $this->write($message);
    }
}
