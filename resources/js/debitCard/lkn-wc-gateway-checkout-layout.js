document.addEventListener('DOMContentLoaded', function () {
  let debounceTimeout = null


  // Define cardBrands globally to avoid reference erros
  const cardBrands = ['visa', 'mastercard', 'elo', 'amex', 'other_card']

  const observer = new MutationObserver(function () {
    const targetElement = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit')
    if (targetElement) {
      const parentLabel = targetElement.closest('label')
      if (parentLabel && !parentLabel.classList.contains('lkn-cielo-credit-debit-header-label')) {
        parentLabel.classList.add('lkn-cielo-credit-debit-header-label')
        if (!parentLabel.querySelector('.lkn-cielo-credit-debit-card-icons')) {
          const iconsContainer = document.createElement('div')
          iconsContainer.setAttribute('class', 'lkn-cielo-credit-debit-card-icons')

          // Só criar os ícones de marca do cartão se a configuração permitir
          if (lknCieloDebitCardIcons.show_card_brand_icons === 'yes') {
            cardBrands.forEach(brand => {
              const icon = document.createElement('img')
              icon.setAttribute('src', lknCieloDebitCardIcons[brand])

              // Define o atributo alt
              const altText = brand === 'other_card' ? lknCieloDebitCardIcons.other_card_alt + ' logo' || 'Other Card logo' : `${brand} logo`
              icon.setAttribute('alt', altText)

              // Define o atributo title
              const titleText = brand === 'other_card'
                ? (lknCieloDebitCardIcons.other_card_alt ? lknCieloDebitCardIcons.other_card_alt.charAt(0).toUpperCase() + lknCieloDebitCardIcons.other_card_alt.slice(1) : 'Other Card')
                : brand.charAt(0).toUpperCase() + brand.slice(1)
              icon.setAttribute('title', titleText)

              icon.setAttribute('style', 'width: 40px; height: auto;')
              iconsContainer.appendChild(icon)
            })
          }

          parentLabel.appendChild(iconsContainer)

          const applyLogic = () => {
            const isChecked = parentLabel.classList.contains('wc-block-components-radio-control__option-checked')
            
            // Só aplicar estilos nos ícones de marca se eles estiverem habilitados
            if (lknCieloDebitCardIcons.show_card_brand_icons === 'yes') {
              iconsContainer.style.filter = isChecked ? 'none' : 'grayscale(20%)'
              iconsContainer.style.opacity = isChecked ? '1' : '1'

              if (!isChecked) {
                iconsContainer.querySelectorAll('img').forEach(icon => {
                  icon.style.filter = 'none'
                  icon.style.opacity = '1'
                })
              }
            }

            parentLabel.style.border = isChecked ? false : 'none'

            if (isChecked) {
              const contentContainer = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit__content')
              if (contentContainer) {
                contentContainer.classList.add('lkn-cielo-credit-debit-content-container')

                const idsToCheck = ['lkn_dc_expdate', 'lkn_dc_cvc', 'lkn_dcno']
                const idsToIcons = {
                  lkn_dcno: lknCieloInputIcons.lock,
                  lkn_dc_expdate: lknCieloInputIcons.calendar,
                  lkn_dc_cvc: lknCieloInputIcons.key
                }
                const inputs = contentContainer.querySelectorAll('input[type="text"]')
                inputs.forEach(element => {
                  const containerInput = element.closest('.wc-block-components-text-input, .wc-block-components-sort-select')
                  if (containerInput) {
                    containerInput.style.position = 'relative' // Define o container como relativo

                    if (element.tagName === 'INPUT' && element.type === 'text') {
                      if (element.id === 'lkn_dcno') {
                        element.addEventListener('input', () => {
                          const value = element.value.trim()

                          // Só executar lógica de detecção de marca se os ícones estiverem habilitados
                          if (lknCieloDebitCardIcons.show_card_brand_icons === 'yes') {
                            if (value.length === 0) {
                              clearTimeout(debounceTimeout)
                              iconsContainer.querySelectorAll('img').forEach(icon => {
                                icon.style.filter = 'none'
                                icon.style.opacity = '1'
                              })
                            } else if (value.length > 0 && value.length < 7) {
                              clearTimeout(debounceTimeout)
                              iconsContainer.querySelectorAll('img').forEach(icon => {
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
                                        'X-WP-Nonce': lknCieloRestSettings.nonce
                                    }
                                })
                                  .then(response => response.json())
                                  .then(data => {
                                    if (data.status) {
                                      const brand = data.brand.toLowerCase()
                                      iconsContainer.querySelectorAll('img').forEach(icon => {
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
                                        const otherCardIcon = iconsContainer.querySelector('img[alt="other card logo"]')
                                        if (otherCardIcon) {
                                          otherCardIcon.style.filter = 'none'
                                          otherCardIcon.style.opacity = '1'
                                        }
                                      }
                                    } else {
                                      iconsContainer.querySelectorAll('img').forEach(icon => {
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

                      if (idsToIcons[element.id]) { // Verifica se o ID está no objeto idsToIcons
                        const iconElement = document.createElement('img')
                        iconElement.setAttribute('src', idsToIcons[element.id])
                        iconElement.setAttribute('alt', `${element.id} icon`)
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
                      containerInput.style.width = '48%'
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
            }
          }

          const classObserver = new MutationObserver(function (mutationsList) {
            mutationsList.forEach(mutation => {
              if (mutation.attributeName === 'class') {
                applyLogic()
              }
            })
          })

          classObserver.observe(parentLabel, { attributes: true })
          applyLogic()
        }
      }
    }
  })

  observer.observe(document.body, { childList: true, subtree: true })
})
