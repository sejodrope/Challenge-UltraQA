<?php

namespace ChallengeQA\Validators;

class InputValidator
{
    /**
     * Validate user registration input
     */
    public static function validateUserRegistration(array $data): array
    {
        $errors = [];

        // Validate email
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (strlen($data['email']) > 255) {
            $errors[] = 'Email is too long (max 255 characters)';
        }

        // Validate password
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        } elseif (strlen($data['password']) > 255) {
            $errors[] = 'Password is too long (max 255 characters)';
        }

        // Validate name (optional)
        if (isset($data['name']) && !empty($data['name'])) {
            if (strlen($data['name']) > 100) {
                $errors[] = 'Name is too long (max 100 characters)';
            }
            if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $data['name'])) {
                $errors[] = 'Name can only contain letters and spaces';
            }
        }

        return $errors;
    }

    /**
     * Validate user login input
     */
    public static function validateUserLogin(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        }

        return $errors;
    }

    /**
     * Validate simple interest calculation input
     */
    public static function validateSimpleInterest(array $data): array
    {
        $errors = [];

        // Validate principal
        if (!isset($data['principal'])) {
            $errors[] = 'Principal amount is required';
        } elseif (!is_numeric($data['principal'])) {
            $errors[] = 'Principal must be a number';
        } elseif (floatval($data['principal']) <= 0) {
            $errors[] = 'Principal must be greater than 0';
        } elseif (floatval($data['principal']) > 999999999.99) {
            $errors[] = 'Principal amount is too large (max 999,999,999.99)';
        }

        // Validate rate
        if (!isset($data['rate'])) {
            $errors[] = 'Interest rate is required';
        } elseif (!is_numeric($data['rate'])) {
            $errors[] = 'Interest rate must be a number';
        } elseif (floatval($data['rate']) < 0) {
            $errors[] = 'Interest rate cannot be negative';
        } elseif (floatval($data['rate']) > 100) {
            $errors[] = 'Interest rate cannot exceed 100%';
        }

        // Validate time
        if (!isset($data['time'])) {
            $errors[] = 'Time period is required';
        } elseif (!is_numeric($data['time'])) {
            $errors[] = 'Time period must be a number';
        } elseif (floatval($data['time']) <= 0) {
            $errors[] = 'Time period must be greater than 0';
        } elseif (floatval($data['time']) > 1200) { // Max 100 years
            $errors[] = 'Time period is too long (max 1200 months)';
        }

        return $errors;
    }

    /**
     * Validate compound interest calculation input
     */
    public static function validateCompoundInterest(array $data): array
    {
        $errors = array_merge(
            self::validateSimpleInterest($data),
            []
        );

        // Validate compounding frequency (optional)
        if (isset($data['compounding_frequency'])) {
            if (!is_numeric($data['compounding_frequency'])) {
                $errors[] = 'Compounding frequency must be a number';
            } elseif (intval($data['compounding_frequency']) <= 0) {
                $errors[] = 'Compounding frequency must be greater than 0';
            } elseif (intval($data['compounding_frequency']) > 365) {
                $errors[] = 'Compounding frequency cannot exceed 365 (daily)';
            }
        }

        return $errors;
    }

    /**
     * Validate installment calculation input
     */
    public static function validateInstallment(array $data): array
    {
        $errors = [];

        // Validate principal
        if (!isset($data['principal'])) {
            $errors[] = 'Principal amount is required';
        } elseif (!is_numeric($data['principal'])) {
            $errors[] = 'Principal must be a number';
        } elseif (floatval($data['principal']) <= 0) {
            $errors[] = 'Principal must be greater than 0';
        }

        // Validate rate
        if (!isset($data['rate'])) {
            $errors[] = 'Interest rate is required';
        } elseif (!is_numeric($data['rate'])) {
            $errors[] = 'Interest rate must be a number';
        } elseif (floatval($data['rate']) < 0) {
            $errors[] = 'Interest rate cannot be negative';
        }

        // Validate installments
        if (!isset($data['installments'])) {
            $errors[] = 'Number of installments is required';
        } elseif (!is_numeric($data['installments'])) {
            $errors[] = 'Number of installments must be a number';
        } elseif (intval($data['installments']) <= 0) {
            $errors[] = 'Number of installments must be greater than 0';
        } elseif (intval($data['installments']) > 360) {
            $errors[] = 'Number of installments cannot exceed 360 (30 years)';
        }

        return $errors;
    }

    /**
     * Sanitize input data
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } elseif (is_numeric($value)) {
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}