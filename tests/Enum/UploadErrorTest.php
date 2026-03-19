<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests\Enum;

use Philharmony\Http\Message\Enum\UploadError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadErrorTest extends TestCase
{
    public function testFromValue(): void
    {
        $error = UploadError::from(0);

        $this->assertSame(UploadError::OK, $error);
    }

    public function testIsOk(): void
    {
        $error = UploadError::OK;

        $this->assertTrue($error->isOk());
        $this->assertFalse($error->isError());
    }

    public function testIsError(): void
    {
        $error = UploadError::NO_FILE;

        $this->assertTrue($error->isError());
        $this->assertFalse($error->isOk());
    }

    #[DataProvider('messageDataProvider')]
    public function testMessage(
        int $statusError,
        string $message
    ): void {
        $uploadError = UploadError::from($statusError);

        $this->assertEquals($message, $uploadError->message());
    }

    /**
     * @return array<string, array{statusError: int, message: string}>
     */
    public static function messageDataProvider(): array
    {
        return [
            'upload error ok' => [
                'statusError' => UPLOAD_ERR_OK,
                'message' => 'There is no error, the file uploaded with success',
            ],
            'upload error ini size' => [
                'statusError' => UPLOAD_ERR_INI_SIZE,
                'message' => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            ],
            'upload error from size' => [
                'statusError' => UPLOAD_ERR_FORM_SIZE,
                'message' => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form',
            ],
            'upload error partial' => [
                'statusError' => UPLOAD_ERR_PARTIAL,
                'message' => 'The uploaded file was only partially uploaded',
            ],
            'upload error no file' => [
                'statusError' => UPLOAD_ERR_NO_FILE,
                'message' => 'No file was uploaded',
            ],
            'upload error no tmp dir' => [
                'statusError' => UPLOAD_ERR_NO_TMP_DIR,
                'message' => 'Missing a temporary folder',
            ],
            'upload error cant write' => [
                'statusError' => UPLOAD_ERR_CANT_WRITE,
                'message' => 'Failed to write file to disk',
            ],
            'upload error extension' => [
                'statusError' => UPLOAD_ERR_EXTENSION,
                'message' => 'A PHP extension stopped the file upload',
            ],
        ];
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(UploadError::tryFrom(999));
    }
}
