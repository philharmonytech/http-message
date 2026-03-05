<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\Request;
use Philharmony\Http\Message\Uri;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $request = Request::create('POST', 'http://localhost');

        $this->assertEquals('POST', $request->getMethod());
        $this->assertInstanceOf(Uri::class, $request->getUri());
    }

    public function testConstructorInitializesHostHeaderFromUri(): void
    {
        $uri = Uri::create('https://philharmony.tech:8080');
        $request = Request::create('GET', $uri);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($uri, $request->getUri());

        $this->assertSame('philharmony.tech:8080', $request->getHeaderLine('Host'));
    }

    public function testIsHttpsDetectsSchemeCorrectingFromUri(): void
    {
        $requestHttps = Request::create('GET', 'https://philharmony.tech');
        $this->assertTrue($requestHttps->isHttps());
        $uri = Uri::create('http://philharmony.tech');
        $requestHttp = $requestHttps->withUri($uri);
        $this->assertFalse($requestHttp->isHttps());
    }

    public function testHostHeaderIsPreservedWhenRequested()
    {
        $request = Request::create('GET', 'https://philharmony.tech', 'Philharmony', ['Host' => 'philharmony.com']);
        $this->assertEquals(['Host' => ['philharmony.com']], $request->getHeaders());
        $requestWithNewUri = $request->withUri(
            Uri::create('http://www.philharmony.com/history?topic=1&page=2'),
            true
        );
        $this->assertEquals('philharmony.com', $requestWithNewUri->getHeaderLine('Host'));
    }

    public function testWithUriDoesNotUpdateHostHeaderIfUriHasNoHost(): void
    {
        $request = Request::create('GET', 'http://philharmony.tech');
        $this->assertEquals('philharmony.tech', $request->getHeaderLine('Host'));

        $relativeUri = Uri::create('/api/v1');
        $this->assertEquals('', $relativeUri->getHost());

        $updatedRequest = $request->withUri($relativeUri);

        $this->assertEquals('/api/v1', $updatedRequest->getUri()->getPath());
        $this->assertEquals('philharmony.tech', $updatedRequest->getHeaderLine('Host'));
    }

    public function testWithMethodImmutabilityAndNormalization(): void
    {
        $request = Request::create('GET', '/');

        $newRequest = $request->withMethod('post');

        $this->assertSame('POST', $newRequest->getMethod());
        $this->assertNotSame($request, $newRequest);
    }

    public function testGetRequestTargetDefaultsToUriPathAndQuery(): void
    {
        $request = Request::create('GET', 'https://philharmony.tech/search?q=php');
        $this->assertSame('/search?q=php', $request->getRequestTarget());

        $requestNoPath = Request::create('GET', 'https://philharmony.tech');
        $this->assertSame('/', $requestNoPath->getRequestTarget());
    }

    public function testWithRequestTargetThrowsExceptionForInvalidTarget(): void
    {
        $request = Request::create('GET', '/');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request target');

        $request->withRequestTarget('/path with spaces');
    }

    public function testWithRequestTargetReturnsNewInstanceWithCustomTarget(): void
    {
        $request = Request::create('GET', '/');
        $customTarget = '*';

        $newRequest = $request->withRequestTarget($customTarget);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame($customTarget, $newRequest->getRequestTarget());
    }

    public function testHttpMethodProperties(): void
    {
        $get = Request::create('GET', '/');
        $this->assertTrue($get->isSafe());
        $this->assertTrue($get->isIdempotent());

        $put = Request::create('PUT', '/');
        $this->assertFalse($put->isSafe());
        $this->assertTrue($put->isIdempotent());

        $post = Request::create('POST', '/');
        $this->assertFalse($post->isSafe());
        $this->assertFalse($post->isIdempotent());
    }

    public function testContentTypeHelpersDetectJsonAndForm(): void
    {
        $requestJson = Request::create('POST', '/', '', ['Content-Type' => 'application/json']);
        $this->assertTrue($requestJson->isJson());
        $this->assertFalse($requestJson->isForm());

        $requestJsonCharset = Request::create('POST', '/', '', ['Content-Type' => 'application/json; charset=utf-8']);
        $this->assertTrue($requestJsonCharset->isJson());

        $requestForm = Request::create('POST', '/', '', ['Content-Type' => 'application/x-www-form-urlencoded']);
        $this->assertTrue($requestForm->isForm());
        $this->assertFalse($requestForm->isJson());

        $requestMultipart = Request::create('POST', '/', '', ['Content-Type' => 'multipart/form-data; boundary=something']);
        $this->assertTrue($requestMultipart->isForm());

        $requestEmpty = Request::create('GET', '/');
        $this->assertFalse($requestEmpty->isJson());
        $this->assertFalse($requestEmpty->isForm());
    }
}
