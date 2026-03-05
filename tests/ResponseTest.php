<?php

declare(strict_types=1);

namespace Philharmony\Http\Message\Tests;

use Philharmony\Http\Message\Response;
use Philharmony\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $response = Response::create();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertInstanceOf(Stream::class, $response->getBody());
    }

    public function testConstructorWithCustomData(): void
    {
        $headers = ['Content-Type' => ['application/json']];
        $body = '{"status":"ok"}';
        $response = Response::create(201, $headers, $body, '2.0', 'Created');

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Created', $response->getReasonPhrase());
        $this->assertEquals('2.0', $response->getProtocolVersion());
        $this->assertEquals($headers, $response->getHeaders());
        $this->assertEquals($body, (string) $response->getBody());
    }

    public function testWithStatusChangesCodeAndReasonPhrase(): void
    {
        $response = Response::create(302);
        $new = $response->withStatus(404);

        $this->assertNotSame($response, $new);
        $this->assertEquals(404, $new->getStatusCode());
        $this->assertEquals('Not Found', $new->getReasonPhrase());
    }

    public function testStatusCodeHelpers(): void
    {
        $response = Response::create(204);
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isInformational());
        $this->assertFalse($response->isRedirection());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
        $this->assertFalse($response->isError());

        $redirect = $response->withStatus(302);
        $this->assertTrue($redirect->isRedirection());
        $this->assertFalse($redirect->isSuccessful());
        $this->assertFalse($redirect->isInformational());
        $this->assertFalse($redirect->isClientError());
        $this->assertFalse($redirect->isServerError());
        $this->assertFalse($redirect->isError());

        $clientError = $response->withStatus(403);
        $this->assertTrue($clientError->isClientError());
        $this->assertTrue($clientError->isError());
        $this->assertFalse($clientError->isRedirection());
        $this->assertFalse($clientError->isSuccessful());
        $this->assertFalse($clientError->isInformational());
        $this->assertFalse($clientError->isServerError());

        $serverError = $response->withStatus(500);
        $this->assertTrue($serverError->isServerError());
        $this->assertTrue($serverError->isError());
        $this->assertFalse($serverError->isRedirection());
        $this->assertFalse($serverError->isSuccessful());
        $this->assertFalse($serverError->isInformational());
        $this->assertFalse($serverError->isClientError());

        $info = $response->withStatus(101);
        $this->assertTrue($info->isInformational());
        $this->assertFalse($info->isRedirection());
        $this->assertFalse($info->isSuccessful());
        $this->assertFalse($info->isClientError());
        $this->assertFalse($info->isServerError());
        $this->assertFalse($info->isError());
    }

}
