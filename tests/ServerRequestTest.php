<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\ServerRequest;
use Philharmony\Http\Message\UploadedFile;
use PHPUnit\Framework\TestCase;

class ServerRequestTest extends TestCase
{
    public function testConstructorInitializesAllParameters(): void
    {
        $server = ['REMOTE_ADDR' => '127.0.0.1'];
        $cookies = ['sid' => 'abc'];
        $query = ['p' => '1'];
        $body = ['u' => 'admin'];
        $files = ['f' => UploadedFile::create('php://temp', 0, UPLOAD_ERR_OK)];

        $request = ServerRequest::make('POST', '/api', 'body', [], '1.1', $server, $cookies, $query, $files, $body);

        $this->assertEquals($server, $request->getServerParams());
        $this->assertEquals($cookies, $request->getCookieParams());
        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals($files, $request->getUploadedFiles());
        $this->assertEquals($body, $request->getParsedBody());
    }

    public function testWithAttributeCreatesNewInstanceWithData(): void
    {
        $request = ServerRequest::make('GET', '/');
        $new = $request->withAttribute('id', 42);

        $this->assertNotSame($request, $new);
        $this->assertEquals(42, $new->getAttribute('id'));
        $this->assertArrayHasKey('id', $new->getAttributes());
        $this->assertEquals('default', $new->getAttribute('missing', 'default'));
    }

    public function testWithoutAttributeRemovesData(): void
    {
        $request = ServerRequest::make('GET', '/')->withAttribute('id', 42);
        $new = $request->withoutAttribute('id');

        $this->assertNotSame($request, $new);
        $this->assertNull($new->getAttribute('id'));
    }

    public function testWithCookieParamsReturnsNewInstanceWithUpdatedData(): void
    {
        $request = ServerRequest::make('GET', '/');
        $cookies = ['session_id' => 'abc123'];

        $new = $request->withCookieParams($cookies);

        $this->assertNotSame($request, $new);
        $this->assertEquals($cookies, $new->getCookieParams());
    }

    public function testWithQueryParamsReturnsNewInstanceWithUpdatedData(): void
    {
        $request = ServerRequest::make('GET', '/');
        $query = ['page' => '1', 'sort' => 'desc'];

        $new = $request->withQueryParams($query);

        $this->assertNotSame($request, $new);
        $this->assertEquals($query, $new->getQueryParams());
    }

    public function testWithoutAttributeReturnsSameInstanceIfMissing(): void
    {
        $request = ServerRequest::make('GET', '/');
        $new = $request->withoutAttribute('non_existent');

        $this->assertSame($request, $new);
    }

    public function testWithParsedBodyAcceptsValidTypes(): void
    {
        $request = ServerRequest::make('POST', '/');

        $this->assertIsArray($request->withParsedBody(['a' => 'b'])->getParsedBody());
        $this->assertIsObject($request->withParsedBody(new \stdClass())->getParsedBody());
        $this->assertNull($request->withParsedBody(null)->getParsedBody());
    }

    public function testWithParsedBodyThrowsExceptionOnInvalidType(): void
    {
        $request = ServerRequest::make('POST', '/');

        $this->expectException(\InvalidArgumentException::class);
        $request->withParsedBody('string is not allowed');
    }

    public function testWithUploadedFilesNormalizesData(): void
    {
        $request = ServerRequest::make('GET', '/');
        $files = ['avatar' => UploadedFile::create('/tmp/php123', 1024, UPLOAD_ERR_OK)];

        $new = $request->withUploadedFiles($files);

        $this->assertNotSame($request, $new);
        $this->assertSame($files, $new->getUploadedFiles());
    }
}
