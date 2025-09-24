<?php

namespace ChallengeQA\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ChallengeQA\Config\Database;
use ChallengeQA\Validators\InputValidator;
use ChallengeQA\Utils\Logger;
use Doctrine\DBAL\Exception;

class UserController
{
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!$data) {
            $result = [
                'success' => false,
                'message' => 'Invalid JSON data'
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Sanitize and validate input
        $data = InputValidator::sanitize($data);
        $validationErrors = InputValidator::validateUserRegistration($data);
        
        if (!empty($validationErrors)) {
            Logger::logValidationError('/api/user/register', $validationErrors, $data);
            $result = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationErrors
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $name = $data['name'] ?? '';
        $email = $data['email'];
        $password = $data['password'];
        
        // FIX BUG-001: Secure password hashing
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn = Database::getConnection();
            
            // FIX BUG-002: Correct email duplication check - only check email, not email+password
            $stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->bindValue(1, $email);
            $result = $stmt->executeQuery();
            $count = $result->fetchOne();

            if ($count > 0) {
                $result = [
                    'success' => false,
                    'message' => 'Email already exists',
                    'error_code' => 'EMAIL_EXISTS'
                ];
                $response->getBody()->write(json_encode($result));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            // FIX: Insert with secure password hashing
            $stmt = $conn->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
            $stmt->bindValue(1, $email);
            $stmt->bindValue(2, $hashedPassword);
            $stmt->executeStatement();
            $userId = $conn->lastInsertId();

            Logger::logAuth('register', $email, true, 'User registered successfully');

            $result = [
                'success' => true,
                'message' => 'User registered successfully',
                'user_id' => $userId,
                'warning' => 'Password is weak but accepted'
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Database error occurred',
                'error' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!$data) {
            $result = [
                'success' => false,
                'message' => 'Invalid JSON data'
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Sanitize and validate input
        $data = InputValidator::sanitize($data);
        $validationErrors = InputValidator::validateUserLogin($data);
        
        if (!empty($validationErrors)) {
            $result = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationErrors
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        if (!isset($data['email']) || !isset($data['password'])) {
            $result = [
                'success' => false,
                'message' => 'Email and password are required'
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $email = $data['email'];
        $password = $data['password'];

        try {
            $conn = Database::getConnection();
            
            $stmt = $conn->prepare('SELECT id, email, password FROM users WHERE email = ?');
            $stmt->bindValue(1, $email);
            $result = $stmt->executeQuery();
            $user = $result->fetchAssociative();

            // FIX BUG-003: Prevent user enumeration attack + BUG-001: Use password_verify for hashed passwords
            // Always return the same generic message regardless of whether user exists or password is wrong
            if (!$user || !password_verify($password, $user['password'])) {
                $result = [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
                $response->getBody()->write(json_encode($result));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            // Login successful - FIX BUG-007: Generate JWT token
            $token = \ChallengeQA\Middleware\JwtAuthMiddleware::generateToken($user['id'], $user['email']);
            
            $result = [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email']
                ],
                'token' => $token,
                'expires_in' => 86400 // 24 hours in seconds
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Database error occurred',
                'error' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function profile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Get user info from JWT token (set by middleware)
        $userId = $request->getAttribute('user_id');
        $userEmail = $request->getAttribute('user_email');

        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
            $stmt->bindValue(1, $userId);
            $result = $stmt->executeQuery();
            $user = $result->fetchAssociative();

            if (!$user) {
                $result = [
                    'success' => false,
                    'message' => 'User not found'
                ];
                $response->getBody()->write(json_encode($result));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $result = [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'created_at' => $user['created_at']
                ]
            ];
            
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $result = [
                'success' => false,
                'message' => 'Database error occurred'
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}