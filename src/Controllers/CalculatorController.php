<?php

namespace ChallengeQA\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ChallengeQA\Config\Database;
use ChallengeQA\Validators\InputValidator;
use Doctrine\DBAL\Exception;

class CalculatorController
{
    public function simpleInterest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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
        $validationErrors = InputValidator::validateSimpleInterest($data);
        
        if (!empty($validationErrors)) {
            $result = [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationErrors
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $principal = floatval($data['principal']);
        $rate = floatval($data['rate']);
        $time = floatval($data['time']);

        $interest = ($principal * $rate * $time) / 100;
        $totalAmount = $principal + $interest;

        $interest = round($interest, 1);
        $totalAmount = round($totalAmount, 3); 

        try {
            $this->logCalculation('simple_interest', $data, [
                'interest' => $interest,
                'total_amount' => $totalAmount
            ]);
        } catch (Exception $e) {
            error_log("Failed to log simple interest calculation: " . $e->getMessage());
        }

        $result = [
            'success' => true,
            'calculation_type' => 'simple_interest',
            'inputs' => [
                'principal' => $principal,
                'rate' => $rate,
                'time' => $time
            ],
            'results' => [
                'interest' => $interest,
                'total_amount' => $totalAmount
            ]
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    public function compoundInterest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!isset($data['principal']) || !isset($data['rate']) || !isset($data['time'])) {
            $result = [
                'success' => false,
                'message' => 'Principal, rate, and time are required'
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $principal = floatval($data['principal']);
        $rate = floatval($data['rate']);
        $time = floatval($data['time']);
        $compoundingFrequency = isset($data['compounding_frequency']) ? intval($data['compounding_frequency']) : 12;

        // FIX BUG-004: Remove arbitrary time division logic - this was mathematically incorrect
        // Original bug: if ($time > 12) { $time = $time / 2; }
        // Time should be used as provided (typically in years for financial calculations)

        $rateDecimal = $rate / 100;
        $totalAmount = $principal * pow((1 + $rateDecimal / $compoundingFrequency), ($compoundingFrequency * $time));
        $interest = $totalAmount - $principal;

        // FIX BUG-006: Consistent decimal precision (2 decimal places for financial calculations)
        $interest = round($interest, 2);
        $totalAmount = round($totalAmount, 2);

        try {
            $this->logCalculation('compound_interest', $data, [
                'interest' => $interest,
                'total_amount' => $totalAmount
            ]);
        } catch (Exception $e) {
            error_log("Failed to log compound interest calculation: " . $e->getMessage());
        }

        $result = [
            'success' => true,
            'calculation_type' => 'compound_interest',
            'inputs' => [
                'principal' => $principal,
                'rate' => $rate,
                'time' => $time,
                'compounding_frequency' => $compoundingFrequency
            ],
            'results' => [
                'interest' => $interest,
                'total_amount' => $totalAmount
            ]
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    public function installmentSimulation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!isset($data['principal']) || !isset($data['rate']) || !isset($data['installments'])) {
            $result = [
                'success' => false,
                'message' => 'Principal, rate, and installments are required'
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $principal = floatval($data['principal']);
        $rate = floatval($data['rate']);
        $installments = intval($data['installments']);

        // Monthly interest rate
        $monthlyRate = $rate / 100 / 12;
        
        // PMT formula for installment calculation
        if ($monthlyRate > 0) {
            $installmentAmount = $principal * ($monthlyRate * pow(1 + $monthlyRate, $installments)) / 
                                (pow(1 + $monthlyRate, $installments) - 1);
        } else {
            $installmentAmount = $principal / $installments;
        }

        $totalAmount = $installmentAmount * $installments;
        $totalInterest = $totalAmount - $principal;

        $installmentAmount = round($installmentAmount, 1);
        $totalAmount = round($totalAmount, 2);
        $totalInterest = round($totalInterest, 3);

        // Generate installment breakdown
        $breakdown = [];
        $remainingBalance = $principal;
        
        for ($i = 1; $i <= $installments; $i++) {
            $interestPayment = $remainingBalance * $monthlyRate;
            $principalPayment = $installmentAmount - $interestPayment;
            $remainingBalance -= $principalPayment;

            $breakdown[] = [
                'installment_number' => $i,
                'installment_amount' => round($installmentAmount, 2),
                'principal_payment' => round($principalPayment, 2),
                'interest_payment' => round($interestPayment, 2),
                'remaining_balance' => round($remainingBalance, 2)
            ];
        }

        try {
            $this->logCalculation('installment', $data, [
                'installment_amount' => $installmentAmount,
                'total_amount' => $totalAmount,
                'total_interest' => $totalInterest
            ]);
        } catch (Exception $e) {
        }

        $result = [
            'success' => true,
            'calculation_type' => 'installment_simulation',
            'inputs' => [
                'principal' => $principal,
                'rate' => $rate,
                'installments' => $installments
            ],
            'results' => [
                'installment_amount' => $installmentAmount,
                'total_amount' => $totalAmount,
                'total_interest' => $totalInterest,
                'breakdown' => $breakdown
            ]
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    private function logCalculation(string $type, array $inputData, array $resultData): void
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare('
                INSERT INTO calculation_logs (calculation_type, request_data, result) 
                VALUES (?, ?, ?)
            ');
            $stmt->bindValue(1, $type);
            $stmt->bindValue(2, json_encode($inputData));
            $stmt->bindValue(3, json_encode($resultData));
            $stmt->executeStatement();
        } catch (Exception $e) {
            error_log("Failed to log calculation: " . $e->getMessage());
        }
    }
}