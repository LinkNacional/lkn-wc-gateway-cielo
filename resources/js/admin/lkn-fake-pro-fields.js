/**
 * Campos "fake" dos recursos PRO (showcase no plano free).
 *
 * Quando não há licença PRO ativa, o gateway replica os campos do PRO como fakes
 * (chaves com sufixo *_fake), porém totalmente interativos — o lojista pode explorar
 * os recursos, mas nada é gravado nas opções reais. Este script apenas exibe um aviso
 * deixando claro que os recursos marcados com o selo "PRO" estão em modo demonstração.
 *
 * As dependências visuais (juros x desconto nas parcelas, etc.) são aplicadas pelo
 * layout admin (lkn-wc-gateway-admin-layout.js), que cobre os campos reais e fake.
 */
document.addEventListener('DOMContentLoaded', function () {
  // Roda após o layout admin refluir os campos (window.load), quando o cabeçalho
  // já está no lugar. A injeção é idempotente.
  if (document.readyState === 'complete') {
    setTimeout(injectFakeProNotice, 200)
  } else {
    window.addEventListener('load', function () {
      setTimeout(injectFakeProNotice, 200)
    })
  }
})

/**
 * Exibe um aviso deixando claro que os recursos marcados como PRO estão em modo
 * demonstração no plano gratuito: podem ser ajustados livremente, mas só surtem
 * efeito com uma licença PRO ativa.
 */
function injectFakeProNotice () {
  if (document.getElementById('lkn-cielo-fake-pro-notice')) {
    return
  }

  var message = (window.lknFakeProFieldsI18n && window.lknFakeProFieldsI18n.notice)
    ? window.lknFakeProFieldsI18n.notice
    : 'PRO features marked with the PRO badge are a preview on the free plan. You can adjust them freely, but they only take effect with an active PRO license.'

  var notice = document.createElement('div')
  notice.id = 'lkn-cielo-fake-pro-notice'
  notice.className = 'notice notice-error inline'
  notice.setAttribute('style', 'margin: 10px 0; padding: 8px 12px; border-left-color: #d63638;')

  var p = document.createElement('p')
  p.setAttribute('style', 'margin: 4px 0;')
  p.textContent = message
  notice.appendChild(p)

  // Coloca logo ACIMA do botão "Salvar alterações" (fim do formulário), para o
  // lojista ver o aviso antes de salvar.
  var submit = document.querySelector('#mainform p.submit') ||
    document.querySelector('#mainform .woocommerce-save-button')
  if (submit && submit.parentNode) {
    submit.parentNode.insertBefore(notice, submit)
    return
  }

  var header = document.querySelector('.wc-admin-header')
  if (header && header.nextElementSibling) {
    header.nextElementSibling.before(notice)
  } else if (header) {
    header.after(notice)
  } else {
    var menu = document.getElementById('lknWcCieloCreditBlocksSettingsLayoutMenu')
    var form = document.querySelector('#mainform')
    var anchor = menu || form
    if (anchor && anchor.parentNode) {
      anchor.parentNode.insertBefore(notice, anchor)
    }
  }
}
