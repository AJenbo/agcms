<?php namespace AGCMS\Tests;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

class TestResponse
{
    /** @var Response */
    private $response;

    /**
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Assert that the client response has a given code.
     *
     * @param int $code
     *
     * @return $this
     */
    public function assertResponseStatus(int $code): self
    {
        $actual = $this->response->getStatusCode();

        Assert::assertEquals($code, $this->response->getStatusCode(), "Expected status code {$code}, got {$actual}.");

        return $this;
    }

    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param string $uri
     *
     * @return $this
     */
    public function assertRedirect(string $uri = null): self
    {
        Assert::assertTrue(
            $this->response->isRedirect(),
            'Response status code [' . $this->response->getStatusCode() . '] is not a redirect status code.'
        );
        if (!is_null($uri)) {
            Assert::assertEquals($this->toUrl($uri), $this->response->headers->get('Location'));
        }

        return $this;
    }

    /**
     * Assert that the given string is contained within the response.
     *
     * @param string $value
     *
     * @return $this
     */
    public function assertSee(string $value): self
    {
        Assert::assertContains($value, $this->response->getContent());

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response.
     *
     * @param string $value
     *
     * @return $this
     */
    public function assertNotSee(string $value): self
    {
        Assert::assertNotContains($value, $this->response->getContent());

        return $this;
    }

    /**
     * Make sure a url is valid.
     *
     * @param string $uri
     *
     * @return string
     */
    private function toUrl(string $uri): string
    {
        if (0 !== mb_strpos($uri, 'http')) {
            $uri = config('base_url') . $uri;
        }

        return $uri;
    }
}
