/**
 * Cielo Débito/Crédito - Animação das bandeiras no layout Compacto (shortcode).
 *
 * Arquivo dedicado ao checkout clássico do layout compacto. Cuida apenas da
 * animação das bandeiras dentro do campo de número (cinza ao digitar; colorida
 * na bandeira detectada; sem match → todas cinzas). O submit do botão custom
 * (`#cielo-debit-submit-btn`) é tratado pelo script de 3DS (lkn-dc-script-*),
 * então NÃO replicamos essa lógica aqui.
 *
 * Substitui o uso do lkn-cielo-brand-detector.js (compartilhado com o layout
 * moderno) apenas no compacto, mantendo o comportamento do moderno intacto.
 *
 * Fonte deste bundle: resources/js/debitCard/lkn-cielo-debit-compact-classic.js
 * (compilado por `npm run build`).
 *
 * @package Lkn\WCCieloPaymentGateway
 */
(function () {
    'use strict';

    var cardNumberInput = null;
    var brandIcons = null;
    var debounceTimer = null;
    var lastDetectedBrand = null;

    function getRestSettings() {
        return (typeof lknCieloRestSettings !== 'undefined') ? lknCieloRestSettings : null;
    }

    function fetchCardBrand(number) {
        var cleanNumber = number.replace(/\s+/g, '');
        if (cleanNumber.length < 6) {
            return Promise.resolve(null);
        }

        var settings = getRestSettings();
        var restUrl = (settings && settings.rest_url) ? settings.rest_url : (window.location.origin + '/wp-json/');
        var nonce = (settings && settings.nonce) ? settings.nonce : '';

        return fetch(restUrl + 'lknWCGatewayCielo/getCardBrand?number=' + encodeURIComponent(cleanNumber) + '&gateway=debit', {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-WP-Nonce': nonce }
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (data && data.status && data.brand) {
                    return data.brand;
                }
                return null;
            })
            .catch(function () {
                return null;
            });
    }

    function updateBrandIcons(detectedBrand) {
        lastDetectedBrand = detectedBrand || null;
        if (!brandIcons || brandIcons.length === 0) {
            brandIcons = document.querySelectorAll('#cielo-debit-card-brands .card-brand-icon');
        }
        if (!brandIcons || brandIcons.length === 0) {
            return;
        }

        brandIcons.forEach(function (icon) {
            var iconBrand = icon.getAttribute('data-brand');
            if (detectedBrand === null) {
                // Sem bandeira detectada: estado colorido padrão.
                icon.style.filter = '';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            } else if (iconBrand === detectedBrand) {
                icon.style.filter = 'grayscale(0%) opacity(1)';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            } else {
                icon.style.filter = 'grayscale(100%) opacity(0.4)';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            }
        });
    }

    function applyGrayFilterToAll() {
        lastDetectedBrand = null;
        if (!brandIcons || brandIcons.length === 0) {
            brandIcons = document.querySelectorAll('#cielo-debit-card-brands .card-brand-icon');
        }
        if (!brandIcons || brandIcons.length === 0) {
            return;
        }
        brandIcons.forEach(function (icon) {
            icon.style.filter = 'grayscale(100%) opacity(0.4)';
            icon.style.transform = '';
            icon.style.transition = 'all 0.3s ease-in-out';
        });
    }

    function handleCardInput() {
        if (!cardNumberInput) return;
        var cleanNumber = cardNumberInput.value.replace(/\s+/g, '');

        if (!brandIcons || brandIcons.length === 0) {
            brandIcons = document.querySelectorAll('#cielo-debit-card-brands .card-brand-icon');
        }
        if (!brandIcons || brandIcons.length === 0) {
            return;
        }

        if (cleanNumber.length === 0) {
            clearTimeout(debounceTimer);
            updateBrandIcons(null);
            return;
        }
        if (cleanNumber.length < 6) {
            clearTimeout(debounceTimer);
            applyGrayFilterToAll();
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            fetchCardBrand(cleanNumber).then(function (detectedBrand) {
                if (detectedBrand) {
                    updateBrandIcons(detectedBrand);
                } else {
                    applyGrayFilterToAll();
                }
            });
        }, 500);
    }

    function initialize() {
        cardNumberInput = document.getElementById('lkn_dcno');
        brandIcons = document.querySelectorAll('#cielo-debit-card-brands .card-brand-icon');

        if (cardNumberInput && !cardNumberInput.hasAttribute('data-lkn-compact-classic-init')) {
            cardNumberInput.setAttribute('data-lkn-compact-classic-init', 'true');
            cardNumberInput.addEventListener('input', handleCardInput);
            cardNumberInput.addEventListener('keyup', handleCardInput);
            cardNumberInput.addEventListener('paste', function () {
                setTimeout(handleCardInput, 100);
            });
            if (cardNumberInput.value) {
                handleCardInput();
            }
        }

        // Reaplica o estado atual (o React/updated_checkout pode recriar os ícones).
        if (lastDetectedBrand) {
            updateBrandIcons(lastDetectedBrand);
        }
    }

    function setupObserver() {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    var hasTarget = Array.prototype.some.call(mutation.addedNodes, function (node) {
                        if (node.nodeType !== Node.ELEMENT_NODE) return false;
                        return node.querySelector('#cielo-debit-card-brands .card-brand-icon') ||
                            node.classList && node.classList.contains('card-brand-icon') ||
                            node.id === 'lkn_dcno';
                    });
                    if (hasTarget) {
                        setTimeout(initialize, 100);
                    }
                }
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (window.jQuery) {
        window.jQuery(document.body).on('updated_checkout', function () {
            setTimeout(initialize, 200);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initialize();
            setupObserver();
        });
    } else {
        initialize();
        setupObserver();
    }
})();
