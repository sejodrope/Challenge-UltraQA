<?php

use Slim\App;
use ChallengeQA\Controllers\UserController;
use ChallengeQA\Controllers\CalculatorController;
use ChallengeQA\Middleware\JwtAuthMiddleware;

return function (App $app) {
    // Public user routes (no authentication required)
    $app->group('/api/user', function ($group) {
        $group->post('/register', [UserController::class, 'register']);
        $group->post('/login', [UserController::class, 'login']);
    });

    // Protected user routes (authentication required)
    $app->group('/api/user', function ($group) {
        $group->get('/profile', [UserController::class, 'profile']);
    })->add(new JwtAuthMiddleware());

    // Protected calculator routes (authentication required)
    $app->group('/api/calculator', function ($group) {
        $group->post('/simple-interest', [CalculatorController::class, 'simpleInterest']);
        $group->post('/compound-interest', [CalculatorController::class, 'compoundInterest']);
        $group->post('/installment', [CalculatorController::class, 'installmentSimulation']);
    })->add(new JwtAuthMiddleware());

    // API documentation endpoint
    $app->get('/', function ($request, $response) {
        $docs = [
            'title' => 'Challenge QA API - Fixed & Improved',
            'version' => '2.0.0',
            'description' => 'Secure financial calculation API with JWT authentication and fixed security vulnerabilities',
            'authentication' => [
                'type' => 'Bearer JWT',
                'description' => 'Calculator endpoints require JWT token in Authorization header',
                'format' => 'Authorization: Bearer <token>',
                'token_lifetime' => '24 hours'
            ],
            'endpoints' => [
                // Public endpoints
                [
                    'method' => 'GET',
                    'path' => '/',
                    'description' => 'API documentation',
                    'authentication' => 'none'
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/user/register',
                    'description' => 'Register a new user with secure password hashing',
                    'authentication' => 'none',
                    'parameters' => [
                        'name' => 'string (optional) - Full name',
                        'email' => 'string (required) - Must be unique',
                        'password' => 'string (required) - Will be securely hashed'
                    ],
                    'responses' => [
                        '201' => 'User created successfully',
                        '400' => 'Missing required fields',
                        '409' => 'Email already exists'
                    ]
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/user/login',
                    'description' => 'Authenticate user and receive JWT token',
                    'authentication' => 'none',
                    'parameters' => [
                        'email' => 'string (required)',
                        'password' => 'string (required)'
                    ],
                    'responses' => [
                        '200' => 'Login successful, returns JWT token and user data',
                        '400' => 'Missing email or password',
                        '401' => 'Invalid credentials'
                    ]
                ],
                // Protected endpoints (require JWT)
                [
                    'method' => 'GET',
                    'path' => '/api/user/profile',
                    'description' => 'Get current user profile information',
                    'authentication' => 'JWT required',
                    'responses' => [
                        '200' => 'User profile data',
                        '401' => 'Invalid or missing token',
                        '404' => 'User not found'
                    ]
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/calculator/simple-interest',
                    'description' => 'Calculate simple interest using formula: I = P × r × t / 12',
                    'authentication' => 'JWT required',
                    'parameters' => [
                        'principal' => 'number (required) - Principal amount in currency',
                        'rate' => 'number (required) - Annual interest rate as percentage (e.g., 5 for 5%)',
                        'time' => 'number (required) - Time period in months'
                    ],
                    'responses' => [
                        '200' => 'Calculation successful with interest and total amount',
                        '400' => 'Missing or invalid parameters',
                        '401' => 'Authentication required'
                    ]
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/calculator/compound-interest',
                    'description' => 'Calculate compound interest using formula: A = P(1 + r/n)^(nt)',
                    'authentication' => 'JWT required',
                    'parameters' => [
                        'principal' => 'number (required) - Principal amount in currency',
                        'rate' => 'number (required) - Annual interest rate as percentage',
                        'time' => 'number (required) - Time period in months',
                        'compounding_frequency' => 'number (optional, default: 12) - Compounding periods per year'
                    ],
                    'responses' => [
                        '200' => 'Calculation successful with interest and total amount',
                        '400' => 'Missing or invalid parameters',
                        '401' => 'Authentication required'
                    ]
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/calculator/installment',
                    'description' => 'Calculate loan installment payments with amortization schedule',
                    'authentication' => 'JWT required',
                    'parameters' => [
                        'principal' => 'number (required) - Loan amount',
                        'rate' => 'number (required) - Annual interest rate as percentage',
                        'installments' => 'number (required) - Number of monthly installments'
                    ],
                    'responses' => [
                        '200' => 'Payment breakdown with monthly amount and total interest',
                        '400' => 'Missing or invalid parameters',
                        '401' => 'Authentication required'
                    ]
                ]
            ],
            'example_usage' => [
                'step1_register' => [
                    'description' => 'First register a new user',
                    'method' => 'POST /api/user/register',
                    'body' => '{"name": "John Doe", "email": "john@example.com", "password": "secure123"}',
                    'response' => '{"success": true, "message": "User registered successfully", "user_id": 1}'
                ],
                'step2_login' => [
                    'description' => 'Login to get JWT token',
                    'method' => 'POST /api/user/login',
                    'body' => '{"email": "john@example.com", "password": "secure123"}',
                    'response' => '{"success": true, "token": "eyJ0eXAiOi...", "user": {...}, "expires_in": 86400}'
                ],
                'step3_authenticated_request' => [
                    'description' => 'Use token for protected endpoints',
                    'method' => 'POST /api/calculator/simple-interest',
                    'headers' => 'Authorization: Bearer eyJ0eXAiOi...',
                    'body' => '{"principal": 10000, "rate": 5, "time": 12}',
                    'response' => '{"success": true, "calculation_type": "simple_interest", "results": {"interest": 500, "total_amount": 10500}}'
                ]
            ],
            'security_improvements' => [
                'Password hashing using PHP password_hash() with bcrypt',
                'JWT tokens for stateless authentication (24h expiry)',
                'Protection against user enumeration attacks',
                'Fixed mathematical calculation bugs',
                'Consistent decimal precision (2 places for financial data)',
                'CORS headers and security headers',
                'All calculation requests logged to database for audit'
            ],
            'bugs_fixed' => [
                'BUG-001: Password security - Now using secure hashing',
                'BUG-002: Email duplication logic - Fixed duplicate check',
                'BUG-003: User enumeration attack - Generic error messages',
                'BUG-004: Mathematical calculation errors - Fixed formulas',
                'BUG-005: Logging system - Now working properly',
                'BUG-006: Decimal precision - Standardized to 2 places',
                'BUG-007: Authentication - JWT middleware implemented'
            ]
        ];
        
        $response->getBody()->write(json_encode($docs, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });
};