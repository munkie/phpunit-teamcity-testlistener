<?php

namespace PHPUnit\TeamCity;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\SelfDescribing;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestListener as BaseTestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Util\Filter;
use PHPUnit\Util\Printer;

class TestListener extends Printer implements BaseTestListener
{
    /**
     * Teamcity service message names
     */
    const MESSAGE_SUITE_STARTED = 'testSuiteStarted';
    const MESSAGE_SUITE_FINISHED = 'testSuiteFinished';
    const MESSAGE_TEST_STARTED = 'testStarted';
    const MESSAGE_TEST_FAILED = 'testFailed';
    const MESSAGE_TEST_IGNORED = 'testIgnored';
    const MESSAGE_TEST_FINISHED = 'testFinished';

    /**
     * Comparison failure message type
     */
    const MESSAGE_COMPARISON_FAILURE = 'comparisonFailure';

    /**
     * If true, all the standard output (and standard error) messages
     * received between testStarted and testFinished messages will be considered test output
     *
     * @var string
     */
    protected $captureStandardOutput = 'true';

    /**
     * Create and write service message to out
     *
     * @param string $type
     * @param Test $test
     * @param array $params
     */
    protected function writeServiceMessage($type, Test $test, array $params = array())
    {
        $message = $this->createServiceMessage($type, $test, $params);
        $this->write($message);
    }

    /**
     * Create service message
     *
     * @param string $type
     * @param Test $test
     * @param array $params
     * @return string
     */
    protected function createServiceMessage($type, Test $test, array $params = array())
    {
        $params += array(
            'name' => $this->getTestName($test),
            'timestamp' => $this->getTimestamp(),
            'flowId' => $this->getFlowId($test)
        );
        $attributes = array();
        foreach ($params as $name => $value) {
            $attributes[] = sprintf("%s='%s'", $name, $this->escapeValue($value));
        }
        return sprintf('##teamcity[%s %s]%s', $type, implode(' ', $attributes), PHP_EOL);
    }

    /**
     * Create timestamp for service message
     *
     * @return string
     */
    protected function getTimestamp()
    {
        list($usec, $sec) = explode(' ', microtime());
        $msec = floor($usec * 1000);
        return date("Y-m-d\\TH:i:s.{$msec}O", $sec);
    }

    /**
     * @param Test $test
     * @return string
     */
    protected function getTestName(Test $test)
    {
        if ($test instanceof TestCase) {
            $name = $test->getName();
        } elseif ($test instanceof TestSuite) {
            $name = $test->getName();
        } elseif ($test instanceof SelfDescribing) {
            $name = $test->toString();
        } else {
            $name = get_class($test);
        }
        return $name;
    }

    /**
     * @param Test $test
     * @return int
     */
    protected function getFlowId(Test $test)
    {
        return getmypid();
    }

    /**
     * @param string $string
     * @return string
     */
    protected function escapeValue($string)
    {
        $string = trim($string);
        return strtr(
            $string,
            array(
                "|"  => "||",
                "'"  => "|'",
                "\n" => "|n",
                "\r" => "|r",
                "["  => "|[",
                "]"  => "|]"
            )
        );
    }

    /**
     * An error occurred.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addError(Test $test, \Exception $e, $time)
    {
        /**
         * Workaround for wrong TestFailure::exceptionToString signature
         * @todo revert after merge pull request https://github.com/sebastianbergmann/phpunit/pull/2554
         */
        if ($e instanceof Exception) {
            $params = array(
                'message' => TestFailure::exceptionToString($e),
                'details' => Filter::getFilteredStacktrace($e),
            );
        } else {
            $params = array(
                'message' => get_class($e) . ': ' . $e->getMessage() . "\n",
                'details' => Filter::getFilteredStacktrace($e),
            );
        }

        if ($e instanceof ExpectationFailedException) {
            $comparisonFailure = $e->getComparisonFailure();
            if (null !== $comparisonFailure) {
                $params += array(
                    'type' => self::MESSAGE_COMPARISON_FAILURE,
                    'expected' => $comparisonFailure->getExpectedAsString(),
                    'actual' => $comparisonFailure->getActualAsString()
                );
            }
        }

        $this->writeServiceMessage(
            self::MESSAGE_TEST_FAILED,
            $test,
            $params
        );
    }

    /**
     * A failure occurred.
     *
     * @param Test|TestCase $test
     * @param AssertionFailedError $e
     * @param float $time
     */
    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
        $this->addError($test, $e, $time);
    }

    /**
     * Incomplete test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addIncompleteTest(Test $test, \Exception $e, $time)
    {
        $this->addSkippedTest($test, $e, $time);
    }

    /**
     * Risky test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     * @since  Method available since Release 4.0.0
     */
    public function addRiskyTest(Test $test, \Exception $e, $time)
    {
        $this->addSkippedTest($test, $e, $time);
    }

    /**
     * Skipped test.
     *
     * @param Test $test
     * @param \Exception $e
     * @param float $time
     */
    public function addSkippedTest(Test $test, \Exception $e, $time)
    {
        $this->writeServiceMessage(
            self::MESSAGE_TEST_IGNORED,
            $test,
            array(
                'message' => $e->getMessage(),
            )
        );
    }

    /**
     * A failure occurred.
     *
     * @param Test $test
     * @param Warning $e
     * @param float $time
     */
    public function addWarning(Test $test, Warning $e, $time)
    {
        // Since PHPUnit 5.1
        if ($e instanceof \Exception) {
            $this->addError($test, $e, $time);
        } else {
            $this->writeServiceMessage(
                self::MESSAGE_TEST_FAILED,
                $test,
                array(
                    'message' => $e->getMessage(),
                )
            );
        }
    }


    /**
     * A test suite started.
     *
     * @param TestSuite $suite
     */
    public function startTestSuite(TestSuite $suite)
    {
        $this->writeServiceMessage(
            self::MESSAGE_SUITE_STARTED,
            $suite
        );
    }

    /**
     * A test suite ended.
     *
     * @param TestSuite $suite
     */
    public function endTestSuite(TestSuite $suite)
    {
        $this->writeServiceMessage(
            self::MESSAGE_SUITE_FINISHED,
            $suite
        );
    }

    /**
     * A test started.
     *
     * @param Test $test
     */
    public function startTest(Test $test)
    {
        $this->writeServiceMessage(
            self::MESSAGE_TEST_STARTED,
            $test,
            array(
                'captureStandardOutput' => $this->captureStandardOutput,
            )
        );
    }

    /**
     * A test ended.
     *
     * @param Test $test
     * @param float $time seconds
     */
    public function endTest(Test $test, $time)
    {
        $this->writeServiceMessage(
            self::MESSAGE_TEST_FINISHED,
            $test,
            array(
                'duration' => floor($time * 1000),
            )
        );
    }
}
