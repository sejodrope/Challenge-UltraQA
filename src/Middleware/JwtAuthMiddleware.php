<?php

namespace ChallengeQA\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response;

class JwtAuthMiddleware implements MiddlewareInterface
{
    private const JWT_SECRET = 'challenge-qa-secret-key-2025'; // In production, use environment variable
    private const JWT_ALGORITHM = 'HS256';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Authorization header missing');
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid authorization format. Use: Bearer <token>');
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key(self::JWT_SECRET, self::JWT_ALGORITHM));
            
            // Add user info to request attributes
            $request = $request->withAttribute('user_id', $decoded->user_id);
            $request = $request->withAttribute('user_email', $decoded->email);
            
            return $handler->handle($request);
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            return $this->unauthorizedResponse('Token expired');
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return $this->unauthorizedResponse('Invalid token signature');
        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Invalid token');
        }
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED'
        ]));
        
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    public static function generateToken(int $userId, string $email): string
    {
        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'iss' => 'challenge-qa-api'
        ];

        return JWT::encode($payload, self::JWT_SECRET, self::JWT_ALGORITHM);
    }
}