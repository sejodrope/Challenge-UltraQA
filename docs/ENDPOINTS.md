# Catálogo de Endpoints - Challenge QA API

| Método | Endpoint | Auth | Params | Status Codes | Observações |
|--------|----------|------|---------|--------------|-------------|
| GET | `/health` | ❌ | - | 200 | Health check, sempre disponível |
| GET | `/` | ❌ | - | 200 | Documentação da API |
| POST | `/api/user/register` | ❌ | email, password | 201, 400, 409, 500 | 🐛 Senhas em texto plano |
| POST | `/api/user/login` | ❌ | email, password | 200, 400, 401, 404, 500 | 🐛 User enumeration |
| POST | `/api/calculator/simple-interest` | ❌ | principal, rate, time | 200, 400 | 🐛 Arredondamento inconsistente |
| POST | `/api/calculator/compound-interest` | ❌ | principal, rate, time, [frequency] | 200, 400 | 🐛 Lógica matemática incorreta |
| POST | `/api/calculator/installment` | ❌ | principal, rate, installments | 200, 400 | 🐛 Precisão numérica |

## LEGENDA:
- ✅ Funcionando corretamente
- ❌ Não implementado  
- 🐛 Bugs identificados
- ⚠️ Comportamento suspeito

## RESUMO DE PROBLEMAS:
- **Segurança:** 4 vulnerabilidades críticas
- **Funcionalidade:** 6 bugs de negócio
- **Qualidade:** 3 problemas de implementação

## PRIORIDADE DE TESTES:
1. **Crítico:** Login/Registro (segurança)
2. **Alto:** Calculadoras (precisão matemática)
3. **Médio:** Validações e edge cases