<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests\Stub\Stream;

use Philharmony\Http\Message\Stream;

final class BrokenStatsStream extends Stream
{
    protected function getStreamStats(): array|false
    {
        return [];
    }
}
