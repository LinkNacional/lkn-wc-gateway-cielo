import React from 'react'
import Cards from 'react-credit-cards'
import 'react-credit-cards/es/styles-compiled.css'
import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
const lknDCsettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const lknDCCardTypeMode = lknDCsettingsCielo.cardTypeMode || 'both'
const lknDCLabelCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.title)
const lknDCDescriptionCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.description)
const lknDCAccessTokenCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessToken)
const lknDCAccessTokenExpiration = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessTokenExpiration)
const lknDCshowCard = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.showCard)
const lknDCActiveInstallmentCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.activeInstallment)
const lknDCUrlCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.url)
const lknDCTotalCartCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.totalCart)
const lknDCOrderNumberCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.orderNumber)
const lknDCDirScript3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScript3DS)
const lknDCInstallmentLimitCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installmentLimit)
const lknDCInstallmentMinAmount = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installmentMinAmount)
const lknCC3DSinstallmentsCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installments)
const lknDCDirScriptConfig3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScriptConfig3DS)
const lknDCTranslationsDebitCielo = lknDCsettingsCielo.translations
const lknDCNonceCieloDebit = lknDCsettingsCielo.nonceCieloDebit
const lknDCTranslationsCielo = lknDCsettingsCielo.translations
const lknDCBec = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.bec)
const lknDCClientIp = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.client_ip)
const lknDCUserGuest = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.user_guest)
const lknDCAuthMethod = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.authentication_method)
const lknDCClient = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.client)

// Definir variável global para comunicar com o script 3DS
window.lknCurrentCardType = lknDCCardTypeMode === 'only_debit' ? 'Debit' : 'Credit'

// Função para formatação de moeda baseada nas configurações do WooCommerce
const formatCurrency = (amount) => {
  if (!window.lknCieloDebitConfig || !window.lknCieloDebitConfig.currency) {
    // Fallback para BRL se as configurações não estiverem disponíveis
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(amount)
  }

  const currency = window.lknCieloDebitConfig.currency
  const locale = currency.code === 'BRL' ? 'pt-BR' : 'en-US' // Pode ser expandido conforme necessário

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency.code,
    minimumFractionDigits: currency.decimals,
    maximumFractionDigits: currency.decimals
  }).format(amount)
}

const lknDCInitCieloPaymentForm = () => {

  // Load Cielo 3DS Config Script FIRST
  const scriptUrl = lknDCDirScriptConfig3DSCielo
  const existingScript = document.querySelector(`script[src="${scriptUrl}"]`)
  
  if (!existingScript) {
    const script = document.createElement('script')
    script.src = scriptUrl
    script.async = false // Load synchronously to ensure bpmpi_config is defined
    script.onload = () => {
      // Only load BP.Mpi after config script is loaded
      const scriptUrlBpmpi = lknDCDirScript3DSCielo
      const existingScriptBpmpi = document.querySelector(`script[src="${scriptUrlBpmpi}"]`)
      if (!existingScriptBpmpi) {
        const scriptBpmpi = document.createElement('script')
        scriptBpmpi.src = scriptUrlBpmpi
        scriptBpmpi.async = true
        document.body.appendChild(scriptBpmpi)
      }
    }
    document.body.appendChild(script)
  } else {
    // Config script already exists, load BP.Mpi
    const scriptUrlBpmpi = lknDCDirScript3DSCielo
    const existingScriptBpmpi = document.querySelector(`script[src="${scriptUrlBpmpi}"]`)
    if (!existingScriptBpmpi) {
      const scriptBpmpi = document.createElement('script')
      scriptBpmpi.src = scriptUrlBpmpi
      scriptBpmpi.async = true
      document.body.appendChild(scriptBpmpi)
    }
  }
}
const lknDCContentCielo = props => {
  // 1. Estados para cartões salvos
  const lknCieloDebitConfig = window.lknCieloDebitConfig || {};
  const { card_array = [], default_card = null } = lknCieloDebitConfig;
  const [selectedCardIndex, setSelectedCardIndex] = window.wp.element.useState(default_card ? parseInt(default_card) : 0);
  const [showAddCardForm, setShowAddCardForm] = window.wp.element.useState(!default_card);

  // 2. Renderizar cartões salvos (visual bonito)
  const cardBrandIcons = {
    Visa: window.lknCieloDebitCardIcons?.visa || '',
    Master: window.lknCieloDebitCardIcons?.mastercard || '',
    Amex: window.lknCieloDebitCardIcons?.amex || '',
    Elo: window.lknCieloDebitCardIcons?.elo || '',
    // Adicione outras bandeiras se necessário
    default: window.lknCieloDebitCardIcons?.other_card || ''
  };

  const renderSavedCards = () => (
    card_array.length > 0 ? (
      <div className="lkn-cielo-saved-cards-list lkn-cielo-saved-cards-flex" style={{
        display: 'flex',
        flexWrap: 'wrap',
        marginBottom: "30px",
        gap: '10px',
        width: '100%',
        justifyContent: 'center',
      }}>
        {card_array.map((card, idx) => {
          const isSelected = selectedCardIndex === idx;
          const brandIcon = cardBrandIcons[card.brand] || cardBrandIcons.default;
          return (
            <button
              type="button"
              key={idx}
              className={`lkn-cielo-saved-card-btn${isSelected ? ' selected' : ''}`}
              onClick={() => {
                setSelectedCardIndex(idx);
                setShowAddCardForm(false);
                updatedebitObject('lkn_dcno', card.cardDigits);
                updatedebitObject('lkn_dc_cardholder_name', card.description || '');
                updatedebitObject('lkn_dc_expdate', card.expirationDate);
                updatedebitObject('lkn_cc_type', card.brand);
              }}
              style={{
                color:  "#2563eb",
                fontWeight: 500,
                fontSize: 16,
                cursor: 'pointer',
                padding: '10px 18px',
                display: 'flex',
                alignItems: 'center',
                gap: 10,
                width: 225,
                border: 'none',
                transition: 'all 0.2s',
                outline: isSelected ? '2px solid #2563eb' : 'none',

              }}
            >
              {brandIcon && <img src={brandIcon} alt={card.brand} style={{ height: 40, marginRight: 8 }} />}
              <span style={{ fontWeight: 600 }}>{card.brand}</span>
              <span style={{ marginLeft: 6 }}>{card.cardDigits.replace(/.*(\d{4})$/, '•••• $1')}</span>
            </button>
          );
        })}
        <button
          type="button"
          className={`lkn-cielo-saved-card-btn lkn-cielo-add-card-btn${showAddCardForm ? ' selected' : ''}`}
          onClick={() => {
            setShowAddCardForm(true);
            setSelectedCardIndex(null);
            updatedebitObject('lkn_dcno', '');
            updatedebitObject('lkn_dc_cardholder_name', '');
            updatedebitObject('lkn_dc_expdate', '');
            updatedebitObject('lkn_cc_type', 'Credit');
          }}
          style={{
            fontWeight: 500,
            color: '#2563eb',
            fontSize: 16,
            cursor: 'pointer',
            padding: '10px 18px',
            display: 'flex',
            alignItems: 'center',
            gap: 10,
            border: 'none',
            width: 225,
            display: 'flex',
            justifyContent: 'center',
            outline: showAddCardForm ? '2px solid #2563eb' : 'none',
          }}
        >
          <span style={{ fontSize: 22, marginRight: 8 }}>＋</span> Adicionar Cartão
        </button>
      </div>
    ) : null
  );

  const wcComponents = window.wc.blocksComponents
  const {
    eventRegistration,
    emitResponse
  } = props
  const {
    onPaymentSetup
  } = eventRegistration
  const [options, setOptions] = window.wp.element.useState([])
  const [isLoadingOptions, setIsLoadingOptions] = window.wp.element.useState(true)
  const [showInstallmentSelect, setShowInstallmentSelect] = window.wp.element.useState(false)
  const [cardBinState, setCardBinState] = window.wp.element.useState(0)
  const [cardTypeOptions, setCardTypeOptions] = window.wp.element.useState(
    lknDCCardTypeMode === 'only_debit'
    ? [{ key: 'Debit', label: lknDCTranslationsCielo.debitCard }]
    : lknDCCardTypeMode === 'only_credit'
    ? [{ key: 'Credit', label: lknDCTranslationsCielo.creditCard }]
    : [{
        key: 'Credit',
        label: lknDCTranslationsCielo.creditCard
      }, {
        key: 'Debit',
        label: lknDCTranslationsCielo.debitCard
      }]
  )
  const [debitObject, setdebitObject] = window.wp.element.useState({
    lkn_dc_cardholder_name: '',
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: '',
    lkn_cc_dc_installments: '1',
    // Definir padrão como 1 parcela
    lkn_cc_type: lknDCCardTypeMode === 'only_debit' ? 'Debit' : 'Credit',
    lkn_save_debit_credit_card: false
  })
  const lknDCCardTypeSelectDisabled = lknDCCardTypeMode !== 'both'
  const [focus, setFocus] = window.wp.element.useState('')

  // Force disabled on native <select> when SortSelect doesn't pass the prop through
  window.wp.element.useEffect(() => {
    if (lknDCCardTypeSelectDisabled) {
      const selectEl = document.getElementById('lkn_cc_type');
      if (selectEl) {
        selectEl.disabled = true;
        selectEl.style.color = '#888';
        selectEl.style.pointerEvents = 'none';
      }
      // Also find the one rendered by SortSelect (different id)
      const altSelect = document.querySelector('.lkn-cc-type-readonly select');
      if (altSelect) {
        altSelect.disabled = true;
        altSelect.style.color = '#888';
        altSelect.style.pointerEvents = 'none';
      }
    }
  }, [lknDCCardTypeSelectDisabled, debitObject.lkn_cc_type])

  // Função para processar dados do carrinho (tanto da API normal quanto da batch)
  const processCartData = (cartData) => {
    // Detecta se é dados da API batch (tem structure responses[0].body) ou API normal
    const data = cartData.responses ? cartData.responses[0].body : cartData
    
    if (data && data.totals) {
      const totals = data.totals

      // Nova fórmula: base = subtotal + shipping
      // Juros/desconto aplicado sobre a base dentro de calculateInstallments
      // Depois soma: externalFees - discount + taxes
      const subtotal = parseFloat(totals.total_items) || 0
      const shipping = parseFloat(totals.total_shipping) || 0
      const discount = parseFloat(totals.total_discount) || 0 // Desconto de cupons (valor negativo)
      const tax = parseFloat(totals.total_tax) || 0

      // Calcula fees externas (excluindo as do plugin Cielo)
      let externalFees = 0
      if (data.totals.total_fees && data.fees) {

        data.fees.forEach(fee => {
          const feeName = fee.name || ''
          // Se a fee NÃO contém os labels do plugin Cielo, é uma fee externa
          if (!feeName.includes('card') && !feeName.includes('cartão') && !feeName.includes('Card') && !feeName.includes('Cartão')) {
            externalFees += parseFloat(fee.totals.total) || 0
          }
        })
      }

      // Base para cálculo de juros/desconto: apenas subtotal + frete
      const baseAmount = (subtotal + shipping) / 100

      // Valores que serão somados no final
      const additionalValues = {
        externalFees: externalFees / 100,
        discount: discount / 100, // Já é negativo
        tax: tax / 100
      }

      return {
        subtotal: subtotal / 100,
        shipping: shipping / 100,
        baseAmount,
        additionalValues
      }
    }
    return null
  }

  // Nova função para buscar dados do carrinho diretamente
  const fetchCartData = async () => {
    try {
      const response = await fetch('/wp-json/wc/store/v1/cart', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const cartData = await response.json()
      const processedData = processCartData(cartData)

      if (processedData && processedData.baseAmount > 0) {
        setOptions([]) // Limpa opções antigas
        calculateInstallments(processedData.baseAmount, processedData.additionalValues)
      }

      return processedData
    } catch (error) {
      console.error('Erro ao buscar dados do carrinho:', error)
      return null
    }
  }

  // Função para executar múltiplas requisições com delay (para o loading na primeira resposta)
  const fetchCartDataWithRetries = async (retries = 4, delay = 1500, onFirstData = null) => {
    let lastCartData = null
    let firstDataSent = false

    for (let i = 0; i < retries; i++) {
      const cartData = await fetchCartData()

      if (cartData) {
        lastCartData = cartData

        // Na primeira vez que obtém dados, chama o callback para parar o loading
        if (!firstDataSent && onFirstData) {
          onFirstData(cartData)
          firstDataSent = true
        }
      }      // Sempre aguarda o delay (exceto na última tentativa)
      if (i < retries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay))
      }
    }

    return lastCartData
  }
  const formatDebitCardNumber = value => {
    if (value?.length > 24) return debitObject.lkn_dcno
    // Remove caracteres não numéricos
    const cleanedValue = value?.replace(/\D/g, '')
    // Adiciona espaços a cada quatro dígitos
    const formattedValue = cleanedValue?.replace(/(.{4})/g, '$1 ')?.trim()
    return formattedValue
  }
  const updatedebitObject = (key, value) => {
    // Atualizar variável global imediatamente quando o tipo de cartão mudar
    if (key === 'lkn_cc_type') {
      window.lknCurrentCardType = value
    }
    
    switch (key) {
      case 'lkn_dc_cardholder_name':
        // Atualiza o estado
        setdebitObject({
          ...debitObject,
          [key]: value
        })
        break
      case 'lkn_dc_expdate':
        if (value.length > 7) return

        // Verifica se o valor é uma data válida (MM/YY)
        const isValidDate = /^\d{2}\/\d{2}$/.test(value)
        if (!isValidDate) {
          // Remove caracteres não numéricos
          const cleanedValue = value?.replace(/\D/g, '')
          let formattedValue = cleanedValue?.replace(/^(.{2})(.{2})$/, '$1 / $2')

          // Se o tamanho da string for 6 (MMYYYY), formate para MM / YY
          if (cleanedValue.length === 6) {
            formattedValue = cleanedValue?.replace(/^(.{2})(.{2})(.{2})$/, '$1 / $3')
          }

          // Atualiza o estado
          setdebitObject({
            ...debitObject,
            [key]: formattedValue
          })
        }
        return
      case 'lkn_dc_cvc':
        if (value.length > 8) return
      case 'lkn_dcno':
        if (value.length > 7 && lknDCCardTypeMode === 'both') {
          const cardBin = value.replace(' ', '').substring(0, 6)
          const restUrl = (typeof window.lknCieloDebitConfig !== 'undefined' && window.lknCieloDebitConfig.rest_url)
            ? window.lknCieloDebitConfig.rest_url
            : (window.location.origin + '/wp-json/');
          const url = restUrl + 'lknWCGatewayCielo/checkCard?cardbin=' + cardBin
          if (cardBin !== cardBinState) {
            setCardBinState(cardBin) // Mova o setCardBinState para antes da requisição

            fetch(url, {
              method: 'GET',
              headers: {
                Accept: 'application/json',
                'X-WP-Nonce': window.lknCieloDebitConfig.nonce
              }
            }).then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText)
              }

              return response.json()
            }).then(data => {
              if (data.CardType == 'Crédito') {
                setCardTypeOptions([{
                  key: 'Credit',
                  label: lknDCTranslationsCielo.creditCard
                }])
                setdebitObject(prevState => ({
                  ...prevState,
                  lkn_cc_type: 'Credit'
                }))
              } else if (data.CardType == 'Débito') {
                setCardTypeOptions([{
                  key: 'Debit',
                  label: lknDCTranslationsCielo.debitCard
                }])
                setdebitObject(prevState => ({
                  ...prevState,
                  lkn_cc_type: 'Debit'
                }))
              } else {
                setCardTypeOptions([{
                  key: 'Credit',
                  label: lknDCTranslationsCielo.creditCard
                }, {
                  key: 'Debit',
                  label: lknDCTranslationsCielo.debitCard
                }])
              }
            }).catch(error => {
              console.error('Erro:', error)
            })
          }
          setdebitObject(prevState => ({
            ...prevState,
            lkn_dcno: value
          }))
        }
        break
      default:
        break
    }
    setdebitObject({
      ...debitObject,
      [key]: value
    })
  }
  
  // useEffect para atualizar a variável global quando o tipo de cartão mudar
  window.wp.element.useEffect(() => {
    window.lknCurrentCardType = debitObject.lkn_cc_type
  }, [debitObject.lkn_cc_type])
  

  const handleButtonClick = () => {
    // Se está usando cartão salvo (não está mostrando o formulário de adicionar)
    if (!showAddCardForm && selectedCardIndex !== null && selectedCardIndex !== undefined) {
      // Envia direto sem validação
      const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
      const event = new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
        view: window
      })
      Button3ds.dispatchEvent(event)
      
      return;
    }

    // Validação normal para formulário de novo cartão
    const allFieldsFilled = Object.keys(debitObject).filter(key => key !== 'lkn_dc_cardholder_name' && key !== 'lkn_save_debit_credit_card').every(key => debitObject[key].trim() !== '')

    // Seleciona os lknDCElements dos campos de entrada
    const cardNumberInput = document.getElementById('lkn_dcno')
    const expDateInput = document.getElementById('lkn_dc_expdate')
    const cvvInput = document.getElementById('lkn_dc_cvc')
    const cardHolder = document.getElementById('lkn_dc_cardholder_name')

    // Remove classes de erro e mensagens de validação existentes
    cardNumberInput?.classList.remove('has-error')
    expDateInput?.classList.remove('has-error')
    cvvInput?.classList.remove('has-error')
    cardHolder?.classList.remove('has-error')
    if (allFieldsFilled) {
      lknDCProccessButton();
    } else {
      if (debitObject.lkn_dcno.trim() === '') {
        const parentDiv = cardNumberInput?.parentElement
        parentDiv?.classList.add('has-error')
      }
      if (debitObject.lkn_dc_expdate.trim() === '') {
        const parentDiv = expDateInput?.parentElement
        parentDiv?.classList.add('has-error')
      }
      if (debitObject.lkn_dc_cvc.trim() === '') {
        const parentDiv = cvvInput?.parentElement
        parentDiv?.classList.add('has-error')
      }
    }
  }
  window.wp.element.useEffect(() => {
    lknDCInitCieloPaymentForm()
    const unsubscribe = onPaymentSetup(async () => {
      const Button3dsEnviar = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')

      // Para cartão de crédito, não enviar dados 3DS
      let paymentCavv, paymentEci, paymentReferenceId, paymentVersion, paymentXid

      paymentCavv = Button3dsEnviar?.getAttribute('data-payment-cavv')
      paymentEci = Button3dsEnviar?.getAttribute('data-payment-eci')
      paymentReferenceId = Button3dsEnviar?.getAttribute('data-payment-ref_id')
      paymentVersion = Button3dsEnviar?.getAttribute('data-payment-version')
      paymentXid = Button3dsEnviar?.getAttribute('data-payment-xid')

      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            lkn_dcno: debitObject.lkn_dcno,
            lkn_dc_cardholder_name: debitObject.lkn_dc_cardholder_name,
            lkn_dc_expdate: debitObject.lkn_dc_expdate,
            lkn_dc_cvc: debitObject.lkn_dc_cvc,
            nonce_lkn_cielo_debit: lknDCNonceCieloDebit,
            lkn_cielo_3ds_cavv: paymentCavv ?? "",
            lkn_cielo_3ds_eci: paymentEci ?? "",
            lkn_cielo_3ds_ref_id: paymentReferenceId ?? "",
            lkn_cielo_3ds_version: paymentVersion ?? "",
            lkn_cielo_3ds_xid: paymentXid ?? "",
            lkn_cc_dc_installments: debitObject.lkn_cc_dc_installments ?? "",
            lkn_cc_type: debitObject.lkn_cc_type ?? "",
            lkn_save_debit_credit_card: debitObject.lkn_save_debit_credit_card ?? false,
            lkn_selected_saved_card_index: !showAddCardForm && selectedCardIndex !== null && selectedCardIndex !== undefined ? String(selectedCardIndex) : ""
          }
        }
      }
    })

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe()
    }
  }, [debitObject, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup, showAddCardForm, selectedCardIndex])
  // Função para recalcular as opções de parcelas com os dados atuais do carrinho
  const recalculateInstallments = async (useRetries = false) => {
    // Ativa o loading e limpa as opções atuais
    setIsLoadingOptions(true)
    setOptions([])

    let cartData
    if (useRetries) {
      // Para mudanças dinâmicas, usa retries com callback para parar loading rapidamente
      let firstDataProcessed = false

      cartData = await fetchCartDataWithRetries(3, 1000, (firstData) => {
        // Para o loading na primeira resposta válida
        if (!firstDataProcessed) {
          calculateInstallments(firstData.baseAmount, firstData.additionalValues)
          setIsLoadingOptions(false)
          firstDataProcessed = true
        }
      })

      // Se obteve dados finais diferentes, atualiza silenciosamente
      if (cartData && firstDataProcessed) {
        calculateInstallments(cartData.baseAmount, cartData.additionalValues)
      }
    } else {
      // Uma única tentativa para mudanças rápidas
      cartData = await fetchCartData()

      if (cartData) {
        calculateInstallments(cartData.baseAmount, cartData.additionalValues)
      }      // Desativa o loading após processar mudanças rápidas
      setIsLoadingOptions(false)
    }
  }

  const calculateInstallments = (baseAmount, additionalValues = {}) => {
    // Valores padrão para additionalValues caso estejam undefined
    const safeAdditionalValues = {
      externalFees: additionalValues.externalFees || 0,
      discount: additionalValues.discount || 0,
      tax: additionalValues.tax || 0
    }

    const installmentMin = parseFloat(lknDCInstallmentMinAmount)
    const totalAmount = baseAmount + safeAdditionalValues.externalFees - safeAdditionalValues.discount + safeAdditionalValues.tax
    const newOptions = [] // Array local para construir as opções

    // Verifica se 'lknDCActiveInstallmentCielo' é 'yes', o valor base é maior que 10 e installmentMin não é maior que o total
    if (lknDCActiveInstallmentCielo === 'yes' && baseAmount > 10 && installmentMin <= totalAmount) {
      const maxInstallments = lknDCInstallmentLimitCielo // Limita o parcelamento

      for (let index = 1; index <= maxInstallments; index++) {
        // Começa com o valor base (subtotal + shipping)
        let baseValue = parseFloat(baseAmount)
        let totalValue = baseValue
        let nextInstallmentAmount = totalValue / index

        let formatedInterest = false
        let typeText = ''

        // Busca a configuração específica para esta parcela no array installments
        const installmentConfig = lknDCsettingsCielo.installments.find(inst => inst.id === index)

        // Se o plugin PRO está válido, aplica juros/descontos sobre a BASE
        if (lknCieloDebitConfig.isProPluginValid && installmentConfig) {
          const interestOrDiscount = lknDCsettingsCielo.interestOrDiscount
          const interestPercent = parseFloat(installmentConfig.interest)

          if (interestOrDiscount === 'discount' && lknDCsettingsCielo.activeDiscount == "yes") {
            // Aplica desconto sobre a BASE (subtotal + shipping)
            const discountMultiplier = 1 - (interestPercent / 100)
            totalValue = baseValue * discountMultiplier

            // Soma valores adicionais no final
            totalValue += safeAdditionalValues.externalFees - safeAdditionalValues.discount + safeAdditionalValues.tax

            nextInstallmentAmount = totalValue / index
            formatedInterest = formatCurrency(nextInstallmentAmount)
            typeText = ` (${interestPercent}${lknDCTranslationsCielo.withDiscount})`
          } else if (interestOrDiscount === "interest" && lknDCsettingsCielo.activeInstallment == "yes") {
            // Aplica juros sobre a BASE (subtotal + shipping)
            const interestMultiplier = 1 + (interestPercent / 100)
            totalValue = baseValue * interestMultiplier

            // Soma valores adicionais no final
            totalValue += safeAdditionalValues.externalFees - safeAdditionalValues.discount + safeAdditionalValues.tax

            nextInstallmentAmount = totalValue / index
            formatedInterest = formatCurrency(nextInstallmentAmount)
            typeText = ` (${interestPercent}${lknDCTranslationsCielo.withInterest})`
          }
        } else {
          // Sem juros/desconto: usa apenas a base + valores adicionais
          totalValue = baseValue + safeAdditionalValues.externalFees - safeAdditionalValues.discount + safeAdditionalValues.tax

          nextInstallmentAmount = totalValue / index
        }

        // Verifica se atende o valor mínimo
        if (nextInstallmentAmount < installmentMin) {
          break
        }

        if (formatedInterest) {
          newOptions.push({
            key: index,
            label: `${lknDCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', formatedInterest)}${typeText}`
          })
        } else {
          // Sem juros/desconto do plugin: usa o total calculado
          const finalAmount = totalValue / index
          const installmentAmount = finalAmount.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          })

          // Se o plugin PRO está válido, usa as configurações originais
          if (lknCieloDebitConfig.isProPluginValid) {
            if (lknDCsettingsCielo.activeDiscount == 'yes') {
              newOptions.push({
                key: index,
                label: `${lknDCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', `R$ ${installmentAmount}`)}${lknDCsettingsCielo.interestOrDiscount == 'interest' ? ` ${lknDCTranslationsCielo.noInterest}` : ''}`
              })
            } else {
              newOptions.push({
                key: index,
                label: `${lknDCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', `R$ ${installmentAmount}`)} ${lknDCTranslationsCielo.noInterest}`
              })
            }
          } else {
            // Se o plugin PRO não está válido, mostra apenas o valor sem texto adicional
            newOptions.push({
              key: index,
              label: lknDCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', `R$ ${installmentAmount}`)
            })
          }
        }
      }
    } else {
      // À vista: usa o valor base + valores adicionais
      const totalAmountValue = baseAmount + safeAdditionalValues.externalFees - safeAdditionalValues.discount + safeAdditionalValues.tax
      const totalAmount = totalAmountValue.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
      newOptions.push({
        key: '1',
        label: `${lknDCTranslationsCielo.installmentText.replace('%1$d', '1').replace('%2$s', `R$ ${totalAmount}`)} ${lknDCTranslationsCielo.cashPayment}`
      })
    }

    // Define todas as opções de uma vez
    setOptions(newOptions)
    
    // Determina se deve mostrar o select (só mostra se tiver mais de 1 opção)
    setShowInstallmentSelect(newOptions.length > 1)
  }
  window.wp.element.useEffect(() => {
    // Executa a primeira busca no carregamento
    const loadInitialData = async () => {
      // Chama lkn_update_payment_fees uma única vez na inicialização
      if (window.lknCieloDebitConfig) {
        const formData = new FormData()
        formData.append('action', 'lkn_update_payment_fees')
        formData.append('payment_method', 'lkn_cielo_debit')
        formData.append('installment', debitObject.lkn_cc_dc_installments)
        formData.append('card_type', 'Credit') // Sempre força Credit na inicialização
        formData.append('nonce', window.lknCieloDebitConfig.fees_nonce)

        try {
          await fetch(window.lknCieloDebitConfig.ajax_url, {
            method: 'POST',
            body: formData
          })
        } catch (error) {
          console.error('Erro ao inicializar sessão de pagamento:', error)
        }
      }

      const finalCartData = await fetchCartDataWithRetries(4, 1500, (firstData) => {
        // Callback chamado na primeira resposta - para o loading imediatamente
        calculateInstallments(firstData.baseAmount, firstData.additionalValues)
        setIsLoadingOptions(false)
      })

      // Se os dados finais são diferentes dos primeiros, atualiza silenciosamente
      if (finalCartData && !isLoadingOptions) {
        calculateInstallments(finalCartData.baseAmount, finalCartData.additionalValues)
      }
    }

    loadInitialData()

    // Intercepta as requisições para detectar mudanças
    const originalFetch = window.fetch

    window.fetch = async (...args) => {
      const [resource, config] = args
      const url = typeof resource === 'string' ? resource : resource.url

      // Detecta mudanças no carrinho que requerem recálculo
      const shouldRecalculate = url && (
        url.includes('/wp-json/wc/store/v1/cart/select-shipping-rate') ||
        url.includes('/wp-json/wc/store/v1/batch')
      )

      // Para requisições de batch, ativa o loading ANTES da requisição
      if (shouldRecalculate && url.includes('/wp-json/wc/store/v1/batch')) {
        setIsLoadingOptions(true)
        setOptions([])
      }

      const response = await originalFetch.apply(window, args)

      if (shouldRecalculate) {
        // Para requisições de batch, processa a resposta diretamente
        if (url.includes('/wp-json/wc/store/v1/batch')) {
          try {
            // Clona a resposta para poder lê-la sem afetar o fluxo original
            const responseClone = response.clone()
            const batchData = await responseClone.json()
            
            // Processa os dados da batch diretamente
            const processedData = processCartData(batchData)
            if (processedData && processedData.baseAmount > 0) {
              calculateInstallments(processedData.baseAmount, processedData.additionalValues)
            }
            
            // Desativa o loading
            setIsLoadingOptions(false)
          } catch (error) {
            console.error('Erro ao processar dados da batch:', error)
            setIsLoadingOptions(false)
          }
        } else {
          // Para outras mudanças, usa o método existente com retry
          setTimeout(() => {
            recalculateInstallments(true) // usa retry leve para mudanças do carrinho
          }, 800) // 800ms de delay para dar tempo do WooCommerce processar
        }
      }

      return response
    }

    // Cleanup: restaura o fetch original quando o componente é desmontado
    return () => {
      window.fetch = originalFetch
    }
  }, [])
  return /* #__PURE__ */React.createElement(React.Fragment, null,
    renderSavedCards(),
    React.createElement('div', {
      style: { display: showAddCardForm ? "contents" : 'none', width: '100%' }
    },
      lknDCshowCard !== 'no' && React.createElement("div", {
        className: "lkn-cielo-animated-card-container",
        style: {
          flex: "1 1 100%"
        }
      }, React.createElement(Cards, {
        number: debitObject.lkn_dcno,
        name: debitObject.lkn_dc_cardholder_name,
        expiry: debitObject.lkn_dc_expdate.replace(/\s+/g, ''),
        cvc: debitObject.lkn_dc_cvc,
        placeholders: {
          name: 'NOME',
          expiry: 'MM/ANO',
          cvc: 'CVC',
          number: '•••• •••• •••• ••••'
        },
        locale: {
          valid: 'VÁLIDO ATÉ'
        },
        focused: focus
      })),
      /* ...restante do formulário... */
      React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_cardholder_name',
    label: lknDCTranslationsDebitCielo.cardHolder,
    value: debitObject.lkn_dc_cardholder_name,
    className: 'lkn-credit-debit-card-field',
    autocomplete: 'cc-name',
    onChange: value => {
      updatedebitObject('lkn_dc_cardholder_name', value)
    },
    required: showAddCardForm,
    onFocus: () => setFocus('name')
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dcno',
    label: lknDCTranslationsDebitCielo.cardNumber,
    value: debitObject.lkn_dcno,
    className: 'lkn-credit-debit-card-field',
    autocomplete: 'cc-number',
    onChange: value => {
      updatedebitObject('lkn_dcno', formatDebitCardNumber(value))
    },
    required: showAddCardForm,
    onFocus: () => setFocus('number')
  }), lknCieloDebitConfig.isProPluginValid && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_type',
    value: debitObject.lkn_cc_type,
    disabled: lknDCCardTypeSelectDisabled,
    className: 'lkn-credit-debit-card-type-select lkn-credit-debit-card-field' + (lknDCCardTypeSelectDisabled ? ' lkn-cc-type-readonly' : ''),
    style: lknDCCardTypeSelectDisabled ? { color: '#888', pointerEvents: 'none' } : {},
    onChange: event => {
      updatedebitObject('lkn_cc_type', event.target.value)
      
      // Chamar AJAX para salvar o tipo de cartão na sessão
      if (window.lknCieloDebitConfig) {
        const formData = new FormData()
        formData.append('action', 'lkn_update_card_type')
        formData.append('payment_method', 'lkn_cielo_debit')
        formData.append('card_type', event.target.value)
        formData.append('nonce', window.lknCieloDebitConfig.fees_nonce)

        fetch(window.lknCieloDebitConfig.ajax_url, {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            // Após salvar o tipo, força recálculo do carrinho
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
          .catch(error => {
            // Mesmo em caso de erro, força o recálculo para manter consistência
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
      } else {
        // Fallback se não tem config - só força recálculo
        if (window.wp && window.wp.data) {
          window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
        }

        setTimeout(() => {
          recalculateInstallments()
        }, 500)
      }
    },
    options: cardTypeOptions
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_expdate',
    label: lknDCTranslationsDebitCielo.cardExpiryDate,
    value: debitObject.lkn_dc_expdate,
    className: 'lkn-credit-debit-card-field',
    autocomplete: 'cc-exp',
    onChange: value => {
      updatedebitObject('lkn_dc_expdate', value)
    },
    required: showAddCardForm,
    onFocus: () => setFocus('expiry')
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_cvc',
    label: lknDCTranslationsDebitCielo.securityCode,
    value: debitObject.lkn_dc_cvc,
    className: 'lkn-credit-debit-card-field',
    autocomplete: 'cc-csc',
    onChange: value => {
      updatedebitObject('lkn_dc_cvc', value)
    },
    required: showAddCardForm,
    onFocus: () => setFocus('cvc')
  }), !lknCieloDebitConfig.isProPluginValid && /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '20px',
      width: '100%'
    }
  }), !lknCieloDebitConfig.isProPluginValid && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_type',
    label: lknDCTranslationsCielo.cardType,
    value: debitObject.lkn_cc_type,
    disabled: lknDCCardTypeSelectDisabled,
    className: 'lkn-credit-debit-card-type-select lkn-credit-debit-card-field lkn-select-type' + (lknDCCardTypeSelectDisabled ? ' lkn-cc-type-readonly' : ''),
    style: lknDCCardTypeSelectDisabled ? { color: '#888', pointerEvents: 'none' } : {},
    onChange: event => {
      updatedebitObject('lkn_cc_type', event.target.value)
      
      // Chamar AJAX para salvar o tipo de cartão na sessão
      if (window.lknCieloDebitConfig) {
        const formData = new FormData()
        formData.append('action', 'lkn_update_card_type')
        formData.append('payment_method', 'lkn_cielo_debit')
        formData.append('card_type', event.target.value)
        formData.append('nonce', window.lknCieloDebitConfig.fees_nonce)

        fetch(window.lknCieloDebitConfig.ajax_url, {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            // Após salvar o tipo, força recálculo do carrinho
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
          .catch(error => {
            // Mesmo em caso de erro, força o recálculo para manter consistência
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
      } else {
        // Fallback se não tem config - só força recálculo
        if (window.wp && window.wp.data) {
          window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
        }

        setTimeout(() => {
          recalculateInstallments()
        }, 500)
      }
    },
    options: cardTypeOptions
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '0px',
      width: '100%'
    }
  }), lknDCActiveInstallmentCielo === 'yes' && debitObject.lkn_cc_type == 'Credit' && parseInt(lknDCInstallmentLimitCielo) > 1 && showInstallmentSelect && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_dc_installments',
    label: lknDCTranslationsCielo.installments,
    value: debitObject.lkn_cc_dc_installments,
    className: `lkn_cielo_debit_select lkn-cielo-credit-debit-custom-select lkn-credit-debit-card-field lkn-select-type ${isLoadingOptions ? 'loading-options' : ''}`,
    disabled: isLoadingOptions,
    style: isLoadingOptions ? { opacity: 0.7, cursor: 'wait' } : {},
    onChange: event => {
      const installmentValue = event.target.value

      // Ignora se está selecionando a opção de loading
      if (installmentValue === 'loading') return

      updatedebitObject('lkn_cc_dc_installments', installmentValue)

      // Faz a requisição AJAX para atualizar as fees quando a parcela mudar
      if (window.lknCieloDebitConfig) {
        const formData = new FormData()
        formData.append('action', 'lkn_update_payment_fees')
        formData.append('payment_method', 'lkn_cielo_debit')
        formData.append('installment', installmentValue)
        formData.append('card_type', debitObject.lkn_cc_type)
        formData.append('nonce', window.lknCieloDebitConfig.fees_nonce)

        fetch(window.lknCieloDebitConfig.ajax_url, {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            // Após a resposta AJAX, força recálculo do carrinho
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            // Aguarda um pouco e depois recalcula as parcelas
            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
          .catch(error => {
            // Mesmo em caso de erro, força o recálculo para manter consistência
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
      }
    },
    options: isLoadingOptions ? [{ key: 'loading', label: `🔄 ${lknDCTranslationsCielo.calculatingInstallments}` }] : options
  }), (debitObject.lkn_cc_type === 'Credit' && lknDCsettingsCielo.showSaveCardToken == 'optional') && /*#__PURE__*/_jsx(wcComponents.CheckboxControl, {
      id: "lkn_save_debit_credit_card",
      label: lknDCTranslationsCielo.saveCard,
      checked: debitObject.lkn_save_debit_credit_card || false,
      onChange: isChecked => {
        updatedebitObject('lkn_save_debit_credit_card', isChecked);
      }
    }), lknDCActiveInstallmentCielo === 'cielo' && /* #__PURE__ */React.createElement(wcComponents.CheckboxControl, {
    id: 'lkn_save_debit_credit_card',
    label: 'Salvar cartão para compra segura e rápida.',
    className: 'lkn-credit-debit-card-field',
    checked: debitObject.lkn_save_debit_credit_card || false,
    onChange: (isChecked) => {
      updatedebitObject('lkn_save_debit_credit_card', isChecked)
    }
  })
    
), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '25px',
      width: '100%'
    }
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      display: 'flex',
      justifyContent: 'center'
    }
  }, /* #__PURE__ */React.createElement(wcComponents.Button, {
    id: 'sendOrder',
    onClick: handleButtonClick
  }, /* #__PURE__ */React.createElement('span', null, 'Finalizar pedido'))), /* #__PURE__ */React.createElement('div', {
    style: {
      margin: '2px',
      width: '100%'
    }
  }), /* #__PURE__ */React.createElement('div', {
    className: 'lkn-cielo-credit-debit-description',
    style: {
      width: '100%'
    }
  }, /* #__PURE__ */React.createElement('p', {
    style: {
      width: '100%',
      textAlign: 'center'
    }
  }, lknDCDescriptionCielo)),
  /* #__PURE__ */React.createElement('div', null, /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_enabled',
    className: 'bpmpi_auth',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_enabled_notifyonly',
    className: 'bpmpi_auth_notifyonly',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_suppresschallenge',
    className: 'bpmpi_auth_suppresschallenge',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_access_token',
    className: 'bpmpi_accesstoken',
    value: lknDCAccessTokenCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_expires_in',
    id: 'expires_in',
    value: lknDCAccessTokenExpiration
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    name: 'lkn_order_number',
    className: 'bpmpi_ordernumber',
    value: lknDCOrderNumberCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_currency',
    className: 'bpmpi_currency',
    value: 'BRL'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    id: 'lkn_cielo_3ds_value',
    name: 'lkn_amount',
    className: 'bpmpi_totalamount',
    value: lknDCTotalCartCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    name: 'lkn_installments',
    className: 'bpmpi_installments',
    value: '1'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_payment_method',
    className: 'bpmpi_paymentmethod',
    value: 'Debit'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_cardnumber',
    className: 'bpmpi_cardnumber'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_expmonth',
    maxLength: '2',
    name: 'lkn_card_expiry_month',
    className: 'bpmpi_cardexpirationmonth'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_expyear',
    maxLength: '4',
    name: 'lkn_card_expiry_year',
    className: 'bpmpi_cardexpirationyear'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_default_card',
    name: 'lkn_default_card',
    className: 'bpmpi_default_card',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_order_recurrence',
    name: 'lkn_order_recurrence',
    className: 'bpmpi_order_recurrence',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_order_productcode',
    value: 'PHY'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_transaction_mode',
    value: 'S'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_merchant_url',
    value: lknDCUrlCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '14',
    id: 'lkn_bpmpi_billto_customerid',
    name: 'lkn_card_customerid',
    className: 'bpmpi_billto_customerid',
    value: lknDCClient.billing_document
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '120',
    id: 'lkn_bpmpi_billto_contactname',
    name: 'lkn_card_contactname',
    className: 'bpmpi_billto_contactname',
    value: lknDCClient.name
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '15',
    id: 'lkn_bpmpi_billto_phonenumber',
    name: 'lkn_card_phonenumber',
    className: 'bpmpi_billto_phonenumber',
    value: lknDCClient.billing_phone
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '255',
    id: 'lkn_bpmpi_billto_email',
    name: 'lkn_card_email',
    className: 'bpmpi_billto_email',
    value: lknDCClient.email
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '60',
    id: 'lkn_bpmpi_billto_street1',
    name: 'lkn_card_billto_street1',
    className: 'bpmpi_billto_street1',
    value: lknDCClient.billing_address_1
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '60',
    id: 'lkn_bpmpi_billto_street2',
    name: 'lkn_card_billto_street2',
    className: 'bpmpi_billto_street2',
    value: lknDCClient.billing_address_2
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    id: 'lkn_bpmpi_billto_city',
    name: 'lkn_card_billto_city',
    className: 'bpmpi_billto_city',
    value: lknDCClient.billing_city
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    id: 'lkn_bpmpi_billto_state',
    name: 'lkn_card_billto_state',
    className: 'bpmpi_billto_state',
    value: lknDCClient.billing_state
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '8',
    id: 'lkn_bpmpi_billto_zipcode',
    name: 'lkn_card_billto_zipcode',
    className: 'bpmpi_billto_zipcode',
    value: lknDCClient.billing_postcode
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    id: 'lkn_bpmpi_billto_country',
    name: 'lkn_card_billto_country',
    className: 'bpmpi_billto_country',
    value: lknDCClient.billing_country
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_shipto_sameasbillto',
    name: 'lkn_card_shipto_sameasbillto',
    className: 'bpmpi_shipto_sameasbillto',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_useraccount_guest',
    name: 'lkn_card_useraccount_guest',
    className: 'bpmpi_useraccount_guest',
    value: lknDCUserGuest
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_useraccount_authenticationmethod',
    name: 'lkn_card_useraccount_authenticationmethod',
    className: 'bpmpi_useraccount_authenticationmethod',
    value: lknDCAuthMethod
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '45',
    id: 'lkn_bpmpi_device_ipaddress',
    name: 'lkn_card_device_ipaddress',
    className: 'bpmpi_device_ipaddress',
    value: lknDCClientIp
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '7',
    id: 'lkn_bpmpi_device_channel',
    name: 'lkn_card_device_channel',
    className: 'bpmpi_device_channel',
    value: 'Browser'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '10',
    id: 'lkn_bpmpi_brand_establishment_code',
    name: 'lkn_card_brand_establishment_code',
    className: 'bpmpi_brand_establishment_code',
    value: lknDCBec
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_cavv',
    name: 'lkn_cielo_3ds_cavv',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_eci',
    name: 'lkn_cielo_3ds_eci',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_ref_id',
    name: 'lkn_cielo_3ds_ref_id',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_version',
    name: 'lkn_cielo_3ds_version',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_xid',
    name: 'lkn_cielo_3ds_xid',
    value: true
  })))
}
const Lkn_DC_Block_Gateway_Cielo = {
  name: 'lkn_cielo_debit',
  label: lknDCLabelCielo,
  content: window.wp.element.createElement(lknDCContentCielo),
  edit: window.wp.element.createElement(lknDCContentCielo),
  canMakePayment: () => true,
  ariaLabel: lknDCLabelCielo,
  supports: {
    features: lknDCsettingsCielo.supports
  }
}
window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_DC_Block_Gateway_Cielo)
