<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Philharmony\Http\Enum\ContentType;
use Philharmony\Http\Enum\HttpMethod;
use Philharmony\Http\Enum\Scheme;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    private string $method;
    private ?string $requestTarget = null;
    private UriInterface $uri;
    private ?HttpMethod $methodEnum = null;

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param StreamInterface|string|resource $body
     * @param array<string, string|string[]> $headers
     * @param string $version
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        mixed $body = '',
        array $headers = [],
        string $version = '1.1'
    ) {
        $this->method = $this->filterMethod($method);
        $this->uri = $uri instanceof UriInterface ? $uri : Uri::create($uri);

        parent::__construct($body, $headers, $version);

        if (!$this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $this->updateHostFromUri();
        }
    }

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param StreamInterface|string|resource $body
     * @param array<string, string|string[]> $headers
     * @param string $version
     */
    public static function create(
        string $method,
        mixed $uri,
        mixed $body = '',
        array $headers = [],
        string $version = '1.1'
    ): RequestInterface {
        return new self($method, $uri, $body, $headers, $version);
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }

        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        if (preg_match('/\s/', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target; cannot contain whitespace');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $method = $this->filterMethod($method);

        if ($method === $this->method) {
            return $this;
        }

        $new = clone $this;
        $new->method = $method;
        $new->methodEnum = HttpMethod::tryFrom(strtoupper($method));

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $new;
        }

        if ($uri->getHost() === '') {
            return $new;
        }

        $new->updateHostFromUri();

        return $new;
    }

    public function getMethodEnum(): ?HttpMethod
    {
        return $this->methodEnum;
    }

    public function isHttps(): bool
    {
        return Scheme::tryFrom($this->uri->getScheme())?->isSecure() ?? false;
    }

    public function isSafe(): bool
    {
        return $this->methodEnum?->isSafe() ?? false;
    }

    public function isIdempotent(): bool
    {
        return $this->methodEnum?->isIdempotent() ?? false;
    }

    public function isJson(): bool
    {
        return ContentType::fromHeader($this->getHeaderLine('Content-Type'))?->isJson() ?? false;
    }

    public function isForm(): bool
    {
        return ContentType::fromHeader($this->getHeaderLine('Content-Type'))?->isForm() ?? false;
    }

    private function filterMethod(string $method): string
    {
        if (!preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/', $method)) {
            throw new \InvalidArgumentException(\sprintf('Invalid HTTP method "%s"', $method));
        }

        $this->methodEnum = HttpMethod::tryFrom(strtoupper($method));

        return $method;
    }

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();
        $port = $this->uri->getPort();

        if ($port !== null) {
            $host .= ':' . $port;
        }

        $this->headerNames['host'] = 'Host';
        $this->headers['Host'] = [$host];
    }
}
