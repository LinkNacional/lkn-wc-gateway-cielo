# 1.34.0 - 02/07/2026
* Ajuste: compatibilidade com o Woocommerce subscription.

# 1.33.6 - 25/06/2026
* Ajuste: versão de teste na documentação.

# 1.33.5 - 24/06/2026
* Ajuste: Validação do 3DS.
  
# 1.33.4 - 12/06/2026
* Ajuste: verificação do nonce.

# 1.33.3 - 11/06/2026
* Ajuste: preenchimento dos campos necessários para o 3DS.

# 1.33.2 - 01/06/2026
* Ajuste: Verificação de nonce ao realizar o pedido.

# 1.33.1 - 27/05/2026
* Novos ícones.

# 1.33.0 - 13/05/2026
* Adição de pagamentos com cartões tokenizados.

# 1.32.1 - 07/05/2026
* Ajuste na redefinição da parcela do cartão de crédito.

# 1.32.0 - 05/05/2026
* Adição checkbox para salvar token em 3ds.

# 1.31.0 - 10/04/2026
* Adição de compatibilidade para pagamentos recorrentes com o plugin de faturas.

# 1.30.4 - 02/04/2026
* Ajuste no ID do campo das parcelas.

# 1.30.3 - 31/03/2026
* Ajuste no carregamento do script do Google Pay.

# 1.30.2 - 25/03/2026
* Ajuste no carregamento do script.

# 1.30.1 - 24/03/2026
* Ajuste nas rotas Api_rest e Ajax.

# 1.30.0 - 16/03/2026
* Ajustes nas notas dos pedidos.
* Ajuste no reembolso do cartão de crédito e débito.
* Novo sistema de captura parcial.
* Ajuste na validação do cartão que inicia com 0.

# 1.29.1 - 14/03/2026 (Security Release)
* 🔒 **SEGURANÇA:** Corrigida vulnerabilidade crítica nos endpoints da API REST - Adicionada verificação de nonce para prevenir acesso não autorizado
* 🔒 **SEGURANÇA:** Corrigida vulnerabilidade de bypass no filtro de reembolso - Adicionadas verificações de permissão para prevenir reembolsos não autorizados
* 🔒 **SEGURANÇA:** Adicionado log de auditoria para operações sensíveis (clearOrderLogs, reembolsos)
* 🔒 **SEGURANÇA:** Implementada verificação de permissão de dupla camada nos processos de reembolso
* ⚠️ **BREAKING:** Endpoints REST agora requerem nonce válido (header X-WP-Nonce ou parâmetro _wpnonce)
* ⚠️ **BREAKING:** Endpoint clearOrderLogs agora requer capacidade manage_woocommerce
* ✅ **COMPATIBILIDADE:** Todas as alterações são retrocompatíveis com integrações existentes
* ✅ **COMPATIBILIDADE:** Filtros (lkn_wc_cielo_*_refund) continuam funcionando para usuários com permissões adequadas
* 📝 Veja SECURITY_FIXES.md para informações detalhadas sobre melhorias de segurança

# 1.29.0 - 04/03/2026
* Novo layout para o shortcode.
* Ajustes de CSS na versão padrão e moderna.

# 1.28.2 - 02/03/2026
* Correção na ABA de extras da versão PRO.
* Ajuste na exibição das parcelas no gateway.
* Correção na exibição do cartão na versão de shortcode.

# 1.28.1 - 19/02/2026
* Correção de obtenção do JSON no processo de pagamento do Google Pay.

# 1.28.0 - 09/02/2026
* Adição do sistema de transações do plugin da Cielo.
* Ajuste no calculo das parcelas com cupom.

# 1.27.4 - 09/01/2026
* Ajuste na verificação do tipo de cartão no 3DS.

# 1.27.3 - 29/12/2025
* Ajuste no Cron de verificação de pagamento Pix

# 1.27.2 - 03/12/2025
* Ajuste visual na tela de pagamento do cliente cielo credito e cielo debito.

# 1.27.1 - 25/11/2025
* Novos atributos personalizados para configurações.

# 1.27.0 - 10/11/2025
* Adição dos calculos de juros/desconto nos parcelamentos dos cartões de crédito(Editor de blocos + Shortcode).

# 1.26.0 - 17/10/2025
* Ajustes no resumo do pedido.
* Adição da porcentagem de juros nas parcelas
* Correção no footer do WooCommerce nas configurações

# 1.25.2 - 09/10/2025
* Alteração em carregamento de hooks.

# 1.25.1 - 06/10/2025
* Correção na variável de parcelamento.
* Correção na verificação de pagamento PIX.

# 1.25.0 - 12/09/2025
* Adição do gateway de pagamento com Google Pay.
* Adição do nome do gateway nas notas de pedido.
* Adição do icone do plugin nos gateways.

# 1.24.1 - 03/09/2025
* Correção no retorno do metadados do pagamento.

# 1.24.0 - 02/09/2025
* Adição das informações no recibo de pagamento.

# 1.23.0 - 24/07/2025
* Adição de configuração PRO para exibir botão "Finalizar e Gerar PIX" no checkout.

# 1.22.0 - 08/07/2025
* Novo layout para os gateways de pagamento de crédito e débito.

# 1.21.5 - 23/06/2025
* Correção de exibição de pix.

# 1.21.4 - 20/06/2025
* Correção em lógica de processar pagamento.

# 1.21.3 - 18/06/2025
* Correção ajustes finais.

# 1.21.2 - 16/06/2025
* Adição de novo layout para página de configuração(new version).

# 1.21.1 - 16/06/2025
* Versão antiga.

# 1.21.0 - 30/05/2025
* Adição de novo layout para página de configuração.

# 1.20.0 - 23/05/2025
* Adição de compatibilidade com configuração PRO para adicionar desconto na parcela.
* Adição de compatibilidade com configuração PRO para definir o valor mínimo para parcelas sem juros.

# 1.19.2 - 24/04/2025
* Correção no envio de parcelas dos métodos de pagamentos.

# 1.19.1 - 07/04/2025
* Alteração em descrições dos métodos de pagamentos.

# 1.19.0 - 20/03/2025
* Adição de mensagem para informar quando as credenciais estão erradas;
* Correção de scripts duplicados.

# 1.18.0 - 17/03/2025
* Método de pagamento pix.

# 1.17.2 - 12/03/2025
* Validação da opção de cartão online no momento do pagamento;
* Correção de exibição de moeda nas parcelas.

# 1.17.1 - 17/02/2025
* Correção de referência de valor mínimo das parcelas;
* Atualização de referência de hooks para evitar colisões;
* Correção de validação de nonce quando configuração estiver como desabilitada.

# 1.17.0 - 06/02/2024
* Adição de compatibilidade com configurações de retentativas para assinaturas.

# 1.16.0 - 31/01/2025
* Adição do notice de donwloado do plugin: fraud-detection-for-woocommerce.
* Adição de mensagem de avaliação do plugin no footer.

# 1.15.0 - 20/12/2024
* Adição de função para renovar token;
* Adição de configuração para exibir logs de transação no pedido;
* Adição de cartão animado no checkout do editor por blocos;
* Adição de configuração para exibir e ocultar cartão animado;
* Adição de seleção automática das abas quando a página configurações é recarregada.
* Alteração de label do método de pagamento de crédito;
* Alteração em urls de endpoints.

# 1.14.1 - 06/12/2024
* Correção de bug para a função de mostrar logs no pedido.

# 1.14.0 - 05/12/2024
* Adição de compatibilidade com botão para capturar pedido;
* Adição de configurações para adicionar logs no pedido;
* Correção em script de layout.

# 1.13.2 - 02/12/2024
* Correção de hook de limitação de parcelamento;
* Correção de evento de consulta bin formulário legado.

# 1.13.1 - 29/11/2024
* Correção de formatação de valor do pedido.

# 1.13.0 - 25/11/2024
* Adição de configuração para permitir transações sem 3DS;
* Adição de filtros para alterar URL de consultas de cartão;
* Alteração em layout de configurações;
* Correção em chamada de de logger.

# 1.12.1 - 06/11/2024
* Adição de correções para transações autenticadas com 3DS 2.2

# 1.12.0 - 22/10/2024
* Adição de compatibilidade com novas funcionalidades do WooCommerce Cielo PRO.

# 1.11.5 - 10/10/2024
* Correção em validação de nonce;
* Alteração em placeholders.

# 1.11.4 - 04/10/2024
* Adicionado modo de compatibilidade para validação de nonce;
* Corrigido erro no envio de cartão de crédito inválido.

# 1.11.3 - 02/10/2024
* Adição de melhoria na validação de campos;
* Ajustes no redirecionamento em caso de falha no pagamento;
* Correção de pedido com parcelamento com juros tem valor total diferente do produto.

# 1.11.2 - 27/09/2024
* Correção de placeholder para WooCommerce classic checkout;
* Correção de cálculo de valores de parcelamento para clientes com Cielo PRO ativo.

# 1.11.1 - 25/09/2024
* Correção em valores das parcelas quando são feitas alterações no carrinho.

# 1.11.0 - 02/09/2024
* Adição de traduções para campo de CPF para pagamentos com Pix.
* Adição compatibilidade com configuração para alterar status quando o pedido for pago.

# 1.10.0 - 16/08/2024
* Adição de pagamentos de crédito com validação 3DS;
* Adição de validação de BIN para selecionar tipo de cartão automáticamente;
* Correção de bug de renderização débito 3DS;
* Melhorias de compatibilidade com WooCommerce Cielo PRO;
* Adição de dependência com o plugin WooCommerce.

# 1.9.3 - 18/06/2024
* Adição de compatibilidade com a funcionalidade Orders Auto-Complete do plugin pro;
* Correção de problema de renderização do Cielo débito 3DS.

# 1.9.2 - 05/06/2024
* Correção de validação no campo nome do titular do cartão;

# 1.9.1 - 23/05/2024
* Correção de erro no campo de titular do cartão;
* Correção de erro na validação dos campos do novo template de checkout;
* Ajustes no reconhecimento de parcelas em faturas e páginas fora do padrão do WooCommerce.

# 1.9.0 - 22/05/2024
* Correção de erro nos campos de entrada do formulário;
* Adição de tratamento de erro para o campo de entrada do titular do cartão;
* Adição de campo de entrada do titular do cartão;
* Adição de botão "seja pro".

# 1.8.1 - 16/05/2024
* Correção de erro na página de editar formulário em blocos;

# 1.8.0 - 10/05/2024
* Adição de compatibilidade com block-based checkout;
* Adição de banner de hospedagem para Wordpress;
* Adição de campos obrigatórios para as credenciais de cartão de crédito e débito;
* Melhoria nos tratamentos de erros;

# 1.7.0 - 25/03/2024
* Correção de bug de carregamento de script do 3DS na opção cartão de débito;
* Adição de lógica para lidar com respostas da API quando o código de retorno é "GF";
* Ajuste na label de código de segurança;
* Adição de descrição nas configurações do cartão de débito;
* Adição de validação no campo de descrição da fatura;

# 1.6.0 - 27/11/2023
* Correção de bug de carregamento de script do 3DS;
* Adição de exibição de informações adicionais do cartão nos detalhes do pedido;
* Correção de bug de máscaras dos campos de cartão de débito;
* Remoção de funções depreciadas do JQuery;
* Adição de botão de 'ver logs' nas configurações do plugin.

# 1.5.0
* Adição de regras de lintagem;
* Adição de carregamento de atributos globais ao script de parcelamento;
* Atualização de repositório para suportar devcontainers.

# 1.4.0
* Implementação de modo de compatibilidade de validação;
* Ajustes nas descrições de configurações;
* Atualizar links de notices;
* Armazenamento de quantidade de parcelas nos metadados do pedido;
* Adicionado quantidade de parcelas na tela de obrigado e no e-mail de novo pedido.

# 1.3.2
* Correção de mensagens de validação duplicadas;
* Ajustes de carregamento de configurações;
* Mudança de diretório de scripts da área administrativa.

# 1.3.1
* Otimização de carregamento de script;
* Removido carregamento de script desnecessário.

# 1.3.0
* Mudança de biblioteca de máscara de campos;
* Implementado preparação para reconhecimento de juros;
* Implementada área de avisos no front-end.

# 1.2.0
* Implementado filtro de BIN;
* Corrigido bugs de inputs na página de finalizar pagamento;
* Carregamento de scripts otimizado;
* Corrigido bug de pagamento de cartão de débito na página de finalizar pagamento.

# 1.1.0
* Implementação de parcelamento;
* Atualização de documentação;
* Correção de bug de validação de data de expiração;
* Correção de bug de inicialização de 3DS.

# 1.0.0
* Lançamento de plugin.