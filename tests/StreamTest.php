<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

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

    public function testToStringReturnsEmptyStringOnFailure(): void
    {
        $stream = Stream::create('Philharmony');
        $stream->detach();
        $this->assertSame('', (string)$stream);
    }

    public function testDetachReturnsNullIfAlreadyDetached(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new Stream($resource);

        $this->assertSame($resource, $stream->detach());
        $this->assertNull($stream->detach());

        if (\is_resource($resource)) {
            fclose($resource);
        }
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

        $stream = new Stream($resource);
        $this->assertNull($stream->getSize());

        fclose($resource);
    }

    public function testTellThrowsOnDetached(): void
    {
        $stream = Stream::create('Philharmony');
        $stream->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');
        $stream->tell();
    }

    public function testTellThrowsExceptionOnSystemError(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new Stream($resource);

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
        $stream = new Stream($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is not seekable');
        $stream->seek(0);

        fclose($resource);
    }

    public function testSeekThrowsExceptionOnSystemError(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new Stream($resource);

        fclose($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error seeking in stream');

        $stream->seek(0);
    }

    public function testSeekAndRewind(): void
    {
        $stream = Stream::create('abcdef');
        $stream->seek(2);
        $this->assertSame(2, $stream->tell());
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function testReadThrowsOnDetached(): void
    {
        $stream = Stream::create('data');
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');
        $stream->read(1);
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

    public function testWriteThrowsOnDetached(): void
    {
        $stream = Stream::create('');
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');
        $stream->write('data');
    }

    public function testReadThrowsWhenNotReadable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phil_write');
        $resource = fopen($tempFile, 'w');
        $stream = new Stream($resource);

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

    public function testWriteThrowsWhenNotWritable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phil_read');
        $resource = fopen($tempFile, 'r');
        $stream = new Stream($resource);

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

    public function testGetContentsThrowsOnDetached(): void
    {
        $stream = Stream::create('');
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');
        $stream->getContents();
    }

    public function testGetContentsThrowsWhenNotReadable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phil_write');
        $resource = fopen($tempFile, 'w');
        $stream = new Stream($resource);

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
        $stream = new Stream($resource);

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
}
