# Análise Estática - Challenge QA API

**Data:** 24/09/2025  
**Analisador:** Agente QA Sênior  
**Commit:** main  
**Linguagem:** PHP 8.2 + Slim Framework 4

---

## RESUMO EXECUTIVO

⚠️ **CRÍTICO**: 4 vulnerabilidades de segurança identificadas  
⚠️ **ALTO**: 6 bugs funcionais encontrados  
⚠️ **MÉDIO**: 3 problemas de qualidade de código

---

## 1. VULNERABILIDADES DE SEGURANÇA

### 🔴 S01 - SENHAS EM TEXTO PLANO (CRÍTICO)
**Arquivo:** `src/Controllers/UserController.php` linhas 26-48 e 81-95  
**Problema:** Senhas são armazenadas e comparadas como texto plano  
**Impacto:** Exposição de credenciais no banco de dados  
**Evidência:**
```php
// Linha 42 - Senha não é hasheada antes de inserir
$stmt->bindValue(2, $password);

// Linha 81 - Comparação direta com texto plano
if ($user['password'] === $password) {
```
**Correção:** Usar `password_hash()` e `password_verify()`

### 🔴 S02 - USER ENUMERATION (CRÍTICO)
**Arquivo:** `src/Controllers/UserController.php` linhas 74-78  
**Problema:** Mensagens diferentes para usuário inexistente vs senha incorreta  
**Impacto:** Atacantes podem enumerar usuários válidos  
**Evidência:**
```php
// Linha 75 - Mensagem específica para usuário não encontrado
'message' => 'User not found' // 404

// Linha 88 - Mensagem específica para senha incorreta  
'message' => 'Password is incorrect' // 401
```

### 🔴 S03 - SQL INJECTION RISK (ALTO)
**Arquivo:** `src/Controllers/UserController.php` linha 25  
**Problema:** Query mal estruturada para verificar usuário existente  
**Evidência:**
```php
// Linha 25 - Busca por email E password (deveria ser só email)
$stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND password = ?');
```

### 🔴 S04 - EXPOSIÇÃO DE STACK TRACES (ALTO)
**Arquivo:** `src/Controllers/UserController.php` linha 45  
**Problema:** Stack traces expostos em resposta de erro  
**Evidência:**
```php
'error' => $e->getMessage() // Expõe detalhes internos
```

---

## 2. BUGS FUNCIONAIS

### 🟠 F01 - LÓGICA INCORRETA NO CADASTRO (ALTO)
**Arquivo:** `src/Controllers/UserController.php` linha 25-32  
**Problema:** Verifica se usuário existe usando email E senha (deveria ser só email)  
**Impacto:** Permite cadastros duplicados com senhas diferentes

### 🟠 F02 - ARREDONDAMENTO INCONSISTENTE (ALTO)
**Arquivo:** `src/Controllers/CalculatorController.php` linhas 30-32, 73-75, 139-141  
**Problema:** Diferentes precisões de arredondamento entre funções  
**Evidência:**
```php
// Juros simples - 1 casa decimal
$interest = round($interest, 1);
$totalAmount = round($totalAmount, 3); // 3 casas?

// Juros compostos - 2 casas decimais  
$interest = round($interest, 2);
$totalAmount = round($totalAmount, 1); // 1 casa?

// Parcelas - diferentes valores
$installmentAmount = round($installmentAmount, 1);
$totalAmount = round($totalAmount, 2);
$totalInterest = round($totalInterest, 3);
```

### 🟠 F03 - LÓGICA INCORRETA JUROS COMPOSTOS (CRÍTICO)
**Arquivo:** `src/Controllers/CalculatorController.php` linha 66-68  
**Problema:** Divisão arbitrária de tempo por 2 quando > 12  
**Evidência:**
```php
if ($time > 12) {
    $time = $time / 2; // ??? Por que dividir por 2?
}
```

### 🟠 F04 - FALTA DE VALIDAÇÕES (ALTO)
**Arquivos:** Todos os controllers  
**Problemas:**
- Não valida formato de email
- Não valida valores negativos
- Não valida tipos de dados
- Não valida limites máximos

### 🟠 F05 - AUSÊNCIA DE AUTENTICAÇÃO (ALTO)
**Arquivo:** `src/routes/api.php`  
**Problema:** Endpoints de calculadora não requerem autenticação  
**Impacto:** Qualquer pessoa pode usar os serviços

### 🟠 F06 - LOGS INCONSISTENTES (MÉDIO)
**Arquivo:** `src/Controllers/CalculatorController.php` linha 157  
**Problema:** Função de log pode falhar silenciosamente e não há verificação

---

## 3. PROBLEMAS DE QUALIDADE

### 🟡 Q01 - FALTA DE RATE LIMITING (MÉDIO)
**Impacto:** APIs desprotegidas contra ataques de força bruta

### 🟡 Q02 - AUSÊNCIA DE LOGS DE SEGURANÇA (MÉDIO)
**Impacto:** Não há auditoria de tentativas de login

### 🟡 Q03 - HEADERS DE SEGURANÇA INADEQUADOS (MÉDIO)
**Arquivo:** `src/Middleware/CorsMiddleware.php` (necessário análise)

---

## 4. ESTRUTURA DO BANCO

### ✅ PONTOS POSITIVOS:
- Prepared statements utilizados
- Índice em email
- Estrutura básica adequada

### ❌ PONTOS NEGATIVOS:
- Falta UNIQUE constraint em email
- Campo password sem limitação de tamanho adequada
- Não há campos para auditoria (last_login, failed_attempts)

---

## 5. DEPENDÊNCIAS E CONFIGURAÇÃO

**Dependências analisadas:**
- Slim Framework 4.11 ✅
- Doctrine Migrations 3.9 ✅ 
- Monolog 3.0 ✅
- PHPUnit 10.0 ✅ (dev)

**Docker Configuration:**
- PHP 8.2 ✅
- MySQL 8.0 ✅
- Apache adequadamente configurado ✅

---

## 6. RECOMENDAÇÕES IMEDIATAS

### CRÍTICAS (P0):
1. Implementar hash de senhas
2. Corrigir user enumeration
3. Corrigir lógica de cadastro duplicado
4. Corrigir fórmula de juros compostos

### ALTAS (P1):
1. Implementar autenticação nos endpoints
2. Padronizar arredondamento
3. Adicionar validações de entrada
4. Remover exposição de stack traces

### MÉDIAS (P2):
1. Implementar rate limiting
2. Adicionar logs de segurança
3. Melhorar tratamento de erros