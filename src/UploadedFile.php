<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Philharmony\Http\Enum\ContentType;
use Philharmony\Http\Message\Enum\UploadError;
use Philharmony\Http\PsrExtension\UploadedFileFullPathInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface, UploadedFileFullPathInterface
{
    private ?string $file = null;
    private ?StreamInterface $stream = null;
    private ?int $size;
    private UploadError $uploadError;
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
        $uploadError = UploadError::tryFrom($errorStatus);

        if (!$uploadError instanceof UploadError) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid upload error status "%d"', $errorStatus)
            );
        }

        $this->size = $size;
        $this->uploadError = $uploadError;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        $this->fullPath = $fullPath;

        if ($uploadError->isError()) {
            return;
        }

        if (\is_string($fileOrStream)) {
            $this->file = $fileOrStream;
        } else {
            $this->stream = $fileOrStream;
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

        $resource = @fopen((string)$this->file, 'rb');
        if ($resource === false) {
            $lastError = error_get_last();
            $message = $lastError['message'] ?? 'Unknown error';

            throw new \RuntimeException(
                \sprintf('Unable to open file "%s": %s', $this->file, $message)
            );
        }

        $this->stream = Stream::create($resource);
        $this->file = null;

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
            $sourceStream = $this->getStream();
            $sourceStream->rewind();

            $targetResource = @fopen($targetPath, 'wb');
            if ($targetResource === false) {
                throw new \RuntimeException(
                    \sprintf('Unable to write to path "%s"', $targetPath)
                );
            }

            $targetStream = Stream::create($targetResource);

            try {
                while (!$sourceStream->eof()) {
                    $targetStream->write($sourceStream->read(4096));
                }
            } catch (\Throwable $exception) {
                if (is_file($targetPath)) {
                    @unlink($targetPath);
                }
                throw $exception;
            } finally {
                $targetStream->close();
            }

            if ($this->stream !== null) {
                $this->stream->close();
                $this->stream = null;
            }

            $this->moved = true;
        }

        if (!$this->moved) {
            throw new \RuntimeException(
                \sprintf('Uploaded file could not be moved to "%s"', $targetPath)
            );
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->uploadError->value;
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
        if ($this->uploadError->isError()) {
            throw new \RuntimeException(
                \sprintf(
                    'Cannot retrieve stream due to upload error: %s',
                    $this->uploadError->message()
                )
            );
        }

        if ($this->moved) {
            throw new \RuntimeException('Uploaded file has already been moved');
        }
    }

    private function moveFile(string $source, string $target): bool
    {
        // This branch relies on PHP SAPI upload internals and is not unit-testable.
        // @codeCoverageIgnoreStart
        if (is_uploaded_file($source)) {
            return @move_uploaded_file($source, $target);
        }
        // @codeCoverageIgnoreEnd

        return @rename($source, $target);
    }
}
