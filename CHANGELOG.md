# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

---
## [1.2.0] - 2026-03-15

### Added
- IPv6 host support in `Uri`.
- Internationalized domain name (IDN) support using `idn_to_ascii()`.
- Added `UploadError` enum for representing PHP upload error codes.
- Added `ServerRequest::getRawBody()` for accessing cached raw request bodies.
- Added `ServerRequest::input()` and `ServerRequest::has()` helpers for retrieving values from query/body data.

### Fixed
- `Message::setHeaders()` now correctly replaces headers that differ only by case during initialization.
- `UploadedFile::moveTo()` now ensures partially written files are cleaned up when stream copy fails.
- `Stream::__toString()` now safely catches `Throwable`.
- `Response` now validates that HTTP status codes are within the valid range (100–599).

### Improved
- Validation of URI host component.
- Validation of percent-encoding in URI components.
- Normalization of path when authority is present.
- Detection of schemes that require a host.
- Improved `Stream` mode detection using `isReadableMode()` and `isWritableMode()`.
- Improved `Stream::close()` readability.
- Improved `UploadedFile::moveTo()` stream handling and error propagation
- `Request` now validates HTTP method tokens and exposes `getMethodEnum()`.

### Changed
- `with*()` methods now return the same instance when the value does not change.
- Query and fragment components now consistently normalize empty values.
- `Stream` now uses `is_array($stats)` for safer stream size detection.
- Internal stream reference is now reset using `$this->stream = null` instead of `unset()`.
- `Stream::create()` now rewinds the pointer when creating a stream from a non-empty string.
- Refactored `UploadedFile` to internally use the `UploadError` enum.
- `ServerRequest` now validates and normalizes nested uploaded file structures.

### Performance
- `Request` now caches the `HttpMethod` enum for helper methods such as `isSafe()` and `isIdempotent()`.
- Reduced body parsing overhead in `ServerRequest`.

### Tests
- Refactored and grouped `Stream` tests for better readability.
- Added additional edge-case tests for stream behavior.
- Added tests covering stream write failure cleanup.
- Added tests for `UploadError`
- Added tests covering header replacement when header names differ only by case in `Message::setHeaders()`
- Added tests verifying HTTP status code validation in `Response`
- Added tests covering `Request::getMethodEnum()` and `withMethod()` behavior.
- Expanded `ServerRequest` coverage for input helpers, parsed body branches, raw body caching, and uploaded files normalization.

---

## [1.0.1] - 2026-03-05

This is a maintenance release focused on stabilizing the CI pipeline and ensuring full compatibility with PHP 8.1 - 8.4 versions.

### 🛠 Fixed
- **Strict Header Validation** — Fixed an issue where some control characters were not correctly identified on PHP 8.1.
- **Universal Regex** — Replaced specific character matching with a robust inverted regex `[^\x20-\x7E\t]` to satisfy RFC 7230 across all supported PHP versions.
- **Type Safety** — Resolved a `TypeError` in `strlen()` that occurred on PHP 8.4 when handling numeric header values.

### ⚙️ CI/CD Improvements
- Optimized GitHub Actions workflows for better stability during Pull Requests.
- Verified 100% code coverage on PHP 8.1, 8.2, 8.3, and 8.4.

---

## [1.0.0] - 2026-03-05

The Foundation: Strict & Smart PSR-7 Implementation
We are proud to announce the first stable release of **philharmony/http-message**. 
This package provides a high-performance, strictly typed implementation of **PSR-7** (HTTP Message Interfaces), designed specifically for the modern PHP 8.4+ ecosystem.

### ✨ Key Features
* **Full PSR-7 Compliance** — 100% interchangeable with any PSR-compliant middleware or library.
* **Strict Type Safety** — Verified with **PHPStan Level 9** to eliminate runtime type errors.
* **Native Enum Integration** — Seamlessly works with `philharmony/http-enum` for status codes, methods, and content types.
* **Smart Helpers** — Built-in methods like `isJson()`, `isSafe()`, `isIdempotent()`, and `isSuccessful()` for a superior developer experience.
* **Modern PHP Support** — First-class support for PHP 8.1+ features, including the `full_path` key in uploaded files.
* **Security by Design** — Robust URI parsing and header management following RFC 3986 and RFC 7230.

### 🛠 Technical Highlights
* **100% Code Coverage** — Every line and edge case is verified with PHPUnit 10.
* **Immutable by Design** — All message objects are immutable, ensuring predictable state in middleware chains.
* **Polymorphic Streams** — Flexible stream creation from strings, resources, or existing PSR-7 streams.

### 📦 Installation
```bash
composer require philharmony/http-message
```

[1.2.0]: https://github.com/philharmonytech/http-message/compare/v1.0.1...v1.2.0
[1.0.1]: https://github.com/philharmonytech/http-message/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/philharmonytech/http-message/releases/tag/v1.0.0
