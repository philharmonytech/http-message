<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Philharmony\Http\Enum\ContentType;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    private ?string $file = null;
    private ?StreamInterface $stream = null;
    private ?int $size;
    private int $error;
    private ?string $clientFilename;
    private ?string $clientMediaType;
    private ?string $fullPath;
    private bool $moved = false;

    public function __construct(
        string|StreamInterface $fileOrStream,
        ?int $size,
        int $errorStatus,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
        ?string $fullPath = null
    ) {
        $this->size = $size;
        $this->error = $errorStatus;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        $this->fullPath = $fullPath;

        if ($this->error === UPLOAD_ERR_OK) {
            if (\is_string($fileOrStream)) {
                $this->file = $fileOrStream;
            } else {
                $this->stream = $fileOrStream;
            }
        }
    }

    public static function create(
        string|StreamInterface $fileOrStream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $filename = null,
        ?string $mediaType = null,
        ?string $fullPath = null
    ): self {
        return new self($fileOrStream, $size, $error, $filename, $mediaType, $fullPath);
    }

    public function getContentType(): ?ContentType
    {
        return $this->clientMediaType !== null
            ? ContentType::fromHeader($this->clientMediaType)
            : null;
    }

    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $resource = @fopen((string) $this->file, 'r');
        if ($resource === false) {
            throw new \RuntimeException(\sprintf('Unable to open file "%s"', $this->file));
        }

        $this->stream = Stream::create($resource);

        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        $this->validateActive();

        if ($targetPath === '') {
            throw new \InvalidArgumentException('Target path must be a non-empty string');
        }

        if ($this->file !== null) {
            $this->moved = $this->moveFile($this->file, $targetPath);

            if ($this->moved) {
                $this->file = null;
            }
        } else {
            $targetResource = @fopen($targetPath, 'w');
            if ($targetResource === false) {
                throw new \RuntimeException(\sprintf('Unable to write to path "%s"', $targetPath));
            }

            $targetStream = Stream::create($targetResource);
            $sourceStream = $this->getStream();
            $sourceStream->rewind();

            while (!$sourceStream->eof()) {
                $targetStream->write($sourceStream->read(4096));
            }

            $targetStream->close();

            if ($this->stream !== null) {
                $this->stream->close();
                $this->stream = null;
            }

            $this->moved = true;
        }

        if (!$this->moved) {
            throw new \RuntimeException(\sprintf('Uploaded file could not be moved to "%s"', $targetPath));
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    public function getFullPath(): ?string
    {
        return $this->fullPath;
    }

    private function validateActive(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has been moved');
        }
    }

    private function moveFile(string $source, string $target): bool
    {
        return PHP_SAPI === 'cli'
            ? @\rename($source, $target)
            : @\move_uploaded_file($source, $target);
    }
}
