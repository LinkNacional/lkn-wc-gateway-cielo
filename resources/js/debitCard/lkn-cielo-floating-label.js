/**
 * Cielo Débito — label flutuante no checkout em Blocos (Gutenberg).
 *
 * Mantém a classe `is-active` no container do campo (`.wc-block-components-text-input`)
 * conforme o estado: ativa ao FOCAR ou quando há valor; volta ao repouso no blur
 * vazio. O CSS (lkn-cielo-basic-layout.css / lkn-wc-gateway-debit-card-checkout-layout.css)
 * é quem anima a label a partir de `is-active` — este script garante o toggle,
 * já que o `wc.blocksComponents.TextInput` usado pelas integrações de Blocos não
 * o faz de forma confiável.
 *
 * Delegado no `document` (sobrevive a re-render do React) e escopado ao container
 * do método Cielo. Sem dependências.
 *
 * @package Lkn\WCCieloPaymentGateway
 */
(function () {
  'use strict'

  var SCOPE_IDS = [
    'radio-control-wc-payment-method-options-lkn_cielo_debit__content',
    'radio-control-wc-payment-method-options-lkn_cielo_credit__content'
  ]

  function inScope (el) {
    return el && el.closest && SCOPE_IDS.some(function (id) { return el.closest('#' + id) })
  }

  function sync (input) {
    if (!input || input.tagName !== 'INPUT' || !inScope(input)) return
    var field = input.closest('.wc-block-components-text-input')
    if (!field) return
    var active = document.activeElement === input || (input.value && input.value.trim() !== '')
    field.classList.toggle('is-active', !!active)
  }

  // Captura (true) para reagir mesmo se o React parar a propagação.
  document.addEventListener('focusin', function (e) { sync(e.target) }, true)
  document.addEventListener('input', function (e) { sync(e.target) }, true)
  document.addEventListener('focusout', function (e) {
    // setTimeout para ler `input.value` já atualizado após o blur.
    setTimeout(function () { sync(e.target) }, 0)
  }, true)

  // Estado inicial (campos que já vêm preenchidos).
  function init () {
    SCOPE_IDS.forEach(function (id) {
      var scope = document.getElementById(id)
      if (scope) {
        scope.querySelectorAll('.wc-block-components-text-input input').forEach(sync)
      }
    })
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }

  if (window.jQuery) {
    window.jQuery(document.body).on('updated_checkout', function () {
      setTimeout(init, 200)
    })
  }
})()
