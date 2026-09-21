/**
 * Cielo Debit - Compact Layout (Blocks / Gutenberg checkout)
 *
 * Reorganiza os campos do checkout em blocos para o layout compacto:
 *  - Linha 1: Nome do titular | Tipo do cartão
 *  - Linha 2: Número do cartão (com bandeiras) | Validade | Código de segurança
 *  - Linha 3: Parcelas / salvar cartão / finalizar
 *
 * As três bandeiras (Visa, Mastercard, Elo) são exibidas dentro do campo de
 * número do cartão, à direita. Conforme o usuário digita, a bandeira detectada
 * fica colorida e as demais ficam em cinza; se não for nenhuma das três, todas
 * ficam cinzas (mesmo esquema do layout moderno).
 *
 * Fontes de ícones (fallbacks em ordem):
 *  - window.lknCieloDebitConfig.cardIcons / inputIcons (sempre presente no bloco)
 *  - window.lknCieloDebitCompactIcons
 *  - window.lknCieloDebitCardIcons / window.lknCieloInputIcons
 *
 * @package Lkn\WCCieloPaymentGateway
 * @author  Link Nacional
 */
(function () {
    'use strict'

    var CONTENT_ID = 'radio-control-wc-payment-method-options-lkn_cielo_debit__content'
    var CARD_BRANDS = ['visa', 'mastercard', 'elo']
    var debounceTimer = null
    var lastBrandValue = null
    var lastDetectedBrand = null

    // Base dos ícones derivada do próprio <script>. Fallback garantido: funciona
    // mesmo se nenhuma variável localizada (lknCieloDebitConfig/...) estiver
    // disponível por algum motivo (ex.: ordem de carregamento).
    var SCRIPT_SRC = (document.currentScript && document.currentScript.src) ? document.currentScript.src : ''
    var ICON_BASE = SCRIPT_SRC ? SCRIPT_SRC.replace(/js\/debitCard\/[^/?#]+.*$/, 'img/') : ''
    if (ICON_BASE === SCRIPT_SRC) ICON_BASE = ''

    function fallbackIcons () {
        if (!ICON_BASE) return null
        return {
            visa: ICON_BASE + 'visa-icon.svg',
            mastercard: ICON_BASE + 'mastercard-icon.svg',
            elo: ICON_BASE + 'elo-icon.svg'
        }
    }

    function getIconsConfig () {
        if (typeof window.lknCieloDebitConfig !== 'undefined' && lknCieloDebitConfig.cardIcons) {
            return {
                visa: lknCieloDebitConfig.cardIcons.visa,
                mastercard: lknCieloDebitConfig.cardIcons.mastercard,
                elo: lknCieloDebitConfig.cardIcons.elo,
                show_card_brand_icons: lknCieloDebitConfig.showCardBrandIcons
            }
        }
        if (typeof window.lknCieloDebitCompactIcons !== 'undefined') return window.lknCieloDebitCompactIcons
        if (typeof window.lknCieloDebitCardIcons !== 'undefined') return window.lknCieloDebitCardIcons
        return fallbackIcons()
    }

    function getInputIcons () {
        if (typeof window.lknCieloDebitConfig !== 'undefined' && lknCieloDebitConfig.inputIcons) return lknCieloDebitConfig.inputIcons
        if (typeof window.lknCieloInputIcons !== 'undefined') return window.lknCieloInputIcons
        if (ICON_BASE) {
            return { calendar: ICON_BASE + 'calendar.svg', key: ICON_BASE + 'key.svg', lock: ICON_BASE + 'lock.svg' }
        }
        return null
    }

    function fieldContainer (inputId) {
        var input = document.getElementById(inputId)
        return input ? input.closest('.wc-block-components-text-input') : null
    }

    function numberFieldContainer () {
        return fieldContainer('lkn_dcno')
    }

    function highlightIcons (brand) {
        var container = numberFieldContainer()
        if (!container) return
        // Registra a última bandeira destacada (null = estado colorido/sem match).
        lastDetectedBrand = (brand === null || brand === '') ? null : brand
        container.querySelectorAll('.lkn-cielo-compact-brands img').forEach(function (img) {
            var b = (img.getAttribute('data-brand') || '').toLowerCase()
            if (brand === null || brand === '') {
                // Sem filtro: volta todas ao estado colorido (padrão).
                img.style.filter = 'none'
                img.style.opacity = '1'
            } else if (b === brand) {
                // Bandeira detectada: colorida.
                img.style.filter = 'none'
                img.style.opacity = '1'
            } else {
                // Outras: cinza.
                img.style.filter = 'grayscale(100%)'
                img.style.opacity = '0.35'
            }
        })
    }

    // Deixa todas as bandeiras cinzas (usuário começou a digitar, ainda sem match).
    function grayAllIcons () {
        lastDetectedBrand = null
        var container = numberFieldContainer()
        if (!container) return
        container.querySelectorAll('.lkn-cielo-compact-brands img').forEach(function (img) {
            img.style.filter = 'grayscale(100%)'
            img.style.opacity = '0.35'
        })
    }

    // Reaplica o estado atual das bandeiras (útil quando o React recria o
    // container e as imagens são recriadas no estado padrão).
    function applyCurrentFlagState () {
        var digits = lastBrandValue || ''
        if (digits.length === 0) {
            highlightIcons(null)
        } else if (lastDetectedBrand) {
            highlightIcons(lastDetectedBrand)
        } else {
            grayAllIcons()
        }
    }

    function fetchBrand (number) {
        var clean = number.replace(/\s+/g, '')
        if (clean.length < 6) return
        var restUrl = (typeof window.lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.rest_url)
            ? lknCieloRestSettings.rest_url
            : (window.location.origin + '/wp-json/')
        var nonce = (typeof window.lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.nonce)
            ? lknCieloRestSettings.nonce
            : ''
        // Ignora respostas obsoletas: se o valor do campo já mudou, não aplica
        // (evita que uma resposta atrasada sobrescreva o estado atual).
        var isStale = function () {
            var input = document.getElementById('lkn_dcno')
            var current = input ? input.value.replace(/\s+/g, '') : ''
            return current !== clean
        }
        fetch(restUrl + 'lknWCGatewayCielo/getCardBrand?number=' + encodeURIComponent(clean) + '&gateway=debit', {
            method: 'GET',
            headers: { Accept: 'application/json', 'X-WP-Nonce': nonce }
        })
            .then(function (r) { return r.json() })
            .then(function (data) {
                if (isStale()) return
                if (data && data.status && data.brand && CARD_BRANDS.indexOf(data.brand.toLowerCase()) !== -1) {
                    highlightIcons(data.brand.toLowerCase())
                } else {
                    // Não é nenhuma das 3 bandeiras (ou falhou): todas cinzas.
                    grayAllIcons()
                }
            })
            .catch(function () { if (!isStale()) grayAllIcons() })
    }

    function buildBrandIcons () {
        var container = numberFieldContainer()
        if (!container) return
        // Já montado com as bandeiras? Nada a fazer.
        if (container.querySelector('.lkn-cielo-compact-brands img')) return

        var config = getIconsConfig()
        var wrap = container.querySelector('.lkn-cielo-compact-brands')
        if (!wrap) {
            wrap = document.createElement('div')
            wrap.className = 'lkn-cielo-compact-brands'
            container.appendChild(wrap)
        }

        // No layout compacto as 3 bandeiras fazem parte do design: sempre exibe
        // (independente da opção "Show card brand icons", que vale para o moderno).
        var added = 0
        if (config) {
            CARD_BRANDS.forEach(function (brand) {
                if (!config[brand]) return
                if (wrap.querySelector('img[data-brand="' + brand + '"]')) return
                var img = document.createElement('img')
                img.src = config[brand]
                img.alt = brand + ' logo'
                img.setAttribute('data-brand', brand)
                wrap.appendChild(img)
                added++
            })
        }

        // Imagens recém-criadas nascem sem filtro; reaplica o estado atual.
        if (added > 0) {
            applyCurrentFlagState()
        }
    }

    function addFieldIcon (inputId, iconUrl) {
        var container = fieldContainer(inputId)
        if (!container || !iconUrl) return
        if (container.querySelector('.lkn-cielo-compact-icon')) return
        var img = document.createElement('img')
        img.className = 'lkn-cielo-compact-icon'
        img.src = iconUrl
        img.alt = ''
        img.setAttribute('aria-hidden', 'true')
        container.appendChild(img)
    }

    function buildFieldIcons () {
        var icons = getInputIcons()
        if (!icons) return
        addFieldIcon('lkn_dc_expdate', icons.calendar)
        addFieldIcon('lkn_dc_cvc', icons.key)
    }

    // Atualiza o estado das bandeiras a partir do valor atual do campo.
    // - 0 dígitos: todas coloridas
    // - 1-5 dígitos: todas cinza
    // - 6+ dígitos: todas cinza + consulta a bandeira (destaca a detectada)
    // Guarda o último valor para não repetir a consulta a cada execução.
    function refreshFlags (value) {
        var digits = (value || '').replace(/\s+/g, '')
        if (digits === lastBrandValue) return
        lastBrandValue = digits
        clearTimeout(debounceTimer)

        if (digits.length === 0) {
            highlightIcons(null)
            return
        }
        grayAllIcons()
        if (digits.length >= 6) {
            debounceTimer = setTimeout(function () { fetchBrand(value) }, 400)
        }
    }

    function setupNumberInput () {
        var input = document.getElementById('lkn_dcno')
        if (!input || input.hasAttribute('data-lkn-compact-init')) return
        input.setAttribute('data-lkn-compact-init', 'true')
        input.addEventListener('input', function () { refreshFlags(input.value) })
    }

    // Placeholders no Gutenberg (o componente TextInput do React não os aceita).
    function setupPlaceholders () {
        var placeholders = {
            lkn_dc_cardholder_name: 'Nome impresso no cartão',
            lkn_dcno: '0000 0000 0000 0000',
            lkn_dc_expdate: 'MM/AA',
            lkn_dc_cvc: 'CVC'
        }
        Object.keys(placeholders).forEach(function (id) {
            var input = document.getElementById(id)
            if (input && input.getAttribute('placeholder') !== placeholders[id]) {
                input.setAttribute('placeholder', placeholders[id])
            }
        })
    }

    // Formatação do código de segurança: só dígitos, no máximo 4. Sincroniza com
    // o estado do React usando o setter nativo (o setter do React deduplica
    // valores iguais e não dispararia o onChange) + evento 'input'.
    function setupCvcMask () {
        var input = document.getElementById('lkn_dc_cvc')
        if (!input || input.hasAttribute('data-lkn-cvc-mask')) return
        input.setAttribute('data-lkn-cvc-mask', 'true')
        input.setAttribute('inputmode', 'numeric')
        input.setAttribute('maxlength', '4')

        var nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value') &&
            Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set

        input.addEventListener('input', function () {
            if (input.__lknMasking) return
            var digits = input.value.replace(/\D/g, '').slice(0, 4)
            if (digits !== input.value) {
                input.__lknMasking = true
                if (nativeSetter) {
                    nativeSetter.call(input, digits)
                } else {
                    input.value = digits
                }
                input.dispatchEvent(new Event('input', { bubbles: true }))
                input.__lknMasking = false
            }
        })
    }

    function setOrder (el, order) {
        if (el) el.style.order = String(order)
    }

    // Esconde os espaçadores vazios que o React do plugin renderiza (os <div>
    // sem classe/id, sem filhos e sem texto). No layout padrão eles separam
    // blocos na vertical; no compacto, com o reorder por `order`, sobram como
    // espaço morto (ex.: abaixo do botão Finalizar).
    function hideEmptySpacers () {
        var content = document.getElementById(CONTENT_ID)
        if (!content) return
        content.querySelectorAll('div').forEach(function (div) {
            if (div.id || (div.className && String(div.className).trim() !== '')) return
            if (div.children.length > 0) return
            if (div.textContent && div.textContent.trim() !== '') return
            div.style.display = 'none'
        })
    }

    function apply () {
        var content = document.getElementById(CONTENT_ID)
        if (!content) return

        var nameEl = document.getElementById('lkn_dc_cardholder_name')
        var nameC = nameEl ? nameEl.closest('.wc-block-components-text-input') : null
        var numberC = numberFieldContainer()
        var expC = fieldContainer('lkn_dc_expdate')
        var cvcC = fieldContainer('lkn_dc_cvc')
        var typeC = content.querySelector('.lkn-credit-debit-card-type-select')

        // Ordem: nome → tipo → número → validade → cvv (a largura vem do CSS,
        // via :has, para não depender de classes que o React remove).
        setOrder(nameC, 10)
        setOrder(typeC, 20)
        setOrder(numberC, 30)
        setOrder(expC, 40)
        setOrder(cvcC, 50)

        // Demais blocos mantêm a ordem natural, porém abaixo dos campos.
        setOrder(content.querySelector('.lkn-cielo-animated-card-container'), 5)
        setOrder(content.querySelector('.lkn-cielo-credit-debit-custom-select'), 60)

        var saveCheckbox = document.getElementById('lkn_save_debit_credit_card')
        if (saveCheckbox) setOrder(saveCheckbox.closest('.wc-block-components-checkbox') || saveCheckbox.parentElement, 70)

        var submit = document.getElementById('sendOrder')
        if (submit) {
            var submitWrap = submit.closest('div')
            setOrder(submitWrap, 80)
        }

        setOrder(content.querySelector('.lkn-cielo-credit-debit-description'), 90)

        buildBrandIcons()
        buildFieldIcons()
        setupNumberInput()
        setupCvcMask()
        setupPlaceholders()
        hideEmptySpacers()

        // Sincroniza as bandeiras com o valor atual (cobre mudanças programáticas,
        // ex.: selecionar um cartão salvo, que não dispara evento 'input').
        var numberInput = document.getElementById('lkn_dcno')
        if (numberInput) refreshFlags(numberInput.value)
    }

    function start () {
        apply()
        // Reaplica em mudanças de DOM e de classe (o React pode recriar/atualizar
        // os containers dos campos). Debounce para não disparar em cascata.
        var scheduled = false
        var schedule = function () {
            if (scheduled) return
            scheduled = true
            setTimeout(function () {
                scheduled = false
                apply()
            }, 50)
        }
        var observer = new MutationObserver(schedule)
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class']
        })

        // Retentativas após a hidratação do React (garante as bandeiras mesmo se
        // nenhuma mutação observada ocorrer depois do carregamento).
        setTimeout(apply, 400)
        setTimeout(apply, 1200)
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start)
    } else {
        start()
    }
})()
