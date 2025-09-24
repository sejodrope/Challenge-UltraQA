<?php

namespace ChallengeQA\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ChallengeQA\Controllers\UserController;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class UserControllerTest extends TestCase
{
    private UserController $controller;
    private $requestFactory;
    private $responseFactory;

    protected function setUp(): void
    {
        $this->controller = new UserController();
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function testLoginWithNonExistentUser(): void
    {
        $requestData = [
            'email' => 'nonexistent@example.com',
            'password' => 'anypassword'
        ];

        $request = $this->requestFactory
            ->createServerRequest('POST', '/api/user/login')
            ->withHeader('Content-Type', 'application/json');
        
        $request->getBody()->write(json_encode($requestData));
        $request->getBody()->rewind();
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->login($request, $response);
        $responseBody = json_decode((string) $result->getBody(), true);
        
        // Test that login fails for non-existent user
        $this->assertEquals(401, $result->getStatusCode());
        $this->assertFalse($responseBody['success']);
        $this->assertEquals('Invalid credentials', $responseBody['message']);
        
        // Ensure response structure is correct
        $this->assertArrayHasKey('success', $responseBody);
        $this->assertArrayHasKey('message', $responseBody);
        $this->assertArrayNotHasKey('user', $responseBody);
        $this->assertArrayNotHasKey('token', $responseBody);
    }

    public function testSuccessfulLoginReturnsJwtToken(): void
    {
        $requestData = [
            'email' => 'test2@example.com',
            'password' => 'securepassword123'
        ];

        $request = $this->requestFactory
            ->createServerRequest('POST', '/api/user/login')
            ->withHeader('Content-Type', 'application/json');
        
        $request->getBody()->write(json_encode($requestData));
        $request->getBody()->rewind();
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->login($request, $response);
        $responseBody = json_decode((string) $result->getBody(), true);

        // Test successful login with JWT token
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($responseBody['success']);
        $this->assertEquals('Login successful', $responseBody['message']);
        
        // Ensure JWT token is present
        $this->assertArrayHasKey('token', $responseBody);
        $this->assertArrayHasKey('expires_in', $responseBody);
        $this->assertArrayHasKey('user', $responseBody);
        
        // Validate token format (JWT has 3 parts separated by dots)
        $this->assertStringContainsString('.', $responseBody['token']);
        $parts = explode('.', $responseBody['token']);
        $this->assertCount(3, $parts);
        
        // Validate user data
        $this->assertEquals('test2@example.com', $responseBody['user']['email']);
        $this->assertEquals(86400, $responseBody['expires_in']); // 24 hours
    }
}