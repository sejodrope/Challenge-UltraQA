# API Specification - Challenge QA

**Base URL:** `http://localhost:8080`  
**Content-Type:** `application/json`  
**Version:** 1.0.0

---

## AUTHENTICATION
❌ **Nenhuma autenticação implementada nos endpoints de calculadora**

---

## ENDPOINTS

### 1. HEALTH CHECK
```http
GET /health
```
**Response 200:**
```json
{
    "status": "ok",
    "timestamp": "2025-09-24 01:30:00",
    "version": "1.0.0"
}
```

---

### 2. API DOCUMENTATION
```http
GET /
```
**Response 200:** Documentação completa da API em JSON

---

### 3. USER REGISTRATION
```http
POST /api/user/register
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "senha123"
}
```

**Responses:**

**201 Created - Sucesso:**
```json
{
    "success": true,
    "message": "User registered successfully",
    "user_id": "123",
    "warning": "Password is weak but accepted"
}
```

**400 Bad Request - Campos faltando:**
```json
{
    "success": false,
    "message": "Email and password are required"
}
```

**409 Conflict - Email já existe:**
```json
{
    "success": false,
    "message": "Email already exists",
    "error_code": "EMAIL_EXISTS"
}
```

**500 Internal Server Error:**
```json
{
    "success": false,
    "message": "Database error occurred",
    "error": "Detailed error message"
}
```

**🐛 BUGS IDENTIFICADOS:**
- Senha armazenada em texto plano
- Lógica incorreta para verificar email duplicado
- Exposição de stack traces
- Ausência de validação de email e senha

---

### 4. USER LOGIN
```http
POST /api/user/login
```

**Request Body:**
```json
{
    "email": "user@example.com", 
    "password": "senha123"
}
```

**Responses:**

**200 OK - Login successful:**
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 123,
        "email": "user@example.com"
    }
}
```

**400 Bad Request:**
```json
{
    "success": false,
    "message": "Email and password are required"
}
```

**404 Not Found - User not found:**
```json
{
    "success": false,
    "message": "User not found"
}
```

**401 Unauthorized - Wrong password:**
```json
{
    "success": false,
    "message": "Password is incorrect"
}
```

**🐛 BUGS IDENTIFICADOS:**
- User enumeration (diferentes mensagens)
- Comparação de senha em texto plano
- Não retorna token de sessão
- Ausência de rate limiting

---

### 5. SIMPLE INTEREST CALCULATOR
```http
POST /api/calculator/simple-interest
```

**Request Body:**
```json
{
    "principal": 1000.00,
    "rate": 5.0,
    "time": 12
}
```

**Formula Esperada:** `J = C * i * t / 100` | `M = C + J`

**Response 200:**
```json
{
    "success": true,
    "calculation_type": "simple_interest",
    "inputs": {
        "principal": 1000,
        "rate": 5,
        "time": 12
    },
    "results": {
        "interest": 600.0,
        "total_amount": 1600.000
    }
}
```

**🐛 BUGS IDENTIFICADOS:**
- Arredondamento inconsistente (1 casa vs 3 casas)
- Não valida entradas negativas
- Falta autenticação

---

### 6. COMPOUND INTEREST CALCULATOR
```http
POST /api/calculator/compound-interest
```

**Request Body:**
```json
{
    "principal": 1000.00,
    "rate": 5.0,
    "time": 12,
    "compounding_frequency": 12
}
```

**Formula Esperada:** `M = C * (1 + i/n)^(n*t)` | `J = M - C`

**Response 200:**
```json
{
    "success": true,
    "calculation_type": "compound_interest",
    "inputs": {
        "principal": 1000,
        "rate": 5,
        "time": 6,
        "compounding_frequency": 12
    },
    "results": {
        "interest": 348.85,
        "total_amount": 1348.9
    }
}
```

**🐛 BUGS IDENTIFICADOS:**
- Divisão arbitrária do tempo por 2 quando > 12
- Arredondamento inconsistente
- Lógica matemática incorreta

---

### 7. INSTALLMENT SIMULATION
```http
POST /api/calculator/installment
```

**Request Body:**
```json
{
    "principal": 10000.00,
    "rate": 12.0,
    "installments": 12
}
```

**Formula Esperada:** `PMT = C * [i*(1+i)^n] / [(1+i)^n - 1]`

**Response 200:**
```json
{
    "success": true,
    "calculation_type": "installment_simulation",
    "inputs": {
        "principal": 10000,
        "rate": 12,
        "installments": 12
    },
    "results": {
        "installment_amount": 888.5,
        "total_amount": 10662.00,
        "total_interest": 662.000,
        "breakdown": [
            {
                "installment_number": 1,
                "installment_amount": 888.49,
                "principal_payment": 788.49,
                "interest_payment": 100.00,
                "remaining_balance": 9211.51
            }
        ]
    }
}
```

**🐛 BUGS IDENTIFICADOS:**
- Arredondamento inconsistente entre valores
- Falta validação de entradas
- Quebra de breakdown com muitas parcelas

---

## CÓDIGOS DE STATUS UTILIZADOS

| Código | Uso | Observações |
|--------|-----|-------------|
| 200 | Sucesso geral | ✅ Adequado |
| 201 | Criação de usuário | ✅ Adequado |
| 400 | Campos obrigatórios | ✅ Adequado |
| 401 | Senha incorreta | ⚠️ User enumeration |
| 404 | Usuário não encontrado | ⚠️ User enumeration |
| 409 | Email duplicado | ✅ Adequado |
| 500 | Erro de banco | ⚠️ Expõe detalhes |

---

## HEADERS DE SEGURANÇA

❌ **Análise necessária dos middlewares CORS**  
❌ **Ausência de headers como X-Frame-Options, CSP**  
❌ **Rate limiting não implementado**

---

## OBSERVAÇÕES DE NEGÓCIO

### TAXAS E PERÍODOS:
- Taxa aparenta ser anual (necessário confirmar)
- Tempo em meses para juros simples/compostos
- Fórmula de Price para parcelamento

### VALIDAÇÕES NECESSÁRIAS:
- Email deve seguir formato válido
- Valores monetários > 0
- Taxa entre 0-100%
- Tempo/parcelas > 0
- Limites máximos para prevenir overflow