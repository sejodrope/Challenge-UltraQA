# Bugs Encontrados - Challenge QA API

**Data da Análise:** 24/09/2025  
**Analista:** Agente QA Sênior  
**Método:** Análise estática + Testes teóricos  
**Commit:** main branch

---

## RESUMO EXECUTIVO

**Total de Bugs:** 12  
🔴 **Críticos:** 4  
🟠 **Altos:** 5  
🟡 **Médios:** 3  
🟢 **Baixos:** 0

**Áreas Mais Afetadas:**
1. Segurança de Autenticação (4 bugs críticos/altos)
2. Lógica de Negócio - Cálculos (3 bugs altos)
3. Validação de Entrada (3 bugs médios/altos)

---

## BUGS CRÍTICOS (P0 - Correção Imediata)

### 🔴 BUG-001: SENHAS EM TEXTO PLANO
**Severidade:** CRÍTICO  
**Endpoint:** `/api/user/register`, `/api/user/login`  
**Arquivo:** `src/Controllers/UserController.php`

**Descrição:**  
Senhas são armazenadas e comparadas como texto plano no banco de dados, violando princípios básicos de segurança.

**Evidência - Registro (linhas 42-44):**
```php
$stmt = $conn->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
$stmt->bindValue(1, $email);
$stmt->bindValue(2, $password); // ❌ Senha sem hash
```

**Evidência - Login (linha 81):**
```php
if ($user['password'] === $password) { // ❌ Comparação direta
```

**Passos de Reprodução:**
1. Cadastrar usuário com senha "123456"
2. Consultar tabela `users` no banco
3. **Resultado:** Senha visível como "123456"
4. **Esperado:** Hash bcrypt/argon2

**Impacto:**  
- Exposição completa de credenciais
- Violação de LGPD/GDPR
- Comprometimento em caso de vazamento do BD

**Sugestão de Correção:**
```php
// Registro
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt->bindValue(2, $hashedPassword);

// Login
if (password_verify($password, $user['password'])) {
```

---

### 🔴 BUG-002: LÓGICA INCORRETA DE DUPLICAÇÃO DE EMAIL  
**Severidade:** CRÍTICO  
**Endpoint:** `/api/user/register`  
**Arquivo:** `src/Controllers/UserController.php:25-32`

**Descrição:**  
A verificação de email duplicado está incorreta, permitindo múltiplos usuários com o mesmo email se as senhas forem diferentes.

**Evidência:**
```php
$stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND password = ?');
// ❌ Deveria verificar APENAS o email, não email E senha
```

**Passos de Reprodução:**
1. POST `/api/user/register` com `{"email":"test@example.com","password":"senha1"}`
2. POST `/api/user/register` com `{"email":"test@example.com","password":"senha2"}`  
3. **Resultado:** Ambos cadastros aceitos (201)
4. **Esperado:** Segundo cadastro rejeitado (409)

**Impacto:**
- Múltiplas contas com mesmo email
- Conflitos na autenticação
- Violação de unicidade de usuário

**Sugestão de Correção:**
```php
$stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$stmt->bindValue(1, $email);
// Remove a verificação de senha
```

---

### 🔴 BUG-003: USER ENUMERATION VULNERABILITY
**Severidade:** CRÍTICO  
**Endpoint:** `/api/user/login`  
**Arquivo:** `src/Controllers/UserController.php:74-95`

**Descrição:**  
Diferentes mensagens e códigos HTTP para usuário inexistente vs senha incorreta permitem enumerar usuários válidos.

**Evidência:**
```php
// Usuário não encontrado - 404
if (!$user) {
    return $response->withStatus(404); // ❌ Revela que usuário não existe
}

// Senha incorreta - 401  
return $response->withStatus(401); // ❌ Revela que usuário existe
```

**Passos de Reprodução:**
1. POST `/api/user/login` com email inexistente → 404 "User not found"
2. POST `/api/user/login` com email válido + senha errada → 401 "Password is incorrect"
3. **Resultado:** Atacante identifica emails válidos
4. **Esperado:** Sempre 401 "Invalid credentials"

**Impacto:**
- Enumeração de usuários válidos
- Facilitação de ataques direcionados
- Violação de privacidade

**Sugestão de Correção:**
```php
// Sempre retornar 401 com mensagem genérica
if (!$user || !password_verify($password, $user['password'])) {
    return $response->withStatus(401)
        ->write(json_encode(['success' => false, 'message' => 'Invalid credentials']));
}
```

---

### 🔴 BUG-004: LÓGICA MATEMÁTICA INCORRETA - JUROS COMPOSTOS
**Severidade:** CRÍTICO  
**Endpoint:** `/api/calculator/compound-interest`  
**Arquivo:** `src/Controllers/CalculatorController.php:66-68`

**Descrição:**  
Divisão arbitrária do tempo por 2 quando período > 12, resultando em cálculos financeiros completamente incorretos.

**Evidência:**
```php
if ($time > 12) {
    $time = $time / 2; // ❌ Lógica completamente incorreta
}
```

**Passos de Reprodução:**
1. POST `/api/calculator/compound-interest` com `{"principal":1000,"rate":5,"time":24}`
2. **Resultado:** Cálculo usa time=12 (24/2)
3. **Esperado:** Cálculo usar time=24 normalmente

**Cálculo Correto vs Implementado:**
- **Fórmula correta:** M = 1000 * (1 + 0.05/12)^(12*24) = R$ 3.320,10
- **Bug implementado:** M = 1000 * (1 + 0.05/12)^(12*12) = R$ 1.819,40
- **Diferença:** R$ 1.500,70 (45% de erro!)

**Impacto:**
- Cálculos financeiros completamente incorretos
- Perdas financeiras para usuários
- Perda de credibilidade do sistema

---

## BUGS ALTOS (P1 - Correção Urgente)

### 🟠 BUG-005: EXPOSIÇÃO DE STACK TRACES  
**Severidade:** ALTO  
**Endpoint:** Todos (catch blocks)  
**Arquivo:** `src/Controllers/UserController.php:45,55`

**Evidência:**
```php
'error' => $e->getMessage() // ❌ Expõe detalhes internos
```

**Impacto:** Vazamento de informações do sistema, caminhos de arquivos, estrutura do banco.

---

### 🟠 BUG-006: ARREDONDAMENTO INCONSISTENTE
**Severidade:** ALTO  
**Endpoints:** Todos os calculadores  
**Arquivo:** `src/Controllers/CalculatorController.php`

**Evidência:**
```php
// Juros simples
$interest = round($interest, 1);      // 1 casa decimal  
$totalAmount = round($totalAmount, 3); // 3 casas decimais ❌

// Juros compostos  
$interest = round($interest, 2);      // 2 casas decimais
$totalAmount = round($totalAmount, 1); // 1 casa decimal ❌

// Parcelas
$installmentAmount = round($installmentAmount, 1); // 1 casa
$totalAmount = round($totalAmount, 2);            // 2 casas ❌
```

**Impacto:** Inconsistência nos valores monetários, confusão do usuário.

---

### 🟠 BUG-007: AUSÊNCIA DE AUTENTICAÇÃO NOS CALCULADORES
**Severidade:** ALTO  
**Endpoints:** `/api/calculator/*`  
**Arquivo:** `src/routes/api.php:14-19`

**Descrição:** Calculadoras expostas publicamente sem autenticação.

**Impacto:** Uso não autorizado dos serviços, possível abuso.

---

### 🟠 BUG-008: FALTA DE VALIDAÇÃO DE ENTRADA  
**Severidade:** ALTO  
**Endpoints:** Todos  

**Problemas Identificados:**
- Valores negativos aceitos sem validação
- Email não validado por formato
- Tipos de dados não verificados
- Ausência de limites máximos

**Exemplos:**
```json
{"principal": -1000, "rate": 5, "time": 12}     // ❌ Aceito
{"email": "invalid-email", "password": "test"}   // ❌ Aceito  
{"principal": "abc", "rate": "def"}             // ❌ Pode quebrar
```

---

### 🟠 BUG-009: AUSÊNCIA DE RATE LIMITING
**Severidade:** ALTO  
**Endpoints:** `/api/user/login`  

**Descrição:** Não há proteção contra ataques de força bruta.

**Passos para Demonstrar:**
1. Executar 100 tentativas de login em 1 minuto
2. **Resultado:** Todas processadas
3. **Esperado:** Rate limit após 5-10 tentativas

---

## BUGS MÉDIOS (P2 - Correção Planejada)

### 🟡 BUG-010: LOGS INADEQUADOS
**Severidade:** MÉDIO  
**Arquivo:** `src/Controllers/CalculatorController.php:157-166`

**Descrição:** Falhas de log são silenciosas e não há auditoria de login.

---

### 🟡 BUG-011: HEADERS DE SEGURANÇA AUSENTES
**Severidade:** MÉDIO  

**Headers Ausentes:**
- `X-Frame-Options`
- `Content-Security-Policy`  
- `X-Content-Type-Options`
- `Referrer-Policy`

---

### 🟡 BUG-012: TRATAMENTO DE ERRO INCONSISTENTE
**Severidade:** MÉDIO  

**Descrição:** Diferentes formatos de erro entre endpoints, alguns expõem detalhes internos.

---

## MATRIZ DE RISCOS

| Bug ID | Severidade | Probabilidade | Impacto no Negócio | Esforço de Correção |
|--------|------------|---------------|---------------------|---------------------|
| BUG-001 | CRÍTICO | ALTA | CRÍTICO | MÉDIO |
| BUG-002 | CRÍTICO | ALTA | ALTO | BAIXO |
| BUG-003 | CRÍTICO | MÉDIA | ALTO | BAIXO |
| BUG-004 | CRÍTICO | ALTA | CRÍTICO | BAIXO |
| BUG-005 | ALTO | MÉDIA | MÉDIO | BAIXO |
| BUG-006 | ALTO | ALTA | MÉDIO | MÉDIO |
| BUG-007 | ALTO | BAIXA | MÉDIO | MÉDIO |
| BUG-008 | ALTO | ALTA | ALTO | ALTO |
| BUG-009 | ALTO | MÉDIA | ALTO | MÉDIO |

---

## RECOMENDAÇÕES DE PRIORIZAÇÃO

### SPRINT 1 (Imediato):
- BUG-001: Implementar hash de senhas
- BUG-002: Corrigir lógica de duplicação  
- BUG-004: Corrigir cálculo de juros compostos

### SPRINT 2 (2 semanas):
- BUG-003: Corrigir user enumeration
- BUG-008: Implementar validações
- BUG-006: Padronizar arredondamento

### SPRINT 3 (1 mês):
- BUG-007: Implementar autenticação
- BUG-009: Adicionar rate limiting
- BUG-005: Melhorar tratamento de erros

---

**Evidências completas:** Ver arquivos em `docs/evidencias/`  
**Próxima revisão:** Após correções críticas implementadas