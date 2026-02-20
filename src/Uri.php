<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private const HTTP = 'http';
    private const HTTPS = 'https';
    private const DEFAULT_HOST = 'localhost';

    private const FTP = 'ftp';
    private const WS = 'ws';
    private const WSS = 'wss';

    private const DEFAULT_PORTS = [
        self::HTTP => 80,
        self::HTTPS => 443,
        self::FTP => 21,
        self::WS => 80,
        self::WSS => 443,
    ];

    private const SCHEMES_REQUIRE_DEFAULT_HOST = [
        self::HTTP,
        self::HTTPS,
        self::FTP,
        self::WS,
        self::WSS
    ];

    private string $scheme = '';
    private string $user = '';
    private string $password = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '/';
    private string $query = '';
    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        if (!empty($uri)) {
            $this->parseUri($uri);
        } else {
            $this->scheme = self::HTTP;
            $this->ensureDefaultHost();
            $this->ensureDefaultPort();
        }
    }

    public static function create(string $uri = ''): UriInterface
    {
        return new self($uri);
    }

    public static function fromParts(array $parts): UriInterface
    {
        $uri = new self();
        $uri->applyParts($parts);

        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $this->validateScheme($scheme);
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);
        $clone->ensureDefaultHost();
        $clone->ensureDefaultPort();

        return $clone;
    }

    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        $userInfo = $this->getUserInfo();
        if ($userInfo !== '') {
            $authority = $userInfo . '@' . $authority;
        }

        $port = $this->getPort();
        if ($port !== null && !$this->isStandardPort()) {
            $authority .= ':' . $port;
        }

        return $authority;
    }


    public function getUserInfo(): string
    {
        if ($this->user === '') {
            return '';
        }

        $userInfo = $this->user;
        if ($this->password !== '') {
            $userInfo .= ':' . $this->password;
        }

        return $userInfo;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password ?? '';

        return $clone;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function withHost(string $host): UriInterface
    {
        $clone = clone $this;
        $clone->host = strtolower($host);
        $clone->ensureDefaultPort();

        return $clone;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function withPort(?int $port): UriInterface
    {
        $this->validatePorts($port);
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function withPath(string $path): UriInterface
    {
        $normalizedPath = preg_replace('/\/+/', '/', $path);
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        } elseif (!str_starts_with($normalizedPath, '/')) {
            $normalizedPath = '/' . $normalizedPath;
        }

        $clone = clone $this;
        $clone->path = $normalizedPath;

        return $clone;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function withQuery(string $query): UriInterface
    {
        $query = ltrim($query, '?');
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $fragment = ltrim($fragment, '#');
        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        if ($uri === '' && !str_starts_with($this->path, '/')) {
            $uri .= '/';
        }
        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    private function parseUri(string $uri): void
    {
        if (empty($uri)) {
            return;
        }

        $parts = parse_url($uri);
        if ($parts === false) {
            throw new \InvalidArgumentException(sprintf('Invalid URI: %s', $uri));
        }

        $this->applyParts($parts);
    }

    private function applyParts(array $parts = []): void
    {
        $this->scheme = $parts['scheme'] ?? '';
        if ($this->scheme !== '') {
            $this->validateScheme($this->scheme);
            $this->scheme = strtolower($this->scheme);
        }

        $this->host = $parts['host'] ?? '';

        $port = isset($parts['port']) ? (int)$parts['port'] : null;
        if ($port !== null) {
            $this->validatePorts($port);
        }
        $this->port = $port;

        $this->path = $parts['path'] ?? '/';
        $this->query = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';
        $this->user = $parts['user'] ?? '';
        $this->password = $parts['pass'] ?? '';

        $this->ensureDefaultHost();
        $this->ensureDefaultPort();
    }


    private function validateScheme(string $scheme): void
    {
        if ($scheme !== '' && !preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/', $scheme)) {
            throw new \InvalidArgumentException(sprintf('Invalid scheme: %s', $scheme));
        }
    }

    private function validatePorts(?int $port): void
    {
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException(sprintf('Invalid port: %d', $port));
        }
    }

    private function requiresDefaultHost(): bool
    {
        return in_array($this->scheme, self::SCHEMES_REQUIRE_DEFAULT_HOST, true);
    }

    private function getDefaultPort(): ?int
    {
        if ($this->port !== null) {
            return $this->port;
        }

        return self::DEFAULT_PORTS[$this->scheme] ?? null;
    }

    private function isStandardPort(): bool
    {
        if ($this->port === null || $this->scheme === '') {
            return true;
        }

        $standardPort = self::DEFAULT_PORTS[$this->scheme] ?? null;

        return $standardPort !== null && $this->port === $standardPort;
    }

    private function ensureDefaultHost(): void
    {
        if ($this->host === '' && $this->requiresDefaultHost()) {
            $this->host = self::DEFAULT_HOST;
        }
    }

    private function ensureDefaultPort(): void
    {
        $defaultPort = $this->getDefaultPort();
        if ($defaultPort !== null && $this->port === null) {
            $this->port = $defaultPort;
        }
    }
}
