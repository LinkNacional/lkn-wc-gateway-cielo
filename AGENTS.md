# AGENTS.md — Diretrizes Absolutas

Arquitetura, segurança, padrões de código e regras de comportamento do agente para o plugin **CIELO API PIX, credit card, debit payment for WooCommerce** e plugins satélites (PRO, faturas, etc.).

---

## 0. Regras de Comportamento do Agente

### 0.1 Caveman Mode — Respostas Telegráficas

**Regra absoluta:** Respostas devem ser mínimas, diretas e sem gordura.

- ✅ **"Feito. `process_subscription_payment()` adicionado em :112."**
- ✅ **"Corrigido. Nonce adicionado no `validate_fields()` de Débito."**
- ❌ **"Agora vou explicar detalhadamente o que foi feito. Primeiro, analisamos o arquivo X e identificamos que..."**
- ❌ **"Perfeito! Vou implementar essa funcionalidade para você. Deixe-me pensar sobre a melhor abordagem..."**
- ❌ Listas com `-` quando uma frase resolve
- ❌ Perguntas retóricas, empatia artificial, celebrações ("Ótimo!", "Perfeito!")
- ❌ Resumos do que "aprendemos" ou "descobrimos" antes de ir direto ao ponto

**Formato de entrega:**
- 1 parágrafo curto (máx 3 linhas) explicando **o que** mudou
- Bloco de código ou diff direto
- Sem narração passo a passo, sem floreios

**Exceção:** Análises e diagnósticos (como a análise de compatibilidade com WCS) justificam respostas detalhadas com tabelas e seções — foram **solicitadas** como análise, não como ação.

### 0.2 RTK — Rust Token Killer (Compressão de Logs)

**Regra absoluta:** Saída de terminal, logs, e stack traces NUNCA devem ser colados integralmente.

- ✅ **"Erro no `process_payment()`: `CardToken` ausente para pedido #123."**
- ✅ **"`npm run build` quebrou: 3 erros em `src/gateway.ts` — ver acima."**
- ✅ Mostrar APENAS a linha relevante do log com `context:0`
- ❌ Colar 80 linhas de stack trace ou saída de `wp_remote_post()`
- ❌ Logs do WooCommerce Status, debug logs brutos, saída de `var_export()`
- ❌ "O sistema retornou:" seguido de dump JSON de 200 linhas

**Como comprimir logs:**
1. Identifique a linha exata que contém o erro/mensagem relevante
2. Extraia APENAS essa linha com `search_content` + `context:0`
3. Descreva em 1 frase: "Erro X na linha Y: motivo Z"
4. Se precisar mostrar múltiplas linhas, use `range` preciso no `read_file`, nunca dump bruto

**Para análise de erros de build:**
- Filtrar com `grep` / `search_content` antes de trazer ao contexto
- Mostrar apenas: arquivo:linha + mensagem de erro
- Nunca colar o output completo de `npm run build` / `composer install` / `phpunit`

---

## 1. Arquitetura — Namespace, Autoload e Estrutura

### 1.1 PSR-4

O namespace raiz é `Lkn\WCCieloPaymentGateway\Includes\` mapeado para `includes/` no `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Lkn\\WCCieloPaymentGateway\\Includes\\": "includes/"
        }
    }
}
```

**Regra:** Toda classe em `includes/` deve declarar `namespace Lkn\WCCieloPaymentGateway\Includes;`. O nome do arquivo deve corresponder exatamente ao nome da classe (case-sensitive). Exemplo: `LknWCGatewayCieloCredit.php` → `class LknWCGatewayCieloCredit`.

Os testes seguem `Lkn\WCCieloPaymentGateway\Tests\` → `tests/`.

### 1.2 Estrutura de diretórios

```
lkn-wc-gateway-cielo.php          # Bootstrap (define constantes, registra activation hooks, chama run())
lkn-wc-gateway-cielo-file.php     # Autoloader + constantes + run()
includes/
  LknWCCieloPayment.php           # Core plugin class — registra gateways, endpoints, hooks
  LknWCGatewayCieloCredit.php     # Gateway Cartão de Crédito (legado, somente crédito)
  LknWCGatewayCieloDebit.php      # Gateway Débito + Crédito com 3DS (gateway primário atual)
  LknWcCieloPix.php               # Gateway PIX
  LknWCGatewayCieloGooglePay.php  # Gateway Google Pay
  LknWcCieloHelper.php            # Helpers estáticos (metadados, BIN, censura de log)
  LknWCGatewayCieloEndpoint.php   # Endpoint REST para consulta BIN
  LknWcCieloCreditBlocks.php      # Suporte a Block Checkout (Crédito)
  LknWcCieloDebitBlocks.php       # Suporte a Block Checkout (Débito)
  LknWcCieloPixBlocks.php         # Suporte a Block Checkout (PIX)
  LknWCGatewayCieloGooglePayBlocks.php
  templates/                      # Templates de checkout (default + modern layout)
resources/
  js/                             # Scripts frontend/admin
  css/                            # Estilos frontend/admin
tests/
  Unit/
vendor/
```

### 1.3 SOLID

**S — Single Responsibility:**
- Cada gateway (`LknWCGatewayCieloCredit`, `LknWCGatewayCieloDebit`) cuida APENAS do fluxo de pagamento daquele meio.
- `LknWcCieloHelper` centraliza funções compartilhadas (salvar metadados, identificar bandeira, censurar logs).
- `LknWCCieloPayment` é o orquestrador — registra os gateways no WooCommerce e conecta hooks, mas não contém lógica de pagamento.

**O — Open/Closed:**
- Os gateways são `final` e **não devem ser estendidos**. Extensibilidade é via **hooks e filtros** (ver seção 1.4), não via herança.
- Novas features entram por filtros (`lkn_wc_cielo_debit_add_support`, `lkn_wc_cielo_process_body`), nunca por override de classes.

**L — Liskov Substitution:**
- Todos os gateways estendem `WC_Payment_Gateway` e respeitam seu contrato: implementam `process_payment()`, `validate_fields()`, `payment_fields()`, `init_form_fields()`.

**I — Interface Segregation:**
- Nenhuma interface customizada ainda existe no projeto. Se surgir necessidade (ex: contrato para gateways que suportam tokenização), criar interface enxuta com métodos específicos, não uma interface monolítica.

**D — Dependency Inversion:**
- O plugin gratuito **não deve depender diretamente do PRO**. A comunicação é unidirecional: Free expõe hooks → PRO consome.
- Use `class_exists()` com o namespace do PRO para verificações condicionais (ex: `LknCieloApiProLicenseHelper`), mas nunca chame métodos do PRO diretamente sem guarda.

### 1.4 Padrão de delegação via Hooks (gateway → PRO)

Este é o mecanismo central de extensibilidade. Toda funcionalidade que o PRO implementa deve ser exposta pelo Free como um hook:

```
┌─────────────────────────────────────────────────────────┐
│ FREE plugin                                             │
│                                                         │
│  Método stub (process_subscription_payment)             │
│    ↓                                                    │
│  do_action('lkn_wc_cielo_{gateway}_scheduled_           │
│            subscription_payment', $amount, $order, ...)  │
│                                                         │
│  Filtro de suporte a features:                          │
│  apply_filters('lkn_wc_cielo_{gateway}_add_support',    │
│                $this->supports)                          │
│                                                         │
│  Filtro de body pré-API:                                │
│  apply_filters('lkn_wc_cielo_process_body',             │
│                $body, $_POST, $order_id)                 │
└──────────────────────┬──────────────────────────────────┘
                       │ hooks (ações e filtros)
┌──────────────────────▼──────────────────────────────────┐
│ PRO plugin (lkn-cielo-api-pro)                          │
│                                                         │
│  add_action('lkn_wc_cielo_{...}_subscription_payment')  │
│  add_filter('lkn_wc_cielo_{gateway}_add_support', ...)  │
│  add_filter('lkn_wc_cielo_process_body', ...)           │
└─────────────────────────────────────────────────────────┘
```

**Regras de nomenclatura de hooks:**
- Prefixo: `lkn_wc_cielo_`
- Sufixo com escopo do gateway: `_credit_*`, `_debit_*`, `_pix_*`
- Sufixo sem escopo quando genérico: `lkn_wc_cielo_process_body`, `lkn_wc_cielo_convert_amount`
- **Nunca reuse o mesmo hook entre gateways diferentes** — cada gateway tem seu próprio namespace de hooks para evitar colisão entre plugins satélites.

---

## 2. Segurança

### 2.1 Sanitização de superglobais

**Toda leitura de `$_POST`, `$_GET`, `$_REQUEST` deve seguir este padrão:**

```php
// ✅ CORRETO — isset() + sanitize_text_field + wp_unslash
$cardNum = isset($_POST['lkn_ccno'])
    ? sanitize_text_field(wp_unslash($_POST['lkn_ccno']))
    : '';

// ✅ CORRETO — int via sanitize + cast
$installments = (int) (isset($_POST['lkn_cc_installments'])
    ? sanitize_text_field(wp_unslash($_POST['lkn_cc_installments']))
    : 1);

// ✅ CORRETO — campo booleano via POST
$saveCard = isset($_POST['lkn_save_debit_credit_card'])
    && $_POST['lkn_save_debit_credit_card'] === '1';
```

**Regra:** `sanitize_text_field(wp_unslash(...))` é o padrão para strings. Para inteiros, sempre faça cast `(int)` após sanitização.

### 2.2 Nonce Verification — obrigatório e uniforme

Todo `process_payment()` e `validate_fields()` deve verificar nonce:

```php
// ✅ CORRETO — padrão obrigatório
$nonceInactive = $this->get_option('nonce_compatibility', 'no');
$nonce = isset($_POST['nonce_lkn_cielo_{gateway}'])
    ? sanitize_text_field(wp_unslash($_POST['nonce_lkn_cielo_{gateway}']))
    : '';

if (!wp_verify_nonce($nonce, 'nonce_lkn_cielo_{gateway}') && 'no' === $nonceInactive) {
    $this->log->log('error', 'Nonce verification failed.', array('source' => 'woocommerce-cielo-{gateway}'));
    throw new Exception(esc_attr(__('Nonce verification failed, try reloading the page.', 'lkn-wc-gateway-cielo')));
}
```

**Regras:**
- O nonce action deve ser idêntico ao nome do campo: `nonce_lkn_cielo_debit` → `wp_verify_nonce($nonce, 'nonce_lkn_cielo_debit')`
- O campo nonce deve ser renderizado no template com `wp_create_nonce('nonce_lkn_cielo_{gateway}')`
- A opção `nonce_compatibility` permite bypass em ambientes problemáticos — **sempre inclua essa válvula de escape**

### 2.3 Output Escaping

```php
// ✅ CORRETO — escaping no contexto devido
esc_html_e('Payment completed successfully.', 'lkn-wc-gateway-cielo');  // HTML
esc_attr($value);                                                        // Atributo HTML
esc_url($url);                                                           // URL
wp_kses_post($html);                                                     // HTML com tags permitidas

// ✅ CORRETO — tradução + escaping combinados
throw new Exception(esc_attr(__('Nonce verification failed.', 'lkn-wc-gateway-cielo')));

// ✅ CORRETO — tradução em notas de pedido
$order->add_order_note(
    '[' . $this->id . '] ' .
    __('Payment completed successfully.', 'lkn-wc-gateway-cielo') .
    ' ' . $responseDecoded->Payment->PaymentId
);
```

### 2.4 Dados sensíveis de cartão

**Proibido:**
- Logar número completo do cartão, CVV, ou token de autenticação mesmo em modo debug
- Armazenar CVV em banco de dados (user_meta, post_meta, etc.)

**Obrigatório:**
- Censurar número do cartão nos logs: `substr($cardNumber, 0, 6) . '******' . substr($cardNumber, -4)`
- Remover CVV dos logs antes de salvar: `unset($orderLogsArray['body']['Payment']['CreditCard']['SecurityCode'])`
- Censurar MerchantId e MerchantKey nos logs: `LknWcCieloHelper::censorString($str, 10)`
- O campo de CVV (`lkn_*_cvc`) jamais pode ser armazenado em `post_meta` ou `user_meta` persistente — só transita em memória durante o `process_payment()`

### 2.5 Verificação de permissão em endpoints REST

Endpoints administrativos devem verificar `current_user_can('manage_woocommerce')` antes de processar:

```php
if (!current_user_can('manage_woocommerce')) {
    wp_send_json_error(array('message' => 'Unauthorized'), 403);
}
```

---

## 3. Padrões de Código WordPress

### 3.1 Gateway Registration

Gateways são registrados no hook `woocommerce_payment_gateways`:

```php
add_filter('woocommerce_payment_gateways', function($gateways) {
    $gateways[] = LknWCGatewayCieloDebit::class;
    return $gateways;
});
```

### 3.2 Text Domain e Internacionalização

Text domain único: **`lkn-wc-gateway-cielo`**

```php
// ✅ CORRETO
__('Payment processed by Cielo API 3.0', 'lkn-wc-gateway-cielo');
esc_html__('Cielo - Credit Card', 'lkn-wc-gateway-cielo');
```

**Text domains do PRO** são separados e não devem ser usados no Free:
- `lkn-wc-gateway-cielo-pro` — PRO strings
- `lkn-wc-gateway-cielo` — Free strings

### 3.3 Constantes do plugin

Todas as constantes de caminho/versão são definidas em `lkn-wc-gateway-cielo-file.php` com prefixo `LKN_WC_CIELO_`:

| Constante | Valor |
|---|---|
| `LKN_WC_CIELO_VERSION` | Versão SemVer |
| `LKN_WC_CIELO_FILE` | Caminho absoluto do bootstrap |
| `LKN_WC_GATEWAY_CIELO_DIR` | Diretório raiz com trailing `/` |
| `LKN_WC_GATEWAY_CIELO_DIR_URL` | URL do diretório do plugin |
| `LKN_WC_GATEWAY_CIELO_BASENAME` | Basename para WordPress (`lkn-wc-gateway-cielo/lkn-wc-gateway-cielo.php`) |
| `LKN_WC_CIELO_MIN_WC_VERSION` | Versão mínima do WooCommerce (`5.0.0`) |

**Nunca** defina constantes com o mesmo prefixo sem verificar `if (!defined(...))` antes.

### 3.4 Logs

Usar `WC_Logger` com `source` identificando o gateway:

```php
$this->log->log('error', $message, array('source' => 'woocommerce-cielo-debit'));
$this->log->log('error', $message, array('source' => 'woocommerce-cielo-credit'));
$this->log->log('error', $message, array('source' => 'woocommerce-cielo-pix'));
```

**Regras:**
- Log condicional: `if ('yes' === $this->get_option('debug')) { ... }`
- Nunca logar dados sensíveis (ver seção 2.4)
- Sempre censurar antes de logar

### 3.5 Bloco de cabeçalho padrão

Todo arquivo PHP no plugin deve começar com:

```php
<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
```

Arquivos no namespace devem declarar o namespace imediatamente após.

---

## 4. Anti-padrões — o que NUNCA repetir

### 4.1 ❌ Opção de gateway inexistente com espaço no default

```php
// ❌ ERRADO — 'disabled ' tem espaço, a opção nem existe no init_form_fields()
$saveCard = $this->get_option('save_card_token', 'disabled ') == 'required';
```

**Por que é ruim:** O método `get_option()` do `WC_Payment_Gateway` só retorna valores de opções registradas em `init_form_fields()`. Se a opção não existe, o default é sempre usado. Espaço no default quebra qualquer comparação futura. Se a opção é exclusiva do PRO, ela deve existir como campo `readonly` visível no Free.

### 4.2 ❌ Variável indefinida como condição

```php
// ❌ ERRADO — $save_token nunca é definido neste escopo (só existe no gateway de Crédito)
$saveCard = ($this->get_option('save_card_token', 'disabled ') == 'required' || $save_token == "1");
```

**Por que é ruim:** PHP emite notice e o valor é sempre `null`. A condição nunca é `true` silenciosamente — o bug fica oculto.

### 4.3 ❌ Usar `WC_Subscriptions_Order` em vez de `wcs_order_contains_subscription()`

```php
// ❌ ERRADO — classe removida no WCS 3.0+
if (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($order_id)) {
```

**Por que é ruim:** `WC_Subscriptions_Order` foi depreciada no WCS 2.0 e removida em versões recentes. O `class_exists()` retorna `false`, o bloco é silenciosamente ignorado, e a detecção de assinatura falha sem erro visível.

**Correto:**
```php
if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
```

### 4.4 ❌ Hardcodar `CardOnFile.Usage` como `'First'`

```php
// ❌ ERRADO — nunca permite cobrança subsequente
'CardOnFile' => array(
    'Usage' => 'First'  // hardcoded
)
```

**Por que é ruim:** Para cobranças recorrentes, a API Cielo exige `'Subsequent'` com `CardToken` nas renovações. Hardcodar `'First'` significa que mesmo com token salvo, a Cielo rejeitará a transação como duplicata.

A lógica deve ser: se é pedido inicial de assinatura → `'First'` com `SaveCard: true`. Se é renovação → `'Subsequent'` com `CardToken`.

### 4.5 ❌ Hook de subscription com mesmo nome entre gateways

```php
// ❌ ERRADO — se dois gateways disparam a mesma action, plugins satélites
// não conseguem distinguir qual gateway originou a cobrança
do_action('lkn_wc_cielo_scheduled_subscription_payment', ...);
```

**Correto:** Cada gateway tem sua própria action:
```php
// Credit
do_action('lkn_wc_cielo_scheduled_subscription_payment', ...);
// Debit
do_action('lkn_wc_cielo_debit_scheduled_subscription_payment', ...);
```

### 4.6 ❌ `use` de classe inexistente sem guarda

```php
// ❌ ERRADO — importar classe que pode não existir sem verificação
use WC_Subscriptions_Order;
use LknWc\WcInvoicePayment\Includes\WcPaymentInvoiceSubscription;
```

Se a classe não existe e for referenciada em código não-guardado, causa fatal error. `use` statements em PHP não causam erro por si sós, mas induzem ao uso sem verificação. Se a classe é de um plugin externo, sempre use `class_exists()` ou `function_exists()` antes de referenciá-la.

---

## 5. Regras para Adição de Novos Gateways ou Features

### 5.1 Checklist de segurança para novo gateway

- [ ] `process_payment()` verifica nonce no início
- [ ] `validate_fields()` verifica nonce
- [ ] Todo `$_POST` é lido com `sanitize_text_field(wp_unslash(...))`
- [ ] Logs censuram número do cartão e removem CVV
- [ ] Logs censuram MerchantId e MerchantKey
- [ ] Textos de saída usam `esc_attr()` / `esc_html__()` / `esc_url()`
- [ ] Text domain é `lkn-wc-gateway-cielo`
- [ ] Constantes usam prefixo `LKN_WC_CIELO_` com guarda `if (!defined(...))`

### 5.2 Checklist de hooks para compatibilidade com PRO

Se o gateway novo deve suportar assinaturas/tokenização:

- [ ] Filtro `lkn_wc_cielo_{gateway}_add_support` para features
- [ ] Action `woocommerce_scheduled_subscription_payment_{gateway_id}` registrada
- [ ] Método `process_subscription_payment()` que dispara `do_action('lkn_wc_cielo_{gateway}_scheduled_subscription_payment', ...)`
- [ ] Filtro `lkn_wc_cielo_process_body` aplicado ao corpo da requisição Cielo
- [ ] Action `lkn_wc_cielo_change_order_status` disparada após pagamento bem-sucedido
- [ ] Filtro `lkn_wc_cielo_convert_amount` aplicado à conversão de moeda

### 5.3 Checklist de namespaces de hooks

Antes de criar um hook, verifique se:
- [ ] O nome do hook contém o identificador do gateway (`_credit_`, `_debit_`, `_pix_`) quando é específico
- [ ] Nenhum outro gateway existente usa o mesmo nome de hook
- [ ] O hook está documentado no MAPEAMENTO_TECNICO_ACOPLAMENTO.md

---

## 6. Testes

### 6.1 Framework

PHPUnit 9.5+ com Brain\Monkey para mocking de funções WordPress.

```bash
composer test        # Suite completa
composer test:unit   # Apenas testes unitários
```

### 6.2 Convenções

- Namespace de testes: `Lkn\WCCieloPaymentGateway\Tests\`
- Diretório: `tests/Unit/`
- Mockar `apply_filters` para retornar o valor padrão quando o filtro não tem callback registrado
- Não mockar classes `final` — testar via integração

---

*Última atualização: análise da v1.34.0 — 2026. Este documento reflete os padrões observados no código e as correções de anti-padrões identificados.*
