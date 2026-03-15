<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\Message;
use Philharmony\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testProtocolVersion(): void
    {
        $message = $this->createMessage();
        $this->assertSame('1.1', $message->getProtocolVersion());

        $new = $message->withProtocolVersion('2.0');
        $this->assertNotSame($message, $new);
        $this->assertSame('2.0', $new->getProtocolVersion());
    }

    public function testHeadersAreCaseInsensitive(): void
    {
        $message = $this->createMessage(['Content-Type' => 'text/html']);

        $this->assertTrue($message->hasHeader('content-type'));
        $this->assertTrue($message->hasHeader('CONTENT-TYPE'));
        $this->assertSame(['text/html'], $message->getHeader('content-type'));
        $this->assertSame('text/html', $message->getHeaderLine('content-type'));
    }

    public function testWithHeaderHandlesArrayOfValues(): void
    {
        $message = $this->createMessage();
        $new = $message->withHeader('X-Foo', ['val1', 'val2']);

        $this->assertSame(['val1', 'val2'], $new->getHeader('X-Foo'));
        $this->assertSame('val1, val2', $new->getHeaderLine('X-Foo'));
    }

    public function testWithoutHeaderRemovesHeader(): void
    {
        $message = $this->createMessage(['X-Foo' => 'bar', 'X-Bar' => 'baz']);
        $new = $message->withoutHeader('x-foo');

        $this->assertFalse($new->hasHeader('X-Foo'));
        $this->assertTrue($new->hasHeader('X-Bar'));
        $this->assertCount(1, $new->getHeaders());
    }

    public function testWithAddedHeaderCreatesNewHeaderIfNotFound(): void
    {
        $message = $this->createMessage();
        $new = $message->withAddedHeader('X-New-Header', 'some-value');

        $this->assertTrue($new->hasHeader('x-new-header'));
        $this->assertSame(['some-value'], $new->getHeader('X-New-Header'));

        $this->assertArrayHasKey('X-New-Header', $new->getHeaders());
    }

    public function testWithHeaderReplacesExisting(): void
    {
        $message = $this->createMessage(['X-Foo' => 'bar']);
        $new = $message->withHeader('x-foo', 'baz');

        $this->assertSame(['X-Foo' => ['bar']], $message->getHeaders());
        $this->assertSame(['x-foo' => ['baz']], $new->getHeaders());
    }

    public function testWithAddedHeaderAppendsValue(): void
    {
        $message = $this->createMessage(['X-Foo' => 'bar']);
        $new = $message->withAddedHeader('x-foo', 'baz');

        $this->assertSame(['X-Foo' => ['bar', 'baz']], $new->getHeaders());
    }

    public function testWithAddedHeaderToExistingHeader(): void
    {
        $message = $this->createMessage(['X-Test' => 'first']);
        $new = $message->withAddedHeader('x-test', 'second');

        $this->assertSame(['first', 'second'], $new->getHeader('X-Test'));
        $this->assertArrayHasKey('X-Test', $new->getHeaders());
    }

    public function testSetHeadersReplacesHeaderWithDifferentCase(): void
    {
        $message = $this->createMessage([
            'Content-Type' => 'text/html',
            'content-type' => 'application/json',
        ]);

        $this->assertCount(1, $message->getHeaders());
        $this->assertSame(['application/json'], $message->getHeader('Content-Type'));
    }

    public function testGetHeaderReturnsEmptyArrayIfNotFound(): void
    {
        $message = $this->createMessage();
        $this->assertSame([], $message->getHeader('X-Not-Found'));
    }

    public function testNormalizeHeaderValueWithNumericValues(): void
    {
        $message = $this->createMessage();
        $new = $message->withHeader('X-Numeric', 123);

        $this->assertSame(['123'], $new->getHeader('X-Numeric'));

        $new2 = $new->withAddedHeader('X-Numeric', [456.7, 0]);
        $this->assertSame(['123', '456.7', '0'], $new2->getHeader('X-Numeric'));
    }

    public function testValidateHeaderNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid HTTP header name');

        $this->createMessage(['Invalid Name' => 'value']);
    }

    public function testValidateHeaderValueThrowsExceptionOnControlCharacters(): void
    {
        $message = $this->createMessage();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header value contains invalid characters');

        $message->withHeader('X-Foo', "value\nnewline");
    }

    public function testBodyMethods(): void
    {
        $body = Stream::create('test body');
        $message = $this->createMessage();

        $new = $message->withBody($body);
        $this->assertSame($body, $new->getBody());
        $this->assertSame('test body', (string) $new->getBody());
    }

    public function testWithoutHeaderReturnsSameInstanceIfHeaderNotFound(): void
    {
        $message = $this->createMessage(['X-Foo' => 'bar']);
        $new = $message->withoutHeader('X-Non-Existent');

        $this->assertSame($message, $new);
    }

    public function testNormalizeHeaderValueThrowsExceptionOnEmptyArray(): void
    {
        $message = $this->createMessage();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header value can not be empty');

        $message->withHeader('X-Empty', []);
    }

    public function testNormalizeHeaderValueThrowsExceptionOnInvalidType(): void
    {
        $message = $this->createMessage();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be strings or numbers');

        $message->withHeader('X-Bad', new \stdClass());
    }

    private function createMessage(array $headers = []): Message
    {
        return new class ('', $headers) extends Message {};
    }
}
