<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\Stream;
use Philharmony\Http\Message\Tests\Stub\Stream\BrokenStatsStream;
use Philharmony\Http\Message\Tests\Stub\Stream\FileOpenFailureStream;
use Philharmony\Http\Message\Tests\Stub\Stream\ForceReadableStream;
use Philharmony\Http\Message\Tests\Stub\Stream\ForceSeekableStream;
use Philharmony\Http\Message\Tests\Stub\Stream\ForceWritableStream;
use Philharmony\Http\Message\Tests\Stub\Stream\MemoryOpenFailureStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class StreamTest extends TestCase
{
    private static string $tempFile;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::initTempFile();
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$tempFile)) {
            unlink(self::$tempFile);
        }

        parent::tearDownAfterClass();
    }

    public function testCreateWithString(): void
    {
        $stream = Stream::create('Philharmony');
        $this->assertSame('Philharmony', (string)$stream);
    }

    public function testCreateWithResource(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = Stream::create($resource);
        $this->assertSame('', (string)$stream);
    }

    public function testCreateWithStreamInterface(): void
    {
        $firstStream = Stream::create('Philharmony');
        $secondStream = Stream::create($firstStream);
        $this->assertSame($firstStream, $secondStream);
        $this->assertSame('Philharmony', (string)$firstStream);
        $this->assertSame('Philharmony', (string)$secondStream);
    }

    public function testCreateFromFileOpensFile(): void
    {
        file_put_contents(self::$tempFile, 'Framework');

        $stream = Stream::createFromFile(self::$tempFile);

        $this->assertSame('Framework', $stream->getContents());
    }

    public function testConstructorThrowsExceptionOnInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream must be a valid PHP resource');
        new Stream('not a resource');
    }

    public function testCreateThrowsExceptionOnInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream must be a valid PHP resource');

        Stream::create(12345);
    }

    public function testCreateThrowsExceptionOnMemoryResourceOpenFailure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open php://temp');

        MemoryOpenFailureStream::create('some content');
    }

    public function testCreateFromFileThrowsOnEmptyFilename(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Stream::createFromFile('');
    }

    public function testCreateFromFileThrowsOnEmptyMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Stream::createFromFile(self::$tempFile, '');
    }

    public function testCreateFromFileThrowsExceptionOnFileResourceOpenFailure(): void
    {
        file_put_contents(self::$tempFile, 'philharmony');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('The file "%s" cannot be opened: Unknown error message', self::$tempFile));

        FileOpenFailureStream::createFromFile(self::$tempFile);
    }

    public function testToStringReturnsEmptyStringOnNotReadable(): void
    {
        $resource = fopen('php://output', 'w');
        $stream = Stream::create($resource);
        $this->assertSame('', (string)$stream);
        fclose($resource);
    }

    public function testToStringReturnsEmptyStringOnResourceClose(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new ForceSeekableStream($resource);
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

    public function testGetSizeReturnsNullWhenSizeNotAvailable(): void
    {
        $resource = fopen('php://temp', 'r');
        $stream = new BrokenStatsStream($resource);

        $this->assertNull($stream->getSize());

        fclose($resource);
    }

    public function testGetSizeWhenStreamIsClosed(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new Stream($resource);
        fclose($resource);
        $this->assertNull($stream->getSize());
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

    public function testGetMetadataOnDetached(): void
    {
        $stream = Stream::create('Philharmony');
        $stream->detach();
        $this->assertNull($stream->getMetadata('uri'));
    }

    public function testGetUriKeyFromMetadata(): void
    {
        $resource = fopen('php://input', 'r+');
        $stream = Stream::create($resource);
        $this->assertSame('php://input', $stream->getMetadata('uri'));
    }

    public function testGetAllKeyFromMetadata(): void
    {
        $resource = fopen('php://output', 'w+');
        $stream = Stream::create($resource);
        $this->assertIsArray($stream->getMetadata());
    }

    public function testNotExistKeyInMetadata(): void
    {
        $resource = fopen('php://memory', 'w+');
        $stream = Stream::create($resource);
        $this->assertNull($stream->getMetadata('non_existent_key'));
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

    #[DataProvider('detachedStateOperations')]
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
    public static function detachedStateOperations(): array
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

    #[DataProvider('invalidStateOperations')]
    public function testThrowsOnInvalidState(
        string $targetResource,
        string $resourceMode,
        callable $operation,
        string $message
    ): void {
        $resource = fopen($targetResource, $resourceMode);
        $stream = Stream::create($resource);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage($message);
            $operation($stream);
        } finally {
            fclose($resource);
        }
    }

    /**
     * @return array<string, array{targetResource: string, resourceMode: string, operation: callable, message: string}>
     */
    public static function invalidStateOperations(): array
    {
        if (!isset(self::$tempFile)) {
            self::$tempFile = tempnam(sys_get_temp_dir(), 'Philharmony');
        }

        return [
            'seek' => [
                'targetResource' => 'php://output',
                'resourceMode' => 'w',
                'operation' => fn (Stream $stream) => $stream->seek(0),
                'message' => 'Stream is not seekable',
            ],
            'write' => [
                'targetResource' => self::$tempFile,
                'resourceMode' => 'r',
                'operation' => fn (Stream $stream): int => $stream->write('data'),
                'message' => 'Stream is not writable',
            ],
            'read' => [
                'targetResource' => self::$tempFile,
                'resourceMode' => 'w',
                'operation' => fn (Stream $stream): string => $stream->read(1),
                'message' => 'Stream is not readable',
            ],
            'getContents' => [
                'targetResource' => self::$tempFile,
                'resourceMode' => 'w',
                'operation' => fn (Stream $stream): string => $stream->getContents(),
                'message' => 'Stream is not readable',
            ],
        ];
    }

    #[DataProvider('warningStateOperations')]
    public function testThrowsOnWarningState(
        string $targetResource,
        string $resourceMode,
        string $streamClass,
        callable $operation,
        string $messageMatches
    ): void {
        $resource = fopen($targetResource, $resourceMode);

        $stream = new $streamClass($resource);
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches($messageMatches);

            $operation($stream);
        } finally {
            fclose($resource);
        }
    }

    /**
     * @return array<string, array{
     *     targetResource: string,
     *     resourceMode: string,
     *     streamClass: string,
     *     operation: callable,
     *     messageMatches: string
     * }>
     */
    public static function warningStateOperations(): array
    {
        self::initTempFile();

        return [
            'write' => [
                'targetResource' => self::$tempFile,
                'resourceMode' => 'r',
                'streamClass' => ForceWritableStream::class,
                'operation' => fn (Stream $stream): int => $stream->write('data'),
                'messageMatches' => '/Error writing to stream: fwrite\(\):/',
            ],
            'read' => [
                'targetResource' => self::$tempFile,
                'resourceMode' => 'w',
                'streamClass' => ForceReadableStream::class,
                'operation' => fn (Stream $stream): string => $stream->read(1),
                'messageMatches' => '/Error reading from stream: fread\(\):/',
            ],
            'seek' => [
                'targetResource' => 'php://output',
                'resourceMode' => 'w',
                'streamClass' => ForceSeekableStream::class,
                'operation' => fn (Stream $stream) => $stream->seek(0),
                'messageMatches' => '/Error seeking in stream: fseek\(\)/',
            ],
            'getContent' => [
                'targetResource' => self::$tempFile,
                'resourceMode' => 'w',
                'streamClass' => ForceReadableStream::class,
                'operation' => fn (Stream $stream): string => $stream->getContents(),
                'messageMatches' => '/Error reading from stream: stream_get_contents\(\):/',
            ],
        ];
    }

    #[DataProvider('systemErrorOperations')]
    public function testSystemErrors(string $streamClass, callable $operation, string $message): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new $streamClass($resource);
        fclose($resource);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);

        $operation($stream);
    }

    /**
     * @return array<string, array{streamClass: string, operation: callable, message: string}>
     */
    public static function systemErrorOperations(): array
    {
        return [
            'write' => [
                'streamClass' => ForceWritableStream::class,
                'operation' => fn (Stream $stream): int => $stream->write('data'),
                'message' => 'Error writing to stream',
            ],
            'read' => [
                'streamClass' => ForceReadableStream::class,
                'operation' => fn (Stream $stream): string => $stream->read(1),
                'message' => 'Error reading from stream',
            ],
            'getContents' => [
                'streamClass' => ForceReadableStream::class,
                'operation' => fn (Stream $stream): string => $stream->getContents(),
                'message' => 'Error reading from stream',
            ],
            'seek' => [
                'streamClass' => Stream::class,
                'operation' => fn (Stream $stream) => $stream->seek(0),
                'message' => 'Error seeking in stream',
            ],
            'tell' => [
                'streamClass' => Stream::class,
                'operation' => fn (Stream $stream): int => $stream->tell(),
                'message' => 'Error getting stream position',
            ],
        ];
    }

    private static function initTempFile(): void
    {
        if (!isset(self::$tempFile)) {
            self::$tempFile = tempnam(sys_get_temp_dir(), 'Philharmony');
        }
    }
}
