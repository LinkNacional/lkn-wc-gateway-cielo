#!/usr/bin/env bash
# check-security.sh — Varredura de sanitização e validação em código PHP
# Uso: ./check-security.sh [arquivo|diretório]
# Se nenhum path for passado, varre includes/ e bootstrap files

set -euo pipefail

TARGET="${1:-includes/ lkn-wc-gateway-cielo.php lkn-wc-gateway-cielo-file.php}"
ISSUES=0
TOTAL_CHECKS=10
PASSED_CHECKS=0
RED="\033[1;31m"
GREEN="\033[1;32m"
YELLOW="\033[1;33m"
RESET="\033[0m"

header() { echo -e "\n${YELLOW}═══ $1 ═══${RESET}"; }
fail()  { echo -e "  ${RED}✘${RESET} $1"; ISSUES=$((ISSUES + 1)); }
pass()  { echo -e "  ${GREEN}✔${RESET} $1"; }

# Whitelists
# hooks compartilhados entre gateways — não precisam de sufixo _credit/_debit
SHARED_HOOKS="lkn_wc_cielo_gateway_icon|\
lkn_wc_cielo_get_custom_configs|\
lkn_wc_cielo_convert_amount|\
lkn_wc_cielo_process_body|\
lkn_wc_cielo_change_order_status|\
lkn_wc_cielo_update_order|\
lkn_wc_cielo_get_cardholder_name|\
lkn_wc_cielo_set_installment_limit|\
lkn_wc_cielo_set_installments|\
lkn_wc_cielo_set_installment_min_amount|\
lkn_wc_cielo_js_3ds_args|\
lkn_wc_cielo_remove_cardholder_name_3ds|\
lkn_wc_cielo_zero_auth"

# ------------------------------------------------------------------
# 1. Superglobais usadas como VALOR sem wp_unslash
#    Ignora: isset($_X['key']), empty($_X['key'])
#    Captura: $var = $_X['key'], $_X['key'] === '...', func($_X['key'])
# ------------------------------------------------------------------
ISSUES=0
header "1. Superglobais lidas sem wp_unslash + sanitize"
while IFS= read -r line; do
    fail "$line"
done < <(
    grep -rn '\$_\(POST\|GET\|REQUEST\)\[' $TARGET --include="*.php" 2>/dev/null \
    | grep -v 'wp_unslash' \
    | grep -v 'sanitize_text_field\|sanitize_email\|sanitize_key\|sanitize_title\|absint\|intval\|floatval\|sanitize_url\|wp_kses\|json_decode\|base64_decode\|trim(' \
    `# Ignora isset/empty puros — só verificam existência, não usam valor` \
    | grep -vE '(if|elseif|while|\|\||&&)\s*\(?\s*!?\s*(isset|empty)\s*\(\s*\$_(POST|GET|REQUEST)' \
    `# Ignora atribuição À superglobal (LHS): $_POST['x'] = 'valor'; (não é leitura)` \
    | grep -vE '\$_POST\[[^]]+\]\s*=' \
    `# Ignora acesso em array sem usar valor (ex: unset, array_keys)` \
    | grep -v 'unset\s*(\s*\$_\|array_key_exists\s*(\s*\$_\|wp_verify_nonce\s*(' \
    `# Ignora linhas com check-security-ignore` \
    | grep -v '// check-security-ignore' \
    | grep -v 'wpcs: csrf ok' \
    || true
)

if [ $ISSUES -eq 0 ]; then
    pass "Todas as leituras de superglobais com sanitização"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR1=$ISSUES

# ------------------------------------------------------------------
# 2. Nonce verification ausente em process_payment / validate_fields
# ------------------------------------------------------------------
ISSUES=0
header "2. Métodos críticos sem wp_verify_nonce"
for func_pattern in "function process_payment" "function validate_fields"; do
    while IFS= read -r file_match; do
        file="${file_match%%:*}"
        lineno="${file_match#*:}"
        lineno="${lineno%%:*}"  # Pega só o número da linha, descarta coluna
        
        # Extrai o bloco da função até a próxima declaração de função ou EOF
        block=$(sed -n "${lineno},/^\s*\(public\|private\|protected\|final\|abstract\)\s*function/p" "$file" 2>/dev/null || true)
        
        if [ -z "$block" ] || ! echo "$block" | grep -q 'wp_verify_nonce'; then
            fail "$file:$lineno — $func_pattern sem wp_verify_nonce"
        fi
    done < <(grep -n "$func_pattern" $TARGET --include="*.php" 2>/dev/null || true)
done

if [ $ISSUES -eq 0 ]; then
    pass "Todos os entry points com nonce"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR2=$ISSUES

# ------------------------------------------------------------------
# 3. Anti-padrão: WC_Subscriptions_Order
# ------------------------------------------------------------------
ISSUES=0
header "3. Classe obsoleta WC_Subscriptions_Order"
while IFS= read -r line; do
    fail "$line — usar wcs_order_contains_subscription()"
done < <(grep -rn 'WC_Subscriptions_Order' $TARGET --include="*.php" 2>/dev/null || true)

if [ $ISSUES -eq 0 ]; then
    pass "Nenhum uso de WC_Subscriptions_Order"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR3=$ISSUES

# ------------------------------------------------------------------
# 4. CardOnFile.Usage hardcoded
# ------------------------------------------------------------------
ISSUES=0
header "4. CardOnFile.Usage hardcoded como 'First'"
while IFS= read -r line; do
    fail "$line — extrair para variável condicional (First vs Subsequent)"
done < <(grep -rn "Usage.*=>.*'First'" $TARGET --include="*.php" 2>/dev/null || true)

if [ $ISSUES -eq 0 ]; then
    pass "CardOnFile.Usage não hardcoded"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR4=$ISSUES

# ------------------------------------------------------------------
# 5. save_card_token com trailing space
# ------------------------------------------------------------------
ISSUES=0
header "5. Trailing space em default de get_option"
while IFS= read -r line; do
    fail "$line — corrigir: 'disabled' (sem espaço)"
done < <(grep -rn "get_option.*'disabled '" $TARGET --include="*.php" 2>/dev/null || true)

if [ $ISSUES -eq 0 ]; then
    pass "Nenhum trailing space em defaults"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR5=$ISSUES

# ------------------------------------------------------------------
# 6. Dados de cartão em logs
# ------------------------------------------------------------------
ISSUES=0
header "6. Vazamento de dados de cartão em logs"
while IFS= read -r line; do
    if echo "$line" | grep -q 'substr.*CardNumber\|unset.*SecurityCode\|censorString\|censur\|Sanitize'; then
        continue
    fi
    fail "$line — possível vazamento de número/CVV em log"
done < <(grep -rn '\$orderLogs\|->log.*->log(' $TARGET --include="*.php" 2>/dev/null \
    | grep -i 'cvv\|securityCode\|cardNumber\|CardNumber' \
    | grep -v 'censor\|****\|Sanitize\|\/\/' \
    || true)

if [ $ISSUES -eq 0 ]; then
    pass "Logs sem dados sensíveis de cartão"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR6=$ISSUES

# ------------------------------------------------------------------
# 7. Echo/print sem escaping (fora de templates)
# ------------------------------------------------------------------
ISSUES=0
header "7. echo/print sem escaping (fora de templates)"
for dirfile in $TARGET; do
    if [ -d "$dirfile" ]; then
        search_path="$dirfile"
    elif [ -f "$dirfile" ]; then
        search_path="$dirfile"
    else
        continue
    fi
    while IFS= read -r line; do
        file_path="${line%%:*}"
        [ -d "$file_path" ] && continue
        echo "$file_path" | grep -q 'templates/' && continue
        echo "$line" | grep -q 'wp_kses\|esc_html\|esc_attr\|esc_url\|wp_json_encode\|json_encode\|var_export\b\|sprintf\|printf\|checked\|selected' && continue
        if echo "$line" | grep -qE "echo\s+['\"].*<"; then
            fail "$line — usar wp_kses_post ou esc_html"
        fi
    done < <(grep -rn '\<echo\>' "$search_path" --include="*.php" 2>/dev/null | grep -v 'templates/' || true)
done

if [ $ISSUES -eq 0 ]; then
    pass "Saídas com escaping adequado"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR7=$ISSUES

# ------------------------------------------------------------------
# 8. Hook sem namespace de gateway (apenas hooks gateway-específicos)
# ------------------------------------------------------------------
ISSUES=0
header "8. Hook específico de gateway sem namespace"
while IFS= read -r line; do
    file_path="${line%%:*}"
    hook_name=$(echo "$line" | grep -oP "(?<=['\"])lkn_wc_cielo_[^'\"]*" | head -1)
    [ -z "$hook_name" ] && continue
    
    # Pula hooks compartilhados (cross-gateway por design)
    if echo "$hook_name" | grep -qE "$SHARED_HOOKS"; then
        continue
    fi
    
    # Se está no gateway Debit e hook não tem _debit_ nem é do PIX
    if echo "$file_path" | grep -q 'Debit' && ! echo "$hook_name" | grep -q '_debit\|_dc_\|debit_add_support\|debit_refund'; then
        fail "$file_path — hook '$hook_name' deve ter namespace do gateway (ex: _debit)"
    fi
done < <(grep -rn "do_action\|apply_filters" $TARGET --include="*.php" 2>/dev/null \
    | grep "lkn_wc_cielo_" \
    || true)

if [ $ISSUES -eq 0 ]; then
    pass "Namespaces de hooks corretos"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR8=$ISSUES

# ------------------------------------------------------------------
# 9. Endpoint REST/AJAX sem verificação de permissão
# ------------------------------------------------------------------
ISSUES=0
header "9. Endpoint sem current_user_can"
while IFS= read -r line; do
    file_path="${line%%:*}"
    if ! grep -q 'current_user_can\|permission_callback\|check_ajax_referer\|wp_verify_nonce' "$file_path"; then
        fail "$line — sem verificação de permissão/nonce"
    fi
done < <(grep -rn 'register_rest_route\|wp_ajax_\|wp_ajax_nopriv_' $TARGET --include="*.php" 2>/dev/null \
    | grep -v 'permission_callback' \
    || true)

if [ $ISSUES -eq 0 ]; then
    pass "Endpoints com verificação de permissão"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR9=$ISSUES

# ------------------------------------------------------------------
# 10. Variável de superglobal usada como RHS (leitura) sem isset/sanitize
# ------------------------------------------------------------------
ISSUES=0
header "10. Leitura de superglobal sem isset/sanitize"
while IFS= read -r line; do
    file_path="${line%%:*}"
    [ -d "$file_path" ] && continue
    # Pula se é atribuição À superglobal (LHS = $_POST['x'] = valor)
    echo "$line" | grep -qE '\$_POST\[[^]]+\]\s*=' && continue
    # Pula se já tem isset, sanitize, etc
    echo "$line" | grep -q 'isset\|sanitize\|wp_unslash\|intval\|floatval\|absint\|empty\|json_decode\|wp_verify_nonce' && continue
    fail "$line — leitura de superglobal sem isset/sanitize"
done < <(grep -rn '\$_POST\[[^]]*\]' $TARGET --include="*.php" 2>/dev/null \
    | grep -vE '^\s*(//|\*|#)' \
    || true)

if [ $ISSUES -eq 0 ]; then
    pass "Nenhum acesso inseguro a superglobal"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
fi
ERR10=$ISSUES

# ------------------------------------------------------------------
# SUMÁRIO
# ------------------------------------------------------------------
TOTAL=$((ERR1 + ERR2 + ERR3 + ERR4 + ERR5 + ERR6 + ERR7 + ERR8 + ERR9 + ERR10))

echo -e "\n${YELLOW}══════════════════════════════════════${RESET}"
echo -e "  Checks passando: ${GREEN}$PASSED_CHECKS${RESET} / $TOTAL_CHECKS"
echo -e "  Issues pendentes: ${RED}$TOTAL${RESET}"
echo -e "${YELLOW}══════════════════════════════════════${RESET}"

if [ "$TOTAL" -gt 0 ]; then
    echo -e "\n${RED}✘ REPROVADO — $TOTAL issue(s) encontrada(s)${RESET}"
    echo -e "  Corrija antes de prosseguir para a próxima fase do ciclo (.reasonix.toml)"
    exit 1
else
    echo -e "\n${GREEN}✔ APROVADO — Nenhum problema de segurança${RESET}"
    exit 0
fi
