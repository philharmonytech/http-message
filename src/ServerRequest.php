<?php

declare(strict_types=1);

namespace Philharmony\Http\Message;

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
    /** @var array<string, UploadedFileInterface> */
    private array $uploadedFiles;
    /** @var null|array<mixed>|object */
    private mixed $parsedBody;

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
     * @param array<string, UploadedFileInterface> $uploadedFiles
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
        mixed $parsedBody = null
    ) {
        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->queryParams = $queryParams;
        $this->uploadedFiles = $uploadedFiles;
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
     * @param array<string, UploadedFileInterface> $uploadedFiles
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
        mixed $parsedBody = null
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
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * @return array<string, UploadedFileInterface>
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array<string, UploadedFileInterface> $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * @return null|array<mixed>|object
     */
    public function getParsedBody(): mixed
    {
        return $this->parsedBody;
    }

    /**
     * @param null|array<mixed>|object $data
     */
    public function withParsedBody(mixed $data): static
    {
        if (!\is_array($data) && !\is_object($data) && $data !== null) {
            throw new \InvalidArgumentException('Parsed body must be an array, object, or null');
        }

        $new = clone $this;
        $new->parsedBody = $data;
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
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $value
     */
    public function withAttribute(string $name, mixed $value): static
    {
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
}
