<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Enum;

enum UploadError: int
{
    case OK = 0;          // UPLOAD_ERR_OK
    case INI_SIZE = 1;    // UPLOAD_ERR_INI_SIZE
    case FORM_SIZE = 2;   // UPLOAD_ERR_FORM_SIZE
    case PARTIAL = 3;     // UPLOAD_ERR_PARTIAL
    case NO_FILE = 4;     // UPLOAD_ERR_NO_FILE
    case NO_TMP_DIR = 6;  // UPLOAD_ERR_NO_TMP_DIR
    case CANT_WRITE = 7;  // UPLOAD_ERR_CANT_WRITE
    case EXTENSION = 8;   // UPLOAD_ERR_EXTENSION

    public function message(): string
    {
        return match ($this) {
            self::OK => 'There is no error, the file uploaded with success',
            self::INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            self::FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form',
            self::PARTIAL => 'The uploaded file was only partially uploaded',
            self::NO_FILE => 'No file was uploaded',
            self::NO_TMP_DIR => 'Missing a temporary folder',
            self::CANT_WRITE => 'Failed to write file to disk',
            self::EXTENSION => 'A PHP extension stopped the file upload',
        };
    }

    public function isOk(): bool
    {
        return $this === self::OK;
    }

    public function isError(): bool
    {
        return !$this->isOk();
    }
}
