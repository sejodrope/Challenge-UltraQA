# Casos de Teste - Challenge QA API

**Projeto:** Challenge QA API  
**Versão:** 1.0.0  
**Data:** 24/09/2025

---

## LEGENDA DE STATUS
✅ **PASS** | ❌ **FAIL** | 🟡 **BLOCKED** | ⏳ **NOT_RUN** | 🐛 **BUG_FOUND**

---

## TC001-TC020: CADASTRO DE USUÁRIO

| ID | Cenário | Entrada | Resultado Esperado | Resultado Obtido | Status |
|----|---------|---------|--------------------|------------------|---------|
| **TC001** | Cadastro válido | `{"email":"test@example.com","password":"senha123"}` | 201, user_id presente, senha não exposta | ⏳ | ⏳ |
| **TC002** | Email duplicado | Mesmo email de TC001 | 409, "Email already exists" | ⏳ | ⏳ |
| **TC003** | Email inválido | `{"email":"invalid-email","password":"senha123"}` | 400, "Invalid email format" | ⏳ | ⏳ |
| **TC004** | Email ausente | `{"password":"senha123"}` | 400, "Email and password are required" | ⏳ | ⏳ |
| **TC005** | Senha ausente | `{"email":"test2@example.com"}` | 400, "Email and password are required" | ⏳ | ⏳ |
| **TC006** | Campos vazios | `{"email":"","password":""}` | 400, campos obrigatórios | ⏳ | ⏳ |
| **TC007** | Email null | `{"email":null,"password":"senha123"}` | 400, validação | ⏳ | ⏳ |
| **TC008** | Senha null | `{"email":"test3@example.com","password":null}` | 400, validação | ⏳ | ⏳ |
| **TC009** | JSON malformado | `{"email":"test@example.com","password"` | 400, "Invalid JSON" | ⏳ | ⏳ |
| **TC010** | Body vazio | ` ` | 400, campos obrigatórios | ⏳ | ⏳ |
| **TC011** | Content-Type incorreto | `text/plain` | 400, content type | ⏳ | ⏳ |
| **TC012** | Email com SQLi | `{"email":"test'; DROP TABLE users;--","password":"senha123"}` | 400/201, sem erro SQL | ⏳ | ⏳ |
| **TC013** | Password com SQLi | `{"email":"test4@example.com","password":"' OR '1'='1"}` | 201, inserido como string | ⏳ | ⏳ |
| **TC014** | Email com XSS | `{"email":"<script>alert('xss')</script>@test.com","password":"senha123"}` | 400/201, sem execução script | ⏳ | ⏳ |
| **TC015** | Email muito longo | Email com 300 caracteres | 400, "Email too long" | ⏳ | ⏳ |
| **TC016** | Senha muito longa | Senha com 1000 caracteres | 400/201, definir limite | ⏳ | ⏳ |
| **TC017** | Email unicode | `{"email":"tëst@üñíçødê.com","password":"senha123"}` | 201, suporte unicode | ⏳ | ⏳ |
| **TC018** | Múltiplos campos | `{"email":"test5@example.com","password":"senha123","extra":"field"}` | 201, campos extras ignorados | ⏳ | ⏳ |
| **TC019** | Cadastros simultâneos | 2 requests simultâneos mesmo email | 1x201, 1x409, race condition | ⏳ | ⏳ |
| **TC020** | Verificar senha BD | Após TC001, verificar se senha está hasheada no BD | Senha deve estar hasheada | ⏳ | ⏳ |

---

## TC021-TC040: LOGIN DE USUÁRIO  

| ID | Cenário | Entrada | Resultado Esperado | Resultado Obtido | Status |
|----|---------|---------|--------------------|------------------|---------|
| **TC021** | Login válido | Credenciais de TC001 | 200, user object, token/sessão | ⏳ | ⏳ |
| **TC022** | Email inexistente | `{"email":"naoexiste@example.com","password":"qualquer"}` | 401, "Invalid credentials" (sem enumeration) | ⏳ | ⏳ |
| **TC023** | Senha incorreta | Email válido + senha errada | 401, "Invalid credentials" (sem enumeration) | ⏳ | ⏳ |
| **TC024** | Email ausente | `{"password":"senha123"}` | 400, "Email and password are required" | ⏳ | ⏳ |
| **TC025** | Senha ausente | `{"email":"test@example.com"}` | 400, "Email and password are required" | ⏳ | ⏳ |
| **TC026** | Credenciais vazias | `{"email":"","password":""}` | 400, campos obrigatórios | ⏳ | ⏳ |
| **TC027** | Credenciais null | `{"email":null,"password":null}` | 400, validação | ⏳ | ⏳ |
| **TC028** | Case sensitive email | Email com case diferente | 200/401, definir comportamento | ⏳ | ⏳ |
| **TC029** | Brute force - 5 tentativas | 5 logins incorretos seguidos | Rate limit ou CAPTCHA | ⏳ | ⏳ |
| **TC030** | Brute force - 10 tentativas | 10 logins incorretos seguidos | Account lockout | ⏳ | ⏳ |
| **TC031** | SQL Injection email | `{"email":"admin'--","password":"qualquer"}` | 401, sem erro SQL | ⏳ | ⏳ |
| **TC032** | SQL Injection password | `{"email":"test@example.com","password":"' OR 1=1--"}` | 401, sem bypass | ⏳ | ⏳ |
| **TC033** | Tempo de resposta | Medir tempo user inexistente vs senha errada | Diferença < 100ms (timing attack) | ⏳ | ⏳ |
| **TC034** | Headers de resposta | Verificar headers de segurança | HSTS, X-Frame-Options, etc | ⏳ | ⏳ |
| **TC035** | JSON malformado | `{"email":"test@example.com","password"` | 400, "Invalid JSON" | ⏳ | ⏳ |
| **TC036** | Method GET | GET ao invés de POST | 405, Method Not Allowed | ⏳ | ⏳ |
| **TC037** | Method PUT | PUT ao invés de POST | 405, Method Not Allowed | ⏳ | ⏳ |
| **TC038** | Token/Session persistence | Após login, verificar duração do token | Token persistente entre requests | ⏳ | ⏳ |
| **TC039** | Logout (se implementado) | Invalidar token/sessão | Token invalidado | ⏳ | ⏳ |
| **TC040** | Content-Type incorreto | `text/plain` | 400, content type validation | ⏳ | ⏳ |

---

## TC041-TC060: JUROS SIMPLES

### Oráculo: J = C * i * t / 100 | M = C + J

| ID | Cenário | Entrada | Resultado Esperado | Resultado Obtido | Status |
|----|---------|---------|--------------------|------------------|---------|
| **TC041** | Cálculo padrão | `{"principal":1000,"rate":5,"time":12}` | interest: 600.00, total: 1600.00 | ⏳ | ⏳ |
| **TC042** | Principal zero | `{"principal":0,"rate":5,"time":12}` | 400, "Principal must be > 0" | ⏳ | ⏳ |
| **TC043** | Taxa zero | `{"principal":1000,"rate":0,"time":12}` | interest: 0.00, total: 1000.00 | ⏳ | ⏳ |
| **TC044** | Tempo zero | `{"principal":1000,"rate":5,"time":0}` | interest: 0.00, total: 1000.00 | ⏳ | ⏳ |
| **TC045** | Valores negativos | `{"principal":-1000,"rate":5,"time":12}` | 400, "Values must be positive" | ⏳ | ⏳ |
| **TC046** | Taxa negativa | `{"principal":1000,"rate":-5,"time":12}` | 400, "Rate must be positive" | ⏳ | ⏳ |
| **TC047** | Tempo negativo | `{"principal":1000,"rate":5,"time":-12}` | 400, "Time must be positive" | ⏳ | ⏳ |
| **TC048** | Valores decimais | `{"principal":1500.50,"rate":4.75,"time":18}` | Cálculo preciso com decimais | ⏳ | ⏳ |
| **TC049** | Taxa alta (100%) | `{"principal":1000,"rate":100,"time":12}` | interest: 12000.00, total: 13000.00 | ⏳ | ⏳ |
| **TC050** | Taxa muito alta (500%) | `{"principal":1000,"rate":500,"time":12}` | 400/200, definir limite | ⏳ | ⏳ |
| **TC051** | Principal muito alto | `{"principal":999999999,"rate":5,"time":12}` | Verificar overflow | ⏳ | ⏳ |
| **TC052** | Campos ausentes | `{"principal":1000,"rate":5}` | 400, "Principal, rate, and time are required" | ⏳ | ⏳ |
| **TC053** | Tipos incorretos | `{"principal":"abc","rate":"xyz","time":"def"}` | 400, "Invalid number format" | ⏳ | ⏳ |
| **TC054** | Valores string numérica | `{"principal":"1000","rate":"5","time":"12"}` | 200, conversão automática | ⏳ | ⏳ |
| **TC055** | Locale brasileiro | `{"principal":"1.000,50","rate":"4,5","time":12}` | 200/400, suporte locale | ⏳ | ⏳ |
| **TC056** | Arredondamento | `{"principal":333.333,"rate":3.333,"time":3}` | Verificar precisão (2 casas) | ⏳ | ⏳ |
| **TC057** | Campos extras | `{"principal":1000,"rate":5,"time":12,"extra":"ignored"}` | 200, campos ignorados | ⏳ | ⏳ |
| **TC058** | JSON malformado | `{"principal":1000,"rate":5,"time"` | 400, "Invalid JSON" | ⏳ | ⏳ |
| **TC059** | Method GET | GET ao invés de POST | 405, Method Not Allowed | ⏳ | ⏳ |
| **TC060** | Autenticação | Request sem auth (se implementada) | 401, Authentication required | ⏳ | ⏳ |

---

## TC061-TC080: JUROS COMPOSTOS

### Oráculo: M = C * (1 + i/n)^(n*t) | J = M - C

| ID | Cenário | Entrada | Resultado Esperado | Resultado Obtido | Status |
|----|---------|---------|--------------------|------------------|---------|
| **TC061** | Cálculo padrão | `{"principal":1000,"rate":5,"time":12,"compounding_frequency":12}` | Usar fórmula correta | ⏳ | ⏳ |
| **TC062** | Frequency padrão | `{"principal":1000,"rate":5,"time":12}` | frequency: 12 (default) | ⏳ | ⏳ |
| **TC063** | Frequency anual | `{"principal":1000,"rate":5,"time":2,"compounding_frequency":1}` | Capitalização anual | ⏳ | ⏳ |
| **TC064** | Time > 12 (BUG TESTE) | `{"principal":1000,"rate":5,"time":24}` | **BUG:** Time dividido por 2? | ⏳ | ⏳ |
| **TC065** | Time = 12 (limite) | `{"principal":1000,"rate":5,"time":12}` | Sem divisão por 2 | ⏳ | ⏳ |
| **TC066** | Frequency zero | `{"principal":1000,"rate":5,"time":12,"compounding_frequency":0}` | 400, "Frequency must be > 0" | ⏳ | ⏳ |
| **TC067** | Valores zero | `{"principal":0,"rate":5,"time":12}` | 400, validação | ⏳ | ⏳ |
| **TC068** | Taxa zero | `{"principal":1000,"rate":0,"time":12}` | total: 1000, interest: 0 | ⏳ | ⏳ |
| **TC069** | Tempo zero | `{"principal":1000,"rate":5,"time":0}` | total: 1000, interest: 0 | ⏳ | ⏳ |
| **TC070** | Valores negativos | `{"principal":-1000,"rate":5,"time":12}` | 400, validação | ⏳ | ⏳ |
| **TC071** | Comparação vs simples | Mesmo valores de TC041 | Juros compostos > simples | ⏳ | ⏳ |
| **TC072** | Arredondamento | Verificar precisão | Consistência com outros endpoints | ⏳ | ⏳ |
| **TC073** | Frequency decimal | `{"principal":1000,"rate":5,"time":12,"compounding_frequency":12.5}` | 200/400, conversão para int | ⏳ | ⏳ |
| **TC074** | Frequency muito alta | `{"principal":1000,"rate":5,"time":12,"compounding_frequency":365}` | Cálculo diário | ⏳ | ⏳ |
| **TC075** | Overflow test | `{"principal":999999999,"rate":50,"time":50}` | Verificar overflow | ⏳ | ⏳ |
| **TC076** | Tipos incorretos | `{"principal":"abc","rate":"def"}` | 400, validação tipos | ⏳ | ⏳ |
| **TC077** | Campos ausentes | `{"principal":1000,"rate":5}` | 400, campos obrigatórios | ⏳ | ⏳ |
| **TC078** | JSON malformado | Invalid JSON | 400, JSON parsing error | ⏳ | ⏳ |
| **TC079** | Method incorreto | PUT/GET | 405, Method Not Allowed | ⏳ | ⏳ |
| **TC080** | Headers incorretos | Content-Type inválido | 400, validação headers | ⏳ | ⏳ |

---

## TC081-TC100: SIMULAÇÃO DE PARCELAS

### Oráculo: PMT = C * [i*(1+i)^n] / [(1+i)^n - 1]

| ID | Cenário | Entrada | Resultado Esperado | Resultado Obtido | Status |
|----|---------|---------|--------------------|------------------|---------|
| **TC081** | Parcelamento padrão | `{"principal":10000,"rate":12,"installments":12}` | PMT usando fórmula Price | ⏳ | ⏳ |
| **TC082** | Taxa zero | `{"principal":10000,"rate":0,"installments":12}` | PMT = principal/installments | ⏳ | ⏳ |
| **TC083** | 1 parcela | `{"principal":10000,"rate":12,"installments":1}` | PMT = principal + juros total | ⏳ | ⏳ |
| **TC084** | Muitas parcelas | `{"principal":10000,"rate":12,"installments":60}` | Cálculo correto, breakdown completo | ⏳ | ⏳ |
| **TC085** | Principal pequeno | `{"principal":100,"rate":12,"installments":12}` | Valores pequenos precisos | ⏳ | ⏳ |
| **TC086** | Principal zero | `{"principal":0,"rate":12,"installments":12}` | 400, "Principal must be > 0" | ⏳ | ⏳ |
| **TC087** | Parcelas zero | `{"principal":10000,"rate":12,"installments":0}` | 400, "Installments must be > 0" | ⏳ | ⏳ |
| **TC088** | Taxa negativa | `{"principal":10000,"rate":-12,"installments":12}` | 400, validação | ⏳ | ⏳ |
| **TC089** | Parcelas negativas | `{"principal":10000,"rate":12,"installments":-12}` | 400, validação | ⏳ | ⏳ |
| **TC090** | Arredondamento breakdown | Verificar soma das parcelas | Total breakdown = total_amount | ⏳ | ⏳ |
| **TC091** | Breakdown precision | Verificar remaining_balance última parcela | Último remaining_balance = 0.00 | ⏳ | ⏳ |
| **TC092** | Valores decimais | `{"principal":15750.75,"rate":8.5,"installments":18}` | Cálculos precisos com decimais | ⏳ | ⏳ |
| **TC093** | Taxa alta | `{"principal":10000,"rate":100,"installments":12}` | Cálculo com taxa extrema | ⏳ | ⏳ |
| **TC094** | Muitas parcelas (100) | `{"principal":10000,"rate":12,"installments":100}` | Performance + precisão | ⏳ | ⏳ |
| **TC095** | Parcelas decimais | `{"principal":10000,"rate":12,"installments":12.5}` | 400/200, conversão para int | ⏳ | ⏳ |
| **TC096** | Tipos incorretos | `{"principal":"abc","rate":"def","installments":"ghi"}` | 400, validação tipos | ⏳ | ⏳ |
| **TC097** | Campos ausentes | `{"principal":10000,"rate":12}` | 400, campos obrigatórios | ⏳ | ⏳ |
| **TC098** | Campos extras | `{"principal":10000,"rate":12,"installments":12,"extra":"ignored"}` | 200, ignorar extras | ⏳ | ⏳ |
| **TC099** | JSON malformado | Invalid JSON | 400, parsing error | ⏳ | ⏳ |
| **TC100** | Method incorreto | GET/PUT/DELETE | 405, Method Not Allowed | ⏳ | ⏳ |

---

## TC101-TC110: TESTES GERAIS

| ID | Cenário | Entrada | Resultado Esperado | Resultado Obtido | Status |
|----|---------|---------|--------------------|------------------|---------|
| **TC101** | Health check | `GET /health` | 200, status: ok, timestamp, version | ⏳ | ⏳ |
| **TC102** | API docs | `GET /` | 200, documentação completa | ⏳ | ⏳ |
| **TC103** | Endpoint inexistente | `GET /api/inexistente` | 404, Not Found | ⏳ | ⏳ |
| **TC104** | CORS headers | Request com Origin | Headers CORS adequados | ⏳ | ⏳ |
| **TC105** | OPTIONS request | `OPTIONS /api/user/login` | 200, métodos permitidos | ⏳ | ⏳ |
| **TC106** | Large payload | JSON > 1MB | 413/400, Entity Too Large | ⏳ | ⏳ |
| **TC107** | Headers de segurança | Verificar response headers | X-Frame-Options, CSP, etc | ⏳ | ⏳ |
| **TC108** | Rate limiting (se implementado) | Múltiplos requests rápidos | 429, Too Many Requests | ⏳ | ⏳ |
| **TC109** | Logs de auditoria | Verificar logs após operações | Logs adequados sem dados sensíveis | ⏳ | ⏳ |
| **TC110** | Performance básica | Tempo de resposta endpoints | < 500ms em cenários normais | ⏳ | ⏳ |

---

## RESUMO DE EXECUÇÃO

**Total de Casos:** 110  
**Executados:** 0 ⏳  
**Passou:** 0 ✅  
**Falhou:** 0 ❌  
**Bugs Encontrados:** 0 🐛  

**Próxima Etapa:** Implementação da automação com Postman/Newman