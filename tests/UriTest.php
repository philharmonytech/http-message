<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class UriTest extends TestCase
{
    #[DataProvider('getUriDataProvider')]
    public function testCreateUri(
        string $uri,
        array $expectedParts
    ): void {
        $uriObj = Uri::create($uri);

        $this->assertSame($expectedParts['scheme'], $uriObj->getScheme());
        $this->assertSame($expectedParts['host'], $uriObj->getHost());
        $this->assertSame($expectedParts['port'], $uriObj->getPort());
        $this->assertSame($expectedParts['explicitPort'], $uriObj->getExplicitPort());
        $this->assertSame($expectedParts['path'], $uriObj->getPath());
        $this->assertSame($expectedParts['query'], $uriObj->getQuery());
        $this->assertSame($expectedParts['fragment'], $uriObj->getFragment());
        $this->assertSame($expectedParts['authority'], $uriObj->getAuthority());
        $this->assertSame($expectedParts['userInfo'], $uriObj->getUserInfo());
        $this->assertSame($expectedParts['uri'], (string)$uriObj);
    }

    /**
     * @return array<string, array{
     *     uri: string,
     *     expectedParts: array{
     *         scheme: string,
     *         host: string,
     *         port: ?int,
     *         explicitPort: ?int,
     *         path: string,
     *         query: string,
     *         fragment: string,
     *         authority: string,
     *         userInfo: string,
     *         uri: string
     *     }
     * }>
     */
    public static function getUriDataProvider(): array
    {
        return [
            'default uri' => [
                'uri' => '',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => '',
                ],
            ],
            'root path only' => [
                'uri' => '/',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => '/',
                ],
            ],
            'urn without authority' => [
                'uri' => 'urn:isbn:0-395-36341-1',
                'expectedParts' => [
                    'scheme' => 'urn',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => 'isbn:0-395-36341-1',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => 'urn:isbn:0-395-36341-1',
                ],
            ],
            'mailto without host' => [
                'uri' => 'mailto:user@example.com',
                'expectedParts' => [
                    'scheme' => 'mailto',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => 'user@example.com',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => 'mailto:user@example.com',
                ],
            ],
            'http with host and port' => [
                'uri' => 'http://philharmony.com:8080/path',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => 8080,
                    'explicitPort' => 8080,
                    'path' => '/path',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com:8080',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com:8080/path',
                ],
            ],
            'https default port' => [
                'uri' => 'https://philharmony.com/path',
                'expectedParts' => [
                    'scheme' => 'https',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/path',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'https://philharmony.com/path',
                ],
            ],
            'ftp with user info' => [
                'uri' => 'ftp://user:pass@ftp.example.com:21/file.txt',
                'expectedParts' => [
                    'scheme' => 'ftp',
                    'host' => 'ftp.example.com',
                    'port' => null,
                    'explicitPort' => 21,
                    'path' => '/file.txt',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'user:pass@ftp.example.com',
                    'userInfo' => 'user:pass',
                    'uri' => 'ftp://user:pass@ftp.example.com/file.txt',
                ],
            ],
            'uri with query' => [
                'uri' => 'http://philharmony.com/search?q=php&lang=en',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/search',
                    'query' => 'q=php&lang=en',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com/search?q=php&lang=en',
                ],
            ],
            'uri with fragment' => [
                'uri' => 'http://philharmony.com:80/page#section-2',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => 80,
                    'path' => '/page',
                    'query' => '',
                    'fragment' => 'section-2',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com/page#section-2',
                ],
            ],
            'full uri' => [
                'uri' => 'http://user:pass@example.com:8080/api/v1?version=2#top',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'port' => 8080,
                    'explicitPort' => 8080,
                    'path' => '/api/v1',
                    'query' => 'version=2',
                    'fragment' => 'top',
                    'authority' => 'user:pass@example.com:8080',
                    'userInfo' => 'user:pass',
                    'uri' => 'http://user:pass@example.com:8080/api/v1?version=2#top',
                ],
            ],
            'relative path' => [
                'uri' => 'path/to/resource',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => 'path/to/resource',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => 'path/to/resource',
                ],
            ],
            'absolute path' => [
                'uri' => '/absolute/path',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/absolute/path',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => '/absolute/path',
                ],
            ],
            'path with dot segments' => [
                'uri' => 'path/././sub/../file.html',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => 'path/././sub/../file.html',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => 'path/././sub/../file.html',
                ],
            ],
            'ipv6 host' => [
                'uri' => 'http://[2001:db8::1]/api',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => '[2001:db8::1]',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/api',
                    'query' => '',
                    'fragment' => '',
                    'authority' => '[2001:db8::1]',
                    'userInfo' => '',
                    'uri' => 'http://[2001:db8::1]/api',
                ],
            ],
            'domain with subdomains' => [
                'uri' => 'https://sub.domain.example.co.uk:443/path',
                'expectedParts' => [
                    'scheme' => 'https',
                    'host' => 'sub.domain.example.co.uk',
                    'port' => null,
                    'explicitPort' => 443,
                    'path' => '/path',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'sub.domain.example.co.uk',
                    'userInfo' => '',
                    'uri' => 'https://sub.domain.example.co.uk/path',
                ],
            ],
            'encoded special chars' => [
                'uri' => 'http://example.com/path?param=value%26other=1',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/path',
                    'query' => 'param=value%26other=1',
                    'fragment' => '',
                    'authority' => 'example.com',
                    'userInfo' => '',
                    'uri' => 'http://example.com/path?param=value%26other=1',
                ],
            ],
            'mixed encoding' => [
                'uri' => 'http://user:pass%40domain.com@example.com:8080/path%3Fquery',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'port' => 8080,
                    'explicitPort' => 8080,
                    'path' => '/path%3Fquery',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'user:pass%40domain.com@example.com:8080',
                    'userInfo' => 'user:pass%40domain.com',
                    'uri' => 'http://user:pass%40domain.com@example.com:8080/path%3Fquery',
                ],
            ],
            'fragment encoding' => [
                'uri' => '#section 1',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '',
                    'query' => '',
                    'fragment' => 'section%201',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => '#section%201',
                ],
            ],
            'unknown scheme with port' => [
                'uri' => 'smb://server:445/share',
                'expectedParts' => [
                    'scheme' => 'smb',
                    'host' => 'server',
                    'port' => 445,
                    'explicitPort' => 445,
                    'path' => '/share',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'server:445',
                    'userInfo' => '',
                    'uri' => 'smb://server:445/share',
                ],
            ],
            'query allows slash' => [
                'uri' => '?a=/foo/bar',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => '',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '',
                    'query' => 'a=/foo/bar',
                    'fragment' => '',
                    'authority' => '',
                    'userInfo' => '',
                    'uri' => '?a=/foo/bar',
                ],
            ],
            'port returned when no scheme is defined' => [
                'uri' => '//philharmony.com:8080',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => 'philharmony.com',
                    'port' => 8080,
                    'explicitPort' => 8080,
                    'path' => '',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com:8080',
                    'userInfo' => '',
                    'uri' => '//philharmony.com:8080',
                ],
            ],
            'empty query is preserved' => [
                'uri' => 'http://philharmony.com?',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com?',
                ],
            ],
            'empty fragment is preserved' => [
                'uri' => 'http://philharmony.com#',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com#',
                ],
            ],
            'path percent encoding' => [
                'uri' => 'http://philharmony.com/foo bar',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/foo%20bar',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com/foo%20bar',
                ],
            ],
            'invalid percent encoding' => [
                'uri' => 'http://philharmony.com/%foo',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/%25foo',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com/%25foo',
                ],
            ],
            'authority without scheme' => [
                'uri' => '//philharmony.com/foo',
                'expectedParts' => [
                    'scheme' => '',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/foo',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => '//philharmony.com/foo',
                ],
            ],
            'utf8 path percent-encoded' => [
                'uri' => 'http://philharmony.com/%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82',
                'expectedParts' => [
                    'scheme' => 'http',
                    'host' => 'philharmony.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'philharmony.com',
                    'userInfo' => '',
                    'uri' => 'http://philharmony.com/%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82',
                ],
            ],
        ];
    }

    #[DataProvider('getIdnDataProvider')]
    public function testIdn(
        string $uri,
        string $host
    ): void {
        if (!\function_exists('idn_to_ascii')) {
            $this->markTestSkipped('Function idn_to_ascii not found');
        }

        $uriObj = Uri::create($uri);
        $this->assertSame($host, $uriObj->getHost());
    }

    /**
     * @return array<string, array{uri: string, host: string}>
     */
    public static function getIdnDataProvider(): array
    {
        return [
            'converted to ascii' => [
                'uri' => 'http://teßt.com',
                'host' => 'xn--tet-6ka.com',
            ],
            'converted to ascii lower cased' => [
                'uri' => 'http://MÜLLER.DE',
                'host' => 'xn--mller-kva.de',
            ],
        ];
    }

    #[DataProvider('withMethodProvider')]
    public function testWithMethodsDoNotMutateOriginalInstance(
        UriInterface $uri,
        callable $operation,
        string $getter,
        mixed $expectedOriginal,
        mixed $expectedNew
    ): void {
        $new = $operation($uri);

        $this->assertSame($expectedOriginal, $uri->$getter());
        $this->assertSame($expectedNew, $new->$getter());
    }

    /**
     * @return array<string, array{
     *     uri: UriInterface,
     *     operation: callable,
     *     getter: string,
     *     expectedOriginal: mixed,
     *     expectedNew: mixed
     * }>
     */
    public static function withMethodProvider(): array
    {
        return [
            'withScheme' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withScheme('https'),
                'getter' => 'getScheme',
                'expectedOriginal' => 'http',
                'expectedNew' => 'https',
            ],
            'withUserInfo' => [
                'uri' => Uri::create('ftp://user:pass@ftp.philharmony.com:21/file.txt'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withUserInfo('root', 'password'),
                'getter' => 'getUserInfo',
                'expectedOriginal' => 'user:pass',
                'expectedNew' => 'root:password',
            ],
            'withUserInfo without password' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withUserInfo('admin'),
                'getter' => 'getUserInfo',
                'expectedOriginal' => '',
                'expectedNew' => 'admin',
            ],
            'withHost' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withHost('example.com'),
                'getter' => 'getHost',
                'expectedOriginal' => 'philharmony.com',
                'expectedNew' => 'example.com'
            ],
            'withPort' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withPort(8080),
                'getter' => 'getPort',
                'expectedOriginal' => null,
                'expectedNew' => 8080,
            ],
            'withPath' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withPath('/foo'),
                'getter' => 'getPath',
                'expectedOriginal' => '',
                'expectedNew' => '/foo',
            ],
            'withQuery' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withQuery('a=1'),
                'getter' => 'getQuery',
                'expectedOriginal' => '',
                'expectedNew' => 'a=1',
            ],
            'withFragment' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withFragment('section-2'),
                'getter' => 'getFragment',
                'expectedOriginal' => '',
                'expectedNew' => 'section-2'
            ],
        ];
    }

    #[DataProvider('sameInstanceProvider')]
    public function testWithMethodsReturnSameInstanceIfValueUnchanged(
        UriInterface $uri,
        callable $operation
    ): void {
        $new = $operation($uri);

        $this->assertSame($uri, $new);
    }

    /**
     * @return array<string, array{uri: UriInterface, operation: callable}>
     */
    public static function sameInstanceProvider(): array
    {
        return [
            'withScheme' => [
                'uri' => Uri::create('https://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withScheme('https'),
            ],
            'withUserInfo' => [
                'uri' => Uri::create('ftp://user:pass@ftp.philharmony.com:21/file.txt'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withUserInfo('user', 'pass'),
            ],
            'withHost' => [
                'uri' => Uri::create('http://philharmony.com'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withHost('philharmony.com'),
            ],
            'withPort' => [
                'uri' => Uri::create('http://philharmony.com:8080'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withPort(8080),
            ],
            'withPath' => [
                'uri' => Uri::create('http://philharmony.com/foo'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withPath('/foo'),
            ],
            'withQuery' => [
                'uri' => Uri::create('http://philharmony.com?a=1'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withQuery('a=1'),
            ],
            'withFragment' => [
                'uri' => Uri::create('http://philharmony.com#section-2'),
                'operation' => fn (Uri $uri): UriInterface => $uri->withFragment('section-2'),
            ],
        ];
    }

    public function testToStringPrependsSlashWhenAuthorityAndPathWithoutSlash(): void
    {
        $uri = Uri::create('http://philharmony.com')
            ->withPath('foo');

        $this->assertSame('http://philharmony.com/foo', (string) $uri);
    }

    public function testToStringPreventsDoubleSlashPath(): void
    {
        $uri = Uri::create('http://philharmony.com')
            ->withPath('//foo');

        $this->assertSame('http://philharmony.com/foo', (string) $uri);
    }

    public function testWithPortCanRemovePort(): void
    {
        $uri = Uri::create('http://philharmony.com:8080');

        $new = $uri->withPort(null);

        $this->assertSame(8080, $uri->getPort());
        $this->assertNull($new->getPort());
    }

    public function testWithHostCanBeEmpty(): void
    {
        $uri = Uri::create('http://philharmony.com');

        $new = $uri->withHost('');

        $this->assertSame('philharmony.com', $uri->getHost());
        $this->assertSame('', $new->getHost());
    }

    public function testUriMutationChain(): void
    {
        $uri = Uri::create()
            ->withScheme('https')
            ->withHost('philharmony.com')
            ->withPath('/api')
            ->withQuery('a=1')
            ->withFragment('top');

        $this->assertSame(
            'https://philharmony.com/api?a=1#top',
            (string)$uri
        );
    }

    public function testFromParts(): void
    {
        $parts = [
            'scheme' => 'ftp',
            'host' => 'files.example.com',
            'port' => 21,
            'path' => '/uploads',
            'user' => 'admin',
            'pass' => 'secret'
        ];

        $uri = Uri::fromParts($parts);
        $this->assertSame('ftp', $uri->getScheme());
        $this->assertSame('files.example.com', $uri->getHost());
        $this->assertSame(null, $uri->getPort());
        $this->assertSame(21, $uri->getExplicitPort());
        $this->assertSame('/uploads', $uri->getPath());
        $this->assertSame('admin:secret@files.example.com', $uri->getAuthority());
    }

    public function testEmptyHostIsReturned(): void
    {
        $uri = Uri::fromParts([
            'host' => '',
        ]);

        $this->assertSame('', $uri->getHost());
    }

    public function testParseInvalidUriThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URI: http://localhost:invalid-port');
        Uri::create('http://localhost:invalid-port');
    }

    public function testSchemeRequiringHostThrowsExceptionWhenHostMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheme "http" requires host');

        Uri::create('http:/foo');
    }


    #[DataProvider('invalidHostProvider')]
    public function testInvalidHostThrowsException(string $host): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Uri::fromParts([
            'scheme' => 'http',
            'host' => $host,
        ]);
    }

    /**
     * @return array<string, array{host: string}>
     */
    public static function invalidHostProvider(): array
    {
        return [
            'with space' => [
                'host' => 'exa mple.com',
            ],
            'with slash' => [
                'host' => 'example/com',
            ],
            'with question mark' => [
                'host' => 'example?com',
            ],
            'with hash' => [
                'host' => 'example#com',
            ],
            'with at' => [
                'host' => 'example@com',
            ],
        ];
    }


    #[DataProvider('invalidSchemeProvider')]
    public function testInvalidSchemeThrowsException(string $invalidScheme): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scheme');

        Uri::create()->withScheme($invalidScheme);
    }

    /**
     * @return array<string, array{invalidScheme: string}>
     */
    public static function invalidSchemeProvider(): array
    {
        return [
            'starts_with_digit' => [
                'invalidScheme' => '1http',
            ],
            'starts_with_dash' => [
                'invalidScheme' => '-http'
            ],
            'contains_spaces' => [
                'invalidScheme' => 'ht tp'
            ],
            'contains_special' => [
                'invalidScheme' => 'http$'
            ],
            'only_symbols' => [
                'invalidScheme' => '+.-'
            ],
        ];
    }

    public function testInvalidIpv6ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Uri::create('http://[invalid-ipv6]/');
    }

    public function testPortValidationBounds(): void
    {
        $minPortUri = Uri::create('http://localhost')->withPort(1);
        $this->assertSame(1, $minPortUri->getPort());

        $maxPortUri = Uri::create('http://localhost')->withPort(65535);
        $this->assertSame(65535, $maxPortUri->getPort());
    }

    public function testInvalidPortThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Uri::create()->withPort(70000);
    }
}
