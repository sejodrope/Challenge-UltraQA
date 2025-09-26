# Challenge-QA - Corrigido e Melhorado 🚀

[![Tests](https://img.shields.io/badge/Tests-10%2F10%20Passing-brightgreen)]()
[![PHP Version](https://img.shields.io/badge/PHP-8.2-blue)]()
[![Framework](https://img.shields.io/badge/Framework-Slim%204-orange)]()
[![Database](https://img.shields.io/badge/Database-MySQL%208.0-blue)]()
[![Security](https://img.shields.io/badge/Security-JWT%20Auth-green)]()

## 🎯 **Transformação Realizada**

**ANTES**: API com 7 bugs críticos para teste de QA  
**DEPOIS**: Sistema de produção com autenticação JWT, validação robusta, logging avançado e 100% dos testes passando.

---

## 🏗️ **Arquitetura do Sistema**

O sistema consiste em APIs REST seguras para cálculos financeiros com autenticação JWT.

### 🔐 **1) API de Autenticação (Segura)**
- **POST** `/api/user/register` - Cadastro com hash bcrypt de senhas
- **POST** `/api/user/login` - Login com JWT token (24h validade)
- **GET** `/api/user/profile` - Perfil do usuário (requer JWT)

### 💰 **2) API de Calculadora Financeira (Protegida)**
- **POST** `/api/calculator/simple-interest` - Juros simples (requer JWT)
- **POST** `/api/calculator/compound-interest` - Juros compostos (requer JWT)
- **POST** `/api/calculator/installment` - Simulação de parcelamento (requer JWT)

### 📚 **3) API de Documentação**
- **GET** `/` - Documentação completa da API com exemplos

---

## 🚀 **Quick Start**

### Pré-requisitos
- Docker Desktop
- Git

### Executar o Projeto
```bash
# 1. Clone o repositório
git clone https://github.com/sejodrope/Challenge-UltraQA.git
cd Challenge-UltraQA

# 2. Inicie os containers
docker-compose up -d

# 3. Instale dependências
docker exec challenge_qa_backend composer install

# 4. Execute os testes
docker exec challenge_qa_backend ./vendor/bin/phpunit

# 5. Acesse a API
curl http://localhost:8080/
```

### Exemplo de Uso Completo
```bash
# 1. Registrar usuário
curl -X POST http://localhost:8080/api/user/register \
  -H "Content-Type: application/json" \
  -d '{"name":"João Silva","email":"joao@example.com","password":"senha123"}'

# 2. Fazer login e obter JWT
curl -X POST http://localhost:8080/api/user/login \
  -H "Content-Type: application/json" \
  -d '{"email":"joao@example.com","password":"senha123"}'

# 3. Usar JWT para calcular juros (substitua <TOKEN>)
curl -X POST http://localhost:8080/api/calculator/simple-interest \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"principal":10000,"rate":5,"time":12}'
```

---

## 🐛 **Bugs Corrigidos & Melhorias Implementadas**

### 🔥 **Bugs Críticos de Segurança Corrigidos**
| Bug ID | Descrição | Status | Impacto |
|--------|-----------|---------|---------|
| **BUG-001** | Senhas em texto plano | ✅ Corrigido | Hash bcrypt implementado |
| **BUG-002** | Lógica de email duplicado incorreta | ✅ Corrigido | Verificação apenas por email |
| **BUG-003** | Ataque de enumeração de usuários | ✅ Corrigido | Mensagens genéricas |
| **BUG-004** | Erro matemático em juros compostos | ✅ Corrigido | Fórmula corrigida |
| **BUG-005** | Sistema de logs não funcional | ✅ Corrigido | Logs duais implementados |
| **BUG-006** | Precisão decimal inconsistente | ✅ Corrigido | 2 casas decimais padrão |
| **BUG-007** | Falta de autenticação | ✅ Corrigido | JWT middleware implementado |

### 🚀 **Novas Funcionalidades Implementadas**
- ✅ **Autenticação JWT** com middleware personalizado
- ✅ **Validação robusta** de entrada com sanitização
- ✅ **Sistema de logging** avançado (arquivo + database)
- ✅ **Documentação API** completa com exemplos
- ✅ **Suite de testes** completa (10 testes, 48 assertions)
- ✅ **Auditoria completa** de operações
- ✅ **Estrutura de produção** com Docker

### 📊 **Métricas de Qualidade**
- **Testes**: 10/10 passando (100%)
- **Cobertura**: Todos os controllers testados
- **Segurança**: Produção-ready
- **Performance**: Otimizada para cálculos financeiros
- **Observabilidade**: Logs estruturados

---

## 🧪 **Testes & Validação**

### Executar Testes
```bash
# Todos os testes
docker exec challenge_qa_backend ./vendor/bin/phpunit

# Apenas testes unitários
docker exec challenge_qa_backend ./vendor/bin/phpunit tests/Unit

# Apenas testes de integração
docker exec challenge_qa_backend ./vendor/bin/phpunit tests/Integration
```

### Cobertura de Testes
- **UserController**: Login, registro, JWT validation
- **CalculatorController**: Cálculos matemáticos corretos
- **JwtAuthMiddleware**: 5 cenários de autenticação
- **ApiEndpoints**: Health check, documentação

### Exemplos de Validação
```bash
# Teste com dados inválidos (deve falhar)
curl -X POST http://localhost:8080/api/calculator/simple-interest \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"principal":-1000,"rate":150,"time":0}'
# Resposta: {"success":false,"errors":["Principal must be greater than 0",...]}

# Teste sem autenticação (deve falhar)
curl -X POST http://localhost:8080/api/calculator/simple-interest
# Resposta: {"success":false,"message":"Authorization header missing"}
```

---

## 📁 **Estrutura do Projeto**

```
📦 Challenge-UltraQA/
├── 🐳 docker/                   # Configurações Docker
├── 📊 logs/                     # Logs da aplicação
├── 🗄️ migrations/               # Migrações do banco
├── 🌐 public/                   # Ponto de entrada web
├── 🏗️ src/
│   ├── Controllers/             # Lógica dos endpoints
│   ├── Middleware/              # JWT, CORS, Logging
│   ├── Validators/              # Validação de entrada
│   ├── Utils/                   # Logger, helpers
│   └── Config/                  # Configuração do banco
├── 🧪 tests/
│   ├── Unit/                    # Testes unitários
│   └── Integration/             # Testes de integração
├── ⚙️ .env.example              # Configuração de ambiente
├── 🚫 .gitignore                # Exclusões do Git
└── 📋 README.md                 # Documentação

```

---

## 🛡️ **Segurança Implementada**

### Autenticação & Autorização
- 🔐 **JWT Tokens** com expiração de 24h
- 🔒 **Password hashing** com bcrypt
- 🛡️ **Middleware de autenticação** em todas as rotas protegidas
- 🚫 **Proteção contra user enumeration**

### Validação & Sanitização
- ✅ **Input validation** rigorosa
- 🧹 **Data sanitization** automática
- 📊 **Business rules** validation
- 🚨 **Error handling** consistente

### Auditoria & Monitoramento
- 📝 **Logging completo** de todas as operações
- 👤 **Rastreamento de usuários** em logs
- 🌐 **IP tracking** para auditoria
- 📊 **Database logging** para compliance

---

## 🏗️ **Stack Tecnológica**

- **Backend**: PHP 8.2 + Slim Framework 4
- **Database**: MySQL 8.0 + Doctrine DBAL
- **Autenticação**: JWT (Firebase PHP-JWT)
- **Containerização**: Docker + Docker Compose  
- **Testes**: PHPUnit 10 (100% passando)
- **Logging**: Sistema dual (arquivo + database)
- **Validação**: Input sanitization + business rules  

---

## Objetivo do Desafio

O candidato deverá:  
1. ✅ Explorar os endpoints disponíveis  
2. ✅ Identificar **falhas funcionais e de negócio** (7 bugs encontrados)  
3. ✅ Criar casos de teste que validem cenários positivos e negativos (10 testes)  
4. ✅ Documentar os defeitos encontrados e sugerir melhorias (README completo)  

**✨ RESULTADO**: Todos os objetivos alcançados + refatoração completa para produção!

---

## 🚀 **Deploy & Produção**

### Ambiente de Produção
```bash
# 1. Clone e configure
git clone <repository>
cd Challenge-QA
cp .env.example .env

# 2. Configure variáveis de produção
JWT_SECRET=<strong-secret-key>
DB_HOST=<production-db-host>
DB_NAME=<production-db-name>

# 3. Deploy com Docker
docker-compose up --build -d

# 4. Execute migrações
docker exec challenge_qa_backend php migrations.php
```

### Monitoramento
- 📊 **Logs**: `logs/app.log` e tabela `application_logs`
- 🔍 **Health Check**: `GET /api/health`
- 📈 **Metrics**: JWT usage, API calls, errors
- 🚨 **Alertas**: Configurar monitoramento nos logs de ERROR

---

## 🤝 **Contribuição**

### Como Contribuir
1. 🍴 **Fork** o projeto
2. 🌿 **Crie** uma branch (`git checkout -b feature/nova-feature`)
3. ✅ **Commit** suas alterações (`git commit -m 'Add: nova feature'`)
4. 📤 **Push** para a branch (`git push origin feature/nova-feature`)
5. 🔄 **Abra** um Pull Request

### Padrões de Desenvolvimento
- 📝 **PSR-4** autoloading
- 🧪 **TDD**: Escreva testes primeiro
- 📋 **Conventional Commits**: `feat:`, `fix:`, `docs:`
- 🔍 **Code Review**: Todas as mudanças devem ser revisadas
- ✅ **100% Tests**: Mantenha cobertura completa

---

## 📞 **Suporte & Contato**

### Informações do Projeto
- 👨‍💻 **Desenvolvido por**: José Pedro
- 📅 **Data**: 25 de Setembro de 2025
- 🏢 **Contexto**: Challenge QA - Ultra LIMS
- 📝 **Versão**: 2.0.0 (Refatorado e Melhorado)

### Links Úteis
- 🌐 **Demo**: `http://localhost:8080/api/docs`
- 📊 **Health Check**: `http://localhost:8080/api/health`
- 🔗 **API Docs**: Veja seção de Endpoints acima

