<?php

namespace Tests;

use App\Services\ConfigService;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

class TestResponse
{
    use ArraySubsetAsserts;

    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Gets the current response content.
     */
    public function getContent(): string
    {
        return $this->response->getContent() ?: '';
    }

    /**
     * Assert that the client response has a given code.
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
     * @return $this
     */
    public function assertRedirect(?string $uri = null): self
    {
        Assert::assertTrue(
            $this->response->isRedirect(),
            'Response status code [' . $this->response->getStatusCode() . '] is not a redirect status code.'
        );
        if (null !== $uri) {
            Assert::assertEquals($this->toUrl($uri), $this->response->headers->get('Location'));
        }

        return $this;
    }

    /**
     * Assert that the given string is contained within the response.
     *
     * @return $this
     */
    public function assertSee(string $value): self
    {
        Assert::assertStringContainsString($value, $this->getContent());

        return $this;
    }

    /**
     * Assert that the given string is not contained within the response.
     *
     * @return $this
     */
    public function assertNotSee(string $value): self
    {
        Assert::assertStringNotContainsString($value, $this->getContent());

        return $this;
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * @param array<string, mixed> $data
     *
     * @return $this
     */
    public function assertJson(array $data, bool $strict = false): self
    {
        self::assertArraySubset($data, $this->decodeResponseJson(), $strict, $this->assertJsonMessage($data));

        return $this;
    }

    /**
     * Assert that the response has a given JSON structure.
     *
     * @param null|array<mixed>        $structure
     * @param null|array<array<mixed>> $responseData
     *
     * @return $this
     */
    public function assertJsonStructure(?array $structure = null, ?array $responseData = null): self
    {
        if (null === $structure) {
            return $this->assertJson($this->json());
        }
        if (null === $responseData) {
            $responseData = $this->decodeResponseJson();
        }
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                Assert::assertArrayHasKey($key, $responseData);
                Assert::assertIsArray($responseData[$key]);
                $this->assertJsonStructure($value, $responseData[$key]);

                continue;
            }

            if (is_string($value) || is_int($value)) {
                Assert::assertArrayHasKey($value, $responseData);

                continue;
            }

            Assert::fail('Invalid JSON structure.');
        }

        return $this;
    }

    /**
     * Get the assertion message for assertJson.
     *
     * @param array<mixed> $data
     */
    private function assertJsonMessage(array $data): string
    {
        $expected = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $actual = json_encode($this->decodeResponseJson(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return 'Unable to find JSON: ' . PHP_EOL . PHP_EOL
            . "[{$expected}]" . PHP_EOL . PHP_EOL
            . 'within response JSON:' . PHP_EOL . PHP_EOL
            . "[{$actual}]." . PHP_EOL . PHP_EOL;
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @return array<mixed>
     */
    private function decodeResponseJson(): array
    {
        $decodedResponse = json_decode($this->getContent(), true);
        if (!is_array($decodedResponse)) {
            Assert::fail('Invalid JSON was returned from the route.');
        }

        return $decodedResponse;
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @return array<mixed>
     */
    public function json(): array
    {
        return $this->decodeResponseJson();
    }

    /**
     * Make sure a url is valid.
     */
    private function toUrl(string $uri): string
    {
        if (0 !== mb_strpos($uri, 'http')) {
            $uri = ConfigService::getString('base_url') . $uri;
        }

        return $uri;
    }
}
