/* eslint-disable no-undef */
(function ($) {
  'use strict'

  $(window).on('load', () => {
    lknWCCieloLoadInstallments()
    $('body').on('updated_checkout', lknWCCieloLoadInstallments)
    lknWCCieloInitInstallmentEvents()
  })

  lknWCCieloLoadInstallments()
  $('body').on('updated_checkout', function () {
    lknWCCieloLoadInstallments()
    lknWCCieloInitInstallmentEvents()
  })

  // Inicializar eventos de change para parcelas
  function lknWCCieloInitInstallmentEvents() {
    $(document).on('change', '#lkn_cc_dc_installments', function () {
      const installment = $(this).val()
      const paymentMethod = 'lkn_cielo_debit'

      if (installment && typeof lknWCCielo3dsAjax !== 'undefined') {
        lknWCCieloUpdateInstallmentSession(paymentMethod, installment)
      }
    })
  }

  // Função para atualizar a sessão com a parcela selecionada
  function lknWCCieloUpdateInstallmentSession(paymentMethod, installment) {
    if (typeof lknWCCielo3dsAjax === 'undefined') {
      return
    }

    // Verificar se a parcela já é a mesma da sessão (evitar trigger desnecessário)
    if (lknWCCielo3dsAjax.current_installment === installment) {
      return
    }

    $.ajax({
      url: lknWCCielo3dsAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'lkn_update_payment_fees',
        nonce: lknWCCielo3dsAjax.nonce,
        payment_method: paymentMethod,
        installment: installment
      },
      success: function (response) {
        if (response.success) {
          // Atualizar a parcela atual em memória
          lknWCCielo3dsAjax.current_installment = installment
          // Trigger para atualizar o checkout após definir a parcela
          $('body').trigger('update_checkout')
        } else {
          console.error('Erro ao atualizar parcela:', response.data.message)
        }
      },
      error: function (xhr, status, error) {
        console.error('Erro na requisição AJAX:', error)
      }
    })
  }

  // Função para atualizar o tipo de cartão
  function lknWCCieloUpdateCardType(cardType) {
    if (typeof lknWCCielo3dsAjax === 'undefined') {
      return
    }

    // Verificar se o tipo já é o mesmo da sessão (evitar trigger desnecessário)
    if (lknWCCielo3dsAjax.current_card_type === cardType) {
      return
    }

    $.ajax({
      url: lknWCCielo3dsAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'lkn_update_card_type',
        payment_method: 'lkn_cielo_debit',
        card_type: cardType,
        nonce: lknWCCielo3dsAjax.nonce
      },
      success: function (response) {
        if (response.success) {
          // Atualizar o tipo de cartão atual em memória
          lknWCCielo3dsAjax.current_card_type = cardType
          // Trigger para atualizar o checkout após definir o tipo
          $('body').trigger('update_checkout')
        } else {
          console.error('Erro ao atualizar tipo de cartão:', response.data.message)
        }
      },
      error: function (xhr, status, error) {
        console.error('Erro na requisição AJAX:', error)
      }
    })
  }

  function lknWCCieloShowInstallments() {
    const installmentShow = $('#lkn_cielo_3ds_installment_show')
    const installmentRow = $('#lkn-cc-dc-installment-row')
    const saveCardRow = $('#lkn-save-debit-credit-card-row')
    const typeCard = $('#lkn_cc_type')

    if (installmentShow && installmentRow && typeCard.length) {
      const cardType = typeCard.val()
      
      // Se é cartão de débito, sempre esconder
      if (cardType === 'Debit') {
        if (installmentRow.length) {
          installmentRow.hide()
          installmentShow.val('no')
        }
        // Esconder checkbox de salvar cartão para débito
        if (saveCardRow.length) {
          saveCardRow.hide()
        }
      } else if (cardType === 'Credit') {
        // Para crédito, mostrar checkbox de salvar cartão
        if (saveCardRow.length) {
          saveCardRow.show()
        }
      }
      // Para cartão de crédito, não fazer nada aqui com as parcelas
      // A visibilidade será controlada pela validação de opções em lknWCCieloLoadInstallments()
    }
  }

  function lknWCCieloLoadInstallments() {
    const typeCard = $('#lkn_cc_type')
    const lknInstallmentSelect = document.getElementById('lkn_cc_dc_installments')
    const lknTotal = document.getElementById('lkn_cc_dc_installment_total') // produto + frete
    const lknFeesTotal = document.getElementById('lkn_cc_dc_fees_total') // fees
    const lknTaxesTotal = document.getElementById('lkn_cc_dc_taxes_total') // taxes
    const lknDiscountsTotal = document.getElementById('lkn_cc_dc_discounts_total') // discounts
    let lknInstallmentLimit = document.getElementById('lkn_cc_dc_installment_limit')
    let lknInstallmentInterest = document.getElementById('lkn_cc_dc_installment_interest')
    let lknInstallmentMin = document.getElementById('lkn_cc_dc_installment_min')

    if (typeCard.length) {
      typeCard.on('change', function() {
        const cardType = $(this).val()
        lknWCCieloShowInstallments()
        if (cardType && typeof lknWCCielo3dsAjax !== 'undefined') {
          lknWCCieloUpdateCardType(cardType)
        }
      })
      
      // Definir o valor correto do tipo de cartão da sessão PRIMEIRO
      if (typeof lknWCCielo3dsAjax !== 'undefined' && lknWCCielo3dsAjax.current_card_type) {
        // Only apply if the option actually exists (e.g. skip 'Credit' when only 'Debit' is present)
        if (typeCard.find('option[value="' + lknWCCielo3dsAjax.current_card_type + '"]').length) {
          typeCard.val(lknWCCielo3dsAjax.current_card_type)
        }
      }
      
      // Depois chamar showInstallments com o valor correto
      lknWCCieloShowInstallments()
    }

    if (lknInstallmentLimit) {
      lknInstallmentLimit = lknInstallmentLimit.value
    }

    if (lknInstallmentInterest) {
      lknInstallmentInterest = JSON.parse(lknInstallmentInterest.value)
    }

    if (lknInstallmentMin) {
      lknInstallmentMin = parseFloat(lknInstallmentMin.value)
    }

    // Remove installment options and repopulate installments
    if (lknInstallmentSelect && lknTotal) {
      // Separar os valores para cálculo correto
      const subtotalShipping = parseFloat(lknTotal.value) || 0 // produto + frete (para cálculo de juros)
      const feesTotal = lknFeesTotal ? parseFloat(lknFeesTotal.value) || 0 : 0 // fees (somado no final)
      const discountsTotal = lknDiscountsTotal ? parseFloat(lknDiscountsTotal.value) || 0 : 0 // discounts (subtraído antes dos taxes)
      const taxesTotal = lknTaxesTotal ? parseFloat(lknTaxesTotal.value) || 0 : 0 // taxes (somado no final)

      // Se o limite de parcelas for 1 ou menor, ou se não houver limite, não renderiza o select
      if (!lknInstallmentLimit || lknInstallmentLimit <= 1) {
        const installmentRow = document.getElementById('lkn-cc-dc-installment-row')
        if (installmentRow) {
          installmentRow.style.display = 'none'
        }
        return
      }

      // Remove opções existentes
      while (lknInstallmentSelect.options.length > 0) {
        lknInstallmentSelect.remove(0)
      }

      let validOptions = 0
      for (let i = 1; i <= lknInstallmentLimit; i++) {
        let finalInstallment
        let formatedInstallment
        let text
        let addOption = true

        // Se a versão PRO está ativa, usar cálculo complexo
        if (typeof lknWCCielo3ds !== 'undefined' && lknWCCielo3ds.licenseResult) {
          let installmentBase = (subtotalShipping - discountsTotal) / i
          finalInstallment = installmentBase + feesTotal + taxesTotal
          let hasCustomConfig = false
          for (let t = 0; t < lknInstallmentInterest.length; t++) {
            const installmentObj = lknInstallmentInterest[t]
            if (installmentObj.id === i) {
              if (installmentObj.label) {
                text = document.createTextNode(installmentObj.label)
                hasCustomConfig = true
              } else if (installmentObj.interest) {
                const interestAmount = (subtotalShipping - discountsTotal) + ((subtotalShipping) * (installmentObj.interest / 100))
                const interestInstallment = (interestAmount / i) + feesTotal + taxesTotal
                formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(interestInstallment)
                text = document.createTextNode(i + 'x de ' + formatedInstallment + ' (' + installmentObj.interest + '% de juros)')
                hasCustomConfig = true
              } else if (installmentObj.discount) {
                const discountAmount = (subtotalShipping - discountsTotal) - ((subtotalShipping) * (installmentObj.discount / 100))
                const discountInstallment = (discountAmount / i) + feesTotal + taxesTotal
                formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(discountInstallment)
                text = document.createTextNode(i + 'x de ' + formatedInstallment + ' (' + installmentObj.discount + '% de desconto)')
                hasCustomConfig = true
              }
              break
            }
          }
          if (!hasCustomConfig) {
            formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(finalInstallment)
            let defaultText = ' sem juros'
            if (typeof lknWCCielo3dsConfig !== 'undefined' && lknWCCielo3dsConfig.interest_or_discount === 'discount') {
              defaultText = ''
            }
            text = document.createTextNode(i + 'x de ' + formatedInstallment + defaultText)
          }
        } else {
          finalInstallment = (subtotalShipping - discountsTotal + feesTotal + taxesTotal) / i
          formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(finalInstallment)
          text = document.createTextNode(i + 'x de ' + formatedInstallment)
        }

        const checkValue = (typeof lknWCCielo3ds !== 'undefined' && lknWCCielo3ds.licenseResult) ?
          subtotalShipping : (subtotalShipping - discountsTotal + feesTotal + taxesTotal)
        if ((checkValue / i) < lknInstallmentMin) {
          addOption = false
        }

        if (addOption) {
          const option = document.createElement('option')
          option.value = i
          option.appendChild(text)
          lknInstallmentSelect.appendChild(option)
          validOptions++

          if (typeof lknWCCielo3dsAjax !== 'undefined' &&
            lknWCCielo3dsAjax.current_installment &&
            i === parseInt(lknWCCielo3dsAjax.current_installment)) {
            option.selected = true
          }
        }
      }

      // Controlar visibilidade baseado no tipo de cartão e opções válidas
      const installmentRow = document.getElementById('lkn-cc-dc-installment-row')
      const saveCardRow = document.getElementById('lkn-save-debit-credit-card-row')
      const installmentShow = $('#lkn_cielo_3ds_installment_show')
      const typeCard = $('#lkn_cc_type')
      
      if (installmentRow && typeCard.length) {
        const cardType = typeCard.val()
        
        if (cardType === 'Credit') {
          // Para crédito, só mostrar parcelas se há múltiplas opções válidas
          if (validOptions > 1) {
            installmentRow.style.display = ''
            if (installmentShow) {
              installmentShow.val('yes')
            }
          } else {
            installmentRow.style.display = 'none'
            if (installmentShow) {
              installmentShow.val('no')
            }
          }
          
          // Para crédito, sempre mostrar checkbox de salvar cartão
          if (saveCardRow) {
            saveCardRow.style.display = ''
          }
        } else {
          // Para débito ou outros, sempre esconder parcelas e checkbox
          installmentRow.style.display = 'none'  
          if (installmentShow) {
            installmentShow.val('no')
          }
          
          // Esconder checkbox de salvar cartão para débito
          if (saveCardRow) {
            saveCardRow.style.display = 'none'
          }
        }
      }
    }
  }
})(jQuery)
