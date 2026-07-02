# 🔄 Guia de Migração - Correções de Segurança v1.29.1

**Para:** Desenvolvedores que integram com o WooCommerce Cielo Payment Gateway  
**Data:** 2026-03-14  
**Versão:** 1.29.0 → 1.29.1

---

## 📋 Resumo das Mudanças

A versão 1.29.1 introduz correções de segurança críticas que podem afetar integrações que utilizam:
1. **REST API endpoints** do plugin
2. **Filtros de refund** customizados

---

## 🔐 REST API - Mudanças Obrigatórias

### Endpoints Afetados

| Endpoint | Mudança | Ação Necessária |
|----------|---------|-----------------|
| `/checkCard` | Requer nonce | Adicionar X-WP-Nonce header |
| `/getAcessToken` | Requer nonce | Adicionar X-WP-Nonce header |
| `/getCardBrand` | Requer nonce | Adicionar X-WP-Nonce header |
| `/clearOrderLogs` | Requer capability | Usuário admin com manage_woocommerce |

### ✅ Se você usa JavaScript padrão do WordPress

**Nenhuma ação necessária!**

O WordPress automaticamente envia o nonce no header `X-WP-Nonce` em todas as requisições REST API.

**Exemplo (funcionará automaticamente):**
```javascript
fetch('/wp-json/lknWCGatewayCielo/getCardBrand?number=411111', {
    method: 'GET',
    credentials: 'include' // Importante: envia cookies de autenticação
})
.then(response => response.json())
.then(data => console.log(data));
```

### ⚠️ Se você usa cURL ou HTTP client externo

**Ação necessária:** Adicionar nonce nas requisições

#### Opção 1: Via Header (Recomendado)

```bash
# 1. Obter nonce do WordPress
NONCE=$(curl -s -c cookies.txt "https://seu-site.com/wp-admin/" | grep -o 'wpApiSettings":{"root":"[^"]*","nonce":"[^"]*' | sed 's/.*nonce":"//' | sed 's/".*//')

# 2. Usar o nonce nas requisições
curl -X GET "https://seu-site.com/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111" \
  -H "X-WP-Nonce: $NONCE" \
  -b cookies.txt
```

#### Opção 2: Via Parâmetro

```bash
curl -X GET "https://seu-site.com/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111&_wpnonce=$NONCE" \
  -b cookies.txt
```

#### PHP com wp_remote_post

```php
// Obter nonce atual
$nonce = wp_create_nonce('wp_rest');

// Fazer requisição
$response = wp_remote_get(
    rest_url('lknWCGatewayCielo/checkCard'),
    array(
        'headers' => array(
            'X-WP-Nonce' => $nonce
        )
    )
);
```

### 🔒 clearOrderLogs - Mudança Crítica

**Antes (v1.29.0):**
```javascript
// Qualquer um podia limpar logs
fetch('/wp-json/lknWCGatewayCielo/clearOrderLogs', {
    method: 'DELETE'
});
```

**Agora (v1.29.1):**
```javascript
// Requer usuário autenticado com manage_woocommerce
fetch('/wp-json/lknWCGatewayCielo/clearOrderLogs', {
    method: 'DELETE',
    credentials: 'include', // Obrigatório
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce // Nonce do admin
    }
});
```

**Verificação de permissão:**
```php
// Se o usuário atual não tiver manage_woocommerce, retorna:
{
    "code": "rest_forbidden",
    "message": "You do not have permission to access this resource.",
    "data": {
        "status": 403
    }
}
```

---

## 🛡️ Filtros de Refund - Mudanças Importantes

### Filtros Afetados

- `lkn_wc_cielo_credit_refund`
- `lkn_wc_cielo_debit_refund`
- `lkn_wc_cielo_google_pay_refund`

### ✅ Se seu filtro é executado no contexto administrativo

**Nenhuma ação necessária!**

Refunds normalmente são processados no admin do WooCommerce por usuários com `manage_woocommerce`, então tudo funcionará automaticamente.

**Exemplo (funcionará automaticamente):**
```php
add_filter('lkn_wc_cielo_credit_refund', function($url, $merchantId, $merchantSecret, $order_id, $amount) {
    // Seu código de refund customizado
    // Usuário admin já tem manage_woocommerce
    
    return wp_remote_post($custom_url, $custom_args);
}, 10, 5);
```

### ⚠️ Se seu filtro é executado em contexto não-administrativo

**Cenário raro:** Se você processa refunds via cronjob, webhook ou frontend.

**Problema:**
```php
// Antes (v1.29.0): Funcionava em qualquer contexto
add_filter('lkn_wc_cielo_credit_refund', function($url, $merchantId, $merchantSecret, $order_id, $amount) {
    return wp_remote_post($custom_url, $custom_args);
}, 10, 5);

// Agora (v1.29.1): Bloqueado se usuário não tiver manage_woocommerce
```

**Solução 1: Executar no contexto administrativo**
```php
// Em webhook ou cronjob, temporariamente elevar permissões
add_filter('lkn_wc_cielo_credit_refund', function($url, $merchantId, $merchantSecret, $order_id, $amount) {
    // Validar que é contexto seguro (webhook verificado, etc.)
    if (!is_webhook_valid()) {
        return new WP_Error('invalid_webhook', 'Invalid webhook signature');
    }
    
    // Temporariamente definir usuário como admin para o refund
    $admin_users = get_users(array('role' => 'administrator', 'number' => 1));
    wp_set_current_user($admin_users[0]->ID);
    
    return wp_remote_post($custom_url, $custom_args);
}, 10, 5);
```

**Solução 2: Não usar filtro, chamar API diretamente**
```php
// Em vez de usar filtro, chamar sua API de refund diretamente
function my_custom_refund($order_id, $amount) {
    // Validar permissões manualmente
    if (!current_user_can('manage_woocommerce')) {
        return false;
    }
    
    // Processar refund sem usar filtro do plugin
    $response = wp_remote_post($custom_url, $custom_args);
    
    return $response;
}
```

### 📝 Logging de Auditoria

**Nova feature:** Uso de filtros é logado automaticamente (quando debug ativo)

```php
// Habilitar logging no gateway
add_filter('option_woocommerce_lkn_cielo_credit_settings', function($options) {
    $options['debug'] = 'yes';
    return $options;
});

// Agora, quando seu filtro for usado:
// Log será criado em: wp-content/uploads/wc-logs/woocommerce-cielo-credit-security-{date}.log
// Mensagem: "Refund filter was used for order: 123"
```

---

## 🧪 Testando Suas Integrações

### Checklist de Testes

#### REST API

- [ ] **Frontend JavaScript**
  - [ ] checkCard funciona no checkout
  - [ ] getCardBrand funciona no checkout
  - [ ] getAcessToken funciona para débito
  - [ ] Verificar console do navegador para erros 403

- [ ] **Scripts externos (cURL, Python, etc.)**
  - [ ] Adicionar X-WP-Nonce header
  - [ ] Testar com nonce válido
  - [ ] Testar com nonce inválido (deve retornar 403)

- [ ] **clearOrderLogs**
  - [ ] Funciona como admin
  - [ ] Falha como usuário comum (403)
  - [ ] Verificar logs em wp-content/uploads/wc-logs/

#### Filtros de Refund

- [ ] **Contexto administrativo**
  - [ ] Refund funciona normalmente
  - [ ] Verificar que filtro é executado
  - [ ] Verificar logs de auditoria (se debug ativo)

- [ ] **Contexto não-administrativo**
  - [ ] Se usar cronjob/webhook, implementar solução
  - [ ] Testar que refund ainda funciona
  - [ ] Verificar logs de tentativas bloqueadas

---

## 🐛 Problemas Comuns e Soluções

### Problema 1: Erro 403 em checkCard/getCardBrand

**Erro:**
```json
{
    "code": "rest_forbidden",
    "message": "Invalid security token.",
    "data": {
        "status": 403
    }
}
```

**Causa:** Nonce não está sendo enviado ou é inválido

**Solução:**
```javascript
// Certifique-se de que credentials: 'include' está presente
fetch('/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111', {
    method: 'GET',
    credentials: 'include' // Essencial!
})
```

### Problema 2: clearOrderLogs retorna 403 para admin

**Erro:**
```json
{
    "code": "rest_forbidden",
    "message": "You do not have permission to access this resource.",
    "data": {
        "status": 403
    }
}
```

**Causa:** Usuário não está autenticado corretamente

**Solução:**
```javascript
// Verificar que wpApiSettings está disponível
console.log(wpApiSettings); // Deve mostrar objeto com nonce

// Adicionar nonce explicitamente
fetch('/wp-json/lknWCGatewayCielo/clearOrderLogs', {
    method: 'DELETE',
    credentials: 'include',
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce // Nonce do WordPress
    }
});
```

### Problema 3: Filtro de refund não funciona em cronjob

**Erro no log:**
```
Refund filter used without proper permissions for order: 123
Order refund blocked: insufficient permissions
```

**Causa:** Cronjob não tem contexto de usuário com manage_woocommerce

**Solução 1 - Definir usuário temporariamente:**
```php
// No cronjob
add_action('my_custom_cron_job', function() {
    // Obter primeiro admin
    $admin_users = get_users(array(
        'role' => 'administrator',
        'number' => 1
    ));
    
    if (!empty($admin_users)) {
        wp_set_current_user($admin_users[0]->ID);
    }
    
    // Agora processar refund
    // Filtro terá permissões adequadas
});
```

**Solução 2 - Chamar API diretamente:**
```php
// Não usar filtro, fazer requisição HTTP direta
add_action('my_custom_cron_job', function() {
    $order = wc_get_order(123);
    
    // Fazer refund via API da Cielo diretamente
    $response = wp_remote_post(
        'https://api.cieloecommerce.cielo.com.br/1/sales/' . $tid . '/void',
        array(
            'headers' => array(
                'MerchantId' => $merchant_id,
                'MerchantKey' => $merchant_key
            )
        )
    );
});
```

---

## 📞 Suporte

### Precisa de Ajuda?

1. **Documentação completa:** Ver SECURITY_FIXES.md
2. **Issues GitHub:** https://github.com/LinkNacional/lkn-wc-gateway-cielo/issues
3. **Email:** contato@linknacional.com.br

### Reportar Bugs de Segurança

Se encontrar problemas de segurança:
- **NÃO** abra issue público
- Email: security@linknacional.com.br
- Resposta em até 48 horas

---

## ✅ Checklist Final

Antes de atualizar para v1.29.1 em produção:

- [ ] Ler este guia completamente
- [ ] Testar REST endpoints em ambiente de desenvolvimento
- [ ] Testar filtros customizados (se usar)
- [ ] Verificar que checkout funciona normalmente
- [ ] Verificar que refunds funcionam para admins
- [ ] Revisar logs de auditoria
- [ ] Fazer backup do banco de dados
- [ ] Atualizar em produção
- [ ] Monitorar logs por 24-48h

---

**Última atualização:** 2026-03-14  
**Versão do guia:** 1.0  
**Status:** ✅ Pronto para produção
