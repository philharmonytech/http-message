<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests\Stub\Stream;

use Philharmony\Http\Message\Stream;

final class MemoryOpenFailureStream extends Stream
{
    /**
     * @return resource|false
     */
    protected static function openMemoryResource(): mixed
    {
        return false;
    }
}
