#!/usr/bin/env bash
# wp-cli-wrapper.sh — WP-CLI wrapper com trava de segurança para Local WP (Site Shell)
# Uso: ./wp-cli-wrapper.sh [comando wp-cli sem o prefixo "wp"]
# Ex:  ./wp-cli-wrapper.sh option get siteurl
#      ./wp-cli-wrapper.sh db drop --yes

set -euo pipefail

RED="\033[1;31m"
GREEN="\033[1;32m"
YELLOW="\033[1;33m"
CYAN="\033[1;36m"
RESET="\033[0m"

# ------------------------------------------------------------------
# 0. Auto-detecção de ambiente
# ------------------------------------------------------------------

# Caminho absoluto do diretório atual
CWD="$(pwd -P)"

# Tenta encontrar o root do WordPress subindo a árvore
WP_ROOT=""
_search="$CWD"
while [ "$_search" != "/" ]; do
    if [ -f "$_search/wp-config.php" ] && [ -d "$_search/wp-admin" ]; then
        WP_ROOT="$_search"
        break
    fi
    # Local WP: a raiz do WordPress fica em app/public/
    if [ -f "$_search/app/public/wp-config.php" ] && [ -d "$_search/app/public/wp-admin" ]; then
        WP_ROOT="$_search/app/public"
        break
    fi
    _search="$(dirname "$_search")"
done

# Detecta binário do WP-CLI
WP_BIN=""
for candidate in "wp" "wp-cli" "/usr/local/bin/wp" "$HOME/.wp-cli/bin/wp" "$HOME/.composer/vendor/bin/wp"; do
    if command -v "$candidate" &>/dev/null; then
        WP_BIN="$candidate"
        break
    fi
done

# Tenta encontrar wp-cli.phar
if [ -z "$WP_BIN" ]; then
    for path in "$CWD" "$WP_ROOT" "$HOME"; do
        [ -z "$path" ] && continue
        if [ -f "$path/wp-cli.phar" ]; then
            WP_BIN="php $path/wp-cli.phar"
            break
        fi
    done
fi

# Se não encontrou, erro amigável com instruções
if [ -z "$WP_BIN" ]; then
    echo -e "${YELLOW}⚠  WP-CLI não encontrado.${RESET}"
    if [ -n "$WP_ROOT" ]; then
        echo -e "${CYAN}ℹ  WordPress detectado em:${RESET} $WP_ROOT"
        echo -e "   Instale: curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
        echo -e "   Depois:  mv wp-cli.phar $WP_ROOT/"
    else
        echo -e "${RED}✘ WordPress root não detectado. Execute de dentro do projeto.${RESET}"
    fi
    exit 1
fi

# Monta o comando base (com aspas para paths com espaço: "Local Sites")
if [ -n "$WP_ROOT" ]; then
    BASE_CMD="$WP_BIN --path=\"$WP_ROOT\""
else
    BASE_CMD="$WP_BIN"
fi

# Detecta se está no Local WP (Site Shell)
IS_LOCAL=false
if echo "$CWD" | grep -q "Local Sites"; then
    IS_LOCAL=true
fi
if [ -n "${LOCAL_SITE_NAME:-}" ] || [ -n "${LOCAL_SITE_ID:-}" ]; then
    IS_LOCAL=true
fi

# ------------------------------------------------------------------
# 1. Validação de argumentos
# ------------------------------------------------------------------
if [ $# -eq 0 ]; then
    echo -e "${RED}Erro:${RESET} Nenhum comando especificado."
    echo -e "Uso: $(basename "$0") <comando-wp-cli> [args...]"
    echo -e "Ex:   $(basename "$0") option get siteurl"
    echo -e "Ex:   $(basename "$0") db export /tmp/backup.sql"
    exit 1
fi

COMMAND="$1"
shift || true

# ------------------------------------------------------------------
# 2. Trava de segurança — comandos destrutivos
# ------------------------------------------------------------------

# Lista de comandos que exigem confirmação interativa
# Cada entrada: "subcomando:mensagem"
DESTRUCTIVE=(
    "db drop:Remove completamente o banco de dados"
    "db reset:Reseta o banco de dados (drop + create)"
    "db clean:Limpa todas as tabelas mantendo o banco"
    "db truncate:Trunca tabelas especificadas"
    "site empty:Remove todos os posts, comments, users"
    "plugin delete:Remove plugin(s) do disco"
    "theme delete:Remove tema(s) do disco"
    "transient delete --all:Remove todos os transients"
    "cache flush:Flush do cache de objeto"
    "rewrite flush:Regenera regras de rewrite"
    "option delete:Limpa opção do banco"
    "user delete:Remove usuário(s) do banco"
    "post delete:Remove post(s) permanentemente"
    "comment delete:Remove comentário(s)"
    "menu delete:Remove menu(s)"
    "widget delete:Remove widget(s)"
    "eval-file:Executa arquivo PHP arbitrário"
    "eval:Executa código PHP arbitrário"
    "super-admin remove:Remove permissão de super-admin"
)

NEEDS_CONFIRM=false
REASON=""

for entry in "${DESTRUCTIVE[@]}"; do
    prefix="${entry%%:*}"
    msg="${entry#*:}"

    # Reconstrói o comando checado (subcomando + primeiros args)
    check_cmd="$COMMAND $*"

    # Verifica se o comando começa com o prefixo perigoso
    if echo "$check_cmd" | grep -q "^$prefix"; then
        NEEDS_CONFIRM=true
        REASON="$msg"
        break
    fi
done

# --yes flag é o indicador universal de "sei o que estou fazendo"
if [ "$NEEDS_CONFIRM" = true ]; then
    HAS_YES=false
    for arg in "$@"; do
        if [ "$arg" = "--yes" ] || [ "$arg" = "-y" ]; then
            HAS_YES=true
            break
        fi
    done

    if [ "$HAS_YES" = false ]; then
        echo -e "${RED}══════════════════════════════════════════════${RESET}"
        echo -e "${RED}  ⛔ TRAVA DE SEGURANÇA${RESET}"
        echo -e "${RED}══════════════════════════════════════════════${RESET}"
        echo -e "  Comando:  ${YELLOW}wp $COMMAND $*${RESET}"
        echo -e "  Ação:     ${RED}$REASON${RESET}"
        echo -e "  Ambiente: ${CYAN}$([ "$IS_LOCAL" = true ] && echo 'Local WP (Site Shell)' || echo 'Direto')${RESET}"
        echo -e "  WP Root:  ${CYAN}${WP_ROOT:-não detectado}${RESET}"
        echo -e "${RED}──────────────────────────────────────────────${RESET}"
        echo ""
        echo -ne "  Confirme digitando ${GREEN}yes${RESET}: "
        read -r confirm
        if [ "$confirm" != "yes" ]; then
            echo -e "\n  ${GREEN}✔ Abortado pelo usuário.${RESET}"
            exit 0
        fi
        echo ""
    else
        echo -e "${YELLOW}⚠  Executando com --yes:${RESET} wp $COMMAND $*"
    fi
fi

# ------------------------------------------------------------------
# 3. Execução
# ------------------------------------------------------------------

# WP-CLI em Docker/root precisa de --allow-root
ALLOW_ROOT=""
if [ "$(id -u 2>/dev/null || echo 0)" = "0" ] || [ "${WP_CLI_ALLOW_ROOT:-}" = "1" ]; then
    ALLOW_ROOT="--allow-root"
fi
FULL_CMD="$BASE_CMD $ALLOW_ROOT $COMMAND $*"

if [ "$IS_LOCAL" = true ]; then
    echo -e "${CYAN}[Local WP Site Shell]${RESET} → wp $COMMAND $*"
else
    echo -e "${CYAN}[WP-CLI]${RESET} → wp $COMMAND $*"
fi

# Executa o comando
if eval "$FULL_CMD"; then
    echo -e "\n${GREEN}✔ Concluído${RESET}"
else
    EXIT_CODE=$?
    echo -e "\n${RED}✘ Falhou (exit: $EXIT_CODE)${RESET}"
    exit $EXIT_CODE
fi
