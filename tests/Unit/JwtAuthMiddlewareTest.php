<?php

namespace ChallengeQA\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ChallengeQA\Middleware\JwtAuthMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class JwtAuthMiddlewareTest extends TestCase
{
    private JwtAuthMiddleware $middleware;
    private $requestFactory;
    private $responseFactory;
    private $mockHandler;

    protected function setUp(): void
    {
        $this->middleware = new JwtAuthMiddleware();
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        
        $this->mockHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockHandler
            ->method('handle')
            ->willReturn($this->responseFactory->createResponse(200));
    }

    public function testMissingAuthorizationHeader(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/api/test');
        
        $response = $this->middleware->process($request, $this->mockHandler);
        $responseBody = json_decode((string) $response->getBody(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseBody['success']);
        $this->assertEquals('Authorization header missing', $responseBody['message']);
        $this->assertEquals('UNAUTHORIZED', $responseBody['error_code']);
    }

    public function testInvalidAuthorizationFormat(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('GET', '/api/test')
            ->withHeader('Authorization', 'InvalidFormat token123');
        
        $response = $this->middleware->process($request, $this->mockHandler);
        $responseBody = json_decode((string) $response->getBody(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseBody['success']);
        $this->assertEquals('Invalid authorization format. Use: Bearer <token>', $responseBody['message']);
    }

    public function testInvalidToken(): void
    {
        $request = $this->requestFactory
            ->createServerRequest('GET', '/api/test')
            ->withHeader('Authorization', 'Bearer invalid-token-123');
        
        $response = $this->middleware->process($request, $this->mockHandler);
        $responseBody = json_decode((string) $response->getBody(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseBody['success']);
        $this->assertEquals('Invalid token', $responseBody['message']);
    }

    public function testValidTokenGeneration(): void
    {
        $userId = 1;
        $email = 'test@example.com';
        
        $token = JwtAuthMiddleware::generateToken($userId, $email);
        
        // JWT should have 3 parts separated by dots
        $this->assertStringContainsString('.', $token);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
        
        // Each part should be base64url encoded (JWT uses base64url, not standard base64)
        foreach ($parts as $part) {
            $this->assertNotEmpty($part);
            $this->assertIsString($part);
        }
    }

    public function testValidTokenAllowsAccess(): void
    {
        // Generate a valid token
        $token = JwtAuthMiddleware::generateToken(1, 'test@example.com');
        
        $request = $this->requestFactory
            ->createServerRequest('GET', '/api/test')
            ->withHeader('Authorization', "Bearer $token");
        
        $response = $this->middleware->process($request, $this->mockHandler);
        
        // Should allow access (200 from mock handler)
        $this->assertEquals(200, $response->getStatusCode());
    }
}