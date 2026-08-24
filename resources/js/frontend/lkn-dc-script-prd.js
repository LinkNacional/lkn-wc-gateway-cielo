/* eslint-disable no-undef */
// Implements script internationalization
// const { __ } = wp.i18n; - Removido para evitar conflito de variável

// Flag global para controlar se 3DS já foi completado
let lkn3DSCompleted = false;

// Provider detectado via BIN — usado para pular MPI se bandeira não tem 3DS
let lknDetectedCardProvider = '';
// Tipo do cartão detectado via BIN: 'Debit' | 'Credit' (vazio = indeterminado)
let lknDetectedCardType = '';

// Função para resetar o status 3DS
function resetLkn3DSStatus() {
  lkn3DSCompleted = false;
  lknDetectedCardProvider = '';
  lknDetectedCardType = '';
  
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
        const url = restRoot + 'lknWCGatewayCielo/getCardBrand?number=' + encodeURIComponent(cardBin) + '&gateway=debit'
        $.ajax({
          url,
          type: 'GET',
          headers: {
            Accept: 'application/json',
            'X-WP-Nonce': nonce
          },
          success: function (response) {
            // Guardar provider detectado para pré-filtro 3DS
            if (response.brand) {
              lknDetectedCardProvider = response.brand.charAt(0).toUpperCase() + response.brand.slice(1);
            }

            // Guardar o tipo real do cartão (Debit/Credit) para o paymentmethod do 3DS
            if (response.cardType === 'Debito') {
              lknDetectedCardType = 'Debit'
            } else if (response.cardType === 'Credito') {
              lknDetectedCardType = 'Credit'
            }

            const options = document.querySelectorAll('#lkn_cc_type option')
            const currentSelection = document.querySelector('#lkn_cc_type').value

            // Reset all options: enable all but preserve current selection if possible
            options.forEach(function (option) {
              option.disabled = false
            })

            let shouldChangeSelection = false

            options.forEach(function (option) {
              if (response.cardType === 'Credito' && option.value !== 'Credit') {
                option.disabled = true
                if (currentSelection === option.value) {
                  shouldChangeSelection = true
                }
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = ''
                }
              } else if (response.cardType === 'Debito' && option.value !== 'Debit') {
                option.disabled = true
                if (currentSelection === option.value) {
                  shouldChangeSelection = true
                }
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = 'none'
                }
              } else if (response.cardType === 'Credito' && option.value === 'Credit') {
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = ''
                }
                if (shouldChangeSelection) {
                  option.selected = true
                }
              } else if (response.cardType === 'Debito' && option.value === 'Debit') {
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
      // UNAVAILABLE: stand-in CAVV válido (issuer indisponível) — submeter direto
      // NOT_ENROLLED: cartão sem 3DS — verificar allow_card_ineligible
      if (e.Cavv) {
        submitForm(e)
      } else if(lknDCScriptAllowCardIneligible.allow == 'yes'){
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
      // Provider not supported for authentication — definitive error, always submit
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage)
      submitForm(e)
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

    // Não preencher billing via DOM (fallback JS). Os campos bpmpi_billto_*
    // permanecem como o template PHP renderizou: preenchidos a partir do
    // user_meta para usuário logado com perfil completo; vazios para convidado.
    // Comportamento idêntico ao fluxo Gutenberg.

    // Pré-filtro por BIN: pular MPI se bandeira não suporta 3DS
    var supported3DSBrands = ['Visa', 'Mastercard', 'Elo', 'Amex', 'American Express'];
    if (lknDetectedCardProvider && supported3DSBrands.indexOf(lknDetectedCardProvider) === -1) {
      console.log('[CIELO 3DS] Provider ' + lknDetectedCardProvider + ' not 3DS-capable, skipping MPI');
      lkn3DSCompleted = true;
      // Limpar campos 3DS para indicar que não houve autenticação
      document.getElementById('lkn_cavv').value = '';
      document.getElementById('lkn_eci').value = '';
      document.getElementById('lkn_ref_id').value = '';
      document.getElementById('lkn_version').value = '';
      document.getElementById('lkn_xid').value = '';
      var btnSkip = document.getElementById('place_order');
      if (btnSkip) {
        btnSkip.removeEventListener('click', lknDCProccessButton, true);
        btnSkip.setAttribute('type', 'submit');
        btnSkip.click();
      }
      return;
    }

    // O gateway aceita crédito E débito. O bpmpi_paymentmethod deve refletir o
    // tipo REAL da operação: usa o tipo detectado via BIN quando a API
    // consegue distinguir (Debito/Credito); caso contrário (cartão "Multiplo"),
    // respeita o que o cliente selecionou no select lkn_cc_type.
    var cardTypeSelect = document.getElementById('lkn_cc_type')
    var paymentMethodEl = document.querySelector('.bpmpi_paymentmethod')
    var cardType = (lknDetectedCardType === 'Credit' || lknDetectedCardType === 'Debit')
      ? lknDetectedCardType
      : ((cardTypeSelect && cardTypeSelect.value) || (paymentMethodEl && paymentMethodEl.value) || 'Credit')
    if (paymentMethodEl) {
      paymentMethodEl.value = cardType
    }

    // Reaplica o orderNumber congelado no load, garantindo que o enroll use o
    // MESMO valor que o /v2/3ds/init usou (evita 400 por divergência).
    if (window.__lknOrderNumber3ds) {
      const orderNumberEl = document.querySelector('.bpmpi_ordernumber')
      if (orderNumberEl) {
        orderNumberEl.value = window.__lknOrderNumber3ds
      }
    }

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

    if (window.__lknBpmpiLoadStarted) {
      return
    }
    window.__lknBpmpiLoadStarted = true

    // Congela o orderNumber que o /v2/3ds/init vai usar. O campo pode ser
    // re-renderizado pelo updated_checkout (cache de página), fazendo o enroll
    // enviar um orderNumber diferente do init e resultando em 400.
    const orderNumberEl = document.querySelector('.bpmpi_ordernumber')
    if (orderNumberEl && orderNumberEl.value) {
      window.__lknOrderNumber3ds = orderNumberEl.value
    }

    const appendBpmpi = function () {
      if (!document.querySelector(`script[src="${scriptUrlBpmpi}"]`)) {
        const scriptBpmpi = document.createElement('script')
        scriptBpmpi.src = scriptUrlBpmpi
        scriptBpmpi.async = true
        document.body.appendChild(scriptBpmpi)
      }
    }

    // O access token embutido no HTML pode estar expirado (cache de página),
    // o que faz o /v2/3ds/init devolver 401. Busca um token fresco via REST
    // antes de carregar o BP.Mpi (igual ao fluxo Gutenberg).
    const restRoot = (typeof lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.rest_url)
      ? lknCieloRestSettings.rest_url
      : null
    const nonce = (typeof lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.nonce)
      ? lknCieloRestSettings.nonce
      : ''

    if (!restRoot) {
      appendBpmpi()
      return
    }

    fetch(restRoot + 'lknWCGatewayCielo/getAcessToken', {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'X-WP-Nonce': nonce
      }
    })
      .then(function (res) { return res.json() })
      .then(function (data) {
        if (data && data.access_token) {
          const tokenEl = document.getElementsByClassName('bpmpi_accesstoken')[0]
          if (tokenEl) {
            tokenEl.value = data.access_token
          }
          if (data.expires_in) {
            const expEl = document.getElementById('expires_in')
            if (expEl) {
              expEl.value = data.expires_in
            }
          }
        }
        appendBpmpi()
      })
      .catch(function () {
        appendBpmpi()
      })
  }
})

// Botão custom "Confirm Payment" no layout padrão (shortcode)
// Delegação em `document` (sem jQuery): o listener fica no document, que é
// persistente, então sobrevive à recriação do formulário pelo updated_checkout.
var lknCustomSubmitLockedUntil = 0

document.addEventListener('click', function (event) {
  var target = event.target
  var btn = (target && typeof target.closest === 'function')
    ? target.closest('#cielo-debit-submit-btn')
    : null

  if (!btn) {
    return
  }

  event.preventDefault()

  // Trava global por 15s: evita múltiplos cliques mesmo se o botão for recriado.
  if (Date.now() < lknCustomSubmitLockedUntil) {
    return
  }
  lknCustomSubmitLockedUntil = Date.now() + 15000

  // Desabilita o botão; o efeito cinza é aplicado via CSS (#cielo-debit-submit-btn:disabled).
  btn.disabled = true

  // Reabilita o botão após o período de trava.
  setTimeout(function () {
    btn.disabled = false
  }, 15000)

  var placeOrder = document.getElementById('place_order')

  // Reaproveita o mesmo fluxo do botão nativo #place_order (3DS -> submit).
  if (typeof lknDCProccessButton === 'function') {
    lknDCProccessButton()
  } else if (placeOrder) {
    placeOrder.click()
  }
})