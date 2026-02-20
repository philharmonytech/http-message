<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Philharmony\Http\Message\Uri;
use Psr\Http\Message\UriInterface;

class UriTest extends TestCase
{
    public function testEmptyUriConstruct(): void
    {
        $uri = new Uri();

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('localhost', $uri->getHost());
        $this->assertSame(80, $uri->getPort());
        $this->assertSame('/', $uri->getPath());
        $this->assertSame('http://localhost/', (string)$uri);
    }

//    #[DataProvider('getUriDataProvider')]
//    public function testUriConstruct(
//        string $uri,
//        array $expectedDataUri
//    ): void {
//        $uriObj = new Uri($uri);
//        $this->assertSame($expectedDataUri['scheme'], $uriObj->getScheme());
//        $this->assertSame($expectedDataUri['host'], $uriObj->getHost());
//        $this->assertSame($expectedDataUri['port'], $uriObj->getPort());
//        $this->assertSame($expectedDataUri['path'], $uriObj->getPath());
//        $this->assertSame($expectedDataUri['query'], $uriObj->getQuery());
//        $this->assertSame($expectedDataUri['fragment'], $uriObj->getFragment());
//        $this->assertSame($expectedDataUri['authority'], $uriObj->getAuthority());
//        $this->assertSame($expectedDataUri['userInfo'], $uriObj->getUserInfo());
//    }
//
//    /**
//     * @return array<string, array{uri: string, expectedScheme: array<string, mixed>}>
//     */
//    public static function getUriDataProvider(): array
//    {
//        return [
//            'default uri' => [
//                'uri' => '',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'localhost',
//                    'port' => 80,
//                    'path' => '/',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => 'localhost:80',
//                    'userInfo' => '',
//                    'uri' => 'http://localhost:80/',
//                ],
//            ],
//            'root path only' => [
//                'uri' => '/',
//                'expectedDataUri' => [
//                    'scheme' => '',
//                    'host' => '',
//                    'port' => null,
//                    'path' => '/',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '',
//                    'userInfo' => '',
//                    'uri' => '/',
//                ],
//            ],
//            'urn without authority' => [
//                'uri' => 'urn:isbn:0-395-36341-1',
//                'expectedDataUri' => [
//                    'scheme' => 'urn',
//                    'host' => '',
//                    'port' => null,
//                    'path' => 'isbn:0-395-36341-1',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '',
//                    'userInfo' => '',
//                    'uri' => 'urn:isbn:0-395-36341-1',
//                ],
//            ],
//            'mailto without host' => [
//                'uri' => 'mailto:user@example.com',
//                'expectedDataUri' => [
//                    'scheme' => 'mailto',
//                    'host' => '',
//                    'port' => null,
//                    'path' => 'user@example.com',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '',
//                    'userInfo' => '',
//                    'uri' => 'mailto:user@example.com',
//                ],
//            ],
//            'http with host and port' => [
//                'uri' => 'http://example.com:8080/path',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'example.com',
//                    'port' => 8080,
//                    'path' => '/path',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => 'example.com:8080',
//                    'userInfo' => '',
//                    'uri' => 'http://example.com:8080/path',
//                ],
//            ],
//            'https default port' => [
//                'uri' => 'https://example.com/path',
//                'expectedDataUri' => [
//                    'scheme' => 'https',
//                    'host' => 'example.com',
//                    'port' => 443,
//                    'path' => '/path',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => 'example.com:443',
//                    'userInfo' => '',
//                    'uri' => 'https://example.com:443/path',
//                ],
//            ],
//            'ftp with user info' => [
//                'uri' => 'ftp://user:pass@ftp.example.com/file.txt',
//                'expectedDataUri' => [
//                    'scheme' => 'ftp',
//                    'host' => 'ftp.example.com',
//                    'port' => 21,
//                    'path' => '/file.txt',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => 'user:pass@ftp.example.com:21',
//                    'userInfo' => 'user:pass',
//                    'uri' => 'ftp://user:pass@ftp.example.com:21/file.txt',
//                ],
//            ],
//            'uri with query' => [
//                'uri' => 'http://example.com/search?q=php&lang=en',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'example.com',
//                    'port' => 80,
//                    'path' => '/search',
//                    'query' => 'q=php&lang=en',
//                    'fragment' => '',
//                    'authority' => 'example.com:80',
//                    'userInfo' => '',
//                    'uri' => 'http://example.com:80/search?q=php&lang=en',
//                ],
//            ],
//            'uri with fragment' => [
//                'uri' => 'http://example.com/page#section-2',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'example.com',
//                    'port' => 80,
//                    'path' => '/page',
//                    'query' => '',
//                    'fragment' => 'section-2',
//                    'authority' => 'example.com:80',
//                    'userInfo' => '',
//                    'uri' => 'http://example.com:80/page#section-2',
//                ],
//            ],
//            'full uri' => [
//                'uri' => 'http://user:pass@example.com:8080/api/v1?version=2#top',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'example.com',
//                    'port' => 8080,
//                    'path' => '/api/v1',
//                    'query' => 'version=2',
//                    'fragment' => 'top',
//                    'authority' => 'user:pass@example.com:8080',
//                    'userInfo' => 'user:pass',
//                    'uri' => 'http://user:pass@example.com:8080/api/v1?version=2#top',
//                ],
//            ],
//            'relative path' => [
//                'uri' => 'path/to/resource',
//                'expectedDataUri' => [
//                    'scheme' => '',
//                    'host' => '',
//                    'port' => null,
//                    'path' => 'path/to/resource',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '',
//                    'userInfo' => '',
//                    'uri' => 'path/to/resource',
//                ],
//            ],
//            'absolute path' => [
//                'uri' => '/absolute/path',
//                'expectedDataUri' => [
//                    'scheme' => '',
//                    'host' => '',
//                    'port' => null,
//                    'path' => '/absolute/path',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '',
//                    'userInfo' => '',
//                    'uri' => '/absolute/path',
//                ],
//            ],
//            'path with dot segments' => [
//                'uri' => 'path/././sub/../file.html',
//                'expectedDataUri' => [
//                    'scheme' => '',
//                    'host' => '',
//                    'port' => null,
//                    'path' => 'path/././sub/../file.html',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '',
//                    'userInfo' => '',
//                    'uri' => 'path/././sub/../file.html',
//                ],
//            ],
//            'ipv6 host' => [
//                'uri' => 'http://[2001:db8::1]:8080/api',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => '[2001:db8::1]',
//                    'port' => 8080,
//                    'path' => '/api',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '[2001:db8::1]:8080',
//                    'userInfo' => '',
//                    'uri' => 'http://[2001:db8::1]:8080/api',
//                ],
//            ],
//            'ipv6 without port' => [
//                'uri' => 'http://[::1]/',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => '[::1]',
//                    'port' => 80,
//                    'path' => '/',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => '[::1]:80',
//                    'userInfo' => '',
//                    'uri' => 'http://[::1]:80/',
//                ],
//            ],
//            'domain with subdomains' => [
//                'uri' => 'https://sub.domain.example.co.uk:443/path',
//                'expectedDataUri' => [
//                    'scheme' => 'https',
//                    'host' => 'sub.domain.example.co.uk',
//                    'port' => 443,
//                    'path' => '/path',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => 'sub.domain.example.co.uk:443',
//                    'userInfo' => '',
//                    'uri' => 'https://sub.domain.example.co.uk:443/path',
//                ],
//            ],
//            'encoded spaces' => [
//                'uri' => 'http://example.com/path%20with%20spaces',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'example.com',
//                    'port' => 80,
//                    'path' => '/path with spaces',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => 'example.com',
//                    'userInfo' => '',
//                    'uri' => 'http://example.com/path%20with%20spaces',
//                ],
//            ],
//            'encoded special chars' => [
//                'uri' => 'http://example.com/path?param=value%26other=1',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'example.com',
//                    'port' => 80,
//                    'path' => '/path',
//                    'query' => 'param=value&other=1',
//                    'fragment' => '',
//                    'authority' => 'example.com',
//                    'userInfo' => '',
//                    'uri' => 'http://example.com/path?param=value%26other=1',
//                ],
//            ],
//            'mixed encoding' => [
//                'uri' => 'http://user:pass%40domain.com@example.com:8080/path%3Fquery',
//                'expectedDataUri' => [
//                    'scheme' => 'http',
//                    'host' => 'example.com',
//                    'port' => 8080,
//                    'path' => '/path?query',
//                    'query' => '',
//                    'fragment' => '',
//                    'authority' => 'user:pass@domain.com@example.com:8080',
//                    'userInfo' => 'user:pass@domain.com',
//                    'uri' => 'http://user:pass%40domain.com@example.com:8080/path%3Fquery',
//                ],
//            ],
//
//        ];
//    }


//
//    public function testUriFromString(): void
//    {
//        $uri = new Uri('https://example.com:443/path?query=value"fragment');
//
//        $this->assertSame('https', $uri->getScheme());
//        $this->assertSame('example.com', $uri->getHost());
//        $this->assertSame(443, $uri->getPort());
//        $this->assertSame('/path', $uri->getPath());
//        $this->assertSame('query=value', $uri->getQuery());
//        $this->assertSame('fragment', $uri->getFragment());
//        $this->assertSame('https://example.com/path?query=value"fragment', (string)$uri);
//    }
//
//    public function testCreateStaticMethod(): void
//    {
//        $uri = Uri::create('http://test.com');
//        $this->assertInstanceOf(UriInterface::class, $uri);
//        $this->assertSame('test.com', $uri->getHost());
//    }

//    public function testWithScheme(): void
//    {
//        $uri = (new Uri())->withScheme('https');
//        $this->assertSame('https', $uri->getScheme());
//        $this->assertSame(443, $uri->getPort()); // Порт изменился с 80 на 443
//    }
//
//    public function testInvalidSchemeThrowsException(): void
//    {
//        $this->expectException(\InvalidArgumentException::class);
//        $this->expectExceptionMessage('Invalid scheme: invalid:scheme');
//        (new Uri())->withScheme('invalid:scheme');
//    }
//
//    public function testGetAuthorityWithUserInfo(): void
//    {
//        $uri = (new Uri())
//            ->withUserInfo('user', 'password')
//            ->withHost('example.com');
//
//        $this->assertSame('user:password@example.com', $uri->getAuthority());
//    }
//
//    public function testGetAuthorityWithoutPortIfDefault(): void
//    {
//        $uri = (new Uri())
//            ->withHost('example.com')
//            ->withPort(80); // Порт по умолчанию для HTTP
//
//        $this->assertSame('example.com', $uri->getAuthority()); // Порт не отображается
//    }
//
//    public function testGetAuthorityWithNonDefaultPort(): void
//    {
//        $uri = (new Uri())
//            ->withHost('example.com')
//            ->withPort(8080);
//
//        $this->assertSame('example.com:8080', $uri->getAuthority());
//    }
//
//    public function testWithUserInfoNullPassword(): void
//    {
//        $uri = (new Uri())->withUserInfo('user', null);
//        $this->assertSame('user', $uri->getUserInfo());
//    }
//
//    public function testWithHostLowercasesHost(): void
//    {
//        $uri = (new Uri())->withHost('EXAMPLE.COM');
//        $this->assertSame('example.com', $uri->getHost());
//    }
//
//    public function testWithPortValid(): void
//    {
//        $uri = (new Uri())->withPort(8080);
//        $this->assertSame(8080, $uri->getPort());
//    }
//
//    public function testWithPortNullRemovesPort(): void
//    {
//        $uri = (new Uri())
//            ->withPort(8080)
//            ->withPort(null);
//        $this->assertNull($uri->getPort());
//    }
//
//    public function testInvalidPortThrowsException(): void
//    {
//        $this->expectException(\InvalidArgumentException::class);
//        $this->expectExceptionMessage('Invalid port: 0');
//        (new Uri())->withPort(0);
//    }
//
//    public function testWithPathNormalization(): void
//    {
//        $uri = (new Uri())->withPath('path/to/resource');
//        $this->assertSame('/path/to/resource', $uri->getPath());
//
//        $uri2 = (new Uri())->withPath('/path//to///resource');
//        $this->assertSame('/path/to/resource', $uri2->getPath());
//
//        $uri3 = (new Uri())->withPath('');
//        $this->assertSame('/', $uri3->getPath());
//    }
//
//    public function testWithQueryRemovesQuestionMark(): void
//    {
//        $uri = (new Uri())->withQuery('?param=value');
//        $this->assertSame('param=value', $uri->getQuery());
//    }
//
//    public function testWithFragmentRemovesHash(): void
//    {
//        $uri = (new Uri())->withFragment('#section');
//        $this->assertSame('section', $uri->getFragment());
//    }
//
//    public function testToStringFullUri(): void
//    {
//        $uri = (new Uri())
//            ->withScheme('https')
//            ->withUserInfo('user', 'pass')
//            ->withHost('api.example.com')
//            ->withPort(8443)
//            ->withPath('/api/v1/users')
//            ->withQuery('limit=10&offset=0')
//            ->withFragment('results');
//
//        $expected = 'https://user:pass@api.example.com:8443/api/v1/users?limit=10&offset=0"results';
//        $this->assertSame($expected, (string)$uri);
//    }
//
//    public function testFromParts(): void
//    {
//        $parts = [
//            'scheme' => 'ftp',
//            'host' => 'files.example.com',
//            'port' => 21,
//            'path' => '/uploads',
//            'user' => 'admin',
//            'pass' => 'secret'
//        ];
//
//        $uri = Uri::fromParts($parts);
//        $this->assertSame('ftp', $uri->getScheme());
//        $this->assertSame('files.example.com', $uri->getHost());
//        $this->assertSame(21, $uri->getPort());
//        $this->assertSame('/uploads', $uri->getPath());
//        $this->assertSame('admin:secret@files.example.com', $uri->getAuthority());
//    }
//
//    public function testParseInvalidUriThrowsException(): void
//    {
//        $this->expectException(\InvalidArgumentException::class);
//        $this->expectExceptionMessage('Invalid URI: invalid-uri');
//        new Uri('invalid-uri');
//    }
//
//    public function testEnsureDefaultPortSchemeChange(): void
//    {
//        // Начинаем с HTTP (порт 80 по умолчанию)
//        $uri = new Uri();
//        $this->assertSame(80, $uri->getPort());
//
//        // Меняем на HTTPS — порт должен измениться на 443
//        $httpsUri = $uri->withScheme('https');
//        $this->assertSame(443, $httpsUri->getPort());
//    }
//
////    public function testDefaultHostAppliedForSchemesInList(): void
////    {
////        // Проверяем схемы, которые требуют хост по умолчанию
////        foreach ([Uri::HTTP, Uri::HTTPS, Uri::FTP, Uri::WS, Uri::WSS] as $scheme) {
////            $uri = (new Uri())->withScheme($scheme)->withHost('');
////            $this->assertSame('localhost', $uri->getHost(), "Scheme $scheme should have default host");
////        }
////    }
//
//    public function testDefaultHostNotAppliedForCustomSchemes(): void
//    {
//        // Для кастомной схемы хост по умолчанию не устанавливается
//        $uri = (new Uri())->withScheme('custom')->withHost('');
//        $this->assertSame('', $uri->getHost(), 'Custom scheme should not have default host');
//    }
//
//    public function testAuthorityWithoutHost(): void
//    {
//        $uri = new Uri();
//        $this->assertSame('', $uri->getAuthority(), 'Authority should be empty when host is empty');
//
//        // Проверяем, что даже при наличии user/password, но без хоста — authority пустой
//        $uriWithUser = (new Uri())->withUserInfo('user', 'pass');
//        $this->assertSame('', $uriWithUser->getAuthority(), 'Authority should be empty when host is missing, even with user info');
//    }
//
//    public function testPathWithoutLeadingSlash(): void
//    {
//        $uri = (new Uri())->withPath('path/to/resource');
//        $this->assertSame('/path/to/resource', $uri->getPath(), 'Path should have leading slash when missing');
//    }
//
//    public function testEmptyPathNormalization(): void
//    {
//        $uri = (new Uri())->withPath('');
//        $this->assertSame('/', $uri->getPath(), 'Empty path should be normalized to single slash');
//    }
//
//    public function testMultipleSlashNormalization(): void
//    {
//        $uri = (new Uri())->withPath('/path///to////resource');
//        $this->assertSame('/path/to/resource', $uri->getPath(), 'Multiple slashes should be normalized to single slashes');
//    }
//
//    public function testPortValidationBounds(): void
//    {
//        // Минимально допустимый порт
//        $minPortUri = (new Uri())->withPort(1);
//        $this->assertSame(1, $minPortUri->getPort());
//
//        // Максимально допустимый порт
//        $maxPortUri = (new Uri())->withPort(65535);
//        $this->assertSame(65535, $maxPortUri->getPort());
//    }
//
////    public function testInvalidPortBoundsThrowsException(): void
////    {
////        // Порт меньше минимального
////        $this->expectException(\InvalidArgumentException::class);
////        $this->expectExceptionMessage('Invalid port: 0');
////        (new Uri())->withPort(0);
////
//////        $this->resetException();
////
////        // Порт больше максимального
////        $this->expectException(\InvalidArgumentException::class);
////        $this->expectExceptionMessage('Invalid port: 65536');
////        (new Uri())->withPort(65536);
////    }
//
//    public function testSchemeCaseInsensitive(): void
//    {
//        $uri = (new Uri())->withScheme('HTTP');
//        $this->assertSame('http', $uri->getScheme(), 'Scheme should be lowercased');
//
//        $uri2 = (new Uri())->withScheme('HTTPS');
//        $this->assertSame('https', $uri2->getScheme(), 'Scheme should be lowercased');
//    }
//
//    public function testToStringWithoutScheme(): void
//    {
//        $uri = (new Uri())
//            ->withHost('example.com')
//            ->withPath('/test');
//
//        $this->assertSame('//example.com/test', (string)$uri, 'URI without scheme should not have colon after scheme');
//    }
//
//    public function testToStringWithOnlyPath(): void
//    {
//        $uri = (new Uri())->withPath('/simple');
//        $this->assertSame('/simple', (string)$uri, 'URI with only path should return just the path');
//    }
}
