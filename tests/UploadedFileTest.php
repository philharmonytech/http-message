<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Enum\ContentType;
use Philharmony\Http\Message\Stream;
use Philharmony\Http\Message\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'philharmony_test');
        file_put_contents($this->tempFile, 'content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testCreateFromString(): void
    {
        $size = filesize($this->tempFile);
        $fileInfo = pathinfo($this->tempFile);

        $uploadedFile = UploadedFile::create(
            $this->tempFile,
            $size,
            UPLOAD_ERR_OK,
            $fileInfo['basename'],
            $fileInfo['extension'] ?? null,
            $fileInfo['dirname']
        );

        $stream = $uploadedFile->getStream();

        $this->assertEquals($size, $uploadedFile->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());
        $this->assertEquals($fileInfo['basename'], $uploadedFile->getClientFilename());
        $this->assertEquals($fileInfo['extension'] ?? null, $uploadedFile->getClientMediaType());
        $this->assertEquals($fileInfo['dirname'], $uploadedFile->getFullPath());
        $this->assertNull($uploadedFile->getContentType());
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('content', $stream->getContents());
    }

    public function testCreateFromStream(): void
    {
        $stream = Stream::create('content from stream');
        $uploadedFile = new UploadedFile(
            $stream,
            19,
            UPLOAD_ERR_OK,
            'Philharmony.json',
            'application/json'
        );

        $this->assertSame($stream, $uploadedFile->getStream());
        $this->assertEquals('content from stream', (string) $uploadedFile->getStream());
        $this->assertSame(ContentType::JSON, $uploadedFile->getContentType());
    }

    public function testCreateThrowsExceptionWhenInvalidUploadErrorProvided(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid upload error status "999"');

        UploadedFile::create('php://temp', null, 999);
    }

    public function testMoveToFromPath(): void
    {
        $uploadedFile = UploadedFile::create($this->tempFile, 7, UPLOAD_ERR_OK);
        $target = $this->tempFile . '_moved';

        $uploadedFile->moveTo($target);

        $this->assertTrue(file_exists($target));
        $this->assertFalse(file_exists($this->tempFile));

        unlink($target);
    }

    public function testMoveToFromStream(): void
    {
        $stream = Stream::create('stream content');
        $uploadedFile = UploadedFile::create($stream, 14, UPLOAD_ERR_OK);
        $target = $this->tempFile . '_from_stream';

        $uploadedFile->moveTo($target);

        $this->assertTrue(file_exists($target));
        $this->assertSame('stream content', file_get_contents($target));

        unlink($target);
    }

    public function testThrowsOnInvalidTargetPath(): void
    {
        $uploadedFile = UploadedFile::create($this->tempFile, 7, UPLOAD_ERR_OK);

        $this->expectException(\InvalidArgumentException::class);
        $uploadedFile->moveTo('');
    }

    public function testGetStreamThrowsOnUploadError(): void
    {
        $uploadedFile = UploadedFile::create($this->tempFile, 7, UPLOAD_ERR_PARTIAL);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot retrieve stream due to upload error');
        $uploadedFile->getStream();
    }

    public function testMoveToThrowsIfAlreadyMoved(): void
    {
        $uploadedFile = UploadedFile::create($this->tempFile, 12, UPLOAD_ERR_OK);
        $target = $this->tempFile . '_moved';

        $uploadedFile->moveTo($target);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Uploaded file has already been moved');

        $uploadedFile->moveTo($target . '_2');
        unlink($target);
    }

    public function testGetStreamThrowsExceptionIfFileCannotBeOpened(): void
    {

        $invalidFile = sys_get_temp_dir() . '/non_existent_' . uniqid();

        $upload = UploadedFile::create($invalidFile, 10, UPLOAD_ERR_OK);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to open file \"$invalidFile\"");

        $upload->getStream();
    }

    public function testMoveToThrowsExceptionIfTargetPathIsInvalid(): void
    {
        $stream = Stream::create('some content');
        $upload = UploadedFile::create($stream, 12, UPLOAD_ERR_OK);

        $invalidTarget = '/non/existent/directory/structure/' . uniqid('test_');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to write to path \"$invalidTarget\"");

        $upload->moveTo($invalidTarget);
    }

    public function testMoveToFromStreamCleansUpOnWriteFailure(): void
    {
        $resource = fopen('php://memory', 'r+');
        $stream = new class ($resource) extends Stream {
            public function read(int $length): string
            {
                throw new \RuntimeException('read failure');
            }
            public function eof(): bool
            {
                return false;
            }
        };

        $upload = UploadedFile::create($stream, 10, UPLOAD_ERR_OK);
        $target = sys_get_temp_dir() . '/philharmony_uploaded_' . uniqid();

        $this->expectException(\RuntimeException::class);

        try {
            $upload->moveTo($target);
        } finally {
            if (\is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    public function testMoveToThrowsExceptionIfRenameFails(): void
    {
        $sourceFile = tempnam(sys_get_temp_dir(), 'source_');
        file_put_contents($sourceFile, 'data');

        $upload = new UploadedFile($sourceFile, 4, UPLOAD_ERR_OK);
        $invalidTarget = '/non/existent/path/' . uniqid('phil_');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Uploaded file could not be moved to');
        $upload->moveTo($invalidTarget);
        if (file_exists($sourceFile)) {
            @unlink($sourceFile);
        }
    }
}
