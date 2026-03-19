<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests\Stub\Stream;

use Philharmony\Http\Message\Stream;

final class ForceWritableStream extends Stream
{
    public function isWritable(): bool
    {
        return true;
    }
}
