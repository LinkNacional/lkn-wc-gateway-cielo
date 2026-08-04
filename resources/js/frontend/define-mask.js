/* eslint-disable no-undef */
(function ($) {
  'use strict'

  $(window).on('load', () => {
    lknWCCieloLoadMask()
    $('body').on('updated_checkout', lknWCCieloLoadMask)
  })

  function lknWCCieloLoadMask () {
    $('.lkn-cvv').mask('00000000')
    $('.lkn-card-exp').mask('00 / 00')
    // Formata número do cartão: só dígitos, com espaço a cada 4
    $('.lkn-card-num').each(function () {
      const $el = $(this)
      if ($el.data('lkn-card-formatted')) return
      $el.data('lkn-card-formatted', true)
      $el.off('input.lknCardNum').on('input.lknCardNum', function () {
        const cursorStart = this.selectionStart
        const oldVal = $el.val()
        const oldSpaces = (oldVal.substring(0, cursorStart).match(/\s/g) || []).length
        const digits = $el.val().replace(/\D/g, '')
        const formatted = digits.replace(/(.{4})/g, '$1 ').trim()
        $el.val(formatted)
        const newSpaces = (formatted.substring(0, cursorStart).match(/\s/g) || []).length
        const spaceDiff = newSpaces - oldSpaces
        this.setSelectionRange(cursorStart + spaceDiff, cursorStart + spaceDiff)
      })
    })
  }
})(jQuery)
