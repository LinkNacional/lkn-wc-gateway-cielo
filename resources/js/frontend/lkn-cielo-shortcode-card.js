window.jQuery(function ($) {
  // Verifica se janela foi carregada antes da criação do card
  $(window).on('load', lknCieloCreditCardRender)
  // Cria o card somente quando a requisição for concluida
  $(document).on('updated_checkout', lknCieloCreditCardRender)
  // $(document).on('wc-fragment-refreshed', lknCieloCreditCardRender)
  // $(document).on('woocommerce_cart_updated', lknCieloCreditCardRender)
  // $(document).on('woocommerce_checkout_update_order_review', lknCieloCreditCardRender)
  // Fallback para quando o evento não é disparado
  // lknCieloCreditCardRender()

  function lknCieloCreditCardRender() {
    if (document.querySelector('.wc-block-checkout')) {
      return // Não carrega para formulário em bloco
    }

    let $form = $('.woocommerce .woocommerce-checkout')
    if ($form.length === 0) {
      $form = $('#order_review')
    }

    // Busca containers de crédito e débito separadamente
    const containers = [
      { selector: '#cielo-credit-card-animation', gateway: 'lkn_cielo_credit' },
      { selector: '#cielo-debit-card-animation', gateway: 'lkn_cielo_debit' }
    ]

    containers.forEach(function (container) {
      const $container = $form.find(container.selector)
      if ($container.length && !$container.hasClass('lkn-cielo-card-inicialized')) {
        // Detecta os seletores de input pelo gateway
        let inputSelectors = getInputSelectorsByGateway({ val: () => container.gateway })
        if (!inputSelectors || !$form.find(inputSelectors.numberInput).length) {
          return
        }

        // maybe delete old card data
        $form.data('card', null)

        // init animated card
        $form.card({
          container: container.selector,
          formSelectors: inputSelectors,
          // A máscara/validação dos campos é responsabilidade do
          // lkn-card-fields.js (padronizado: MM/AA, só dígitos, inputmode).
          // Aqui mantemos apenas a animação visual do cartão.
          formatting: false,
          placeholders: {
            number: '•••• •••• •••• ••••',
            name: 'NOME',
            expiry: 'MM/ANO',
            cvc: 'CVC'
          },
          messages: {
            validDate: 'VALIDADE',
            monthYear: ''
          },
          debug: false
        })

        // Workaround para manter os dados do cartão renderizados após atualizações do checkout
        Object.values(inputSelectors).reverse().forEach(function (selector) {
          $(selector)[0]?.dispatchEvent(new CustomEvent('change'))
        })
        $(inputSelectors.numberInput)[0]?.dispatchEvent(new CustomEvent('focus'))
        $(inputSelectors.numberInput)[0]?.dispatchEvent(new CustomEvent('blur'))

        // Se está usando campo virtual (já existe no HTML), sincronizar com first_name + last_name
        if (inputSelectors.nameInput === '#complete_name_lkn_cielo_credit' || inputSelectors.nameInput === '#complete_name_lkn_cielo_debit') {
          const virtualNameField = $(inputSelectors.nameInput)
          
          // Detectar se deve usar billing ou shipping
          const useBilling = document.querySelector('#billing_first_name')
          const firstNameSelector = useBilling ? '#billing_first_name' : '#shipping_first_name'
          const lastNameSelector = useBilling ? '#billing_last_name' : '#shipping_last_name'
          
          const firstNameField = $(firstNameSelector)
          const lastNameField = $(lastNameSelector)
          
          // Função para concatenar e atualizar o campo virtual
          const updateVirtualName = function() {
            const firstName = firstNameField.val() || ''
            const lastName = lastNameField.val() || ''
            const fullName = (firstName + ' ' + lastName).trim() || 'NOME'
            
            // Atualizar campo virtual com múltiplos triggers
            virtualNameField.val(fullName)
            virtualNameField.trigger('input')
            virtualNameField.trigger('change') 
            virtualNameField.trigger('keyup')
            
            // Force manual update para o jQuery.card.js
            virtualNameField[0]?.dispatchEvent(new CustomEvent('input', { bubbles: true }))
            virtualNameField[0]?.dispatchEvent(new CustomEvent('change', { bubbles: true }))
          }
          
          // Escutar eventos em ambos os campos com múltiplos events
          if (firstNameField.length) {
            firstNameField.on('input keyup change paste blur', updateVirtualName)
          }
          if (lastNameField.length) {
            lastNameField.on('input keyup change paste blur', updateVirtualName)
          }
          
          // Disparar evento inicial para mostrar nome já preenchido
          updateVirtualName()
          
          // Garantir que a animação seja atualizada após pequeno delay
          setTimeout(updateVirtualName, 100)
        }

        // Marca como inicializado
        $container.addClass('lkn-cielo-card-inicialized')
      }
    })
  }

  function getInputSelectorsByGateway(selectedPaymentMethod) {
    if (!selectedPaymentMethod || !selectedPaymentMethod.val()) {
      return null
    }

    const paymentMethodValue = selectedPaymentMethod.val()

    // Gateway de crédito
    if (paymentMethodValue === 'lkn_cielo_credit') {
      let nameInput = '#lkn_cc_cardholder_name'
      if (!document.querySelector('#lkn_cc_cardholder_name')) {
        nameInput = '#complete_name_lkn_cielo_credit'
      }
      return {
        numberInput: '#lkn_ccno',
        nameInput: nameInput,
        expiryInput: '#lkn_cc_expdate',
        cvcInput: '#lkn_cc_cvc'
      }
    }

    // Gateway de débito
    if (paymentMethodValue === 'lkn_cielo_debit') {
      let nameInput = '#lkn_dc_cardholder_name'
      if (!document.querySelector('#lkn_dc_cardholder_name')) {
        nameInput = '#complete_name_lkn_cielo_debit'
      }
      return {
        numberInput: '#lkn_dcno',
        nameInput: nameInput,
        expiryInput: '#lkn_dc_expdate',
        cvcInput: '#lkn_dc_cvc'
      }
    }

    return null
  }
})
