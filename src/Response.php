<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Philharmony\Http\Enum\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response extends Message implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase;

    /**
     * @param int $status
     * @param array<string, string|string[]> $headers
     * @param StreamInterface|string|resource $body
     * @param string $version
     * @param string|null $reason
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        mixed $body = '',
        string $version = '1.1',
        ?string $reason = null
    ) {
        $this->validateStatusCode($status);
        parent::__construct($body, $headers, $version);

        $this->statusCode = $status;
        $this->reasonPhrase = $this->resolveReasonPhrase($status, $reason);
    }

    /**
     * @param int $status
     * @param array<string, string|string[]> $headers
     * @param StreamInterface|string|resource $body
     * @param string $version
     * @param string $reason
     */
    public static function create(
        int $status = 200,
        array $headers = [],
        mixed $body = '',
        string $version = '1.1',
        string $reason = ''
    ): ResponseInterface {
        return new self($status, $headers, $body, $version, $reason);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, ?string $reasonPhrase = null): static
    {
        $this->validateStatusCode($code);
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $this->resolveReasonPhrase($code, $reasonPhrase);

        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function isInformational(): bool
    {
        return StatusCode::tryFrom($this->statusCode)?->isInformational() ?? false;
    }

    public function isSuccessful(): bool
    {
        return StatusCode::tryFrom($this->statusCode)?->isSuccess() ?? false;
    }

    public function isRedirection(): bool
    {
        return StatusCode::tryFrom($this->statusCode)?->isRedirection() ?? false;
    }

    public function isClientError(): bool
    {
        return StatusCode::tryFrom($this->statusCode)?->isClientError() ?? false;
    }

    public function isServerError(): bool
    {
        return StatusCode::tryFrom($this->statusCode)?->isServerError() ?? false;
    }

    public function isError(): bool
    {
        return StatusCode::tryFrom($this->statusCode)?->isError() ?? false;
    }

    private function resolveReasonPhrase(int $status, ?string $reason): string
    {
        if ($reason !== null && $reason !== '') {
            return $reason;
        }

        return StatusCode::tryFrom($status)?->phrase() ?? '';
    }

    private function validateStatusCode(int $code): void
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException(\sprintf('Invalid HTTP status code "%d"', $code));
        }
    }
}
