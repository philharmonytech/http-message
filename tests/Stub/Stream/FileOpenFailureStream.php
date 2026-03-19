<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests\Stub\Stream;

use Philharmony\Http\Message\Stream;

final class FileOpenFailureStream extends Stream
{
    /**
     * @return resource|false
     */
    protected static function openFileResource(string $fileName, string $mode): mixed
    {
        return false;
    }
}
