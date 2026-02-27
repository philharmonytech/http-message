# philharmony/http-message

[![Validate](https://github.com/philharmonytech/http-message/actions/workflows/ci-validate.yml/badge.svg)](https://github.com/philharmonytech/http-message/actions/workflows/ci-validate.yml)
[![Analysis](https://github.com/philharmonytech/http-message/actions/workflows/ci-analysis.yml/badge.svg)](https://github.com/philharmonytech/http-message/actions/workflows/ci-analysis.yml)
[![Test](https://github.com/philharmonytech/http-message/actions/workflows/ci-test.yml/badge.svg)](https://github.com/philharmonytech/http-message/actions/workflows/ci-test.yml)
[![codecov](https://codecov.io/github/philharmonytech/http-message/graph/badge.svg?token=JVGM1RRACK)](https://codecov.io/github/philharmonytech/http-message)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%20to%208.4-8892BF.svg)](https://www.php.net/supported-versions.php)
[![Latest Stable Version](https://img.shields.io/github/v/release/philharmonytech/http-message?label=stable)](https://github.com/philharmonytech/http-message/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/philharmony/http-message)](https://packagist.org/packages/philharmony/http-message)
[![License](https://img.shields.io/packagist/l/philharmony/http-message)](https://github.com/philharmonytech/http-message/blob/main/LICENSE)

Strict, type-safe PSR-7 HTTP Message implementation for PHP. Designed for maximum security, immutability, and seamless integration with modern PHP 8.4+ ecosystems.

## 📖 Description

`philharmony/http-message` provides a robust, high-performance implementation of the **PSR-7 HTTP Message interfaces**. This package serves as the foundational messaging layer for the Philharmony framework, focusing on:

- **Full PSR-7 Compliance** — Interchangeable with any PSR-compliant library.
- **Strict Typing** — Verified with **PHPStan Level 9** for rock-solid reliability.
- **Security by Design** — Prevents common URI vulnerabilities like path/host ambiguity (RFC 3986).
- **Smart Encoding** — Handles multi-byte characters and avoids double-encoding of existing percent-encoded sequences.
- **Enum Integration** — Works natively with `philharmony/http-enum` for scheme and port logic.

## 📦 Installation

Install via Composer:

```bash
composer require philharmony/http-message
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
### Immutability in Action

As per PSR-7, `Uri` objects are immutable. Every modification returns a new instance.

```php
$baseUri = Uri::create('http://localhost');

$secureUri = $baseUri
    ->withScheme('https')
    ->withPath('/search')
    ->withQuery('q=php+8');

echo $baseUri; // http://localhost
echo $secureUri; // https://localhost/search?q=php+8
```

### ✨Advanced Encoding

The library automatically handles complex paths and query strings, ensuring they are RFC-compliant.

```php
// Handles spaces and special characters
$uri = (new Uri())->withPath('/my documents/notes & tasks');
echo $uri->getPath(); // /my%20documents/notes%20%26%20tasks

// Protects already encoded characters (prevents double % encoding)
$uri = $uri->withQuery('search=php%208');
echo $uri->getQuery(); // search=php%208 (NOT search=php%25208)
```

## 🚀 Usage: Stream

The `Stream` class provides a type-safe wrapper around PHP resources (`fopen, php://memory`, etc.).

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
