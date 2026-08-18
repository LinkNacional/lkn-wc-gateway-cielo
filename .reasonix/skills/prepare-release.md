---
name: prepare-release
description: Prepara release do lkn-wc-gateway-cielo: atualiza README.txt, CHANGELOG.md, cabeçalho PHP, constante LKN_WC_CIELO_VERSION e DEPLOY_TAG dos workflows baseado no git log
---

# prepare-release

Atualiza **todos** os arquivos que contêm o número de versão para uma nova release do plugin.

## Parâmetros (via `arguments`)

O usuário pode passar os valores diretamente: `"version=1.37.0 tested_up=7.0 php=8.2 wp_min=5.8 highlights=Correção de bug X"`. Se algum valor faltar, pergunte.

- **version** — nova versão (Stable tag)
- **tested_up** — versão do WP testada (Tested up to)
- **php** — versão mínima do PHP (Requires PHP)
- **wp_min** — versão mínima do WordPress (Requires at least; default 5.8)
- **highlights** — resumo da versão (opcional, usa git log se vazio)

## Fluxo de execução

### 1. Coletar valores
Se não recebidos via arguments, pergunte ao usuário um por um. Detecte a versão atual via grep no `.php` raiz:
```
grep -E "Version:|LKN_WC_CIELO_VERSION" *.php
```

### 2. Analisar as mudanças reais (NÃO copiar os comentários dos commits)
Os bullets do changelog devem descrever o **efeito real** das mudanças, não o texto dos `git log`.
1. Liste os arquivos alterados: `git diff --stat ${LAST_TAG}..HEAD`
2. Leia o diff dos arquivos de código: `git diff ${LAST_TAG}..HEAD -- includes/ *.php`
3. Escreva cada bullet como "o que mudou para o usuário", ex.: "não exige mais 3DS para cartões sem suporte" em vez de "correção na verificação dos campos".

Só se não for possível ler o diff, use os comentários dos commits como pista — nunca como texto final.

### 3. Atualizar TODOS os arquivos com versão

A versão aparece em **6 locais** espalhados por **6 arquivos**. Atualize todos:

#### 3a. `README.txt` (ATENÇÃO: maiúsculo, NÃO é `readme.txt`)
- `Stable tag:` → nova versão
- `Tested up to:` e `Requires PHP:` se alterados
- `Requires at least:` se alterado (versão mínima do WP)
- Adicionar entrada no topo da seção `== Changelog ==`, **em inglês**, preservando o formato atual:
  ```
  = 1.37.0 =
  ** 13/08/2026 **
  * Added: ...

  = 1.36.2 =
  ```
  (formato clássico do WP.org: `= VERSION =` + linha `** dd/mm/aaaa **` + bullets + linha em branco antes da versão anterior)
- Se `highlights` foi fornecido, avalie adicionar na `== Description ==` (NUNCA apague conteúdo existente)

#### 3b. `CHANGELOG.md`
- Adicionar entrada no topo do arquivo, **em português**, no formato atual (`# VERSION - dd/mm/aaaa` + bullets + linha em branco):
  ```
  # 1.37.0 - 13/08/2026
  * Adicionado: ...

  # 1.36.2 - 13/08/2026
  ```

#### 3c. `lkn-wc-gateway-cielo.php`
- `* Version: NOVA_VERSION` (cabeçalho do plugin)
- `* Requires at least:` e `* Requires PHP:` — manter em sincronia com o `README.txt`

#### 3d. `lkn-wc-gateway-cielo-file.php`
- `define( 'LKN_WC_CIELO_VERSION', 'NOVA_VERSION' );` (constante)

#### 3e. `.github/workflows/main.yml`
- `DEPLOY_TAG: "NOVA_VERSION"`

#### 3f. `.github/workflows/wordpressRelease.yml`
- `DEPLOY_TAG: "NOVA_VERSION"`

### 4. Validação final
Rodar grep com a versão **antiga** para confirmar que não restou nenhuma ocorrência fora do esperado:
```
grep -r "VERSAO_ANTIGA" --include="*.php" --include="*.md" --include="*.txt" --include="*.yml" .
```
O esperado: `README.txt` e `CHANGELOG.md` ainda contêm a versão antiga **apenas** nas entradas antigas do Changelog (isso é correto). Qualquer outro arquivo retornando a versão antiga é **erro** e deve ser corrigido.

Depois, grep com a versão **nova** para confirmar que aparece em todos os **6 locais**:
```
grep -rn "NOVA_VERSAO" --include="*.php" --include="*.md" --include="*.txt" --include="*.yml" .
```

## Observações específicas deste plugin
- **Slug/pasta/arquivo**: `lkn-wc-gateway-cielo` (com hífens). NÃO renomear. O `.php` principal é `lkn-wc-gateway-cielo.php` e a constante fica em `lkn-wc-gateway-cielo-file.php`.
- **`LKN_WC_CIELO_VERSION`**: é a constante usada em todo o plugin. Deve ficar **igual** ao `* Version:` do cabeçalho. O valor de versionamento é lido daqui pelo `LknWCCieloPayment` (`includes/LknWCCieloPayment.php`).
- **Fallback hardcoded**: `includes/LknWCCieloPayment.php:102` tem `$this->version = '1.25.0';` como fallback quando `LKN_WC_CIELO_VERSION` não está definida. É apenas fallback e **não** precisa ser atualizado a cada release (a constante sempre é definida antes).
- **`README.txt` é MAIÚSCULO** (diferente de `readme.txt`). O campo de versão fica lá; `README.md` **não** tem campo de versão explícito — não editar.
- `composer.json` **não** tem campo `version` — não editar.
- `package.json` tem `"version": "1.0.0"` mas é boilerplate do docker-php-dev-container — **não** editar (não é a versão do plugin).
- **Apenas 2 workflows** com `DEPLOY_TAG`: `main.yml` (gera release no GitHub) e `wordpressRelease.yml` (deploy no WP.org). NÃO existe `dev-release.yml`.
- O changelog do `README.txt` usa formato `= VERSION =` + `** dd/mm/aaaa **` (WordPress clássico), **diferente** do `CHANGELOG.md` que usa `# VERSION - dd/mm/aaaa`.
- **Idioma do changelog**: `README.txt` → inglês; `CHANGELOG.md` → português.
- **Requires at least / Requires PHP**: devem existir **tanto** no cabeçalho PHP (`lkn-wc-gateway-cielo.php`) quanto no `README.txt`, com valores idênticos (WP mínimo `5.8`, PHP mínimo `8.2`).
- **Datas de release**: fuso `America/Sao_Paulo`. Tanto `README.txt` quanto `CHANGELOG.md` usam `dd/mm/aaaa`.
- **Assets do WP.org**: ficam em `resources/assets/wordpressAssets` (referenciado no `wordpressRelease.yml` como `ASSETS_DIR`).
