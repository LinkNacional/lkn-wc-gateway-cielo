# 📚 Índice da Documentação - Mapeamento de Acoplamento

Este diretório contém a documentação completa do mapeamento técnico do plugin WooCommerce Cielo Payment Gateway para criação de estratégia de testes unitários.

---

## 📄 Documentos Disponíveis

### 1. 🎯 [RESUMO_EXECUTIVO.md](./RESUMO_EXECUTIVO.md)
**👉 COMECE POR AQUI!**

Visão geral executiva de 1 página com:
- Nível de acoplamento (Médio-Alto)
- 5 descobertas críticas principais
- Estratégia de testes resumida
- Checklist condensado
- Links para documentação detalhada

**Tempo de leitura:** ~5 minutos  
**Público:** Gerentes, Tech Leads, QA Leads

---

### 2. 📖 [MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md)
**📊 DOCUMENTO TÉCNICO PRINCIPAL**

Relatório técnico completo com **1.487 linhas** dividido em 14 seções:

1. **Comunicação HTTP** - Todas as funções e endpoints
2. **Manipulação de Pedidos** - Métodos WC_Order
3. **Rotas e Callbacks** - Sistema de polling (não webhooks!)
4. **Logging e Exceções** - 4 níveis de log com mascaramento
5. **Fluxo PIX** - 11 passos sequenciados
6. **Fluxo Cartão de Crédito** - 12 passos com validações
7. **Fluxo Estorno** - 10 passos com filtros customizáveis
8. **Hooks e Filtros** - 9 customizados documentados
9. **Estrutura de Classes** - Herança e composição
10. **Considerações para Testes** - 30+ funções a mockar
11. **Checklist de Testes** - 32 casos organizados
12. **Pontos de Atenção** - Segurança e performance
13. **Arquivos de Configuração** - composer, package.json
14. **Conclusão e Recomendações**

**Tempo de leitura:** ~45 minutos  
**Público:** Desenvolvedores, Arquitetos de Software, QA Engineers

---

### 3. 🎨 [GUIA_VISUAL_REFERENCIA.md](./GUIA_VISUAL_REFERENCIA.md)
**🔍 REFERÊNCIA RÁPIDA COM DIAGRAMAS**

Guia visual com **759 linhas** contendo:

- 📐 Diagramas ASCII de arquitetura
- 🔄 Fluxogramas de sequência (PIX, Crédito, Estorno)
- 📊 Tabelas de pontos de integração
- 💻 Exemplos de código para mocks (Brain\Monkey + Mockery)
- 🧪 Respostas mockadas da API Cielo (fixtures)
- ✅ Matriz de testes (32 casos checklist)
- 📋 Tabela de códigos de status Cielo → WooCommerce
- ⚠️ Pontos de atenção de segurança

**Tempo de leitura:** ~20 minutos  
**Público:** Desenvolvedores implementando testes

---

## 🗂️ Estrutura dos Documentos

```
📁 lkn-wc-gateway-cielo/
├── 📄 RESUMO_EXECUTIVO.md          (212 linhas, 5.8KB)
│   └── Visão geral para tomada de decisão
│
├── 📖 MAPEAMENTO_TECNICO_ACOPLAMENTO.md  (1.487 linhas, 42KB)
│   ├── 1. Comunicação HTTP
│   ├── 2. Manipulação de Pedidos
│   ├── 3. Rotas e Callbacks
│   ├── 4. Logging e Exceções
│   ├── 5-7. Fluxos Críticos (PIX, Crédito, Estorno)
│   ├── 8. Hooks e Filtros
│   ├── 9. Estrutura de Classes
│   ├── 10. Considerações para Testes
│   ├── 11. Checklist de Testes
│   ├── 12. Pontos de Atenção
│   └── 13-14. Conclusão
│
└── 🎨 GUIA_VISUAL_REFERENCIA.md    (759 linhas, 36KB)
    ├── Diagramas de Arquitetura
    ├── Fluxogramas de Sequência
    ├── Tabelas de Integração
    ├── Exemplos de Mocks
    ├── Fixtures de API
    └── Matriz de Testes
```

**Total:** 2.458 linhas de documentação técnica (sem contar README)

---

## 🎯 Fluxo de Leitura Recomendado

### Para Gerentes / Tech Leads:
1. 📄 Leia **RESUMO_EXECUTIVO.md** (5 min)
2. 📊 Revise a seção "Conclusão" do **MAPEAMENTO_TECNICO_ACOPLAMENTO.md** (5 min)
3. ⚠️ Verifique "Pontos de Atenção de Segurança" no **GUIA_VISUAL_REFERENCIA.md** (3 min)

### Para Desenvolvedores / QA Engineers:
1. 📄 Comece com **RESUMO_EXECUTIVO.md** (5 min)
2. 🎨 Estude os diagramas no **GUIA_VISUAL_REFERENCIA.md** (20 min)
3. 📖 Aprofunde nos fluxos específicos no **MAPEAMENTO_TECNICO_ACOPLAMENTO.md** (45 min)
4. 💻 Implemente testes usando exemplos do **GUIA_VISUAL_REFERENCIA.md**

### Para Arquitetos de Software:
1. 📖 Leia **MAPEAMENTO_TECNICO_ACOPLAMENTO.md** completo (45 min)
2. 🎨 Valide diagramas no **GUIA_VISUAL_REFERENCIA.md** (20 min)
3. 📄 Revise conclusões no **RESUMO_EXECUTIVO.md** (5 min)

---

## 📊 Principais Descobertas

### ✅ Pontos Fortes

1. **100% WordPress Native HTTP**
   - Usa apenas `wp_remote_post()` e `wp_remote_get()`
   - NUNCA usa cURL, Guzzle ou file_get_contents
   - Facilita mocking com Brain\Monkey

2. **Manipulação de Pedidos Consistente**
   - SEMPRE usa métodos `WC_Order`
   - NUNCA usa `update_post_meta()` direto
   - 17 metadados salvos por transação

3. **Logging Robusto**
   - WC_Logger com 4 níveis
   - 3 métodos de mascaramento de dados sensíveis
   - Logs em arquivos + order meta

4. **Código Bem Estruturado**
   - Classes finais que estendem `WC_Payment_Gateway`
   - Separação clara de responsabilidades
   - 9 hooks/filtros customizados para extensibilidade

### ⚠️ Pontos de Atenção

1. **Sistema de Confirmação: Polling, NÃO Webhook**
   - Cron job verifica PIX a cada minuto
   - Pode acumular muitos jobs em alta demanda
   - Considerar implementar webhooks reais

2. **REST Endpoints Públicos**
   - 4 endpoints sem `nonce` verification
   - `permission_callback => '__return_true'`
   - Podem ser abusados para DDoS ou info disclosure

3. **Filtros de Estorno Abertos**
   - `apply_filters('lkn_wc_cielo_credit_refund', ...)`
   - Qualquer plugin pode substituir lógica completa
   - Risco de estornos fraudulentos

4. **Performance**
   - Timeout de 120s pode bloquear PHP-FPM
   - Muitos cron jobs simultâneos em alta demanda

---

## 🧪 Estratégia de Testes

### Ferramentas Necessárias

```bash
composer require --dev phpunit/phpunit
composer require --dev brain/monkey
composer require --dev mockery/mockery
```

### Funções a Mockar (Brain\Monkey)

**WordPress Core (20+):**
- HTTP: `wp_remote_post()`, `wp_remote_get()`, `is_wp_error()`
- Sanitização: `sanitize_text_field()`, `wp_unslash()`, `esc_attr()`
- Options: `get_option()`, `update_option()`
- Cron: `wp_schedule_event()`, `wp_next_scheduled()`
- Hooks: `do_action()`, `apply_filters()`

**WooCommerce (10+):**
- Orders: `wc_get_order()`, `wc_get_orders()`
- Classes: `WC_Order`, `WC_Logger`

### Exemplos de Testes

Ver seção "6. Mapeamento de Mocks" no **GUIA_VISUAL_REFERENCIA.md** para:
- Código completo de mocks com Brain\Monkey
- Mocks de classes com Mockery
- Fixtures de resposta da API Cielo (sandbox)

---

## 📈 Estatísticas

| Métrica | Valor |
|---------|-------|
| Arquivos PHP analisados | 22 |
| Linhas de código do plugin | ~8.000 |
| Pontos de comunicação HTTP | 10 |
| Métodos WC_Order utilizados | 12+ |
| Hooks/Filtros customizados | 9 |
| REST endpoints | 4 |
| AJAX endpoints | 3 |
| Metadados por pedido | 17 |
| Casos de teste mapeados | 32 |
| **Linhas de documentação** | **2.458** |

---

## 🔗 Links Úteis

### Documentação Externa

- **Brain\Monkey**: https://giuseppe-mazzapica.gitbook.io/brain-monkey/
- **Mockery**: http://docs.mockery.io/en/latest/
- **PHPUnit**: https://phpunit.de/documentation.html
- **WooCommerce Unit Tests**: https://github.com/woocommerce/woocommerce/tree/trunk/tests
- **Cielo API Docs**: https://developercielo.github.io/manual/cielo-ecommerce

### Ferramentas

- **Composer**: https://getcomposer.org/
- **PHPUnit**: https://phpunit.de/
- **WP-CLI**: https://wp-cli.org/ (para testes de integração)

---

## 👥 Autores e Manutenção

**Mapeamento criado por:** Análise automatizada  
**Data:** 2026-03-14  
**Versão do Plugin:** 1.29.0  
**Repositório:** https://github.com/LinkNacional/lkn-wc-gateway-cielo

---

## 📝 Changelog da Documentação

### v1.0.0 (2026-03-14)
- ✅ Criação do mapeamento técnico completo
- ✅ Criação do resumo executivo
- ✅ Criação do guia visual com diagramas
- ✅ 32 casos de teste mapeados
- ✅ 10 pontos de comunicação HTTP identificados
- ✅ 9 hooks/filtros documentados
- ✅ 3 pontos de atenção de segurança destacados

---

## 📧 Contato

Para dúvidas ou sugestões sobre esta documentação:
- **Issues**: Abra uma issue no repositório do plugin
- **Pull Requests**: Contribuições são bem-vindas!

---

**Última atualização:** 2026-03-14  
**Versão da documentação:** 1.0.0
