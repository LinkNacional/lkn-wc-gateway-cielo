/* eslint-disable no-undef */
// Implements script internationalization
// const { __ } = wp.i18n; - Removido para evitar conflito de variável

// Flag global para controlar se 3DS já foi completado
let lkn3DSCompleted = false;

// Função para resetar o status 3DS
function resetLkn3DSStatus() {
  lkn3DSCompleted = false;
  
  // Reconfigurar o botão para tipo button (caso tenha sido alterado)
  const btnSubmit = document.getElementById('place_order');
  if (btnSubmit) {
    btnSubmit.setAttribute('type', 'button');
    btnSubmit.removeEventListener('click', lknDCProccessButton, true);
    btnSubmit.addEventListener('click', lknDCProccessButton, true);
  }
}

// Detectar erros de checkout e resetar 3DS
function setupErrorDetection() {
  // Garantir que o document.body existe
  if (!document.body) {
    // Se o body não existe ainda, aguardar o DOM estar pronto
    document.addEventListener('DOMContentLoaded', setupErrorDetection);
    return;
  }

  // Observar mensagens de erro do WooCommerce
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      mutation.addedNodes.forEach(function(node) {
        if (node.nodeType === 1) {
          // Detectar notices de erro
          const errorElements = node.querySelectorAll ? 
            node.querySelectorAll('.woocommerce-error, .woocommerce-message, .wc-block-components-notice-banner--error') : [];
          
          if (errorElements.length > 0 || 
              (node.classList && (node.classList.contains('woocommerce-error') || 
               node.classList.contains('wc-block-components-notice-banner--error')))) {
            resetLkn3DSStatus();
          }
        }
      });
    });
  });
  
  observer.observe(document.body, { childList: true, subtree: true });
  
  // Detectar quando o checkout é atualizado (falha de validação) - usando jQuery global
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('checkout_error updated_checkout', function() {
      setTimeout(resetLkn3DSStatus, 100);
    });
  }
}

(function ($) {
  'use strict'
  
  // Inicializar detecção de erros dentro do contexto jQuery
  setupErrorDetection();

  const lknLoadDebitFunctions = function () {
    const btnSubmit = document.getElementById('place_order')

    if (btnSubmit) {
      btnSubmit.setAttribute('type', 'button')
      btnSubmit.removeEventListener('click', lknDCProccessButton, true)
      btnSubmit.addEventListener('click', lknDCProccessButton, true)
    }
  }

  const lknVerifyGateway = function () {
    const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')

    if (debitPaymethod && debitPaymethod.checked === false) {
      const btnSubmit = document.getElementById('place_order')
      if (btnSubmit) {
        btnSubmit.setAttribute('type', 'submit')
        btnSubmit.removeEventListener('click', lknDCProccessButton, true)
      }
    }
  }

  $(window).on('load', () => {
    const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')
    const debitForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
    const paymentBox = document.getElementById('payment')
    const lknWcCieloPaymentCCTypeInput = document.querySelector('#lkn_cc_type')
    const lknWcCieloCcDcInstallment = document.querySelector('#lkn_cc_dc_installments')
    const lknWcCieloCcDcNo = document.querySelector('#lkn_dcno')

    if (lknWcCieloCcDcNo && lknWcCieloPaymentCCTypeInput) {
      lknWcCieloCcDcNo.onchange = (e) => {
        // Skip BIN auto-detection when card type is forced by admin config
        if (typeof lknDCCardTypeMode !== 'undefined' && lknDCCardTypeMode.mode !== 'both') {
          return
        }
        const cardBin = e.target.value.replace(/\s+/g, '').substring(0, 6)
        if(!cardBin) {
          return
        }
        // Fallback: suporta tanto rest_url (padrão) quanto root (legado)
        var restRoot = (typeof lknCieloRestSettings !== 'undefined' && (lknCieloRestSettings.rest_url || lknCieloRestSettings.root)) 
          ? (lknCieloRestSettings.rest_url || lknCieloRestSettings.root)
          : (typeof wpApiSettings !== 'undefined' && wpApiSettings.root ? wpApiSettings.root : null)
        if (!restRoot) {
          console.warn('lknCieloRestSettings and wpApiSettings not available, skipping BIN check')
          return
        }
        var nonce = (typeof lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.nonce)
          ? lknCieloRestSettings.nonce
          : (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce ? wpApiSettings.nonce : '')
        const url = restRoot + 'lknWCGatewayCielo/checkCard?cardbin=' + cardBin
        $.ajax({
          url,
          type: 'GET',
          headers: {
            Accept: 'application/json',
            'X-WP-Nonce': nonce
          },
          success: function (response) {
            const options = document.querySelectorAll('#lkn_cc_type option')
            const currentSelection = document.querySelector('#lkn_cc_type').value

            // Reset all options: enable all but preserve current selection if possible
            options.forEach(function (option) {
              option.disabled = false
            })

            let shouldChangeSelection = false

            options.forEach(function (option) {
              if (response.CardType === 'Crédito' && option.value !== 'Credit') {
                option.disabled = true
                if (currentSelection === option.value) {
                  shouldChangeSelection = true
                }
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = ''
                }
              } else if (response.CardType === 'Débito' && option.value !== 'Debit') {
                option.disabled = true
                if (currentSelection === option.value) {
                  shouldChangeSelection = true
                }
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = 'none'
                }
              } else if (response.CardType === 'Crédito' && option.value === 'Credit') {
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = ''
                }
                if (shouldChangeSelection) {
                  option.selected = true
                }
              } else if (response.CardType === 'Débito' && option.value === 'Debit') {
                if (shouldChangeSelection) {
                  option.selected = true
                }
              }
            })
          },
          error: function (error) {
            console.error('Erro:', error)
          }
        })
      }
    }

    if (debitPaymethod || debitForm) {
      lknLoadDebitFunctions()
    }

    if (debitPaymethod) {
      debitPaymethod.removeEventListener('click', lknLoadDebitFunctions, true)
      debitPaymethod.addEventListener('click', lknLoadDebitFunctions, true)
    }

    if (paymentBox) {
      paymentBox.removeEventListener('click', lknVerifyGateway, true)
      paymentBox.addEventListener('click', lknVerifyGateway, true)
    }

    $('body').on('updated_checkout', function () {
      const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')
      const debitForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
      const paymentBox = document.getElementById('payment')

      if (debitPaymethod || debitForm) {
        lknLoadDebitFunctions()
      }

      if (debitPaymethod) {
        debitPaymethod.removeEventListener('click', lknLoadDebitFunctions, true)
        debitPaymethod.addEventListener('click', lknLoadDebitFunctions, true)
      }

      if (paymentBox) {
        paymentBox.removeEventListener('click', lknVerifyGateway, true)
        paymentBox.addEventListener('click', lknVerifyGateway, true)
      }
    })
  })
})(jQuery)

function submitForm (e) {
  const cavv = e.Cavv || ''
  const xid = e.Xid || ''
  const eci = e.Eci || ''
  const version = e.Version || ''
  const referenceId = e.ReferenceId || ''
  const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]?.closest('form')

  // Marcar que 3DS foi completado
  lkn3DSCompleted = true;

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
    return;
  }

  // Configurar os valores dos campos hidden para checkout clássico
  document.getElementById('lkn_cavv').value = cavv
  document.getElementById('lkn_eci').value = eci
  document.getElementById('lkn_ref_id').value = referenceId
  document.getElementById('lkn_version').value = version
  document.getElementById('lkn_xid').value = xid

  // Fazer clique no botão para continuar o processo
  const btnSubmit = document.getElementById('place_order')
  if (btnSubmit) {
    btnSubmit.removeEventListener('click', lknDCProccessButton, true)
    btnSubmit.setAttribute('type', 'submit')
    
    // Timeout para detectar se o envio falhou
    setTimeout(function() {
      if (document.getElementById('place_order') && window.location.pathname.includes('checkout')) {
        resetLkn3DSStatus();
      }
    }, 5000);
    
    btnSubmit.click()
  }
}

function bpmpi_config () {
  return {
    onReady: function () {
    },
    onSuccess: function (e) {
      // Card is eligible for authentication, and the bearer successfully authenticated
      submitForm(e)
    },
    onFailure: function (e) {
      // Card is not eligible for authentication, but the bearer failed payment
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage)

      const lknDebitCCForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
      if (lknDebitCCForm) {
        if(lknDCScriptAllowCardIneligible.allow == 'yes'){
          submitForm(e)
        }else{
          alert(wp.i18n.__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
        }
      }
    },
    onUnenrolled: function (e) {
      // Card is not eligible for authentication (unauthenticable)
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage)
      // ECI 04/07 = Data Only = NÃO autenticada. Requer allow_card_ineligible.
      if(lknDCScriptAllowCardIneligible.allow == 'yes'){
        submitForm(e)
      }else{
        alert(wp.i18n.__('Card Ineligible for Authentication', 'lkn-wc-gateway-cielo'))
      }
    },
    onDisabled: function (e) {
      // Store don't require bearer authentication (class "bpmpi_auth" false -> disabled authentication).
      console.log('code ' + (e ? e.ReturnCode : 'N/A') + ' ' + ' message ' + (e ? e.ReturnMessage : 'N/A'))
      //aqui
      if(lknDCScriptAllowCardIneligible.allow == 'yes'){
        submitForm(e || {})
      }else{
        alert(wp.i18n.__('Authentication disabled by the store', 'lkn-wc-gateway-cielo'))
      }
    },
    onError: function (e) {
      // Error on proccess in authentication
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage)

      const lknDebitCCForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
      if (lknDebitCCForm) {
        alert(wp.i18n.__('Error in the 3DS 2.2 authentication process check that your credentials are filled in correctly', 'lkn-wc-gateway-cielo'))
      }
    },
    onUnsupportedBrand: function (e) {
      // Provider not supported for authentication
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage)
      //aqui
      if(lknDCScriptAllowCardIneligible.allow == 'yes'){
        submitForm(e)
      }else{
        alert(wp.i18n.__('Provider not supported by Cielo 3DS authentication', 'lkn-wc-gateway-cielo'))
      }
    },

    Environment: 'PRD', // SDB or PRD
    Debug: false // true or false
  }
}

function lknDCProccessButton () {
  try {
    // Sempre executar 3DS, independente do tipo de cartão
    
    // Se 3DS já foi completado, submeter diretamente
    if (lkn3DSCompleted) {
      const btnSubmit = document.getElementById('place_order')
      if (btnSubmit) {
        btnSubmit.removeEventListener('click', lknDCProccessButton, true)
        btnSubmit.setAttribute('type', 'submit')
        
        // Timeout para detectar se o envio falhou
        setTimeout(function() {
          if (document.getElementById('place_order') && window.location.pathname.includes('checkout')) {
            resetLkn3DSStatus();
          }
        }, 5000);
        
        btnSubmit.click()
      }
      return;
    }

    // Se o gateway está selecionado mas não há campo de cartão visível,
    // o pedido não precisa de 3DS (ex: produto gratuito, total R$0).
    // Submete o formulário normalmente.
    var lknDcnoEl = document.getElementById('lkn_dcno')
    if (!lknDcnoEl || !lknDcnoEl.value.trim() || !lknDcnoEl.offsetParent) {
      var btnSubmitFree = document.getElementById('place_order')
      if (btnSubmitFree) {
        btnSubmitFree.removeEventListener('click', lknDCProccessButton, true)
        btnSubmitFree.setAttribute('type', 'submit')
        btnSubmitFree.click()
      }
      return;
    }

    const cardNumber = document.getElementById('lkn_dcno').value.replace(/\D/g, '')
    let expDate = document.getElementById('lkn_dc_expdate').value

    expDate = expDate.split('/')

    if (expDate.length === 2) {
      expDate[1] = '20' + expDate[1]
    }

    document.getElementById('lkn_bpmpi_cardnumber').value = cardNumber
    document.getElementById('lkn_bpmpi_expmonth').value = expDate[0].replace(/\D/g, '')
    document.getElementById('lkn_bpmpi_expyear').value = expDate[1].replace(/\D/g, '')

    // Preencher campos browser info para conformidade ELO 3DS
    var userAgentEl = document.getElementById('lkn_bpmpi_device_useragent')
    if (userAgentEl) {
      userAgentEl.value = navigator.userAgent || ''
    }
    var screenWidthEl = document.getElementById('lkn_bpmpi_device_screenwidth')
    if (screenWidthEl) {
      screenWidthEl.value = (screen.width || window.innerWidth || 0).toString()
    }
    var screenHeightEl = document.getElementById('lkn_bpmpi_device_screenheight')
    if (screenHeightEl) {
      screenHeightEl.value = (screen.height || window.innerHeight || 0).toString()
    }
    var colorDepthEl = document.getElementById('lkn_bpmpi_device_colordepth')
    if (colorDepthEl) {
      colorDepthEl.value = (screen.colorDepth || 24).toString()
    }
    var timezoneEl = document.getElementById('lkn_bpmpi_device_timezone')
    if (timezoneEl) {
      timezoneEl.value = (new Date().getTimezoneOffset()).toString()
    }
    var javaEnabledEl = document.getElementById('lkn_bpmpi_device_javaenabled')
    if (javaEnabledEl) {
      javaEnabledEl.value = (typeof navigator.javaEnabled === 'function' && navigator.javaEnabled()) ? 'true' : 'false'
    }

    // Preencher campos billto com fallback DOM: billing → shipping → custom
    // Phone com 3 camadas: billing-phone → shipping-phone → custom-phone
    function getDomValueWithFallback(ids) {
      for (var i = 0; i < ids.length; i++) {
        var el = document.getElementById(ids[i])
        if (el && el.value && el.value.trim() !== '') {
          return el.value.trim()
        }
      }
      return ''
    }
    function setIfExists(id, value) {
      var el = document.getElementById(id)
      if (el) el.value = value
    }
    setIfExists('lkn_bpmpi_billto_phonenumber', getDomValueWithFallback(['billing-phone', 'shipping-phone', 'custom-phone']))
    setIfExists('lkn_bpmpi_billto_street1', getDomValueWithFallback(['billing-address_1', 'shipping-address_1']))
    setIfExists('lkn_bpmpi_billto_street2', getDomValueWithFallback(['billing-address_2', 'shipping-address_2']))
    setIfExists('lkn_bpmpi_billto_city', getDomValueWithFallback(['billing-city', 'shipping-city']))
    setIfExists('lkn_bpmpi_billto_state', getDomValueWithFallback(['billing-state', 'shipping-state']))
    setIfExists('lkn_bpmpi_billto_zipcode', getDomValueWithFallback(['billing-postcode', 'shipping-postcode']))
    setIfExists('lkn_bpmpi_billto_country', getDomValueWithFallback(['billing-country', 'shipping-country']))
    setIfExists('lkn_bpmpi_billto_email', getDomValueWithFallback(['billing-email', 'shipping-email']))
    setIfExists('lkn_bpmpi_billto_contactname', getDomValueWithFallback(['billing-first_name']))
    // Se o email do DOM não foi encontrado, manter o valor já existente no campo hidden

    bpmpi_authenticate()
  } catch (error) {
    resetLkn3DSStatus();
    alert(wp.i18n.__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
  }
}

// Carrega js do 3DS
document.addEventListener('DOMContentLoaded', function () {
  const radioInputCieloDebitId = 'payment_method_lkn_cielo_debit';

  // Configura o MutationObserver para monitorar alterações no DOM
  const observer = new MutationObserver((mutationsList) => {
    // Verifica se o input de pagamento desejado está selecionado
    const radioInputCieloDebit = document.getElementById(radioInputCieloDebitId);
    if (radioInputCieloDebit && radioInputCieloDebit.checked) {
      lknWcGatewayCieloLoadScript()
    }
  })

  // Configura o observer para observar mudanças no body
  observer.observe(document.body, {
    childList: true, // Monitoramento de adição/remoção de elementos
    subtree: true,   // Monitoramento em todo o DOM, não apenas no nível imediato
  });

  function lknWcGatewayCieloLoadScript () {
    const scriptUrlBpmpi = lknDCDirScript3DSCieloShortCode.url
    const existingScriptBpmpi = document.querySelector(`script[src="${scriptUrlBpmpi}"]`)

    if (!existingScriptBpmpi) {
      const scriptBpmpi = document.createElement('script')
      scriptBpmpi.src = scriptUrlBpmpi
      scriptBpmpi.async = true
      document.body.appendChild(scriptBpmpi)
    }
  }
})