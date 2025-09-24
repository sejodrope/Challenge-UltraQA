# Plano de Testes - Challenge QA API

**Projeto:** Challenge QA API  
**Data:** 24/09/2025  
**QA Lead:** Agente QA Sênior  
**Versão:** 1.0.0

---

## 1. OBJETIVO

Validar a funcionalidade, segurança e qualidade da API Challenge QA, com foco especial em:
- **Segurança de autenticação e autorização**
- **Precisão dos cálculos financeiros** 
- **Robustez contra entradas inválidas**
- **Conformidade com contratos de API**

---

## 2. ESCOPO

### FUNCIONALIDADES TESTADAS:
✅ **Cadastro de usuário** (`/api/user/register`)  
✅ **Login de usuário** (`/api/user/login`)  
✅ **Calculadora de juros simples** (`/api/calculator/simple-interest`)  
✅ **Calculadora de juros compostos** (`/api/calculator/compound-interest`)  
✅ **Simulação de parcelamento** (`/api/calculator/installment`)  
✅ **Health check** (`/health`)

### FORA DE ESCOPO:
❌ Performance/Load testing (simulação básica apenas)  
❌ Testes de infraestrutura Docker detalhados  
❌ Testes de migração de dados

---

## 3. ESTRATÉGIA DE TESTES

### ABORDAGEM RISK-BASED TESTING:
**Pirâmide de Testes Adaptada:**
- **70% Functional API Tests** (Postman/Newman)
- **20% Security & Edge Cases** (Manual + Scripts)  
- **10% Integration & E2E** (Docker + DB)

### CLASSIFICAÇÃO DE RISCOS:
- 🔴 **CRÍTICO:** Segurança, cálculos financeiros incorretos
- 🟠 **ALTO:** Validações de entrada, contratos HTTP
- 🟡 **MÉDIO:** Mensagens de erro, logs
- 🟢 **BAIXO:** Documentação, cosmético

---

## 4. FERRAMENTAS E AMBIENTE

### FERRAMENTAS:
- **Docker Compose** (ambiente completo)
- **Postman/Newman** (testes automatizados principais)
- **REST Client VS Code** (testes exploratórios)
- **cURL** (testes de segurança)
- **Custom Scripts** (validação matemática)

### AMBIENTE:
- **SO:** Windows com PowerShell
- **Runtime:** Docker containers (PHP 8.2 + MySQL 8.0)
- **Base URL:** `http://localhost:8080`
- **Network:** challenge_qa_network

### DADOS DE TESTE:
- **Emails:** Válidos, inválidos, extremos, SQLi, XSS
- **Senhas:** Fracas, fortes, especiais, ataques
- **Valores financeiros:** Zero, negativos, decimais, overflow
- **Oráculos matemáticos:** Calculadoras externas, fórmulas manuais

---

## 5. CRITÉRIOS DE ENTRADA/SAÍDA

### CRITÉRIOS DE ENTRADA:
✅ Ambiente Docker functional  
✅ Migrations executadas com sucesso  
✅ Todos os endpoints acessíveis (status 200/400/etc)  
✅ Base de dados limpa  
✅ Logs de erro configurados

### CRITÉRIOS DE SAÍDA:
✅ **>85%** dos cenários críticos executados  
✅ **0 bugs críticos** (segurança/financeiro) em aberto  
✅ **Documentação completa** com evidências  
✅ **Bugs catalogados** com reprodução e severidade  
✅ **Métricas de qualidade** calculadas

---

## 6. TIPOS DE TESTE

### 6.1 TESTES FUNCIONAIS

#### CADASTRO DE USUÁRIO:
- ✅ Cenário feliz (email válido + senha)
- ⚠️ Email duplicado (verificar lógica incorreta)
- ❌ Email inválido/malformado
- ❌ Senha ausente/vazia
- 🔍 Campos extras ignorados
- 🛡️ Injeção SQL/XSS como dados

#### LOGIN:
- ✅ Credenciais válidas
- ⚠️ User enumeration (mensagens diferentes)
- ❌ Brute force (rate limiting ausente)
- ❌ Credenciais inválidas
- 🔍 Token/sessão (não implementado)

#### CALCULADORAS:
- ✅ Fórmulas corretas vs implementação
- ⚠️ Arredondamento consistente
- ❌ Valores zero/negativos  
- ❌ Overflow/underflow
- 🔍 Locale (10,5 vs 10.5)
- 🔍 Tipos de dados incorretos

### 6.2 TESTES DE SEGURANÇA

#### OWASP TOP 10 APLICÁVEL:
- 🔴 **A01** - Broken Access Control (endpoints sem auth)
- 🔴 **A02** - Cryptographic Failures (senhas texto plano)  
- 🔴 **A03** - Injection (SQLi/XSS testing)
- 🔴 **A07** - ID & Auth Failures (user enumeration)

#### TESTES ESPECÍFICOS:
- SQL Injection (string patterns)
- XSS (script tags em inputs)
- Rate limiting (tentativas múltiplas)
- Headers de segurança
- Stack trace exposure

### 6.3 TESTES DE CONTRATO

#### VALIDAÇÕES HTTP:
- Status codes corretos
- Content-Type: application/json
- Response structure/schema
- Error message format
- Headers obrigatórios

---

## 7. CRONOGRAMA

| Fase | Atividade | Duração | Responsável |
|------|-----------|---------|-------------|
| 1 | Setup ambiente + Análise estática | 1h | QA Agent |
| 2 | Criação de casos de teste | 1h | QA Agent |
| 3 | Automação Postman/Newman | 2h | QA Agent |
| 4 | Execução testes funcionais | 1h | QA Agent |
| 5 | Testes de segurança | 1h | QA Agent |
| 6 | Análise de resultados + bugs | 1h | QA Agent |
| 7 | Documentação final | 1h | QA Agent |
| **TOTAL** | **8 horas** | - | - |

---

## 8. GESTÃO DE RISCOS

### RISCOS IDENTIFICADOS:

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| Docker não funcionar | 🟡 Média | 🔴 Alto | Fallback para PHP local + MySQL |
| Bugs críticos de segurança | 🔴 Alta | 🔴 Crítico | Foco em testes de segurança |
| Cálculos financeiros incorretos | 🟠 Alta | 🔴 Alto | Oráculos externos validados |
| Instabilidade do ambiente | 🟡 Média | 🟠 Médio | Reset containers + DB |
| Falta de dados para alguns casos | 🟡 Baixa | 🟡 Baixo | Geração sintética |

### CONTINGÊNCIAS:
- **Ambiente instável:** Reset completo + logs detalhados
- **Performance lenta:** Reduzir escopo de testes de carga  
- **Descoberta de bugs críticos:** Parar execução + documentar imediatamente

---

## 9. COMUNICAÇÃO E ENTREGÁVEIS

### RELATÓRIOS:
- **Diário:** Status de execução (% complete)
- **Bugs:** Imediato para severidade crítica/alta
- **Final:** Relatório consolidado + métricas

### ARTEFATOS:
- Casos de teste executáveis (Postman)
- Evidências de bugs (JSON responses)
- Scripts de automação
- Documentação completa
- Métricas de qualidade

---

## 10. APROVAÇÃO

**Testplan aprovado por:** Agente QA Sênior  
**Data:** 24/09/2025  
**Versão:** 1.0

---

**📋 PRÓXIMOS PASSOS:**
1. ✅ Executar setup de ambiente
2. ⏳ Criar casos de teste detalhados  
3. ⏳ Implementar automação
4. ⏳ Executar bateria de testes
5. ⏳ Documentar bugs e resultados