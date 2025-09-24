<?php

namespace ChallengeQA\Utils;

use ChallengeQA\Config\Database;
use Exception;

class Logger
{
    private const LOG_LEVEL_INFO = 'INFO';
    private const LOG_LEVEL_WARNING = 'WARNING';
    private const LOG_LEVEL_ERROR = 'ERROR';
    private const LOG_LEVEL_DEBUG = 'DEBUG';

    /**
     * Log general application events
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_INFO, $message, $context);
    }

    /**
     * Log warning events
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_WARNING, $message, $context);
    }

    /**
     * Log error events
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * Log debug information
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log user authentication events
     */
    public static function logAuth(string $action, ?string $email = null, bool $success = true, string $details = ''): void
    {
        $message = "Authentication: {$action}";
        $context = [
            'action' => $action,
            'email' => $email,
            'success' => $success,
            'details' => $details,
            'ip_address' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        if ($success) {
            self::info($message, $context);
        } else {
            self::warning($message, $context);
        }
    }

    /**
     * Log API requests
     */
    public static function logApiRequest(string $method, string $uri, int $statusCode, ?int $userId = null, float $responseTime = 0): void
    {
        $message = "API Request: {$method} {$uri} - Status: {$statusCode}";
        $context = [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'user_id' => $userId,
            'response_time' => $responseTime,
            'ip_address' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        if ($statusCode >= 400) {
            self::warning($message, $context);
        } else {
            self::info($message, $context);
        }
    }

    /**
     * Log validation errors
     */
    public static function logValidationError(string $endpoint, array $errors, array $inputData = []): void
    {
        $message = "Validation Error: {$endpoint}";
        $context = [
            'endpoint' => $endpoint,
            'errors' => $errors,
            'request_data' => $inputData,
            'ip_address' => self::getClientIp()
        ];

        self::warning($message, $context);
    }

    /**
     * Log financial calculations
     */
    public static function logCalculation(string $type, array $inputs, array $results, ?int $userId = null): void
    {
        $message = "Financial Calculation: {$type}";
        $context = [
            'calculation_type' => $type,
            'inputs' => $inputs,
            'results' => $results,
            'user_id' => $userId,
            'ip_address' => self::getClientIp()
        ];

        self::info($message, $context);
    }

    /**
     * Main logging method
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        // Log to file (for debugging)
        self::logToFile($level, $message, $context);
        
        // Log to database (for audit trail)
        self::logToDatabase($level, $message, $context);
    }

    /**
     * Log to file system
     */
    private static function logToFile(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextString}" . PHP_EOL;

        $logFile = '/var/www/html/logs/app.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log to database
     */
    private static function logToDatabase(string $level, string $message, array $context = []): void
    {
        try {
            $conn = Database::getConnection();
            
            // Create application_logs table if it doesn't exist
            $conn->executeStatement('
                CREATE TABLE IF NOT EXISTS application_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level VARCHAR(10) NOT NULL,
                    message TEXT NOT NULL,
                    context JSON NULL,
                    ip_address VARCHAR(45) NULL,
                    user_id INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level (level),
                    INDEX idx_created_at (created_at),
                    INDEX idx_user_id (user_id)
                )
            ');

            $stmt = $conn->prepare('
                INSERT INTO application_logs (level, message, context, ip_address, user_id) 
                VALUES (?, ?, ?, ?, ?)
            ');
            
            $stmt->bindValue(1, $level);
            $stmt->bindValue(2, $message);
            $stmt->bindValue(3, !empty($context) ? json_encode($context) : null);
            $stmt->bindValue(4, self::getClientIp());
            $stmt->bindValue(5, $context['user_id'] ?? null);
            
            $stmt->executeStatement();
            
        } catch (Exception $e) {
            // Fallback to error_log if database logging fails
            error_log("Failed to log to database: {$e->getMessage()}");
            error_log("Original log: [{$level}] {$message}");
        }
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get recent logs from database
     */
    public static function getRecentLogs(int $limit = 100, string $level = null): array
    {
        try {
            $conn = Database::getConnection();
            
            $whereClause = $level ? 'WHERE level = ?' : '';
            $query = "
                SELECT level, message, context, ip_address, user_id, created_at
                FROM application_logs 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ?
            ";
            
            $stmt = $conn->prepare($query);
            
            $paramIndex = 1;
            if ($level) {
                $stmt->bindValue($paramIndex++, $level);
            }
            $stmt->bindValue($paramIndex, $limit);
            
            $result = $stmt->executeQuery();
            return $result->fetchAllAssociative();
            
        } catch (Exception $e) {
            error_log("Failed to retrieve logs: {$e->getMessage()}");
            return [];
        }
    }
}