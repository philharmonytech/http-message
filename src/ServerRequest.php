<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

use Philharmony\Http\Enum\ContentType;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array<string, mixed> */
    private array $serverParams;
    /** @var array<string, string|string[]> */
    private array $cookieParams;
    /** @var array<string, mixed> */
    private array $queryParams;
    /** @var array<int|string, UploadedFileInterface|array<int|string, mixed>> */
    private array $uploadedFiles;
    /** @var null|array<mixed>|object */
    private array|object|null $parsedBody;
    private bool $parsedBodyResolved = false;
    private ?string $cachedBody = null;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param StreamInterface|string|resource $body
     * @param array<string, string|string[]> $headers
     * @param string $version
     * @param array<string, mixed> $serverParams
     * @param array<string, string|string[]> $cookieParams
     * @param array<string, mixed> $queryParams
     * @param array<int|string, UploadedFileInterface|array<int|string, mixed>> $uploadedFiles
     * @param null|array<mixed>|object $parsedBody
     */
    public function __construct(
        string $method,
        mixed $uri,
        mixed $body = '',
        array $headers = [],
        string $version = '1.1',
        array $serverParams = [],
        array $cookieParams = [],
        array $queryParams = [],
        array $uploadedFiles = [],
        array|object|null $parsedBody = null
    ) {
        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->queryParams = $queryParams;
        $this->uploadedFiles = $this->validateUploadedFiles($uploadedFiles);
        $this->parsedBody = $parsedBody;

        parent::__construct($method, $uri, $body, $headers, $version);
    }

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param StreamInterface|string|resource $body
     * @param array<string, string|string[]> $headers
     * @param string $version
     * @param array<string, mixed> $serverParams
     * @param array<string, string|string[]> $cookieParams
     * @param array<string, mixed> $queryParams
     * @param array<int|string, UploadedFileInterface|array<int|string, mixed>> $uploadedFiles
     * @param null|array<mixed>|object $parsedBody
     */
    public static function make(
        string $method,
        mixed $uri,
        mixed $body = '',
        array $headers = [],
        string $version = '1.1',
        array $serverParams = [],
        array $cookieParams = [],
        array $queryParams = [],
        array $uploadedFiles = [],
        array|object|null $parsedBody = null
    ): ServerRequestInterface {
        return new self(
            $method,
            $uri,
            $body,
            $headers,
            $version,
            $serverParams,
            $cookieParams,
            $queryParams,
            $uploadedFiles,
            $parsedBody
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return array<string, string|string[]>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @param array<string, string|string[]> $cookies
     */
    public function withCookieParams(array $cookies): static
    {
        if ($this->cookieParams === $cookies) {
            return $this;
        }

        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param array<string, mixed> $query
     */
    public function withQueryParams(array $query): static
    {
        if ($this->queryParams === $query) {
            return $this;
        }

        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /** @return array<int|string, UploadedFileInterface|array<int|string, mixed>> */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array<int|string, UploadedFileInterface|array<int|string, mixed>> $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        if ($this->uploadedFiles === $uploadedFiles) {
            return $this;
        }

        $new = clone $this;
        $new->uploadedFiles = $this->validateUploadedFiles($uploadedFiles);
        return $new;
    }

    /** @return array<mixed>|object|null */
    public function getParsedBody(): array|object|null
    {
        return $this->resolveParsedBody();
    }

    /**
     * Returns the raw request body as a cached string.
     */
    public function getRawBody(): string
    {
        return $this->readBody();
    }

    /**
     * @param null|array<mixed>|object $data
     */
    public function withParsedBody(mixed $data): static
    {
        if ($this->parsedBody === $data) {
            return $this;
        }

        if (!\is_array($data) && !\is_object($data) && $data !== null) {
            throw new \InvalidArgumentException('Parsed body must be an array, object, or null');
        }

        $new = clone $this;
        $new->parsedBody = $data;
        $new->parsedBodyResolved = true;

        return $new;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function withAttribute(string $name, mixed $value): static
    {
        if (\array_key_exists($name, $this->attributes) && $this->attributes[$name] === $value) {
            return $this;
        }

        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute(string $name): static
    {
        if (!\array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();
        return $this->input($key, $sentinel) !== $sentinel;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (\array_key_exists($key, $this->queryParams)) {
            return $this->queryParams[$key];
        }

        $body = $this->getParsedBody();

        if (!\is_array($body)) {
            return $default;
        }

        return $this->getValueByPath($body, $key, $default);
    }

    /**
     * @param array<int|string, mixed> $uploadedFiles
     * @return array<int|string, UploadedFileInterface|array<int|string, mixed>>
     */
    private function validateUploadedFiles(array $uploadedFiles): array
    {
        foreach ($uploadedFiles as $key => $file) {
            if ($file instanceof UploadedFileInterface) {
                continue;
            }

            if (\is_array($file)) {
                /** @var array<int|string, mixed> $file */
                $uploadedFiles[$key] = $this->validateUploadedFiles($file);
                continue;
            }

            throw new \InvalidArgumentException(
                'Uploaded files must be an instance of UploadedFileInterface or array'
            );
        }

        return $uploadedFiles;
    }

    /** @return array<mixed>|object|null */
    private function resolveParsedBody(): array|object|null
    {
        if ($this->parsedBodyResolved) {
            return $this->parsedBody;
        }

        $this->parsedBodyResolved = true;

        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }

        $header = $this->getHeaderLine('Content-Type');

        if ($header === '') {
            return null;
        }

        $contentType = ContentType::fromHeader($header);

        if ($contentType === null) {
            return null;
        }

        if ($contentType->isJson()) {
            return $this->parseJsonBody();
        }

        if ($contentType->isForm()) {
            return $this->parseFormBody();
        }

        return null;
    }

    /** @return array<mixed>|null */
    private function parseJsonBody(): ?array
    {
        $body = $this->readBody();

        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !\is_array($decoded)) {
            return null;
        }

        $this->parsedBody = $decoded;

        return $decoded;
    }

    /** @return array<int|string, string|array<string, mixed>>|null */
    private function parseFormBody(): ?array
    {
        $body = $this->readBody();

        if ($body === '') {
            return null;
        }

        parse_str($body, $data);

        $this->parsedBody = $data;

        return $data;
    }

    private function readBody(): string
    {
        if ($this->cachedBody !== null) {
            return $this->cachedBody;
        }

        $stream = $this->getBody();

        if (!$stream->isSeekable()) {
            return $this->cachedBody = (string) $stream;
        }

        $position = $stream->tell();

        $stream->rewind();
        $this->cachedBody = $stream->getContents();

        $stream->seek($position);

        return $this->cachedBody;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getValueByPath(array $data, string $path, mixed $default): mixed
    {
        if (!str_contains($path, '.')) {
            return $data[$path] ?? $default;
        }

        $segments = explode('.', $path);

        foreach ($segments as $segment) {
            if (!\is_array($data) || !\array_key_exists($segment, $data)) {
                return $default;
            }

            $data = $data[$segment];
        }

        return $data;
    }
}
