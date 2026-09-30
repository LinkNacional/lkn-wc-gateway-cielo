/* eslint-disable no-undef */
// Implements script internationalization

// O bundle do bloco (lknCieloDebitCompiled.js) NÃO esconde o botão nativo
// "Place order" do WooCommerce blocks. Esse botão envia o checkout direto,
// sem executar o 3DS (ECI/CAVV/XID vazios). Interceptamos o clique REAL do
// usuário nesse botão e executamos o fluxo 3DS antes de deixar o envio seguir.
document.addEventListener('click', function (e) {
  var native = e.target && e.target.closest && e.target.closest('.wc-block-components-checkout-place-order-button')
  if (!native) {
    return
  }

  // Cliques sintéticos (disparados pelo próprio fluxo 3DS via dispatchEvent)
  // têm isTrusted === false e devem passar direto. Só interceptamos o clique
  // real do usuário no botão nativo.
  if (e.isTrusted === true && typeof lknDCProccessButton === 'function') {
    e.preventDefault()
    e.stopPropagation()
    lknDCProccessButton()
  }
}, true)

window.__lknBpmpiReady = false
window.__lknBpmpiLoading = false
window.__lknPendingAuthenticate = false

// Feedback visual do botão de envio durante o 3DS (cinza + texto "carregando") para
// evitar cliques repetidos. Restaurado nas respostas do 3DS (falha/erro) e antes do
// envio final (sucesso), sincronizando com o botão do WooCommerce.
var lknSubmitLoadingEl = null
function lknLockSubmitButton () {
  if (lknSubmitLoadingEl) return
  var btn = document.getElementById('sendOrder') ||
    document.querySelector('.wc-block-components-checkout-place-order-button')
  if (!btn) return
  try {
    if (btn.getAttribute('data-lkn-prev-html') === null) {
      btn.setAttribute('data-lkn-prev-html', btn.innerHTML)
    }
    btn.disabled = true
    btn.classList.add('lkn-btn-loading')
    btn.textContent = wp.i18n.__('Processing...', 'lkn-wc-gateway-cielo')
    window.__lkn3DSInProgress = true
    lknSubmitLoadingEl = btn
  } catch (e) {}
}
function lknUnlockSubmitButton () {
  window.__lkn3DSInProgress = false
  var btn = lknSubmitLoadingEl
  if (!btn) return
  try {
    btn.disabled = false
    btn.classList.remove('lkn-btn-loading')
    var prev = btn.getAttribute('data-lkn-prev-html')
    if (prev !== null) {
      btn.innerHTML = prev
      btn.removeAttribute('data-lkn-prev-html')
    }
  } catch (e) {}
  lknSubmitLoadingEl = null
}

function bpmpi_config () {
  return {
    onReady: function () {
      window.__lknBpmpiReady = true
      if (window.__lknPendingAuthenticate) {
        window.__lknPendingAuthenticate = false
        bpmpi_authenticate()
      }
    },
    onSuccess: function (e) {
      lknUnlockSubmitButton()
      // Card is eligible for authentication, and the bearer successfully authenticated
      const cavv = e.Cavv || ''
      const xid = e.Xid || ''
      const eci = e.Eci || ''
      const version = e.Version || ''
      const referenceId = e.ReferenceId || ''

      const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]?.closest('form')

      if (Form3dsButton) {
        Form3dsButton.setAttribute('data-payment-cavv', cavv)
        Form3dsButton.setAttribute('data-payment-eci', eci)
        Form3dsButton.setAttribute('data-payment-ref_id', referenceId)
        Form3dsButton.setAttribute('data-payment-version', version)
        Form3dsButton.setAttribute('data-payment-xid', xid)

        const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
        const event = new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        })

        Button3ds.dispatchEvent(event)
      } else {
        console.error('Form3dsButton não encontrado.')
      }
    },
    onFailure: function (e) {
      lknUnlockSubmitButton()
      // Autenticação FALHOU (ex.: usuário cancelou o desafio 3DS). NUNCA prosseguir
      // com o pedido: desafio cancelado/recusado é tratado como recusa. A opção
      // allow_card_ineligible vale apenas para cartões não elegíveis (onUnenrolled).
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))
      alert(wp.i18n.__('Authentication not completed. Your order was not placed. Please try again.', 'lkn-wc-gateway-cielo'))
    },
    onUnenrolled: function (e) {
      lknUnlockSubmitButton()
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      // ECI 04/07 = Data Only = NÃO autenticada (risco do lojista). Requer allow_card_ineligible.
      const allowCardIneligible = window.lknDCScriptAllowCardIneligible && window.lknDCScriptAllowCardIneligible.allow === 'yes'
      
      if (allowCardIneligible) {
        const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]?.closest('form')
        
        if (Form3dsButton) {
          Form3dsButton.setAttribute('data-payment-cavv', e.Cavv || '')
          Form3dsButton.setAttribute('data-payment-eci', e.Eci || '')
          Form3dsButton.setAttribute('data-payment-ref_id', e.ReferenceId || '')
          Form3dsButton.setAttribute('data-payment-version', e.Version || '')
          Form3dsButton.setAttribute('data-payment-xid', e.Xid || '')
          
          const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
          const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: window
          })
          Button3ds.dispatchEvent(event)
        }
      } else {
        // Card is not eligible for authentication (unauthenticable)
        alert(wp.i18n.__('Card Ineligible for Authentication', 'lkn-wc-gateway-cielo'))
      }
    },
    onDisabled: function (e) {
      lknUnlockSubmitButton()
      // Store don't require bearer authentication (class "bpmpi_auth" false -> disabled authentication).
      console.log('code ' + (e ? e.ReturnCode : 'N/A') + ' ' + ' message ' + (e ? e.ReturnMessage : 'N/A') + ' raw: ' + JSON.stringify(e || {}))

      const allowCardIneligible = window.lknDCScriptAllowCardIneligible && window.lknDCScriptAllowCardIneligible.allow === 'yes'
      if (allowCardIneligible) {
        // Continuar sem 3DS mas preservando dados do MPI (ECI pode ser útil para data-only)
        const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]?.closest('form')
        if (Form3dsButton) {
          Form3dsButton.setAttribute('data-payment-cavv', '')
          Form3dsButton.setAttribute('data-payment-eci', '')
          Form3dsButton.setAttribute('data-payment-ref_id', '')
          Form3dsButton.setAttribute('data-payment-version', '')
          Form3dsButton.setAttribute('data-payment-xid', '')
          const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
          const event = new MouseEvent('click', { bubbles: true, cancelable: true, view: window })
          Button3ds.dispatchEvent(event)
        }
      } else {
        alert(wp.i18n.__('Authentication disabled by the store', 'lkn-wc-gateway-cielo'))
      }
    },
    onError: function (e) {
      lknUnlockSubmitButton()
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      // MPI900 = falha de rede/HTTP (ex.: 400/403/CORS no /v2/3ds/enroll).
      // Mensagem curta com o status HTTP para diagnóstico.
      if (e && e.ReturnCode === 'MPI900') {
        var httpStatus = ''
        if (e.ReturnMessage) {
          var statusMatch = String(e.ReturnMessage).match(/\((\d{3})\)/)
          if (statusMatch) {
            httpStatus = statusMatch[1]
          }
        }
        var mpiMessage = wp.i18n.__('3DS authentication error', 'lkn-wc-gateway-cielo')
        if (httpStatus) {
          mpiMessage += ' (HTTP ' + httpStatus + ')'
        }
        mpiMessage += '. ' + wp.i18n.__('Try again or contact the store.', 'lkn-wc-gateway-cielo')
        alert(mpiMessage)
      } else {
        alert(wp.i18n.__('3DS authentication error. Check your credentials.', 'lkn-wc-gateway-cielo'))
      }
    },
    onUnsupportedBrand: function (e) {
      lknUnlockSubmitButton()
      // Provider not supported for authentication — definitive error, always submit
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))
      const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]?.closest('form')
      if (Form3dsButton) {
        Form3dsButton.setAttribute('data-payment-cavv', '')
        Form3dsButton.setAttribute('data-payment-eci', '')
        Form3dsButton.setAttribute('data-payment-ref_id', '')
        Form3dsButton.setAttribute('data-payment-version', '')
        Form3dsButton.setAttribute('data-payment-xid', '')
        const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
        const event = new MouseEvent('click', { bubbles: true, cancelable: true, view: window })
        Button3ds.dispatchEvent(event)
      }
    },

    Environment: 'PRD', // SDB ou PRD
    Debug: false // true ou false
  }
}

function lknDCProccessButton () {
  // Reentrância: ignora se já há um fluxo 3DS em andamento.
  if (window.__lkn3DSInProgress) {
    return
  }
  // Feedback visual imediato e proteção contra duplo clique.
  lknLockSubmitButton()
  // Sempre executar 3DS, independente do tipo de cartão
  lknProcessDebitCard()
}

// Helper: retorna o primeiro valor não vazio de uma lista de IDs (fallback billing → shipping → custom)
function getDomValueWithFallback (ids) {
  for (var i = 0; i < ids.length; i++) {
    var el = document.getElementById(ids[i])
    if (el && el.value && el.value.trim() !== '') {
      return el.value.trim()
    }
  }
  return ''
}

// Helper: seta valor em elemento se existir
function setIfExists (id, value) {
  var el = document.getElementById(id)
  if (el) el.value = value
}

// Função para processar cartão de débito (com 3DS)
function lknProcessDebitCard () {
  try {
    var dcNoEl = document.getElementById('lkn_dcno')
    var cardNumber = dcNoEl ? dcNoEl.value.replace(/\D/g, '') : ''

    // No fluxo de blocos (Gutenberg) esta função só é executada depois que o
    // React valida os campos do cartão. Removida a checagem de offsetParent /
    // visibilidade, que fazia o pedido ser enviado sem 3DS (ECI/CAVV/XID
    // vazios) e resultava em "Autenticação Cielo 3DS 2.2 inválida".
    if (!dcNoEl || !cardNumber) {
      lknUnlockSubmitButton()
      var btn = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
      if (btn) btn.click()
      return;
    }

    // Garante que o tipo enviado ao 3DS (bpmpi_paymentmethod) corresponda ao
    // tipo de cartão. A Cielo exige "Credit"/"Debit" (valor inválido → 102).
    // Usa o id real do select do React (#card_type_selector); NÃO cai no
    // select de parcelas (.lkn-select-type), que não é o tipo de cartão.
    var cardTypeSelect = document.getElementById('card_type_selector') ||
      document.querySelector('.lkn-credit-debit-card-type-select select')
    var paymentMethodEl = document.querySelector('.bpmpi_paymentmethod')
    var rawCardType = (cardTypeSelect && cardTypeSelect.value) ||
      (typeof window.lknCurrentCardType !== 'undefined' ? window.lknCurrentCardType : '') ||
      (paymentMethodEl && paymentMethodEl.value) || 'Credit'
    var cardType = /^debit/i.test(rawCardType) ? 'Debit' : 'Credit'
    if (paymentMethodEl) {
      paymentMethodEl.value = cardType
    }

    var cardHolder = document.getElementById('lkn_dc_cardholder_name')

    // Nome do portador: cardholder > billing_first_name + billing_last_name > DOM block
    if (cardHolder && cardHolder.value.trim() !== '') {
      setIfExists('lkn_bpmpi_billto_contactname', cardHolder.value)
    } else {
      var firstName = getDomValueWithFallback(['billing_first_name', 'shipping_first_name'])
      var lastName = getDomValueWithFallback(['billing_last_name', 'shipping_last_name'])
      if (firstName || lastName) {
        setIfExists('lkn_bpmpi_billto_contactname', (firstName + ' ' + lastName).trim())
      } else {
        var nameBlock = document.querySelector('.wc-block-components-address-card__address-section')
        if (nameBlock && nameBlock.textContent) {
          setIfExists('lkn_bpmpi_billto_contactname', nameBlock.textContent.trim())
        }
      }
    }

    // Dados do portador com fallback billing → shipping → custom
    // Phone: billing-phone → shipping-phone → custom-phone
    setIfExists('lkn_bpmpi_billto_phonenumber', getDomValueWithFallback(['billing-phone', 'shipping-phone', 'custom-phone']))
    setIfExists('lkn_bpmpi_billto_street1', getDomValueWithFallback(['billing-address_1', 'shipping-address_1']))
    setIfExists('lkn_bpmpi_billto_street2', getDomValueWithFallback(['billing-address_2', 'shipping-address_2']))
    setIfExists('lkn_bpmpi_billto_city', getDomValueWithFallback(['billing-city', 'shipping-city']))
    setIfExists('lkn_bpmpi_billto_state', getDomValueWithFallback(['billing-state', 'shipping-state']))
    setIfExists('lkn_bpmpi_billto_zipcode', getDomValueWithFallback(['billing-postcode', 'shipping-postcode']))
    setIfExists('lkn_bpmpi_billto_country', getDomValueWithFallback(['billing-country', 'shipping-country']))
    setIfExists('lkn_bpmpi_billto_email', getDomValueWithFallback(['billing-email', 'shipping-email', 'email']))

    // CPF/CNPJ: campo personalizado > billing_cpf > billing_cnpj
    setIfExists('lkn_bpmpi_billto_customerid', getDomValueWithFallback(['lknCieloApiPixBillingCpf', 'billing_cpf', 'billing_cnpj', 'billing_document']))

    // Browser info para conformidade ELO 3DS
    setIfExists('lkn_bpmpi_device_useragent', navigator.userAgent || '')
    setIfExists('lkn_bpmpi_device_screenwidth', (screen.width || window.innerWidth || 0).toString())
    setIfExists('lkn_bpmpi_device_screenheight', (screen.height || window.innerHeight || 0).toString())
    setIfExists('lkn_bpmpi_device_colordepth', (screen.colorDepth || 24).toString())
    setIfExists('lkn_bpmpi_device_timezone', (new Date().getTimezoneOffset()).toString())
    setIfExists('lkn_bpmpi_device_javaenabled', (typeof navigator.javaEnabled === 'function' && navigator.javaEnabled()) ? 'true' : 'false')

    var expDate = document.getElementById('lkn_dc_expdate').value

    expDate = expDate.split('/')

    if (expDate.length === 2) {
      expDate[1] = '20' + expDate[1]
    }

    setIfExists('lkn_bpmpi_cardnumber', cardNumber)
    setIfExists('lkn_bpmpi_expmonth', expDate[0].replace(/\D/g, ''))
    setIfExists('lkn_bpmpi_expyear', expDate[1].replace(/\D/g, ''))

    // O bundle React (lknCieloDebitCompiled.js) já preenche bpmpi_installments
    // com a parcela correta. O bpmpi_totalamount ainda vem com o valor da
    // renderização inicial (sem juros). Lemos o total final do store do
    // WooCommerce Blocks (fonte React), aguardando o refetch terminar.
    lknReadBlockTotalAndProceed(10)
  } catch (error) {
    lknUnlockSubmitButton()
    console.log(error)
    alert(wp.i18n.__('Authentication not completed. Your order was not placed. Please try again.', 'lkn-wc-gateway-cielo'))
  }
}

// Carrega o BP.Mpi (init) sob demanda, no clique de finalizar, para que o
// amount enviado ao MPI reflita o total final (com juros/desconto).
function lknLoadBpmpiScript () {
  if (window.__lknBpmpiLoading) {
    return
  }
  window.__lknBpmpiLoading = true

  var scriptUrlBpmpi = window.__lknBpmpiUrl
  if (!scriptUrlBpmpi) {
    window.__lknPendingAuthenticate = false
    bpmpi_authenticate()
    return
  }

  if (!document.querySelector('script[src="' + scriptUrlBpmpi + '"]')) {
    var scriptBpmpi = document.createElement('script')
    scriptBpmpi.src = scriptUrlBpmpi
    scriptBpmpi.async = true
    document.body.appendChild(scriptBpmpi)
  }
}

function lknProceedToAuthenticate () {
  window.__lknPendingAuthenticate = true
  if (window.__lknBpmpiReady) {
    bpmpi_authenticate()
  } else {
    lknLoadBpmpiScript()
  }
}

// Lê o total final (com juros/desconto) direto do store do WooCommerce Blocks
// (React) — a fonte real que renderiza o "Total". Durante o refetch (loading)
// o selector ainda não terminou de resolver; aguardamos e tentamos de novo.
function lknReadBlockTotalAndProceed (retriesLeft) {
  var totalAmountEl = document.querySelector('.bpmpi_totalamount')
  if (!totalAmountEl) {
    lknProceedToAuthenticate()
    return
  }

  var cents = lknGetCartTotalCentsFromStore()
  if (cents > 0) {
    totalAmountEl.value = String(cents)
    lknProceedToAuthenticate()
    return
  }

  if (retriesLeft > 0) {
    setTimeout(function () {
      lknReadBlockTotalAndProceed(retriesLeft - 1)
    }, 150)
    return
  }

  // Esgotou as tentativas: usa o valor atual do hidden (fallback seguro).
  lknProceedToAuthenticate()
}

// Retorna o total do carrinho em centavos vindo do store wc/store/cart, ou 0
// se indisponível / ainda resolvendo.
function lknGetCartTotalCentsFromStore () {
  var select = window.wp && window.wp.data && window.wp.data.select
  if (typeof select !== 'function') {
    return 0
  }

  var store = null
  try {
    store = select('wc/store/cart')
  } catch (e) {
    return 0
  }

  if (!store) {
    return 0
  }

  // Se o selector ainda está resolvendo (ex.: refetch após troca de parcela),
  // retorna 0 para o retry aguardar.
  if (typeof store.hasFinishedResolution === 'function' && typeof store.getCartData === 'function') {
    if (!store.hasFinishedResolution('getCartData', [])) {
      return 0
    }
  }

  var totals = null
  try {
    totals = (typeof store.getCartTotals === 'function')
      ? store.getCartTotals()
      : (store.getCartData ? store.getCartData().totals : null)
  } catch (e) {
    return 0
  }

  if (totals && totals.total_price) {
    return parseInt(totals.total_price, 10) || 0
  }

  return 0
}
