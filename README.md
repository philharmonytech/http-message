# philharmony/http-message

[![Validate](https://github.com/philharmonytech/http-message/actions/workflows/ci.yaml/badge.svg?job=validate)](https://github.com/philharmonytech/http-message/actions/workflows/ci.yml)
[![Analysis](https://github.com/philharmonytech/http-message/actions/workflows/ci.yaml/badge.svg?job=static-analysis)](https://github.com/philharmonytech/http-message/actions/workflows/ci.yml)
[![Test](https://github.com/philharmonytech/http-message/actions/workflows/ci.yaml/badge.svg?job=tests)](https://github.com/philharmonytech/http-message/actions/workflows/ci.yml)
[![codecov](https://codecov.io/github/philharmonytech/http-message/graph/badge.svg?token=JVGM1RRACK)](https://codecov.io/github/philharmonytech/http-message)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%20to%208.4-8892BF.svg)](https://www.php.net/supported-versions.php)
[![Latest Stable Version](https://img.shields.io/github/v/release/philharmonytech/http-message?label=stable)](https://github.com/philharmonytech/http-message/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/philharmony/http-message)](https://packagist.org/packages/philharmony/http-message)
[![License](https://img.shields.io/packagist/l/philharmony/http-message)](https://github.com/philharmonytech/http-message/blob/main/LICENSE)

Strict, type-safe **PSR-7 HTTP Message implementation** for modern PHP (8.1+). Designed for maximum security, immutability, and seamless integration with modern PHP 8.1+ ecosystems.

## 📖 Description

`philharmony/http-message` provides a robust, high-performance implementation of the **PSR-7 HTTP Message interfaces**. This package serves as the foundational messaging layer for the Philharmony framework, focusing on:

- **Full PSR-7 Compliance** — Interchangeable with any PSR-compliant library.
- **Strict Typing** — Verified with **PHPStan Level 9** for rock-solid reliability.
- **Security by Design** — Prevents common URI vulnerabilities like path/host ambiguity (RFC 3986).
- **Smart Encoding** — Handles UTF-8 characters and avoids double-encoding of existing percent-encoded sequences.
- **IPv6 & IDN Support** — Fully supports IPv6 hosts and internationalized domain names.
- **Enum Integration** — Works natively with `philharmony/http-enum` for scheme and port logic.
- **Strict Validation** — Prevents invalid hosts and illegal URI characters according to RFC 3986.

## ✨ Key Features

- **Full PSR-7 compliance** — Fully compatible with the PHP ecosystem.
- **Strict typing** — Verified with PHPStan Level 9.
- **Immutable design** — Safe for middleware pipelines.
- **IPv6 & IDN support** — Modern host formats supported out of the box.
- **Smart URI encoding** — Prevents double-encoding and handles Unicode safely.
- **Security-focused validation** — Rejects invalid hosts and illegal URI characters.
- **Upload error abstraction** — PHP upload errors wrapped in a type-safe enum.

## 💡 Why philharmony/http-message?

While many PSR-7 implementations focus on compatibility, **philharmony/http-message** focuses on:

- **Maximum type safety** through strict typing and PHPStan Level 9 analysis
- **Security-first URI handling** following RFC 3986
- **Modern PHP support** (8.1–8.4)
- **Clean immutable design** optimized for middleware architectures

## 📦 Installation

Install via Composer:

```bash
composer require philharmony/http-message
```

## ⚡ Quick Start

A minimal example demonstrating the core PSR-7 objects provided by this package.

```php
use Philharmony\Http\Message\Uri;
use Philharmony\Http\Message\Request;
use Philharmony\Http\Message\Response;
use Philharmony\Http\Message\ServerRequest;

// Create a URI
$uri = Uri::create('https://api.example.com/users');

// Create a client request
$request = Request::create('GET', $uri);

// Server-side request (incoming HTTP request)
$serverRequest = ServerRequest::make(
    method: 'POST',
    uri: '/users',
    body: '{"name":"John"}',
    headers: ['Content-Type' => 'application/json']
);

// Access parsed body
$data = $serverRequest->getParsedBody();

// Create a response
$response = Response::create(200)
    ->withHeader('Content-Type', 'application/json');

echo $response->getStatusCode(); // 200
```

## 🚀 Usage: URI

### Creating a URI

```php
use Philharmony\Http\Message\Uri;

// From string
$uri = new Uri('https://user:pass@example.com:8080/path?query=1#fragment');

// Using factory method
$uri = Uri::create('https://github.com');

// From parts (parse_url compatible)
$uri = Uri::fromParts([
    'scheme' => 'https',
    'host' => 'api.example.com',
    'path' => '/v1/users'
]);
```

### IPv6 and IDN Hosts

The URI implementation supports modern host formats including IPv6 and internationalized domain names.

```php
use Philharmony\Http\Message\Uri;

// IPv6 host
$uri = Uri::create('http://[2001:db8::1]/api');

// IDN host (automatically normalized)
$uri = Uri::create('https://münich.example');
```

### Immutability in Action

As per PSR-7, `Uri` objects are immutable. Every modification returns a new instance.
If the new value is identical to the current one, the same instance is returned for performance.

```php
$baseUri = Uri::create('http://localhost');

$secureUri = $baseUri
    ->withScheme('https')
    ->withPath('/search')
    ->withQuery('q=php+8');

echo $baseUri; // http://localhost
echo $secureUri; // https://localhost/search?q=php+8
```

### Advanced Encoding

The library automatically handles complex paths and query strings, ensuring they are RFC-compliant.

```php
// Handles spaces and special characters
$uri = Uri::create('https://example.com')
    ->withPath('/my documents/notes & tasks');
echo $uri->getPath(); // /my%20documents/notes%20%26%20tasks

// Protects already encoded characters (prevents double % encoding)
$uri = $uri->withQuery('search=php%208');
echo $uri->getQuery(); // search=php%208 (NOT search=php%25208)
```

## 🚀 Usage: Stream

The `Stream` class provides a type-safe wrapper around PHP resources (`fopen`, `php://memory`, etc.).

### Polymorphic Creation

The `static create()` method is highly flexible and accepts strings, resources, or existing streams:

```php
use Philharmony\Http\Message\Stream;

// Create from string (automatically uses php://memory)
$stream = Stream::create('Body content');

// Create from an existing resource
$resource = fopen('data.txt', 'r+');
$streamFromResource = Stream::create($resource);

// Decorate another PSR-7 Stream
$newStream = Stream::create($streamFromResource);
```

### Safe Operations

Unlike raw PHP functions, `Stream` ensures that system errors are handled gracefully:

```php
$stream = Stream::create('Philharmony');
$stream->write(' Framework');
$stream->rewind();

echo $stream->getContents(); // Philharmony Framework
echo $stream->getSize(); // 21
```

### Predictable Stream Behaviour

The `Stream` implementation ensures consistent and predictable behavior across different stream types.

- **Automatic rewind for string bodies** — when creating a stream from a non-empty string, the pointer is automatically rewound.
- **Reliable mode detection** — readable and writable capabilities are detected based on the underlying stream mode.
- **Safe string casting** — `__toString()` safely catches all `Throwable` errors as required by PSR-7.
- **Robust size detection** — stream size detection gracefully handles environments where `fstat()` may return unexpected values.

These guarantees make the `Stream` implementation safe to use with memory streams, file streams, and custom PHP resources.

## 🏗️ Architecture: Base Message

All HTTP entities inherit from the abstract Message class, providing robust header management:

- **Case-insensitive headers** — withHeader('content-type', ...) and getHeader('Content-Type') work seamlessly.
- **Protocol versioning** — Supports HTTP/1.0, 1.1, 2.0.
- **Immutable state** — Every change produces a new instance, preventing side effects in middleware chains.

## 🚀 Usage: HTTP Messages (Request & Response)

Philharmony provides full implementations of PSR-7 HTTP message types:

- **Request** — client-side HTTP request
- **ServerRequest** — server-side request containing environment data
- **Response** — HTTP response message

All messages extend the base `Message` abstraction and follow a fully immutable design.

### Request (Client Request)

The `Request` class represents an outgoing HTTP request typically used by HTTP clients.

#### Creating a Request

```php
use Philharmony\Http\Message\Request;

$request = Request::create(
    'GET',
    'https://api.example.com/users',
    '',
    ['Accept' => 'application/json']
);
```

#### Smart HTTP Method Helpers

Philharmony provides helper methods powered by the `HttpMethod` enum.

```php
if ($request->isSafe()) {
    echo "Safe request (GET, HEAD, OPTIONS)";
}

if ($request->isIdempotent()) {
    echo "Request can be safely repeated";
}

if ($request->isHttps()) {
    echo "Secure request";
}
```

### ServerRequest (Server-Side Request)

`ServerRequest` extends `Request` and represents an incoming HTTP request received by the server.

It provides access to additional environment data such as:
- server parameters (`$_SERVER`)
- cookies (`$_COOKIE`)
- query parameters (`$_GET`)
- uploaded files (`$_FILES`)
- parsed request bodies

#### Creating a ServerRequest

```php
use Philharmony\Http\Message\ServerRequest;

$serverRequest = ServerRequest::make(
    method: 'POST',
    uri: '/profile/update',
    serverParams: $_SERVER,
    body: '{"name":"Philharmony"}',
    headers: ['Content-Type' => 'application/json'],
    cookieParams: $_COOKIE
);
```

#### Parsed Body Helpers

The request body is automatically parsed depending on the `Content-Type` header.

```php
if ($serverRequest->isJson()) {
    $data = $serverRequest->getParsedBody();
}

if ($serverRequest->isForm()) {
    $form = $serverRequest->getParsedBody();
}
```

#### Raw Body & Input Helpers

Convenience helpers allow retrieving input values from query parameters or parsed body data.

Lookup order:
1. Query parameters
2. Parsed request body

```php
$raw = $serverRequest->getRawBody();

$userId = $serverRequest->input('user.id', 'guest');

if ($serverRequest->has('token')) {
    // token exists
}
```

### Response

The `Response` class automatically handles **Reason Phrases** using the `Philharmony\Http\Enum\StatusCode`.

```php
use Philharmony\Http\Message\Response;

// Automatically sets "201 Created" reason phrase
$response = Response::create(201); 
echo $response->getReasonPhrase(); // "Created"

// Fluent interface and smart status checks
$errorResponse = $response
    ->withStatus(403)
    ->withHeader('X-Reason', 'Security');

// Powerful status code helpers powered by Philharmony Enums
if ($response->isInformational()) {
    echo "Status is 1xx";
}

if ($response->isSuccessful()) {
    echo "Status is 2xx (Success!)";
}

if ($response->isRedirection()) {
    echo "Status is 3xx (Redirecting...)";
}

if ($response->isClientError()) {
    echo "Status is 4xx (Bad Request, Unauthorized, etc.)";
}

if ($response->isServerError()) {
    echo "Status is 5xx (Server crashed)";
}

if ($response->isError()) {
    echo "Any error occurred (4xx or 5xx)";
}
```

## 🚀 Usage: Uploaded Files

Handle file uploads with full PSR-7 compatibility and support for modern PHP features like full_path (PHP 8.1+).

```php
use Philharmony\Http\Message\UploadedFile;

$file = UploadedFile::create(
    fileOrStream: '/tmp/phpYzdqkD',
    size: 1024,
    errorStatus: UPLOAD_ERR_OK,
    clientFilename: 'avatar.png',
    clientMediaType: 'image/png',
    fullPath: 'users/avatars/avatar.png' // PHP 8.1+ support
);

// Integration with ContentType Enum
if ($file->getContentType()?->isImage()) {
    $file->moveTo('/var/www/uploads/profile.png');
}
```

### Upload Error Handling

Upload errors are represented internally by the `UploadError` enum.

```php
use Philharmony\Http\Message\Enum\UploadError;

$error = UploadError::from($file->getError());

if ($error->isError()) {
    echo $error->message();
}
```

## 🔍 Technical Specifications

| Feature | Implementation                                                            |
|---------|---------------------------------------------------------------------------|
| **PHP Version** | 8.1 / 8.2 / 8.3 / **8.4**                                                 |
| **PSR Standards** | **[PSR-7](https://www.php-fig.org/psr/psr-7/)** (HTTP Message Interfaces) |
| **Static Analysis** | PHPStan **Level 9** (Max Strictness)                                      |
| **Code Quality** | PSR-12, Strict Types, Clean Code                                          |
| **Dependencies** | `psr/http-message`, `philharmony/http-enum`                               |

## 🧪 Testing

The package is strictly tested with PHPUnit 10 to ensure full compliance with HTTP standards and RFCs.

### Run Tests

```bash
composer test
```

### Code Coverage

```bash
composer test:coverage
```

## 🏗️ Static Analysis & Code Style

Verified with PHPStan Level 9 to ensure total type safety and prevent runtime errors.

```bash
composer phpstan
```

Check and fix code style (PSR-12):

```bash
composer cs-check
composer cs-fix
```

## 📄 License

This package is open-source and licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome.

If you find a bug or have an idea for improvement, please open an issue or submit a pull request.

## ⭐ Support

If you find this package useful, please consider giving it a star on GitHub.
It helps the project grow and reach more developers.
