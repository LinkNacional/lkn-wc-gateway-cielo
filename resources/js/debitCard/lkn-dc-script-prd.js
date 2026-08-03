/* eslint-disable no-undef */
// Implements script internationalization

function bpmpi_config () {
  return {
    onReady: function () {
    },
    onSuccess: function (e) {
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
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      const allowCardIneligible = window.lknDCScriptAllowCardIneligible && window.lknDCScriptAllowCardIneligible.allow === 'yes'
      if (allowCardIneligible) {
        // User opted to continue even on 3DS failure — submit with whatever auth data we have
        const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]?.closest('form')
        if (Form3dsButton) {
          Form3dsButton.setAttribute('data-payment-cavv', e.Cavv || '')
          Form3dsButton.setAttribute('data-payment-eci', e.Eci || '')
          Form3dsButton.setAttribute('data-payment-ref_id', e.ReferenceId || '')
          Form3dsButton.setAttribute('data-payment-version', e.Version || '')
          Form3dsButton.setAttribute('data-payment-xid', e.Xid || '')
          const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
          const event = new MouseEvent('click', { bubbles: true, cancelable: true, view: window })
          Button3ds.dispatchEvent(event)
        }
      } else {
        alert(wp.i18n.__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
      }
    },
    onUnenrolled: function (e) {
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
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      // Error on proccess in authentication
      alert(wp.i18n.__('Error in the 3DS 2.2 authentication process check that your credentials are filled in correctly', 'lkn-wc-gateway-cielo'))
    },
    onUnsupportedBrand: function (e) {
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
    var cardNumber = document.getElementById('lkn_dcno').value.replace(/\D/g, '')

    // Se não há campo de cartão visível, não precisa de 3DS — submete direto
    if (!document.getElementById('lkn_dcno') || !cardNumber || !document.getElementById('lkn_dcno').offsetParent) {
      var btn = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
      if (btn) btn.click()
      return;
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

    bpmpi_authenticate()
  } catch (error) {
    console.log(error)
    alert(wp.i18n.__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
  }
}
