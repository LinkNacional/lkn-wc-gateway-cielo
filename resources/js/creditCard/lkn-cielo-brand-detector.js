/**
 * Cielo Card Brand Detector
 * 
 * Monitors card number input and applies visual effects to brand icons.
 * Handles WooCommerce checkout integration and submit button synchronization.
 * 
 * Features:
 * - Real-time card brand detection (6+ digits)
 * - Visual feedback on brand icons (grayscale filters)
 * - Focus effects for modern form fields
 * - Submit button synchronization with WooCommerce
 * - MutationObserver for dynamic content
 * - WooCommerce events integration (updated_checkout)
 * 
 * @package Lkn\WCCieloPaymentGateway
 * @since 1.0.0
 * @author Link Nacional
 * 
 * Used with: lkn-cielo-credit-payment-fields-modern-layout.php
 * CSS Support: lkn-cielo-modern-layout.css
 */

(function() {
    'use strict';
    
    let isInitialized = false;
    let cardNumberInput = null;
    let brandIcons = null;
    let debounceTimer = null;
    let lastDetectedBrand = null;
    
    /**
     * Fetch card brand from the REST endpoint (online + offline fallback).
     * @param {string} number - Card number
     * @returns {Promise<object|null>}
     */
    function fetchCardBrand(number) {
        const cleanNumber = number.replace(/\s+/g, '');
        
        if (cleanNumber.length < 6) {
            return Promise.resolve(null);
        }
        
        // Detect REST URL + nonce
        var restUrl = (typeof lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.rest_url)
            ? lknCieloRestSettings.rest_url
            : (window.location.origin + '/wp-json/');
        var nonce = (typeof lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.nonce)
            ? lknCieloRestSettings.nonce
            : '';
        
        return fetch(restUrl + 'lknWCGatewayCielo/getCardBrand?number=' + encodeURIComponent(cleanNumber) + '&gateway=credit', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-WP-Nonce': nonce
            }
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.status && data.brand) {
                return data.brand;
            }
            return null;
        })
        .catch(function() {
            return null;
        });
    }
    
    /**
     * Apply visual effects to brand icons
     * @param {string|null} detectedBrand - Brand name or null
     */
    function updateBrandIcons(detectedBrand) {
        // Só manipular ícones se estiverem habilitados
        if (typeof lknCieloCreditBrandConfig !== 'undefined' && lknCieloCreditBrandConfig.show_card_brand_icons !== 'yes') {
            return; // Não manipular os ícones se não estiverem habilitados
        }
        
        if (!brandIcons || brandIcons.length === 0) {
            brandIcons = document.querySelectorAll('#cielo-credit-card-brands .card-brand-icon');
        }
        
        brandIcons.forEach(icon => {
            const iconBrand = icon.getAttribute('data-brand');
            
            if (detectedBrand === null) {
                // No brand detected - remove all effects  
                icon.style.filter = '';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            } else if (iconBrand === detectedBrand) {
                // Highlight detected brand - remove grayscale only
                icon.style.filter = 'grayscale(0%) opacity(1)';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            } else {
                // Gray out other brands  
                icon.style.filter = 'grayscale(100%) opacity(0.4)';
                icon.style.transform = '';
                icon.style.transition = 'all 0.3s ease-in-out';
            }
        });
    }
    
    /**
     * Handle card number input changes
     */
    function handleCardInput() {
        if (!cardNumberInput) return;
        
        const cardNumber = cardNumberInput.value;
        const cleanNumber = cardNumber.replace(/\s+/g, '');
        
        // Só aplicar efeitos nos ícones se estiverem habilitados
        if (typeof lknCieloCreditBrandConfig !== 'undefined' && lknCieloCreditBrandConfig.show_card_brand_icons === 'yes') {
            // Apply gray filter when user starts typing (1+ digits)
            if (cleanNumber.length >= 1 && cleanNumber.length < 6) {
                applyGrayFilterToAll();
                return;
            }
            
            // Debounce the REST call (only when 6+ digits)
            if (cleanNumber.length >= 6) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    fetchCardBrand(cleanNumber).then(function(detectedBrand) {
                        updateBrandIcons(detectedBrand);
                    });
                }, 500);
            }
        }
        
        // Outras funcionalidades do input continuam funcionando independentemente
    }
    
    /**
     * Apply gray filter to all brand icons
     */
    function applyGrayFilterToAll() {
        // Só manipular ícones se estiverem habilitados
        if (typeof lknCieloCreditBrandConfig !== 'undefined' && lknCieloCreditBrandConfig.show_card_brand_icons !== 'yes') {
            return; // Não manipular os ícones se não estiverem habilitados
        }
        
        if (!brandIcons || brandIcons.length === 0) {
            brandIcons = document.querySelectorAll('#cielo-credit-card-brands .card-brand-icon');
        }
        
        brandIcons.forEach(icon => {
            icon.style.filter = 'grayscale(100%) opacity(0.4)';
            icon.style.transform = '';
            icon.style.transition = 'all 0.3s ease-in-out';
        });
    }
    
    /**
     * Initialize the brand detector
     */
    function initializeBrandDetector() {
        // Get elements - credit specific ID
        cardNumberInput = document.getElementById('lkn_ccno');
        brandIcons = document.querySelectorAll('#cielo-credit-card-brands .card-brand-icon');
        
        // Setup card input listeners apenas se input existir
        if (cardNumberInput && !cardNumberInput.hasAttribute('data-cielo-initialized')) {
            cardNumberInput.setAttribute('data-cielo-initialized', 'true');
            
            // Event listeners
            cardNumberInput.addEventListener('input', handleCardInput);
            cardNumberInput.addEventListener('keyup', handleCardInput);
            cardNumberInput.addEventListener('paste', function() {
                // Small delay to allow paste content to be processed
                setTimeout(handleCardInput, 100);
            });
            
            // Initial check if there's already a value
            if (cardNumberInput.value) {
                handleCardInput();
            }
        }
        
        // Enhanced focus effects for modern layout - sempre executar
        const modernInputs = document.querySelectorAll('.field-input:not([data-focus-initialized]), .field-select:not([data-focus-initialized])');
        
        modernInputs.forEach(input => {
            input.setAttribute('data-focus-initialized', 'true');
            
            input.addEventListener('focus', function() {
                this.closest('.modern-field').classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.modern-field').classList.remove('focused');
            });
        });
        
        // Initialize submit button synchronization - SEMPRE executar
        initializeSubmitButton();
        
        return true;
    }
    
    /**
     * Initialize submit button synchronization
     */
    function initializeSubmitButton() {
        const cieloSubmitBtn = document.getElementById('cielo-credit-submit-btn');
        
        if (!cieloSubmitBtn) return;
        
        // Find the actual WooCommerce submit button
        const wooSubmitBtn = document.querySelector('#place_order, [name="woocommerce_checkout_place_order"], .wc-block-components-checkout-place-order-button');
        
        if (wooSubmitBtn) {
            // Sync custom button with WooCommerce button state
            const syncButtonState = () => {
                // Don't sync if button is in processing state (custom feedback)
                if (cieloSubmitBtn.textContent === 'Processing...' && cieloSubmitBtn.style.backgroundColor === 'rgb(108, 117, 125)') {
                    return;
                }
                
                if (wooSubmitBtn.disabled) {
                    cieloSubmitBtn.disabled = true;
                    cieloSubmitBtn.textContent = wooSubmitBtn.textContent || 'Processing...';
                } else {
                    cieloSubmitBtn.disabled = false;
                    cieloSubmitBtn.textContent = cieloSubmitBtn.getAttribute('data-original-text') || 'Confirm Payment';
                }
            };
            
            // Store original text
            if (!cieloSubmitBtn.getAttribute('data-original-text')) {
                cieloSubmitBtn.setAttribute('data-original-text', cieloSubmitBtn.textContent);
            }
            
            // Click handler - trigger WooCommerce submit with delay feedback
            cieloSubmitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.disabled && wooSubmitBtn && !wooSubmitBtn.disabled) {
                    // Immediate visual feedback
                    this.disabled = true;
                    this.style.backgroundColor = '#6c757d';
                    this.style.borderColor = '#6c757d';
                    this.style.cursor = 'not-allowed';
                    this.textContent = 'Processing...';
                    
                    // Trigger WooCommerce submit
                    wooSubmitBtn.click();
                    
                    // If no checkout error occurs, restore after 4 seconds
                    const restoreButton = () => {
                        setTimeout(() => {
                            if (this.disabled) {
                                this.disabled = false;
                                this.style.backgroundColor = '';
                                this.style.borderColor = '';
                                this.style.cursor = '';
                                this.textContent = this.getAttribute('data-original-text') || 'Confirm Payment';
                            }
                        }, 4000);
                    };
                    
                    restoreButton();
                }
            });
            
            // Monitor WooCommerce button changes
            const observer = new MutationObserver(syncButtonState);
            observer.observe(wooSubmitBtn, { 
                attributes: true, 
                attributeFilter: ['disabled', 'class'],
                childList: true,
                subtree: true 
            });
            
            // Initial sync
            syncButtonState();
            
            // Also listen for form validation changes
            document.addEventListener('checkout_error', () => {
                // Restore button immediately on error
                if (cieloSubmitBtn.disabled) {
                    cieloSubmitBtn.disabled = false;
                    cieloSubmitBtn.style.backgroundColor = '';
                    cieloSubmitBtn.style.borderColor = '';
                    cieloSubmitBtn.style.cursor = '';
                    cieloSubmitBtn.textContent = cieloSubmitBtn.getAttribute('data-original-text') || 'Confirm Payment';
                }
                syncButtonState();
            });
            
            document.addEventListener('updated_checkout', () => {
                setTimeout(syncButtonState, 100);
            });
        }
    }
    
    /**
     * Setup MutationObserver to watch for DOM changes
     */
    function setupObserver() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if any new nodes contain our target elements
                    const hasTargetElements = Array.from(mutation.addedNodes).some(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            return node.querySelector('#lkn_ccno, #cielo-credit-card-brands .card-brand-icon') ||
                                   node.id === 'lkn_ccno' ||
                                   node.classList?.contains('card-brand-icon');
                        }
                        return false;
                    });
                    
                    if (hasTargetElements) {
                        setTimeout(initializeBrandDetector, 100);
                    }
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initialize on WooCommerce checkout update
    jQuery(document.body).on('updated_checkout', function() {
        setTimeout(initializeBrandDetector, 200);
    });
    
    // Initialize on DOMContentLoaded as fallback
    document.addEventListener('DOMContentLoaded', function() {
        initializeBrandDetector();
        setupObserver();
    });
    
    // Initialize immediately if DOM is already loaded
    if (document.readyState === 'loading') {
        // Do nothing, DOMContentLoaded will fire
    } else {
        initializeBrandDetector();
        setupObserver();
    }
})();