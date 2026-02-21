<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Philharmony\Http\Message\Uri;

class UriTest extends TestCase
{
    #[DataProvider('getUriDataProvider')]
    public function testUriConstruct(
        string $uri,
        array $expectedDataUri
    ): void {
        $uriObj = Uri::create($uri);

        $this->assertSame($expectedDataUri['scheme'], $uriObj->getScheme());
        $this->assertSame($expectedDataUri['host'], $uriObj->getHost());
        $this->assertSame($expectedDataUri['port'], $uriObj->getPort());
        $this->assertSame($expectedDataUri['explicitPort'], $uriObj->getExplicitPort());
        $this->assertSame($expectedDataUri['path'], $uriObj->getPath());
        $this->assertSame($expectedDataUri['query'], $uriObj->getQuery());
        $this->assertSame($expectedDataUri['fragment'], $uriObj->getFragment());
        $this->assertSame($expectedDataUri['authority'], $uriObj->getAuthority());
        $this->assertSame($expectedDataUri['userInfo'], $uriObj->getUserInfo());
    }

    /**
     * @return array<string, array{uri: string, expectedScheme: array<string, mixed>}>
     */
    public static function getUriDataProvider(): array
    {
        return [
            'default uri' => [
                'uri' => '',
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'uri' => 'http://example.com:8080/path',
                'expectedDataUri' => [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'port' => 8080,
                    'explicitPort' => 8080,
                    'path' => '/path',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'example.com:8080',
                    'userInfo' => '',
                    'uri' => 'http://example.com:8080/path',
                ],
            ],
            'https default port' => [
                'uri' => 'https://example.com/path',
                'expectedDataUri' => [
                    'scheme' => 'https',
                    'host' => 'example.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/path',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'example.com',
                    'userInfo' => '',
                    'uri' => 'https://example.com/path',
                ],
            ],
            'ftp with user info' => [
                'uri' => 'ftp://user:pass@ftp.example.com:21/file.txt',
                'expectedDataUri' => [
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
                'uri' => 'http://example.com/search?q=php&lang=en',
                'expectedDataUri' => [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'port' => null,
                    'explicitPort' => null,
                    'path' => '/search',
                    'query' => 'q=php&lang=en',
                    'fragment' => '',
                    'authority' => 'example.com',
                    'userInfo' => '',
                    'uri' => 'http://example.com/search?q=php&lang=en',
                ],
            ],
            'uri with fragment' => [
                'uri' => 'http://example.com:80/page#section-2',
                'expectedDataUri' => [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'port' => null,
                    'explicitPort' => 80,
                    'path' => '/page',
                    'query' => '',
                    'fragment' => 'section-2',
                    'authority' => 'example.com',
                    'userInfo' => '',
                    'uri' => 'http://example.com/page#section-2',
                ],
            ],
            'full uri' => [
                'uri' => 'http://user:pass@example.com:8080/api/v1?version=2#top',
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
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
                'expectedDataUri' => [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'port' => 8080,
                    'explicitPort' => 8080,
                    'path' => '/path%3Fquery',
                    'query' => '',
                    'fragment' => '',
                    'authority' => 'user:pass%40domain.com@example.com:8080',
                    'userInfo' => 'user:pass%40domain.com',
                    'uri' => 'http://user:pass%40domain.com@example.com/path%3Fquery',
                ],
            ],
            'unknown scheme with port' => [
                'uri' => 'smb://server:445/share',
                'expectedDataUri' => [
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
        ];
    }

    public function testToStringWithOnlyPath(): void
    {
        $uri = Uri::create()->withPath('//simple');
        $this->assertSame('/simple', (string)$uri);
    }

    public function testToStringFullUri(): void
    {
        $uri = Uri::create()
            ->withScheme('https')
            ->withUserInfo('user', 'pass')
            ->withHost('api.example.com')
            ->withPort(8443)
            ->withPath('api/v1/users')
            ->withQuery('limit=10&offset=0')
            ->withFragment('results');

        $expected = 'https://user:pass@api.example.com:8443/api/v1/users?limit=10&offset=0#results';
        $this->assertSame($expected, (string)$uri);
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

    public function testParseInvalidUriThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URI: http://localhost:invalid-port');
        Uri::create('http://localhost:invalid-port');
    }

    #[DataProvider('invalidSchemeProvider')]
    public function testInvalidSchemeThrowsException(string $invalidScheme): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scheme');

        Uri::create()->withScheme($invalidScheme);
    }

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
