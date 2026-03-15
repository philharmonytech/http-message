<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\ServerRequest;
use Philharmony\Http\Message\Stream;
use Philharmony\Http\Message\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testWithCookieParamsReturnsSameInstanceWhenUnchanged(): void
    {
        $cookies = ['session_id' => 'abc123'];
        $request = ServerRequest::make('GET', '/', '', [], '1.1', [], $cookies);

        $same = $request->withCookieParams($cookies);

        $this->assertSame($request, $same);
    }

    public function testWithQueryParamsReturnsNewInstanceWithUpdatedData(): void
    {
        $request = ServerRequest::make('GET', '/');
        $query = ['page' => '1', 'sort' => 'desc'];

        $new = $request->withQueryParams($query);

        $this->assertNotSame($request, $new);
        $this->assertEquals($query, $new->getQueryParams());
    }

    public function testWithQueryParamsReturnsSameInstanceWhenUnchanged(): void
    {
        $query = ['page' => '1'];
        $request = ServerRequest::make('GET', '/', '', [], '1.1', [], [], $query);

        $same = $request->withQueryParams($query);

        $this->assertSame($request, $same);
    }

    public function testWithoutAttributeReturnsSameInstanceIfMissing(): void
    {
        $request = ServerRequest::make('GET', '/');
        $new = $request->withoutAttribute('non_existent');

        $this->assertSame($request, $new);
    }

    public function testWithAttributeReturnsSameInstanceWhenUnchanged(): void
    {
        $request = ServerRequest::make('GET', '/')->withAttribute('id', 42);

        $same = $request->withAttribute('id', 42);

        $this->assertSame($request, $same);
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

    #[DataProvider('uploadedFilesProvider')]
    public function testWithUploadedFilesNormalizesData(array $files): void
    {
        $request = ServerRequest::make('GET', '/');

        $new = $request->withUploadedFiles($files);

        $this->assertSame($files, $new->getUploadedFiles());
    }

    #[DataProvider('uploadedFilesProvider')]
    public function testWithUploadedFilesReturnsSameInstanceWhenUnchanged(array $files): void
    {
        $request = ServerRequest::make('GET', '/', '', [], '1.1', [], [], [], $files);

        $same = $request->withUploadedFiles($files);

        $this->assertSame($request, $same);
    }

    /**
     * @return array<string, array{files: array<int|string, mixed>}>
     */
    public static function uploadedFilesProvider(): array
    {
        return [
            'files with a single structure' => [
                'files' => [
                    'avatar' => UploadedFile::create('/tmp/php123', 1024, UPLOAD_ERR_OK),
                ],
            ],
            'files with a nested structure' => [
                'files' => [
                    'gallery' => [
                        'first' => UploadedFile::create('/tmp/php124', 2048, UPLOAD_ERR_OK),
                    ],
                ],
            ],
        ];
    }

    public function testWithUploadedFilesThrowsOnInvalidType(): void
    {
        $request = ServerRequest::make('GET', '/');

        $this->expectException(\InvalidArgumentException::class);
        $request->withUploadedFiles(['invalid' => 'string']);
    }

    public function testInputAndHasPreferQueryParamsOverBody(): void
    {
        $query = ['user' => 'query'];
        $body = ['user' => ['name' => 'body']];
        $request = ServerRequest::make('POST', '/', '', [], '1.1', [], [], $query, [], $body);

        $this->assertTrue($request->has('user'));
        $this->assertSame('query', $request->input('user'));
        $this->assertSame('body', $request->input('user.name'));
        $this->assertSame('fallback', $request->input('missing', 'fallback'));
    }

    public function testInputReturnsDefaultWhenPathMissing(): void
    {
        $body = ['user' => 'string'];
        $request = ServerRequest::make('POST', '/', '', [], '1.1', [], [], [], [], $body);

        $this->assertSame('fallback', $request->input('user.name', 'fallback'));
    }

    public function testInputReturnsDefaultWhenParsedBodyIsObject(): void
    {
        $request = ServerRequest::make('POST', '/')->withParsedBody((object) ['a' => 1]);

        $this->assertSame('fallback', $request->input('a', 'fallback'));
    }

    #[DataProvider('parsedBodyNullProvider')]
    public function testGetParsedBodyReturnsNull(
        ServerRequest $request
    ): void {
        $this->assertNull($request->getParsedBody());
    }

    /**
     * @return array<string, array{request: ServerRequest}>
     */
    public static function parsedBodyNullProvider(): array
    {
        return [
            'no content-type' => [
                'request' => ServerRequest::make('POST', '/', '{"a":1}'),
            ],
            'unknown content-type' => [
                'request' => ServerRequest::make('POST', '/', '{"a":1}', ['Content-Type' => 'application/unknown']),
            ],
            'not json or form' => [
                'request' => ServerRequest::make('POST', '/', 'plain', ['Content-Type' => 'text/plain']),
            ],
            'invalid json' => [
                'request' => ServerRequest::make('POST', '/', '{"a":', ['Content-Type' => 'application/json']),
            ],
            'empty form body' => [
                'request' => ServerRequest::make('POST', '/', '', ['Content-Type' => 'application/x-www-form-urlencoded']),
            ],
            'empty json body' => [
                'request' => ServerRequest::make('POST', '/', '', ['Content-Type' => 'application/json']),
            ],
        ];
    }

    #[DataProvider('parsedBodySuccessProvider')]
    public function testGetParsedBodyParsesBody(
        string $body,
        array $headers,
        array $expected
    ): void {
        $request = ServerRequest::make('POST', '/', $body, $headers);

        $this->assertSame($expected, $request->getParsedBody());
    }

    /**
     * @return array<string, array{body: string, headers: array<string, string>, expected: array<mixed>}>
     */
    public static function parsedBodySuccessProvider(): array
    {
        return [
            'body is json' => [
                'body' => '{"a":1}',
                'headers' => ['Content-Type' => 'application/json'],
                'expected' => ['a' => 1],
            ],
            'body is form' => [
                'body' => 'name=John&filters[active]=1',
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'expected' => ['name' => 'John', 'filters' => ['active' => '1']],
            ],
        ];
    }

    public function testParsedBodyOverridesHeaderParsing(): void
    {
        $request = ServerRequest::make('POST', '/', '{"a":1}', ['Content-Type' => 'application/json'])
            ->withParsedBody(['override' => true]);

        $this->assertSame(['override' => true], $request->getParsedBody());
    }

    public function testReadBodyUsesNonSeekableStream(): void
    {
        $resource = fopen('php://temp', 'r+');
        $stream = new class ($resource) extends Stream {
            public function isSeekable(): bool
            {
                return false;
            }
            public function __toString(): string
            {
                return 'a=1';
            }
        };

        $request = ServerRequest::make('POST', '/', '', ['Content-Type' => 'application/x-www-form-urlencoded'])
            ->withBody($stream);

        $this->assertSame(['a' => '1'], $request->getParsedBody());
        fclose($resource);
    }

    public function testReadBodyReturnsCachedBodyOnSecondCall(): void
    {
        $request = ServerRequest::make('POST', '/', 'a=1', ['Content-Type' => 'application/x-www-form-urlencoded']);
        $first = $request->getRawBody();
        $second = $request->getRawBody();

        $this->assertSame('a=1', $first);
        $this->assertSame($first, $second);
    }
}
