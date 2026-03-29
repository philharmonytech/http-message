<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /** @var resource|null */
    private $stream;
    /** @var int|null */
    private ?int $size = null;
    private bool $seekable = false;
    private bool $readable = false;
    private bool $writable = false;
    private ?string $uri = null;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a valid PHP resource');
        }

        $meta = stream_get_meta_data($stream);

        $this->stream = $stream;
        $this->seekable = $meta['seekable'];
        $this->uri = $meta['uri'];

        $mode = $meta['mode'];

        $this->readable = self::isReadableMode($mode);
        $this->writable = self::isWritableMode($mode);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param string|resource|StreamInterface $body
     * @return StreamInterface
     */
    public static function create(mixed $body): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (\is_string($body)) {
            $resource = static::openMemoryResource();
            if ($resource === false) {
                throw new \RuntimeException('Failed to open php://temp');
            }

            if ($body !== '') {
                fwrite($resource, $body);
                rewind($resource);
            }

            $body = $resource;
        }

        if (!\is_resource($body)) {
            throw new \InvalidArgumentException('Stream must be a valid PHP resource');
        }

        return new self($body);
    }

    public static function createFromFile(string $fileName, string $mode = 'r'): StreamInterface
    {
        if ($fileName === '') {
            throw new \InvalidArgumentException('File name cannot be empty');
        }

        if ($mode === '') {
            throw new \InvalidArgumentException('Mode cannot be empty');
        }

        $resource = static::openFileResource($fileName, $mode);
        if ($resource === false) {
            $lastError = error_get_last();
            $errorMessage = \is_array($lastError) ? $lastError['message'] : 'Unknown error message';

            throw new \RuntimeException(\sprintf('The file "%s" cannot be opened: %s', $fileName, $errorMessage));
        }

        return self::create($resource);
    }

    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if (!isset($this->stream)) {
            return;
        }

        if (\is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->detach();
    }

    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        $this->stream = null;
        $this->size = null;
        $this->uri = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;

        return $result;
    }

    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        $stats = $this->getStreamStats();
        if (\is_array($stats) && isset($stats['size'])) {
            $this->size = (int)$stats['size'];

            return $this->size;
        }

        return null;
    }

    public function tell(): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        return $this->callWithErrorHandler(
            $this->stream,
            fn () => (int)@ftell($this->stream),
            'Error getting stream position'
        );
    }

    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }

        $this->callWithErrorHandler(
            $this->stream,
            fn () => @fseek($this->stream, $offset, $whence),
            'Error seeking in stream'
        );
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable');
        }

        $this->size = null;

        return $this->callWithErrorHandler(
            $this->stream,
            fn () => (int)@fwrite($this->stream, $string),
            'Error writing to stream'
        );
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        if ($length < 0) {
            throw new \InvalidArgumentException('Length must be a non-negative integer');
        }

        if ($length === 0) {
            return '';
        }

        return $this->callWithErrorHandler(
            $this->stream,
            fn () => (string)@fread($this->stream, $length),
            'Error reading from stream'
        );
    }

    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        return $this->callWithErrorHandler(
            $this->stream,
            fn () => (string)@stream_get_contents($this->stream),
            'Error reading from stream'
        );
    }

    public function getMetadata(?string $key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }

        if ($key === 'uri') {
            return $this->uri;
        }

        $meta = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    /**
     * @return resource|false
     */
    protected static function openMemoryResource(): mixed
    {
        return self::openResource('php://temp', 'r+');
    }

    /**
     * @return resource|false
     */
    protected static function openFileResource(string $fileName, string $mode): mixed
    {
        return self::openResource($fileName, $mode);
    }

    /**
     * @return resource|false
     */
    private static function openResource(string $target, string $mode): mixed
    {
        return @fopen($target, $mode);
    }

    /**
     * @return array{
     *     dev: int,
     *     ino: int,
     *     mode: int,
     *     nlink: int,
     *     uid: int,
     *     gid: int,
     *     rdev: int,
     *     size: int,
     *     atime: int,
     *     mtime: int,
     *     ctime: int,
     *     blksize: int|false,
     *     blocks: int|false
     * }|false
     */
    protected function getStreamStats(): array|false
    {
        if (!\is_resource($this->stream)) {
            return false;
        }

        return fstat($this->stream);
    }

    private static function isReadableMode(string $mode): bool
    {
        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    private static function isWritableMode(string $mode): bool
    {
        return str_contains($mode, 'w') ||
            str_contains($mode, 'a') ||
            str_contains($mode, 'x') ||
            str_contains($mode, 'c') ||
            str_contains($mode, '+');
    }

    /**
     * @template T
     * @param resource $resource
     * @param callable(resource): T $callback
     * @param string $errorMessage
     * @return T
     */
    private function callWithErrorHandler($resource, callable $callback, string $errorMessage): mixed
    {
        set_error_handler(static function (int $errNo, string $errStr) use ($errorMessage): never {
            throw new \RuntimeException("{$errorMessage}: {$errStr}");
        });

        try {
            return $callback($resource);
        } catch (\Throwable $exception) {
            throw new \RuntimeException("{$errorMessage}: " . $exception->getMessage(), 0, $exception);
        } finally {
            restore_error_handler();
        }
    }
}
