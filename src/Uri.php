<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Philharmony\Http\Enum\Scheme;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $scheme = '';
    private string $user = '';
    private string $password = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private ?string $query = null;
    private ?string $fragment = null;

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
        $scheme = strtolower($scheme);
        $this->validateScheme($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;

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
        $password = $password ?? '';

        if ($this->user === $user && $this->password === $password) {
            return $this;
        }

        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password;

        return $clone;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function withHost(string $host): static
    {
        $newHost = $this->filterHost($host);

        if ($this->host === $newHost) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $newHost;

        return $clone;
    }

    public function getPort(): ?int
    {
        return $this->isStandardPort() ? null : $this->port;
    }

    public function withPort(?int $port): static
    {
        $this->validatePorts($port);

        if ($this->port === $port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Returns the explicitly defined port even if it is the standard port.
     */
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
        $newPath = $this->filterComponent($path, '/@');

        if ($this->path === $newPath) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $newPath;

        return $clone;
    }

    public function getQuery(): string
    {
        return $this->query ?? '';
    }

    public function withQuery(string $query): static
    {
        $query = ltrim($query, '?');
        $newQuery = $this->filterComponent($query, '/?@');

        if ($this->query === $newQuery) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $newQuery;

        return $clone;
    }

    public function getFragment(): string
    {
        return $this->fragment ?? '';
    }

    public function withFragment(string $fragment): static
    {
        $fragment = ltrim($fragment, '#');
        $newFragment = $this->filterComponent($fragment, '/?@');

        if ($this->fragment === $newFragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $newFragment;

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

        if ($authority !== '' && $path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        } elseif (str_starts_with($path, '//')) {
            $path = '/' . ltrim($path, '/');
        }

        $uri .= $path;

        if ($this->query !== null) {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== null) {
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

        $this->host = \is_string($parts['host'] ?? null) ? $this->filterHost($parts['host']) : '';

        $schemeEnum = Scheme::tryFrom($this->scheme);

        if ($schemeEnum !== null && $schemeEnum->requiresHost() && $this->host === '') {
            throw new \InvalidArgumentException(
                \sprintf('Scheme "%s" requires host', $this->scheme)
            );
        }

        $port = (\is_int($parts['port'] ?? null) || is_numeric($parts['port'] ?? null))
            ? (int)$parts['port']
            : null;
        if ($port !== null) {
            $this->validatePorts($port);
        }
        $this->port = $port;

        $this->path = \is_string($parts['path'] ?? null) ? $this->filterComponent($parts['path'], '/@') : '';
        $this->query = \array_key_exists('query', $parts) && \is_string($parts['query'])
            ? $this->filterComponent($parts['query'], '/?@')
            : null;
        $this->fragment = \array_key_exists('fragment', $parts) && \is_string($parts['fragment'])
            ? $this->filterComponent($parts['fragment'], '/?@')
            : null;
        $this->user = \is_string($parts['user'] ?? null) ? $parts['user'] : '';
        $this->password = \is_string($parts['pass'] ?? null) ? $parts['pass'] : '';
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
        if ($this->port === null) {
            return true;
        }

        if ($this->scheme === '') {
            return false;
        }

        $schemeEnum = Scheme::tryFrom($this->scheme);

        if ($schemeEnum === null) {
            return false;
        }

        return $this->port === $schemeEnum->defaultPort();
    }

    private function filterHost(string $host): string
    {
        if ($host === '') {
            return '';
        }

        $host = strtolower($host);

        if (\function_exists('idn_to_ascii') && !filter_var($host, FILTER_VALIDATE_IP)) {
            $converted = idn_to_ascii(
                $host,
                IDNA_DEFAULT,
                \defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0
            );

            if ($converted !== false) {
                $host = $converted;
            }
        }

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $ip = substr($host, 1, -1);

            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                throw new \InvalidArgumentException(\sprintf('Invalid IPv6 host: %s', $host));
            }

            return $host;
        }

        if ($host !== '' && preg_match('/[\x00-\x20\/?#@]/', $host)) {
            throw new \InvalidArgumentException(\sprintf('Invalid host: %s', $host));
        }

        return $host;
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
