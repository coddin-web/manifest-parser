<?php

declare(strict_types=1);

namespace Tests\Unit;

use Coddin\ManifestParser\ManifestData;
use Coddin\ManifestParser\Processor;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ProcessorTest extends TestCase
{
    /** @var MockObject & ClientInterface  */
    private $client;
    /** @var MockObject & ResponseInterface */
    private $response;
    /** @var MockObject & StreamInterface */
    private $body;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->body = $this->createMock(StreamInterface::class);
    }

    public function testMissingUrl(): void
    {
        $processor = new Processor($this->client);
        $manifestData = $processor->getManifestData('');

        $this->assertError(
            $manifestData,
            'The manifest URL is not set'
        );
    }

    public function testFailedUrlParse(): void
    {
        $processor = new Processor($this->client);
        $manifestData = $processor->getManifestData('/search/year:1234');

        $this->assertError(
            $manifestData,
            'Parsing the provided URL failed'
        );
    }

    public function testMalformedCall(): void
    {
        $this->client
            ->expects(self::once())
            ->method('request')
            ->willThrowException(new RequestException('', $this->createMock(RequestInterface::class)));

        $processor = new Processor($this->client);
        $manifestData = $processor->getManifestData('fake-url.test');

        $this->assertError(
            $manifestData,
            'Something went wrong when retrieving the manifest data, perhaps the URL is malformed'
        );
    }

    public function testEmptyBody(): void
    {
        $this->setBodyOnClientResponse('');

        $processor = new Processor($this->client);
        $manifestData = $processor->getManifestData('fake-url.test');

        $this->assertError(
            $manifestData,
            'The manifest URL is working but returned an empty body'
        );
    }

    public function testFailedJsonDecode(): void
    {
        $this->setBodyOnClientResponse('[{1}]');

        $processor = new Processor($this->client);
        $manifestData = $processor->getManifestData('fake-url.test');

        $this->assertError(
            $manifestData,
            'Syntax error'
        );
    }

    public function testNonArrayJsonDecode(): void
    {
        $this->setBodyOnClientResponse('true');

        $processor = new Processor($this->client);
        $manifestData = $processor->getManifestData('fake-url.test');

        $this->assertError(
            $manifestData,
            'Decoding the manifest JSON did not result in an array'
        );
    }

    public function testGetManifestData(): void
    {
        $this->setBodyOnClientResponse('{
            "app.css": "/css/app.css",
            "app.js": "/js/app.js"
        }');

        $processor = new Processor($this->client);
        $manifestData = $processor->getManifestData('http://fake-url.test:1234');

        self::assertTrue($manifestData->hasData());
        self::assertNotEmpty($manifestData->getScripts());
        self::assertNotEmpty($manifestData->getStyleSheets());
        self::assertCount(1, $manifestData->getScripts());
        self::assertCount(1, $manifestData->getStyleSheets());
        self::assertEquals('http://fake-url.test:1234/js/app.js', $manifestData->getScripts()[0]);
        self::assertEquals('http://fake-url.test:1234/css/app.css', $manifestData->getStyleSheets()[0]);
    }

    private function setBodyOnClientResponse(string $bodyContents): void
    {
        $this->client
            ->expects(self::once())
            ->method('request')
            ->willReturn($this->response);

        $this->response
            ->expects(self::once())
            ->method('getBody')
            ->willReturn($this->body);

        $this->body
            ->expects(self::once())
            ->method('getContents')
            ->willReturn($bodyContents);
    }

    private function assertError(ManifestData $manifestData, string $errorMessage): void
    {
        self::assertFalse($manifestData->hasData());
        self::assertTrue($manifestData->hasError());
        self::assertEquals($errorMessage, $manifestData->getError());
    }
}
