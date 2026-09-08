# Verificação 3DS 2.2 — itens para confirmar com o suporte eCommerce da Cielo

Olá!

Identificamos que o erro "Erro no processo de autenticação 3DS 2.2" que aparece no checkout
não é causado pelas credenciais. Na verdade, o fluxo de autenticação 3DS está sendo
**rejeitado pelo servidor da Braspag com erro 403** na etapa de `enroll`.

Para resolver, peço que verifique os itens abaixo **junto ao suporte eCommerce da Cielo**
(não é necessário alterar nada no plugin):

1. **Domínio habilitado para 3DS 2.2**
   Confirmar se o domínio `aflexchair.com.br` está habilitado/cadastrado para o 3DS 2.2.

2. **URL cadastrada: raiz ou página completa?**
   O plugin envia a URL **completa da página de checkout** (ex.: `https://aflexchair.com.br/checkout/`).
   Confirmar se no cadastro 3DS está registrada:
   - a URL raiz (`https://aflexchair.com.br/`), ou
   - a URL completa do checkout (`https://aflexchair.com.br/checkout/`).

   Se o cadastro estiver apenas na raiz, o servidor rejeita a requisição com 403.

3. **Variação de `www`**
   Confirmar se o cadastro está em `aflexchair.com.br` ou `www.aflexchair.com.br`.
   (origem com e sem `www` são tratadas como domínios diferentes.)

---

### Resumo técnico (opcional, para suporte)

- Ambiente: produção (`https://mpi.braspag.com.br`)
- Etapa `/v2/3ds/init` retorna sucesso (credenciais OK).
- Etapa `/v2/3ds/enroll` retorna **403 Forbidden** sem header `Access-Control-Allow-Origin`.
- O campo `merchant_url` enviado pelo plugin contém a URL da página de checkout
  (`https://aflexchair.com.br/checkout/`), não o domínio raiz.
