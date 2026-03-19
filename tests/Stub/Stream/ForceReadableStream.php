<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests\Stub\Stream;

use Philharmony\Http\Message\Stream;

final class ForceReadableStream extends Stream
{
    public function isReadable(): bool
    {
        return true;
    }
}
