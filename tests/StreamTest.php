<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class StreamTest extends TestCase
{
    public function testConstructorThrowsExceptionOnInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream must be a valid PHP resource');
        new Stream('not a resource');
    }

    public function testCreateWithVariousInputs(): void
    {
        $stream = Stream::create('Philharmony');
        $this->assertSame('Philharmony', (string)$stream);

        $resource = fopen('php://memory', 'r+');
        $stream2 = Stream::create($resource);
        $this->assertTrue($stream2->isWritable());

        $stream3 = Stream::create($stream2);
        $this->assertSame($stream2, $stream3);
    }

    public function testCreateThrowsExceptionOnInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream must be a valid PHP resource');

        Stream::create(12345);
    }

    public function testCreateThrowsExceptionOnFopenFailure(): void
    {
        $brokenStreamClass = new class (fopen('php://memory', 'r')) extends Stream {
            protected static function openMemoryResource(): mixed
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open php://memory');

        $brokenStreamClass::create('some content');
    }

    public function testToString(): void
    {
        $stream = Stream::create('Philharmony');

        $this->assertSame('Philharmony', (string) $stream);
    }

    public function testToStringReturnsEmptyStringOnFailure(): void
    {
        $stream = Stream::create('Philharmony');
        $stream->detach();
        $this->assertSame('', (string)$stream);
    }

    public function testToStringReturnsEmptyStringOnRuntimeException(): void
    {
        $resource = fopen('php://memory', 'r+');

        $stream = new class ($resource) extends Stream {
            public function isReadable(): bool
            {
                return true;
            }
            public function isSeekable(): bool
            {
                return true;
            }
        };

        fclose($resource);

        $this->assertSame('', (string)$stream);
    }

    public function testGetSizeCachedAndNull(): void
    {
        $stream = Stream::create('Philharmony');
        $this->assertSame(11, $stream->getSize());
        $this->assertSame(11, $stream->getSize());

        $stream->detach();
        $this->assertNull($stream->getSize());
    }

    public function testGetSizeReturnsNullForSocketResource(): void
    {
        $resource = fopen('http://google.com', 'r');

        $stream = Stream::create($resource);
        $this->assertNull($stream->getSize());

        fclose($resource);
    }

    public function testSizeResetAfterDetach(): void
    {
        $stream = Stream::create('Philharmony');
        $this->assertSame(11, $stream->getSize());
        $stream->detach();
        $this->assertNull($stream->getSize());
    }

    public function testSeekAndRewind(): void
    {
        $stream = Stream::create('abcdef');
        $stream->seek(2);
        $this->assertSame(2, $stream->tell());
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function testTellThrowsExceptionOnSystemError(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = Stream::create($resource);

        fclose($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error getting stream position');

        $stream->tell();
    }

    public function testSeekThrowsExceptionOnDetachedStream(): void
    {
        $stream = Stream::create('test');
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $stream->seek(0);
    }

    public function testSeekThrowsExceptionWhenNotSeekable(): void
    {
        $resource = fopen('php://output', 'w');
        $stream = Stream::create($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is not seekable');
        $stream->seek(0);

        fclose($resource);
    }

    public function testSeekThrowsExceptionOnSystemError(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = Stream::create($resource);

        fclose($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error seeking in stream');

        $stream->seek(0);
    }

    public function testRewindThrowsWhenNotSeekable(): void
    {
        $resource = fopen('php://output', 'w');
        $stream = Stream::create($resource);

        $this->expectException(\RuntimeException::class);

        $stream->rewind();
    }

    #[DataProvider('detachedOperations')]
    public function testOperationsThrowWhenDetached(
        StreamInterface $stream,
        callable $operation
    ): void {
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $operation($stream);
    }

    /**
     * @return array<string, array{stream: StreamInterface, operation: callable}>
     */
    public static function detachedOperations(): array
    {
        return [
            'read' => [
                'stream' => Stream::create('Philharmony'),
                'operation' => fn (Stream $stream): string => $stream->read(1),
            ],
            'write' => [
                'stream' => Stream::create('Philharmony'),
                'operation' => fn (Stream $stream): int => $stream->write('h'),
            ],
            'tell' => [
                'stream' => Stream::create('Philharmony'),
                'operation' => fn (Stream $stream): int => $stream->tell(),
            ],
            'seek' => [
                'stream' => Stream::create('Philharmony'),
                'operation' => fn (Stream $stream) => $stream->seek(0),
            ],
            'getContents' => [
                'stream' => Stream::create('Philharmony'),
                'operation' => fn (Stream $stream): string => $stream->getContents(),
            ],
        ];
    }

    public function testReadWithZeroLengthReturnsEmptyString(): void
    {
        $stream = Stream::create('Philharmony');
        $this->assertSame('', $stream->read(0));
        $stream->close();
    }

    public function testReadWithNegativeLengthThrowsException(): void
    {
        $stream = Stream::create('Philharmony');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Length must be a non-negative integer');

        $stream->read(-1);
    }

    public function testReadThrowsWhenNotReadable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phil_write');
        $resource = fopen($tempFile, 'w');
        $stream = Stream::create($resource);

        $this->assertFalse($stream->isReadable());

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Stream is not readable');
            $stream->read(1);
        } finally {
            fclose($resource);
            unlink($tempFile);
        }
    }

    public function testReadPastEndReturnsEmptyString(): void
    {
        $stream = Stream::create('abc');

        $stream->read(3);

        $this->assertSame('', $stream->read(1));
    }

    public function testWriteAndReadSystemErrors(): void
    {
        $resource = fopen('php://memory', 'r+');

        $stream = new class ($resource) extends Stream {
            public function isWritable(): bool
            {
                return true;
            }
            public function isReadable(): bool
            {
                return true;
            }
        };

        fclose($resource);

        $exceptionThrown = false;
        try {
            $stream->write('data');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Error writing to stream', $exception->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Write exception was not thrown');

        $exceptionThrown = false;
        try {
            $stream->read(1);
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Error reading from stream', $exception->getMessage());
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Read exception was not thrown');
    }

    public function testWriteAndRead(): void
    {
        $stream = Stream::create('');

        $bytes = $stream->write('Philharmony');

        $this->assertSame(11, $bytes);

        $stream->rewind();

        $this->assertSame('Philharmony', $stream->read(11));
    }

    public function testWriteEmptyString(): void
    {
        $stream = Stream::create('');

        $this->assertSame(0, $stream->write(''));
    }

    public function testWriteThrowsWhenNotWritable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phil_read');
        $resource = fopen($tempFile, 'r');
        $stream = Stream::create($resource);

        $this->assertFalse($stream->isWritable());

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Stream is not writable');
            $stream->write('data');
        } finally {
            fclose($resource);
            unlink($tempFile);
        }
    }

    public function testWriteMovesPointer(): void
    {
        $stream = Stream::create('');

        $stream->write('abc');

        $this->assertSame(3, $stream->tell());
    }

    public function testTriggerErrorHandlerWithWarning(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phil_test');
        $resource = fopen($tempFile, 'r');

        $stream = new class ($resource) extends Stream {
            public function isWritable(): bool
            {
                return true;
            }
        };

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Error writing to stream: fwrite():');

            $stream->write('data');
        } finally {
            fclose($resource);
            unlink($tempFile);
        }
    }

    public function testGetContents(): void
    {
        $stream = Stream::create('Philharmony');

        $stream->read(4);

        $this->assertSame('harmony', $stream->getContents());
    }

    public function testGetContentsThrowsOnFailure(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new class ($resource) extends Stream {
            public function isReadable(): bool
            {
                return true;
            }
        };

        fclose($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error reading from stream');

        $stream->getContents();
    }

    public function testGetContentsThrowsWhenNotReadable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phil_write');
        $resource = fopen($tempFile, 'w');
        $stream = Stream::create($resource);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Stream is not readable');
            $stream->getContents();
        } finally {
            fclose($resource);
            unlink($tempFile);
        }
    }

    public function testGetMetadataOnDetached(): void
    {
        $stream = Stream::create('Philharmony');
        $stream->detach();
        $this->assertSame([], $stream->getMetadata());
        $this->assertNull($stream->getMetadata('uri'));
    }

    public function testGetMetadataReturnsAllAndSpecificKeys(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = Stream::create($resource);

        $this->assertIsArray($stream->getMetadata());
        $this->assertSame('php://memory', $stream->getMetadata('uri'));

        $this->assertSame('PHP', $stream->getMetadata('wrapper_type'));

        $this->assertNull($stream->getMetadata('non_existent_key'));

        $stream->close();
    }

    public function testEof(): void
    {
        $stream = Stream::create('a');
        $this->assertFalse($stream->eof());
        $stream->read(1);
        $stream->read(1);
        $this->assertTrue($stream->eof());

        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    public function testCloseClosesStream(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = Stream::create($resource);

        $stream->close();

        $this->assertNull($stream->detach());
    }

    public function testStreamStateConsistency(): void
    {
        $stream = Stream::create('abcdef');

        $this->assertSame(0, $stream->tell());
        $this->assertFalse($stream->eof());

        $this->assertSame('ab', $stream->read(2));
        $this->assertSame(2, $stream->tell());

        $this->assertSame('cd', $stream->read(2));
        $this->assertSame(4, $stream->tell());

        $this->assertSame('ef', $stream->getContents());
        $this->assertTrue($stream->eof());

        $stream->rewind();

        $this->assertSame(0, $stream->tell());
        $this->assertSame('abcdef', $stream->getContents());
    }

    public function testDetachReturnsNullIfAlreadyDetached(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = Stream::create($resource);

        $this->assertSame($resource, $stream->detach());
        $this->assertNull($stream->detach());

        if (\is_resource($resource)) {
            fclose($resource);
        }
    }

    public function testAppendModePointerBehaviour(): void
    {
        $resource = fopen('php://memory', 'a+');
        fwrite($resource, 'abc');
        $stream = Stream::create($resource);
        $stream->seek(1);
        $this->assertSame(1, $stream->tell());
        $stream->write('X');
        $stream->rewind();
        $this->assertSame('abcX', $stream->getContents());
    }
}
