<?php

namespace BunqTest;

use bunq\Exception\TooManyRequestsException;

/**
 * The bunq helpers in includes/bunq.php that do not need a bunq session.
 */
final class BunqFunctionsTest extends TestCase
{
    public function testRetryReturnsTheResultOfTheCallback()
    {
        $calls = 0;

        $result = bunq_retry(function () use (&$calls) {
            $calls++;

            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertSame(1, $calls);
        $this->assertSame(array(), Environment::$logs);
    }

    public function testRetryDoesNotRetryOtherExceptions()
    {
        $calls = 0;
        $exception = new \RuntimeException('bunq is down');

        try {
            bunq_retry(function () use (&$calls, $exception) {
                $calls++;
                throw $exception;
            });
            $this->fail('The exception should have been rethrown');
        } catch (\RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }

        $this->assertSame(1, $calls);
        $this->assertSame(array(), Environment::$logs);
    }

    public function testRetryGivesUpImmediatelyWhenOnlyOneAttemptIsAllowed()
    {
        $calls = 0;
        $exception = new TooManyRequestsException('Too many requests', 429, 'response-id');

        $start = microtime(true);
        try {
            bunq_retry(function () use (&$calls, $exception) {
                $calls++;
                throw $exception;
            }, 1);
            $this->fail('The exception should have been rethrown');
        } catch (TooManyRequestsException $caught) {
            $this->assertSame($exception, $caught);
        }

        $this->assertSame(1, $calls);
        $this->assertLessThan(0.5, microtime(true) - $start, 'No waiting when there is no attempt left');
        $this->assertSame(array(), Environment::$logs);
    }

    /**
     * @group slow
     */
    public function testRetriesAfterARateLimitResponse()
    {
        $calls = 0;

        $start = microtime(true);
        $result = bunq_retry(function () use (&$calls) {
            $calls++;
            if ($calls === 1) {
                throw new TooManyRequestsException('Too many requests', 429, 'response-id');
            }

            return 'ok';
        }, 3);

        $this->assertSame('ok', $result);
        $this->assertSame(2, $calls);
        $this->assertGreaterThanOrEqual(3.0, microtime(true) - $start, 'Waits 3 seconds before the retry');
        $this->assertSame(
            array('bunq rate limit hit, retrying in 3 seconds (attempt 1 of 3)'),
            Environment::logMessages('warning')
        );
    }

    /**
     * @group slow
     */
    public function testGivesUpAfterTheConfiguredNumberOfAttempts()
    {
        $calls = 0;

        try {
            bunq_retry(function () use (&$calls) {
                $calls++;
                throw new TooManyRequestsException('Too many requests', 429, 'response-id');
            }, 2);
            $this->fail('The exception should have been rethrown');
        } catch (TooManyRequestsException $caught) {
            $this->assertSame('Too many requests', $caught->getMessage());
        }

        $this->assertSame(2, $calls);
        $this->assertSame(
            array('bunq rate limit hit, retrying in 3 seconds (attempt 1 of 2)'),
            Environment::logMessages('warning')
        );
    }

    public function testEnvironmentFollowsTheTestModeSetting()
    {
        $this->assertSame('SANDBOX', bunq_environment(true)->getChoiceString());
        $this->assertSame('PRODUCTION', bunq_environment(false)->getChoiceString());
    }

    public function testBankAccountsWithoutAnApiContextExplainTheSetupIsIncomplete()
    {
        $this->assertSame(array('' => 'API key not valid or not setup yet'), bunq_get_bank_accounts(false));
        $this->assertSame(array('' => 'API key not valid or not setup yet'), bunq_get_bank_accounts(''));
        $this->assertSame(array(), Environment::$logs);
    }

    public function testLoadingAnApiContextFromInvalidJsonFailsAndIsLogged()
    {
        $this->assertFalse(bunq_load_api_context_from_json('not json'));
        $this->assertFalse(bunq_load_api_context_from_json(''));

        $this->assertCount(1, Environment::$logs, 'Only the invalid JSON is logged; an empty context is a normal state');
        $this->assertSame('error', Environment::$logs[0]['level']);
        $this->assertSame('bunq', Environment::$logs[0]['source']);
        $this->assertStringContainsString('json_decode error', Environment::$logs[0]['message']);
    }

    /**
     * @dataProvider descriptions
     */
    public function testPaymentDescriptionOnlyKeepsWhatSepaAllows($description, $expected)
    {
        $this->assertSame($expected, bunq_sanitize_payment_description($description));
    }

    public function descriptions()
    {
        return array(
            'plain text' => array('Refund order 123 Test Shop', 'Refund order 123 Test Shop'),
            'hash sign' => array('Refund order #123 Test Shop', 'Refund order 123 Test Shop'),
            'emoji' => array('Thanks 🙏 for your order 🎉', 'Thanks for your order'),
            'allowed punctuation' => array("Order 12/3 - ref? (x): 1,5 'a' +b.", "Order 12/3 - ref? (x): 1,5 'a' +b."),
            'accented letters' => array('Café Zürich Ærø', 'Café Zürich Ærø'),
            'other punctuation' => array('A & B "C" _D_ E*F G=H I;J K!L', 'A B C D E F G H I J K L'),
            'multiple spaces' => array('a    b', 'a b'),
            'whitespace characters' => array("a\tb\nc\r\nd", 'a b c d'),
            'surrounding whitespace' => array('   padded   ', 'padded'),
            'only rejected characters' => array('###', ''),
            'empty string' => array('', ''),
            'null' => array(null, ''),
            'number' => array(123, '123'),
        );
    }
}
