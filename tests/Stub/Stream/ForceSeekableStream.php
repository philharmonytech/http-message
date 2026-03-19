<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests\Stub\Stream;

use Philharmony\Http\Message\Stream;

final class ForceSeekableStream extends Stream
{
    public function isSeekable(): bool
    {
        return true;
    }
}
