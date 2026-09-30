---
name: open-pr
description: Abre PR de dev → main no lkn-wc-gateway-cielo no padrão Link Nacional (título VERSION - repo (resumo); corpo com metadados e CHANGELOG)
---

# open-pr (lkn-wc-gateway-cielo)

Abre um Pull Request de `dev` → `main` via `gh pr create`, no padrão Link Nacional, para o repositório `lkn-wc-gateway-cielo`.

## Parâmetros (via `arguments`)

O usuário pode passar: `version=1.37.3 tested_up=7.1 summary=Padronização das mensagens de erro da Cielo (ABECS)`. Qualquer valor ausente é extraído do código.

- **version** — versão da release (Stable tag / cabeçalho PHP)
- **tested_up** — WP testado até
- **summary** — resumo CURTO usado no TÍTULO. Se ausente, derive da entrada mais recente do `CHANGELOG.md` (NÃO do `git log`).

## Fluxo de execução

### 1. Extrair metadados (se não vierem nos arguments)

```bash
# Cabeçalho PHP (fonte da verdade da versão)
grep -m1 -E "^\s*\*\s*Version:" lkn-wc-gateway-cielo.php
grep -m1 -E "^\s*\*\s*Requires PHP:" lkn-wc-gateway-cielo.php

# README.txt (Tested up to / Stable tag) — ATENÇÃO: MAIÚSCULO
grep -m1 -i "^Tested up to:" README.txt
grep -m1 -i "^Stable tag:" README.txt

# Nome do repositório no GitHub (derivado do remote)
REPO_NAME=$(basename -s .git "$(git config --get remote.origin.url)")
# → lkn-wc-gateway-cielo
```

### 2. Ler o changelog da versão atual (fonte do resumo e dos bullets)

⚠️ **Regra anti-redundância.** NÃO use `git log` para gerar o resumo — o range de commits está dessincronizado e traz itens de versões já publicadas. Leia a entrada mais recente do changelog:

```bash
# Preferir CHANGELOG.md (português). Fallback: README.txt, seção == Changelog ==.
head -n 20 CHANGELOG.md
```

A entrada mais recente tem o formato `# VERSION - dd/mm/aaaa` seguido de bullets `* ...`.

- **TÍTULO**: resuma esses bullets em uma frase curta (≤ ~14 palavras).
- **CORPO (seção CHANGELOG)**: copie os bullets do `CHANGELOG.md` (português), sem hash e sem reescrever.

### 3. Montar TÍTULO

Formato exato (obrigatório) — **com um espaço antes do parêntese**:

```
VERSION - REPO_NAME (RESUMO_CURTO)
```

Exemplo:

```
1.37.3 - lkn-wc-gateway-cielo (Correção: Padronização das mensagens de erro da Cielo seguindo o padrão ABECS.)
```

### 4. Montar CORPO

Use exatamente este gabarito. Só variam `{VERSION}`, `{TESTED_UP}` e os bullets do CHANGELOG; o restante é fixo:

```markdown
# Cielo Payment Gateway for WooCommerce
Contribuidores: linknacional
Link: https://www.linknacional.com.br/wordpress/
Tags: woocommerce, payment, paymethod, card, credit
Testado até: {TESTED_UP}
Versão estável: {VERSION}
Licença: GPLv2 ou posterior
URI da Licença: https://opensource.org/licenses/MIT
Traduções: Português(Brasil) / Inglês

Receba pagamentos por meio de cartão de crédito, débito, Pix e Google Pay através da Cielo diretamente na sua loja WooCommerce.

## Descrição

Integre os gateways de pagamento da Cielo à sua loja WooCommerce e habilite seus clientes a pagarem via cartão de crédito, cartão de débito, Pix e Google Pay.

A [Cielo](https://www.cielo.com.br) é uma das maiores adquirentes do Brasil, oferecendo gateway seguro e eficiente para empresas aceitarem pagamentos online. O plugin oferece suporte completo aos métodos de pagamento da Cielo com autenticação 3DS 2.2 para cartões de débito e recursos avançados de parcelamento com juros/desconto.

**Recursos principais:**

- Cartão de Crédito com parcelamento inteligente
- Cartão de Débito com autenticação 3DS 2.2
- Pagamento via Pix
- Google Pay
- Cálculos de juros/desconto nos parcelamentos
- Compatibilidade com WooCommerce Blocks (Editor de Blocos)
- Layout responsivo e moderno
- Validação de BIN automática
- Suporte a múltiplas moedas

**Dependências**

Este plugin depende do WooCommerce. Certifique-se de que o WooCommerce está instalado e configurado antes de instalar o Cielo Payment Gateway for WooCommerce.

**Instruções de uso**

1. Procure na barra lateral do WordPress por 'Cielo Payment Gateway for WooCommerce'.
2. Nas opções do WooCommerce, acesse 'Pagamentos' e configure os métodos Cielo desejados.
3. Configure suas credenciais da Cielo (MerchantId, MerchantKey).
4. Configure as opções de parcelamento e juros conforme necessário.
5. Salve as configurações.

Pronto! Seus clientes poderão pagar via Cielo.

## Instalação

1. Baixe o plugin.
2. No painel administrativo do WordPress, vá para Plugins > Adicionar Novo.
3. Clique em "Enviar Plugin" e selecione o arquivo ZIP do plugin que você baixou.
4. Clique em "Instalar Agora" e, em seguida, em "Ativar Plugin".
5. Certifique-se de que o plugin WooCommerce também está ativado.

## CHANGELOG:

{BULLETS copiados da entrada mais recente do CHANGELOG.md, no formato "* Item". NÃO invente a partir do git log.}
```

### 5. Abrir o PR

Sempre `dev` → `main`:

```bash
gh pr create \
  --base main \
  --head dev \
  --title "VERSION - REPO_NAME (RESUMO_CURTO)" \
  --body "$(cat <<'EOF'
...corpo...
EOF
)"
```

### 6. Confirmar

Mostre a URL retornada pelo `gh` e o comando usado. Se o PR já existir para `dev` → `main`, o `gh` vai avisar — não force `--force` sem pedir.

## Regras

- **Nunca** edite arquivos do repo para abrir o PR (é só `gh pr create`).
- Título SEMPRE no formato `VERSION - lkn-wc-gateway-cielo (resumo)`, **com um espaço antes do parêntese**.
- `REPO_NAME` é o nome do repositório/slug: `lkn-wc-gateway-cielo` (derivado do git remote). Não use o nome do diretório local de forma divergente.
- Corpo SEMPRE com o cabeçalho de metadados, Descrição, Instalação e a seção `## CHANGELOG:` com os bullets da versão.
- `README.txt` é **MAIÚSCULO** (não é `readme.txt`); a versão também está no cabeçalho de `lkn-wc-gateway-cielo.php`.
- Se `version` / `tested_up` divergirem entre o cabeçalho PHP e o `README.txt`, use o **cabeçalho PHP** e avise.
- Nunca inclua hashes de commit no corpo.
- Bullets do corpo SEMPRE vindos da entrada mais recente do `CHANGELOG.md` — nunca do `git log`.
