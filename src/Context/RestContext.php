<?php
declare(strict_types=1);

namespace Behatch\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Behatch\HttpCall\Request;

class RestContext extends BaseContext
{
    public function __construct(
        protected Request $request
    )
    {
    }

    /**
     * Sends a HTTP request
     *
     * @Given I send a :method request to :url
     */
    public function iSendARequestTo($method, $url, PyStringNode $body = null, $files = [])
    {
        return $this->request->send(
            $method,
            $this->locatePath($url),
            [],
            $files,
            $body?->getRaw()
        );
    }

    /**
     * Sends a HTTP request with a some parameters
     *
     * @Given I send a :method request to :url with parameters:
     * @throws \Exception
     */
    public function iSendARequestToWithParameters($method, $url, TableNode $data)
    {
        $files = [];
        $parameters = [];

        foreach ($data->getHash() as $row) {
            if (!isset($row['key'], $row['value'])) {
                throw new \Exception("You must provide a 'key' and 'value' column in your table node.");
            }

            if (\is_string($row['value']) && str_starts_with($row['value'], '@')) {
                $files[$row['key']] = \rtrim(
                        (string) $this->getMinkParameter('files_path'),
                        DIRECTORY_SEPARATOR
                    ) . DIRECTORY_SEPARATOR . \substr($row['value'], 1);
            } else {
                $parameters[$row['key']] = $row['value'];
            }
        }

        return $this->request->send(
            $method,
            $this->locatePath($url),
            $parameters,
            $files
        );
    }

    /**
     * Sends a HTTP request with a body
     *
     * @Given I send a :method request to :url with body:
     */
    public function iSendARequestToWithBody($method, $url, PyStringNode $body)
    {
        return $this->iSendARequestTo($method, $url, $body);
    }

    /**
     * Checks, whether the response content is equal to given text
     *
     * @Then the response should be equal to
     * @Then the response should be equal to:
     * @throws ExpectationException
     */
    public function theResponseShouldBeEqualTo(PyStringNode|string $expected): void
    {
        $expected = \str_replace('\\"', '"', (string)$expected);
        $actual = $this->request->getContent();
        $message = "Actual response is '$actual', but expected '$expected'";
        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Checks, whether the response content is null or empty string
     *
     * @Then the response should be empty
     * @throws ExpectationException
     */
    public function theResponseShouldBeEmpty(): void
    {
        $actual = $this->request->getContent();
        $message = "The response of the current page is not empty, it is: $actual";
        $this->assertTrue(null === $actual || "" === $actual, $message);
    }

    /**
     * Checks, whether the header name is equal to given text
     *
     * @Then the header :name should be equal to :value
     * @throws ExpectationException
     */
    public function theHeaderShouldBeEqualTo(string $name, string $value): void
    {
        $actual = $this->request->getHttpHeader($name);
        $this->assertEquals(
            \strtolower($value),
            \strtolower($actual),
            "The header '$name' should be equal to '$value', but it is: '$actual'"
        );
    }

    /**
     * Checks, whether the header name is not equal to given text
     *
     * @Then the header :name should not be equal to :value
     * @throws ExpectationException
     */
    public function theHeaderShouldNotBeEqualTo(string $name, string $value): void
    {
        $actual = $this->getSession()->getResponseHeader($name);
        if (\strtolower($value) === \strtolower((string) $actual)) {
            throw new ExpectationException(
                "The header '$name' is equal to '$actual'",
                $this->getSession()->getDriver()
            );
        }
    }

    /**
     * @throws ExpectationException
     */
    public function theHeaderShouldBeContains($name, $value): void
    {
        \trigger_error(
            \sprintf(
                'The %s function is deprecated since version 3.1 and will be removed in 4.0. Use the %s::theHeaderShouldContain function instead.',
                __METHOD__,
                self::class
            ),
            E_USER_DEPRECATED
        );
        $this->theHeaderShouldContain($name, $value);
    }

    /**
     * Checks, whether the header name contains the given text
     *
     * @Then the header :name should contain :value
     * @throws ExpectationException
     */
    public function theHeaderShouldContain(string $name, string $value): void
    {
        $actual = $this->request->getHttpHeader($name);
        $this->assertContains(
            $value,
            $actual,
            "The header '$name' should contain value '$value', but actual value is '$actual'"
        );
    }

    /**
     * Checks, whether the header name doesn't contain the given text
     *
     * @Then the header :name should not contain :value
     * @throws ExpectationException
     */
    public function theHeaderShouldNotContain(string $name, string $value): void
    {
        $this->assertNotContains(
            $value,
            $this->request->getHttpHeader($name),
            "The header '$name' contains '$value'"
        );
    }

    /**
     * Checks, whether the header not exist
     *
     * @Then the header :name should not exist
     * @throws ExpectationException
     */
    public function theHeaderShouldNotExist(string $name): void
    {
        $this->not(
            function () use ($name): void {
                $this->theHeaderShouldExist($name);
            },
            "The header '$name' exists"
        );
    }

    protected function theHeaderShouldExist($name)
    {
        return $this->request->getHttpHeader($name);
    }

    /**
     * @Then the header :name should match :regex
     * @throws ExpectationException
     */
    public function theHeaderShouldMatch($name, $regex): void
    {
        $actual = $this->request->getHttpHeader($name);

        $this->assertEquals(
            1,
            \preg_match($regex, $actual),
            "The header '$name' should match '$regex', but it is: '$actual'"
        );
    }

    /**
     * @Then the header :name should not match :regex
     * @throws ExpectationException
     */
    public function theHeaderShouldNotMatch($name, $regex): void
    {
        $this->not(
            function () use ($name, $regex): void {
                $this->theHeaderShouldMatch($name, $regex);
            },
            "The header '$name' should not match '$regex'"
        );
    }

    /**
     * Checks, that the response header expire is in the future
     *
     * @Then the response should expire in the future
     * @throws \Exception
     */
    public function theResponseShouldExpireInTheFuture(): void
    {
        $date = new \DateTime($this->request->getHttpRawHeader('Date')[0]);
        $expires = new \DateTime($this->request->getHttpRawHeader('Expires')[0]);

        $this->assertSame(
            1,
            $expires->diff($date)->invert,
            sprintf('The response doesn\'t expire in the future (%s)', $expires->format(DATE_ATOM))
        );
    }

    /**
     * Add an header element in a request
     *
     * @Given I add :name header equal to :value
     */
    public function iAddHeaderEqualTo(string $name, $value): void
    {
        $this->theHeaderIsSetEqualTo($name, $value);
    }

    /**
     * Add an header element in a request
     *
     * @Given the header :name is set equal to :value
     */
    public function theHeaderIsSetEqualTo(string $name, $value): void
    {
        $this->request->setHttpHeader($name, $value);
    }

    /**
     * @Then the response should be encoded in :encoding
     * @throws \Exception
     */
    public function theResponseShouldBeEncodedIn($encoding): void
    {
        $content = $this->request->getContent();
        if (!mb_check_encoding($content, $encoding)) {
            throw new \Exception("The response is not encoded in $encoding");
        }

        $this->theHeaderShouldContain('Content-Type', "charset=$encoding");
    }

    /**
     * @Then print last response headers
     */
    public function printLastResponseHeaders(): void
    {
        $text = '';
        $headers = $this->request->getHttpHeaders();

        foreach (array_keys($headers) as $name) {
            $text .= $name . ': ' . $this->request->getHttpHeader($name) . "\n";
        }
        echo $text;
    }

    /**
     * @Then print the corresponding curl command
     */
    public function printTheCorrespondingCurlCommand(): void
    {
        $method = $this->request->getMethod();
        $url = $this->request->getUri();

        $headers = '';
        foreach ($this->request->getServer() as $name => $value) {
            if ($name !== 'HTTPS' && !\str_starts_with((string) $name, 'HTTP_')) {
                $headers .= " -H '$name: $value'";
            }
        }

        $data = '';
        $params = $this->request->getParameters();
        if (!empty($params)) {
            $query = http_build_query($params);
            $data = " --data '$query'";
        }

        echo "curl -X $method$data$headers '$url'";
    }
}
