<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Philharmony\Http\Enum\Scheme;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private const DEFAULT_HOST = 'localhost';

    private string $scheme = '';
    private string $user = '';
    private string $password = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $this->parseUri($uri);
        }
    }

    public static function create(string $uri = ''): self
    {
        return new self($uri);
    }

    /**
     * @param array<string, mixed> $parts
     */
    public static function fromParts(array $parts): self
    {
        $uri = new self();
        $uri->applyParts($parts);

        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function withScheme(string $scheme): static
    {
        $this->validateScheme($scheme);
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);

        $clone->ensureDefaultHost();

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
        if ($port !== null) {
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

    public function withUserInfo(string $user, ?string $password = null): static
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

    public function withHost(string $host): static
    {
        $clone = clone $this;
        $clone->host = strtolower($host);

        return $clone;
    }

    public function getPort(): ?int
    {
        return $this->isStandardPort() ? null : $this->port;
    }

    public function withPort(?int $port): static
    {
        $this->validatePorts($port);
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    public function getExplicitPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function withPath(string $path): static
    {
        $clone = clone $this;
        $clone->path = $this->filterComponent($path, '/@');

        return $clone;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function withQuery(string $query): static
    {
        $query = ltrim($query, '?');
        $clone = clone $this;
        $clone->query = $this->filterComponent($query, '/?@');

        return $clone;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withFragment(string $fragment): static
    {
        $fragment = ltrim($fragment, '#');
        $clone = clone $this;
        $clone->fragment = $this->filterComponent($fragment, '/?@');

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

        $path = $this->getPath();

        if ($authority !== '') {
            if ($path !== '' && $path[0] !== '/') {
                $path = '/' . $path;
            }
        } else {
            if (str_starts_with($path, '//')) {
                $path = '/' . ltrim($path, '/');
            }
        }

        $uri .= $path;

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
        $parts = parse_url($uri);
        if ($parts === false) {
            throw new \InvalidArgumentException(\sprintf('Invalid URI: %s', $uri));
        }

        $this->applyParts($parts);
    }

    /**
     * @param array<string, mixed> $parts
     */
    private function applyParts(array $parts = []): void
    {
        $this->scheme = \is_string($parts['scheme'] ?? null) ? $parts['scheme'] : '';
        if ($this->scheme !== '') {
            $this->validateScheme($this->scheme);
            $this->scheme = strtolower($this->scheme);
        }

        $this->host = \is_string($parts['host'] ?? null) ? $parts['host'] : '';

        $port = (\is_int($parts['port'] ?? null) || is_numeric($parts['port'] ?? null))
            ? (int)$parts['port']
            : null;
        if ($port !== null) {
            $this->validatePorts($port);
        }
        $this->port = $port;

        $this->path = \is_string($parts['path'] ?? null) ? $this->filterComponent($parts['path'], '/@') : '';
        $this->query = \is_string($parts['query'] ?? null) ? $this->filterComponent($parts['query'], '/?@') : '';
        $this->fragment = \is_string($parts['fragment'] ?? null) ? $this->filterComponent($parts['fragment'], '/?@') : '';
        $this->user = \is_string($parts['user'] ?? null) ? $parts['user'] : '';
        $this->password = \is_string($parts['pass'] ?? null) ? $parts['pass'] : '';

        $this->ensureDefaultHost();
    }

    private function validateScheme(string $scheme): void
    {
        if ($scheme !== '' && !preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/', $scheme)) {
            throw new \InvalidArgumentException(\sprintf('Invalid scheme: %s', $scheme));
        }
    }

    private function validatePorts(?int $port): void
    {
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException(\sprintf('Invalid port: %d', $port));
        }
    }

    private function isStandardPort(): bool
    {
        if ($this->port === null || $this->scheme === '') {
            return true;
        }

        $schemeEnum = Scheme::tryFrom($this->scheme);
        if ($schemeEnum === null) {
            return false;
        }

        return $this->port === $schemeEnum->defaultPort();
    }

    private function ensureDefaultHost(): void
    {
        $schemeEnum = Scheme::tryFrom($this->scheme);

        if ($this->host === '' && $schemeEnum?->requiresHost()) {
            $this->host = self::DEFAULT_HOST;
        }
    }

    private function filterComponent(string $value, string $additionalAllowed = ''): string
    {
        $result = preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:' . preg_quote($additionalAllowed, '/') . ']++|%(?![A-Fa-f0-9]{2}))/',
            static fn (array $matches): string => rawurlencode((string)$matches[0]),
            $value
        );

        return (string) $result;
    }
}
