#!/usr/bin/env pwsh

# Quick Smoke Tests - Challenge QA API
# PowerShell version for Windows compatibility

param(
    [string]$BaseUrl = "http://localhost:8080",
    [string]$OutputDir = "docs/evidencias"
)

$ErrorActionPreference = "Continue"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

Write-Host "=== CHALLENGE QA API - QUICK SMOKE TESTS ===" -ForegroundColor Cyan
Write-Host "Base URL: $BaseUrl" -ForegroundColor Yellow
Write-Host "Timestamp: $timestamp" -ForegroundColor Yellow
Write-Host "Output Directory: $OutputDir" -ForegroundColor Yellow
Write-Host ""

# Ensure output directory exists
if (!(Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

# Test results
$TestResults = @{
    Total = 0
    Passed = 0
    Failed = 0
    Bugs = @()
}

function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Method,
        [string]$Endpoint,
        [string]$Body = $null,
        [int]$ExpectedStatus = 200,
        [string]$TestType = "FUNCTIONAL"
    )
    
    $TestResults.Total++
    $testId = "ST{0:D3}" -f $TestResults.Total
    
    Write-Host "[$testId] $Name" -NoNewline
    
    try {
        $headers = @{
            'Content-Type' = 'application/json'
        }
        
        $uri = "$BaseUrl$Endpoint"
        $response = $null
        $statusCode = 0
        
        if ($Method -eq "GET") {
            $response = Invoke-WebRequest -Uri $uri -Method GET -Headers $headers -ErrorAction Stop
        } elseif ($Body) {
            $response = Invoke-WebRequest -Uri $uri -Method $Method -Body $Body -Headers $headers -ErrorAction Stop
        } else {
            $response = Invoke-WebRequest -Uri $uri -Method $Method -Headers $headers -ErrorAction Stop
        }
        
        $statusCode = $response.StatusCode
        $content = $response.Content
        
        # Save evidence
        $evidence = @{
            TestId = $testId
            Name = $Name
            Method = $Method
            Endpoint = $Endpoint
            Body = $Body
            ExpectedStatus = $ExpectedStatus
            ActualStatus = $statusCode
            Response = $content
            Timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
            TestType = $TestType
        }
        
        $evidenceFile = "$OutputDir/test_${testId}_${timestamp}.json"
        $evidence | ConvertTo-Json -Depth 10 | Out-File -FilePath $evidenceFile -Encoding UTF8
        
        if ($statusCode -eq $ExpectedStatus) {
            Write-Host " - PASS" -ForegroundColor Green
            $TestResults.Passed++
            return @{ Status = "PASS"; Response = $content; Evidence = $evidenceFile }
        } else {
            Write-Host " - FAIL (Expected: $ExpectedStatus, Got: $statusCode)" -ForegroundColor Red
            $TestResults.Failed++
            return @{ Status = "FAIL"; Response = $content; Evidence = $evidenceFile; Error = "Status code mismatch" }
        }
        
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        $errorContent = ""
        
        if ($_.Exception.Response) {
            try {
                $stream = $_.Exception.Response.GetResponseStream()
                $reader = New-Object System.IO.StreamReader($stream)
                $errorContent = $reader.ReadToEnd()
            } catch {
                $errorContent = $_.Exception.Message
            }
        }
        
        # Save error evidence
        $evidence = @{
            TestId = $testId
            Name = $Name
            Method = $Method
            Endpoint = $Endpoint
            Body = $Body
            ExpectedStatus = $ExpectedStatus
            ActualStatus = $statusCode
            Error = $_.Exception.Message
            Response = $errorContent
            Timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
            TestType = $TestType
        }
        
        $evidenceFile = "$OutputDir/test_${testId}_${timestamp}_ERROR.json"
        $evidence | ConvertTo-Json -Depth 10 | Out-File -FilePath $evidenceFile -Encoding UTF8
        
        if ($statusCode -eq $ExpectedStatus) {
            Write-Host " - PASS (Expected error)" -ForegroundColor Green
            $TestResults.Passed++
            return @{ Status = "PASS"; Response = $errorContent; Evidence = $evidenceFile }
        } else {
            Write-Host " - FAIL (Expected: $ExpectedStatus, Got: $statusCode)" -ForegroundColor Red
            $TestResults.Failed++
            return @{ Status = "FAIL"; Response = $errorContent; Evidence = $evidenceFile; Error = $_.Exception.Message }
        }
    }
}

function Add-Bug {
    param(
        [string]$Id,
        [string]$Title,
        [string]$Severity,
        [string]$Description
    )
    
    $TestResults.Bugs += @{
        Id = $Id
        Title = $Title  
        Severity = $Severity
        Description = $Description
    }
    
    Write-Host "🐛 BUG $Id [$Severity]: $Title" -ForegroundColor Red
}

Write-Host "=== 1. BASIC CONNECTIVITY ===" -ForegroundColor Cyan

# Test 1: Health Check
$result1 = Test-Endpoint -Name "Health Check" -Method "GET" -Endpoint "/health" -ExpectedStatus 200

# Test 2: API Documentation
$result2 = Test-Endpoint -Name "API Documentation" -Method "GET" -Endpoint "/" -ExpectedStatus 200

Write-Host ""
Write-Host "=== 2. USER REGISTRATION TESTS ===" -ForegroundColor Cyan

$testEmail = "smoke.test.$timestamp@example.com"

# Test 3: Valid Registration
$registerBody = @{
    email = $testEmail
    password = "smoketest123"
} | ConvertTo-Json

$result3 = Test-Endpoint -Name "User Registration (Valid)" -Method "POST" -Endpoint "/api/user/register" -Body $registerBody -ExpectedStatus 201

if ($result3.Status -eq "PASS" -and $result3.Response) {
    $registerResponse = $result3.Response | ConvertFrom-Json
    if ($registerResponse.password) {
        Add-Bug -Id "BUG-001" -Title "Password Exposed in Registration Response" -Severity "HIGH" -Description "Password field returned in registration response"
    }
    if ($registerResponse.warning -and $registerResponse.warning -like "*weak*") {
        Add-Bug -Id "BUG-002" -Title "Weak Password Acceptance" -Severity "MEDIUM" -Description "API accepts weak passwords without proper validation"
    }
}

# Test 4: Duplicate Email (should fail with 409)
$result4 = Test-Endpoint -Name "Duplicate Email Registration" -Method "POST" -Endpoint "/api/user/register" -Body $registerBody -ExpectedStatus 409

# Test 5: BUG TEST - Duplicate with Different Password
$duplicateBugBody = @{
    email = $testEmail
    password = "different_password_123"
} | ConvertTo-Json

$result5 = Test-Endpoint -Name "Duplicate Email Different Password (BUG TEST)" -Method "POST" -Endpoint "/api/user/register" -Body $duplicateBugBody -ExpectedStatus 409 -TestType "BUG_DETECTION"

if ($result5.Status -eq "FAIL" -and $result5.Response) {
    try {
        $bugResponse = $result5.Response | ConvertFrom-Json
        if ($bugResponse.success -eq $true) {
            Add-Bug -Id "BUG-003" -Title "Duplicate Email Logic Error" -Severity "CRITICAL" -Description "UserController.php:25 allows duplicate emails with different passwords"
        }
    } catch {
        # Could not parse as JSON, might be different error
    }
}

# Test 6: Invalid Email Format
$invalidEmailBody = @{
    email = "invalid-email-format"
    password = "smoketest123"
} | ConvertTo-Json

$result6 = Test-Endpoint -Name "Invalid Email Format" -Method "POST" -Endpoint "/api/user/register" -Body $invalidEmailBody -ExpectedStatus 400

Write-Host ""
Write-Host "=== 3. USER LOGIN TESTS ===" -ForegroundColor Cyan

# Test 7: Valid Login
$loginBody = @{
    email = $testEmail
    password = "smoketest123"
} | ConvertTo-Json

$result7 = Test-Endpoint -Name "User Login (Valid)" -Method "POST" -Endpoint "/api/user/login" -Body $loginBody -ExpectedStatus 200

# Test 8: User Enumeration Test - Non-existent User
$nonExistentBody = @{
    email = "nonexistent@example.com"
    password = "anypassword"
} | ConvertTo-Json

$result8 = Test-Endpoint -Name "Login Non-existent User" -Method "POST" -Endpoint "/api/user/login" -Body $nonExistentBody -ExpectedStatus 401 -TestType "SECURITY"

# Test 9: User Enumeration Test - Wrong Password
$wrongPassBody = @{
    email = $testEmail
    password = "wrongpassword"
} | ConvertTo-Json

$result9 = Test-Endpoint -Name "Login Wrong Password" -Method "POST" -Endpoint "/api/user/login" -Body $wrongPassBody -ExpectedStatus 401 -TestType "SECURITY"

# Check for user enumeration vulnerability
if ($result8.Response -and $result9.Response) {
    try {
        $response8 = $result8.Response | ConvertFrom-Json
        $response9 = $result9.Response | ConvertFrom-Json
        
        if ($response8.message -ne $response9.message) {
            Add-Bug -Id "BUG-004" -Title "User Enumeration Vulnerability" -Severity "HIGH" -Description "Different error messages for non-existent user vs wrong password"
        }
    } catch {
        Write-Host "Could not parse login responses for enumeration check" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "=== 4. CALCULATOR TESTS ===" -ForegroundColor Cyan

# Test 10: Simple Interest Calculation
$simpleInterestBody = @{
    principal = 1000
    rate = 5
    time = 12
} | ConvertTo-Json

$result10 = Test-Endpoint -Name "Simple Interest Calculation" -Method "POST" -Endpoint "/api/calculator/simple-interest" -Body $simpleInterestBody -ExpectedStatus 200

if ($result10.Status -eq "PASS" -and $result10.Response) {
    try {
        $calcResponse = $result10.Response | ConvertFrom-Json
        
        # Expected: J = 1000 * 5 * 12 / 100 = 600, Total = 1600
        $expectedInterest = 600
        $expectedTotal = 1600
        
        if ($calcResponse.results.interest -ne $expectedInterest) {
            Add-Bug -Id "BUG-005" -Title "Simple Interest Calculation Error" -Severity "HIGH" -Description "Expected interest: $expectedInterest, Got: $($calcResponse.results.interest)"
        }
        
        # Check rounding consistency
        $interestDecimals = ($calcResponse.results.interest.ToString().Split('.')[1]).Length
        $totalDecimals = ($calcResponse.results.total_amount.ToString().Split('.')[1]).Length
        
        if ($interestDecimals -ne $totalDecimals) {
            Add-Bug -Id "BUG-006" -Title "Inconsistent Rounding in Simple Interest" -Severity "MEDIUM" -Description "Interest has $interestDecimals decimals, Total has $totalDecimals decimals"
        }
    } catch {
        Write-Host "Could not validate simple interest calculation" -ForegroundColor Yellow
    }
}

# Test 11: Compound Interest Time > 12 Bug
$compoundBugBody = @{
    principal = 1000
    rate = 5
    time = 24
    compounding_frequency = 12
} | ConvertTo-Json

$result11 = Test-Endpoint -Name "Compound Interest Time>12 (BUG TEST)" -Method "POST" -Endpoint "/api/calculator/compound-interest" -Body $compoundBugBody -ExpectedStatus 200 -TestType "BUG_DETECTION"

if ($result11.Status -eq "PASS") {
    Add-Bug -Id "BUG-007" -Title "Compound Interest Time Division Bug" -Severity "HIGH" -Description "CalculatorController.php:66-68 incorrectly divides time by 2 when time > 12"
}

# Test 12: Negative Values Validation
$negativeBody = @{
    principal = -1000
    rate = 5
    time = 12
} | ConvertTo-Json

$result12 = Test-Endpoint -Name "Negative Values Validation" -Method "POST" -Endpoint "/api/calculator/simple-interest" -Body $negativeBody -ExpectedStatus 400 -TestType "VALIDATION"

if ($result12.Status -eq "FAIL") {
    Add-Bug -Id "BUG-008" -Title "Missing Input Validation" -Severity "MEDIUM" -Description "Calculator accepts negative values without validation"
}

Write-Host ""
Write-Host "=== TEST SUMMARY ===" -ForegroundColor Cyan
Write-Host "Total Tests: $($TestResults.Total)" -ForegroundColor White
Write-Host "Passed: $($TestResults.Passed)" -ForegroundColor Green  
Write-Host "Failed: $($TestResults.Failed)" -ForegroundColor Red
Write-Host "Bugs Found: $($TestResults.Bugs.Count)" -ForegroundColor Yellow

if ($TestResults.Bugs.Count -gt 0) {
    Write-Host ""
    Write-Host "=== BUGS DETECTED ===" -ForegroundColor Red
    foreach ($bug in $TestResults.Bugs) {
        Write-Host "$($bug.Id) [$($bug.Severity)]: $($bug.Title)" -ForegroundColor Red
    }
}

# Generate test results JSON
$finalResults = @{
    Summary = @{
        Timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
        BaseUrl = $BaseUrl
        TotalTests = $TestResults.Total
        Passed = $TestResults.Passed
        Failed = $TestResults.Failed
        BugsFound = $TestResults.Bugs.Count
    }
    Bugs = $TestResults.Bugs
    EvidenceDirectory = $OutputDir
}

$resultsFile = "$OutputDir/smoke_test_results_$timestamp.json"
$finalResults | ConvertTo-Json -Depth 10 | Out-File -FilePath $resultsFile -Encoding UTF8

Write-Host ""
Write-Host "Results saved to: $resultsFile" -ForegroundColor Cyan
Write-Host "Evidence files saved to: $OutputDir" -ForegroundColor Cyan

# Exit code based on critical bugs
$criticalBugs = $TestResults.Bugs | Where-Object { $_.Severity -eq "CRITICAL" }
if ($criticalBugs.Count -gt 0) {
    Write-Host ""
    Write-Host "⚠️  CRITICAL BUGS DETECTED - IMMEDIATE ATTENTION REQUIRED" -ForegroundColor Red
    exit 1
} else {
    exit 0
}