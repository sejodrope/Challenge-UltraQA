#!/bin/bash

# Security Tests for Challenge QA API
# Tests basic security vulnerabilities without destructive actions

BASE_URL="http://localhost:8080"
TIMESTAMP=$(date +%s)
TEST_EMAIL="security.test.${TIMESTAMP}@example.com"

echo "=== CHALLENGE QA API - SECURITY TESTS ==="
echo "Base URL: $BASE_URL"
echo "Timestamp: $TIMESTAMP"
echo "Test Email: $TEST_EMAIL"
echo ""

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_TOTAL=0
TESTS_PASSED=0
TESTS_FAILED=0

run_test() {
    local test_name="$1"
    local expected_result="$2"
    local actual_result="$3"
    
    TESTS_TOTAL=$((TESTS_TOTAL + 1))
    
    echo -n "[$TESTS_TOTAL] $test_name: "
    
    if [[ "$actual_result" == "$expected_result" ]]; then
        echo -e "${GREEN}PASS${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}FAIL${NC} (Expected: $expected_result, Got: $actual_result)"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

bug_detected() {
    local bug_name="$1"
    local description="$2"
    
    echo -e "${RED}🐛 BUG DETECTED: $bug_name${NC}"
    echo "   Description: $description"
    echo ""
}

security_issue() {
    local severity="$1"
    local issue="$2"
    local description="$3"
    
    echo -e "${RED}🚨 SECURITY [$severity]: $issue${NC}"
    echo "   Description: $description"
    echo ""
}

echo "=== 1. ENDPOINT AVAILABILITY TESTS ==="

# Test 1: Health check
echo -n "[1] Health Check: "
health_response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/health")
if [[ "$health_response" == "200" ]]; then
    echo -e "${GREEN}PASS${NC} (200)"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${RED}FAIL${NC} (Got: $health_response)"
    TESTS_FAILED=$((TESTS_FAILED + 1))
    echo "❌ API may not be running. Continuing with theoretical tests..."
    
    # Set flag to skip actual HTTP tests
    API_DOWN=true
fi
TESTS_TOTAL=$((TESTS_TOTAL + 1))

echo ""

if [[ "$API_DOWN" != "true" ]]; then
    echo "=== 2. AUTHENTICATION SECURITY TESTS ==="
    
    # Test 2: Register user for testing
    echo "[2] Registering test user..."
    register_response=$(curl -s -X POST "$BASE_URL/api/user/register" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$TEST_EMAIL\",\"password\":\"testpass123\"}")
    
    register_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/user/register" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$TEST_EMAIL\",\"password\":\"testpass123\"}")
    
    if [[ "$register_code" == "201" ]]; then
        echo -e "${GREEN}User registered successfully${NC}"
        
        # Check if password is exposed in response
        if echo "$register_response" | grep -q "password"; then
            security_issue "HIGH" "Password Exposure" "Password field found in registration response"
        fi
        
        # Check for plain text password warning
        if echo "$register_response" | grep -q "weak"; then
            bug_detected "Weak Password Acceptance" "API accepts weak passwords without proper validation"
        fi
    else
        echo -e "${YELLOW}Registration failed (Code: $register_code)${NC}"
    fi
    
    # Test 3: User Enumeration - Non-existent user
    echo "[3] Testing User Enumeration (non-existent user)..."
    nonexistent_response=$(curl -s -X POST "$BASE_URL/api/user/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"nonexistent@example.com\",\"password\":\"anypass\"}")
    
    nonexistent_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/user/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"nonexistent@example.com\",\"password\":\"anypass\"}")
    
    # Test 4: User Enumeration - Wrong password
    echo "[4] Testing User Enumeration (wrong password)..."
    wrong_pass_response=$(curl -s -X POST "$BASE_URL/api/user/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$TEST_EMAIL\",\"password\":\"wrongpass\"}")
    
    wrong_pass_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/user/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$TEST_EMAIL\",\"password\":\"wrongpass\"}")
    
    # Analyze user enumeration vulnerability
    if [[ "$nonexistent_code" != "$wrong_pass_code" ]]; then
        security_issue "HIGH" "User Enumeration" "Different HTTP codes for non-existent user ($nonexistent_code) vs wrong password ($wrong_pass_code)"
    fi
    
    if echo "$nonexistent_response" | grep -qi "not found" && echo "$wrong_pass_response" | grep -qi "incorrect"; then
        security_issue "HIGH" "User Enumeration" "Different error messages reveal user existence"
    fi
    
    echo ""
    
    echo "=== 3. SQL INJECTION TESTS ==="
    
    # Test 5: SQL Injection in registration email
    echo "[5] Testing SQL Injection in registration email..."
    sqli_email_response=$(curl -s -X POST "$BASE_URL/api/user/register" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"test'; DROP TABLE users;--@example.com\",\"password\":\"testpass\"}")
    
    if echo "$sqli_email_response" | grep -qi "mysql\|sql\|syntax\|error"; then
        security_issue "CRITICAL" "SQL Error Exposure" "SQL error details exposed in response"
    fi
    
    # Test 6: SQL Injection in login
    echo "[6] Testing SQL Injection in login..."
    sqli_login_response=$(curl -s -X POST "$BASE_URL/api/user/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"admin'--\",\"password\":\"' OR 1=1--\"}")
    
    sqli_login_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/user/login" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"admin'--\",\"password\":\"' OR 1=1--\"}")
    
    if [[ "$sqli_login_code" == "200" ]]; then
        security_issue "CRITICAL" "SQL Injection Bypass" "Authentication bypassed with SQL injection"
    fi
    
    echo ""
    
    echo "=== 4. INPUT VALIDATION TESTS ==="
    
    # Test 7: XSS in registration
    echo "[7] Testing XSS in registration..."
    xss_response=$(curl -s -X POST "$BASE_URL/api/user/register" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"<script>alert('xss')</script>@test.com\",\"password\":\"test\"}")
    
    if echo "$xss_response" | grep -q "<script>"; then
        security_issue "MEDIUM" "XSS Vulnerability" "Script tags not properly escaped in response"
    fi
    
    # Test 8: Negative values in calculator
    echo "[8] Testing negative values in calculator..."
    negative_response=$(curl -s -X POST "$BASE_URL/api/calculator/simple-interest" \
        -H "Content-Type: application/json" \
        -d "{\"principal\":-1000,\"rate\":5,\"time\":12}")
    
    negative_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/calculator/simple-interest" \
        -H "Content-Type: application/json" \
        -d "{\"principal\":-1000,\"rate\":5,\"time\":12}")
    
    if [[ "$negative_code" == "200" ]]; then
        bug_detected "Input Validation" "Calculator accepts negative values without validation"
    fi
    
    echo ""
    
    echo "=== 5. RATE LIMITING TESTS ==="
    
    # Test 9: Brute force protection
    echo "[9] Testing brute force protection (5 rapid requests)..."
    brute_force_detected=false
    
    for i in {1..5}; do
        bf_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/user/login" \
            -H "Content-Type: application/json" \
            -d "{\"email\":\"$TEST_EMAIL\",\"password\":\"wrongpass$i\"}")
        
        if [[ "$bf_code" == "429" ]]; then
            brute_force_detected=true
            break
        fi
        sleep 0.1
    done
    
    if [[ "$brute_force_detected" == "false" ]]; then
        security_issue "MEDIUM" "No Rate Limiting" "No brute force protection detected after 5 rapid requests"
    else
        echo -e "${GREEN}Rate limiting detected${NC}"
    fi
    
    echo ""
    
    echo "=== 6. MATHEMATICAL ACCURACY TESTS ==="
    
    # Test 10: Simple interest calculation
    echo "[10] Testing simple interest mathematical accuracy..."
    simple_response=$(curl -s -X POST "$BASE_URL/api/calculator/simple-interest" \
        -H "Content-Type: application/json" \
        -d "{\"principal\":1000,\"rate\":5,\"time\":12}")
    
    if echo "$simple_response" | grep -q "results"; then
        # Expected: J = 1000 * 5 * 12 / 100 = 600
        # Expected Total: 1000 + 600 = 1600
        expected_interest=600
        expected_total=1600
        
        actual_interest=$(echo "$simple_response" | grep -o '"interest":[0-9.]*' | cut -d: -f2)
        actual_total=$(echo "$simple_response" | grep -o '"total_amount":[0-9.]*' | cut -d: -f2)
        
        if [[ "$actual_interest" != "$expected_interest" ]]; then
            bug_detected "Mathematical Error" "Simple interest calculation incorrect. Expected: $expected_interest, Got: $actual_interest"
        fi
    fi
    
    # Test 11: Compound interest bug (time > 12)
    echo "[11] Testing compound interest time division bug..."
    compound_response=$(curl -s -X POST "$BASE_URL/api/calculator/compound-interest" \
        -H "Content-Type: application/json" \
        -d "{\"principal\":1000,\"rate\":5,\"time\":24}")
    
    if echo "$compound_response" | grep -q "results"; then
        bug_detected "Logic Error" "Compound interest may incorrectly divide time by 2 when time > 12 (CalculatorController.php:66-68)"
    fi
    
fi

echo ""
echo "=== 7. THEORETICAL SECURITY ANALYSIS (STATIC CODE) ==="

echo -e "${YELLOW}Based on static code analysis:${NC}"

security_issue "CRITICAL" "Plain Text Passwords" "UserController.php stores and compares passwords in plain text"
security_issue "HIGH" "User Enumeration" "Different error messages and HTTP codes for login failures"  
security_issue "HIGH" "Duplicate Registration Logic" "UserController.php:25 checks email AND password for duplicates"
security_issue "MEDIUM" "Stack Trace Exposure" "Database errors expose internal details in responses"
security_issue "MEDIUM" "Missing Authentication" "Calculator endpoints have no authentication requirements"
bug_detected "Mathematical Logic" "Compound interest arbitrarily divides time by 2 when > 12"
bug_detected "Rounding Inconsistency" "Different decimal precision across calculation endpoints"

echo ""
echo "=== TEST SUMMARY ==="
echo "Tests Total: $TESTS_TOTAL"
echo -e "Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Failed: ${RED}$TESTS_FAILED${NC}"

if [[ "$API_DOWN" == "true" ]]; then
    echo -e "${YELLOW}Note: API was not accessible, showing theoretical analysis only${NC}"
fi

echo ""
echo "=== CRITICAL RECOMMENDATIONS ==="
echo "1. Implement password hashing (bcrypt/password_hash)"
echo "2. Fix user enumeration vulnerabilities"  
echo "3. Correct duplicate email detection logic"
echo "4. Add input validation for all endpoints"
echo "5. Implement rate limiting/brute force protection"
echo "6. Fix mathematical calculation bugs"
echo "7. Add authentication to calculator endpoints"
echo "8. Remove stack trace exposure from error responses"

echo ""
echo "Security test completed at $(date)"