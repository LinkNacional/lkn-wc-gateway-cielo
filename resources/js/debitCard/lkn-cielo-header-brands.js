/**
 * Cielo Débito — faixa de bandeiras no CABEÇALHO do método (checkout em Blocos).
 *
 * Injeta as bandeiras (Visa, Mastercard, Elo, Amex, Outros) dentro do
 * `.wc-block-components-radio-control__label-group`, ao lado do título do método,
 * gated pela opção "Show card brand icons". Vale para TODOS os layouts do débito
 * (Basic, Modern e Compact). Auto-contido (estilos inline) para não depender do
 * CSS de um layout específico. Delegado a um MutationObserver e idempotente.
 *
 * @package Lkn\WCCieloPaymentGateway
 */
(function () {
  'use strict'

  var BRANDS = ['visa', 'mastercard', 'elo', 'amex', 'other_card']

  function readConfig () {
    var block = window.lknCieloDebitConfig || {}
    var header = window.lknCieloHeaderIcons || {}
    var compact = window.lknCieloDebitCompactIcons || {}
    var cardIcons = window.lknCieloDebitCardIcons || {}

    var icons = Object.assign({}, cardIcons, compact, header, block.cardIcons || {})

    var show = block.showCardBrandIcons
    if (show === undefined) show = header.show_card_brand_icons
    if (show === undefined) show = compact.show_card_brand_icons
    if (show === undefined) show = cardIcons.show_card_brand_icons

    return {
      icons: icons,
      enabled: show === 'yes',
      otherAlt: header.other_card_alt || 'other card'
    }
  }

  function inject () {
    var target = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit')
    if (!target) return

    var parentLabel = target.closest('label')
    if (!parentLabel) return

    var labelGroup = parentLabel.querySelector('.wc-block-components-radio-control__label-group')
    if (!labelGroup) return

    // Já injetado? Nada a fazer.
    if (labelGroup.querySelector('.lkn-cielo-credit-debit-card-icons')) return

    var cfg = readConfig()
    if (!cfg.enabled) return

    labelGroup.style.display = 'flex'
    labelGroup.style.justifyContent = 'space-between'
    labelGroup.style.alignItems = 'center'
    labelGroup.style.gap = '10px'

    var wrap = document.createElement('div')
    wrap.setAttribute('class', 'lkn-cielo-credit-debit-card-icons')
    wrap.setAttribute('style', 'display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px;')

    BRANDS.forEach(function (brand) {
      var src = cfg.icons[brand]
      if (!src) return

      var img = document.createElement('img')
      img.setAttribute('src', src)
      img.setAttribute('alt', brand === 'other_card' ? cfg.otherAlt + ' logo' : brand + ' logo')
      img.setAttribute('title', brand === 'other_card'
        ? cfg.otherAlt.charAt(0).toUpperCase() + cfg.otherAlt.slice(1)
        : brand.charAt(0).toUpperCase() + brand.slice(1))
      img.setAttribute('style', 'width: 40px; height: 40px; object-fit: contain; filter: none !important; opacity: 1 !important; transition: .3s !important;')
      wrap.appendChild(img)
    })

    // Sem nenhum ícone disponível, não injeta container vazio.
    if (!wrap.children.length) return

    labelGroup.appendChild(wrap)
  }

  var scheduled = false
  function schedule () {
    if (scheduled) return
    scheduled = true
    setTimeout(function () { scheduled = false; inject() }, 50)
  }

  var observer = new MutationObserver(schedule)
  observer.observe(document.body, { childList: true, subtree: true })

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inject)
  } else {
    inject()
  }
  setTimeout(inject, 400)
  setTimeout(inject, 1200)
})()
