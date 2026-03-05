<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface
{
    /** @var array<string, string[]> */
    protected array $headers = [];
    /** @var array<string, string> */
    protected array $headerNames = [];
    protected string $protocol = '1.1';
    protected StreamInterface $body;

    /**
     * @param StreamInterface|string|resource $body
     * @param array<string, string|string[]> $headers
     * @param string $version
     */
    public function __construct(
        mixed $body = '',
        array $headers = [],
        string $version = '1.1'
    ) {
        $this->setHeaders($headers);
        $this->protocol = $version;
        $this->body = Stream::create($body);
    }

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return [];
        }

        $originalName = $this->headerNames[$normalized];

        return $this->headers[$originalName];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, mixed $value): static
    {
        $this->validateHeaderName($name);
        $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($name);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = $value;

        return $new;
    }

    public function withAddedHeader(string $name, mixed $value): static
    {
        $this->validateHeaderName($name);
        $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($name);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            $originalName = $new->headerNames[$normalized];
            $new->headers[$originalName] = array_merge($new->headers[$originalName], $value);
        } else {
            $new->headerNames[$normalized] = $name;
            $new->headers[$name] = $value;
        }

        return $new;
    }

    public function withoutHeader(string $name): static
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $new = clone $this;
        $originalName = $new->headerNames[$normalized];
        unset($new->headers[$originalName], $new->headerNames[$normalized]);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    protected function setHeaders(array $headers): void
    {
        foreach ($headers as $header => $value) {
            $headerName = (string) $header;
            $this->validateHeaderName($headerName);

            $normalized = strtolower($headerName);
            $this->headerNames[$normalized] = $headerName;
            $this->headers[$headerName] = $this->normalizeHeaderValue($value);
        }
    }

    private function validateHeaderName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9\'!#$%&*+.^_`|~-]+$/D', $name)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid HTTP header name', $name));
        }
    }

    /**
     * @return string[]
     */
    private function normalizeHeaderValue(mixed $value): array
    {
        $values = \is_array($value) ? array_values($value) : [$value];

        if ($values === []) {
            throw new \InvalidArgumentException('Header value can not be empty');
        }

        return array_map(function (mixed $v): string {
            if (!\is_string($v) && !is_numeric($v)) {
                throw new \InvalidArgumentException('Header values must be strings or numbers');
            }

            $v = (string) $v;
            if (preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $v)) {
                throw new \InvalidArgumentException('Header value contains invalid characters (CR, LF or NULL)');
            }

            return $v;
        }, $values);
    }
}
