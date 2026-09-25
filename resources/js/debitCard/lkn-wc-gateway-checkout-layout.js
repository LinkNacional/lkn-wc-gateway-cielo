document.addEventListener('DOMContentLoaded', function () {
  let debounceTimeout = null
  let lastBinErrorBin = null

  // Alerta de falha da consulta online (deduplicado por BIN)
  const showBinErrorAlert = (bin, message) => {
    if (bin === lastBinErrorBin) return
    lastBinErrorBin = bin
    if (typeof window !== 'undefined' && typeof window.alert === 'function') {
      window.alert(message || 'Could not validate the card with the card issuer. Please try again or use another card.')
    }
  }

  // Define cardBrands globally to avoid reference erros
  const cardBrands = ['visa', 'mastercard', 'elo', 'amex', 'other_card']

  // Ícones das bandeiras do cabeçalho. O bloco do Cielo localiza esta variável em
  // dois handles (bundle React e layout); para não depender de qual venceu, fazemos
  // um merge defensivo dos objetos disponíveis.
  const cardIcons = Object.assign(
    {},
    window.lknCieloHeaderIcons || {},
    window.lknCieloDebitCardIcons || {}
  )
  const showCardBrandIcons = cardIcons.show_card_brand_icons === 'yes'
  const inputIcons = window.lknCieloInputIcons || {}
  const restSettings = window.lknCieloRestSettings || {}

  // Ícones dos campos + container/conteúdo do método (só quando o método está
  // selecionado). Roda uma vez por label.
  const applyFieldLogic = (parentLabel) => {
    const contentContainer = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit__content')
    if (!contentContainer) return

    contentContainer.classList.add('lkn-cielo-credit-debit-content-container')

    const idsToCheck = ['lkn_dc_expdate', 'lkn_dc_cvc', 'lkn_dcno']
    // Quando o seletor de tipo de cartão não é renderizado (escondido/PRO),
    // o campo de número deve ocupar 100% da largura (senão sobra um buraco).
    const hasCardTypeSelect = !!contentContainer.querySelector('.lkn-credit-debit-card-type-select')
    const idsToIcons = {
      lkn_dcno: inputIcons.lock,
      lkn_dc_expdate: inputIcons.calendar,
      lkn_dc_cvc: inputIcons.key
    }
    const inputs = contentContainer.querySelectorAll('input[type="text"]')
    inputs.forEach(element => {
      const containerInput = element.closest('.wc-block-components-text-input, .wc-block-components-sort-select')
      if (containerInput) {
        containerInput.style.position = 'relative' // Define o container como relativo

        if (element.tagName === 'INPUT' && element.type === 'text') {
          if (element.id === 'lkn_dcno' && !element.hasAttribute('data-lkn-brand-init')) {
            element.setAttribute('data-lkn-brand-init', 'true')
            element.addEventListener('input', () => {
              const value = element.value.trim()

              // Só executar lógica de detecção de marca se os ícones estiverem habilitados
              if (showCardBrandIcons) {
                const brandIcons = document.querySelectorAll('.lkn-cielo-credit-debit-card-icons img')
                if (value.length >= 0 && value.length < 7) {
                  clearTimeout(debounceTimeout)
                  brandIcons.forEach(icon => {
                    icon.style.filter = 'none'
                    icon.style.opacity = '1'
                  })
                } else if (value.length >= 7) {
                  // Limpa o timeout anterior para evitar múltiplas chamadas
                  clearTimeout(debounceTimeout)
                  let isBrandMatched = false

                  debounceTimeout = setTimeout(() => {
                    fetch(`/wp-json/lknWCGatewayCielo/getCardBrand?number=${value}&gateway=debit`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-WP-Nonce': restSettings.nonce
                        }
                    })
                      .then(response => response.json())
                      .then(data => {
                        if (data && data.error) {
                          showBinErrorAlert(value.replace(/\s+/g, '').substring(0, 6), data.message)
                          brandIcons.forEach(icon => {
                            icon.style.filter = 'none'
                            icon.style.opacity = '1'
                          })
                          return
                        }
                        if (data.status) {
                          const brand = data.brand.toLowerCase()
                          brandIcons.forEach(icon => {
                            const iconBrand = icon.getAttribute('alt').replace(' logo', '').toLowerCase()

                            if (cardBrands.includes(brand)) {
                              icon.style.filter = iconBrand === brand ? 'none' : 'grayscale(100%)'
                              icon.style.opacity = iconBrand === brand ? '1' : '0.3'
                              isBrandMatched = true
                            } else {
                              icon.style.filter = 'grayscale(100%)'
                              icon.style.opacity = '0.3'
                            }
                          })

                          if (!isBrandMatched) {
                            const otherCardIcon = Array.from(brandIcons).find(
                              icon => (icon.getAttribute('alt') || '').toLowerCase().indexOf('other card') === 0
                            )
                            if (otherCardIcon) {
                              otherCardIcon.style.filter = 'none'
                              otherCardIcon.style.opacity = '1'
                            }
                          }
                        } else {
                          brandIcons.forEach(icon => {
                            icon.style.filter = 'none'
                            icon.style.opacity = '1'
                          })
                        }
                      })
                      .catch(error => {
                        console.error('Error fetching card brand:', error)
                      })
                  }, 1000)
                }
              }
            })
          }

          if (idsToIcons[element.id] && !containerInput.querySelector('img[data-lkn-field-icon]')) {
            const iconElement = document.createElement('img')
            iconElement.setAttribute('src', idsToIcons[element.id])
            iconElement.setAttribute('alt', `${element.id} icon`)
            iconElement.setAttribute('data-lkn-field-icon', 'true')
            iconElement.style.position = 'absolute'
            iconElement.style.right = '10px'
            iconElement.style.top = '50%'
            iconElement.style.transform = 'translateY(-50%)'
            iconElement.style.width = '20px'
            iconElement.style.height = '20px'

            containerInput.appendChild(iconElement)
          }
        }

        if (idsToCheck.includes(element.id)) {
          // Campo de número vai a 100% quando o seletor de tipo não existe (some).
          containerInput.style.width = (element.id === 'lkn_dcno' && !hasCardTypeSelect) ? '100%' : '48%'
        } else {
          containerInput.style.width = '100%'
        }
      }
      element.classList.add('lkn-cielo-credit-debit-custom-input')
    })

    const orderButton = document.getElementById('sendOrder')
    if (orderButton) {
      const divContainer = orderButton.closest('div')
      if (divContainer) {
        divContainer.classList.add('lkn-wc-gateway-cielo-credit-debit-order-button')
      }
    }
  }

  // Reaplica a lógica dos campos quando o método é (des)selecionado. A seleção
  // alterna a classe `wc-block-components-radio-control__option-checked` no label,
  // o que nem sempre gera mutação de childList — então observamos atributos.
  let classObserved = false
  const setupClassObserver = (parentLabel) => {
    if (classObserved || !parentLabel) return
    classObserved = true
    const classObserver = new MutationObserver(() => {
      if (parentLabel.classList.contains('wc-block-components-radio-control__option-checked')) {
        applyFieldLogic(parentLabel)
      }
    })
    classObserver.observe(parentLabel, { attributes: true, attributeFilter: ['class'] })
  }

  const observer = new MutationObserver(function () {
    const targetElement = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit')
    if (!targetElement) return

    const parentLabel = targetElement.closest('label')
    if (!parentLabel) return

    // A faixa de bandeiras do cabeçalho é injetada pelo script dedicado
    // `lkn-cielo-header-brands.js` (compartilhado por todos os layouts).

    setupClassObserver(parentLabel)

    // Lógica dos campos (só quando o método está selecionado).
    const isChecked = parentLabel.classList.contains('wc-block-components-radio-control__option-checked')
    if (isChecked) {
      applyFieldLogic(parentLabel)
    }
  })

  observer.observe(document.body, { childList: true, subtree: true })
})
