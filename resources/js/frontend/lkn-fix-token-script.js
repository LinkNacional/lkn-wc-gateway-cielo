(function ($) {
    $(window).on('load', () => {
        // Suporta rest_url (padrão) e root (legado), com fallback para wpApiSettings
        const restRoot = (typeof lknCieloRestSettings !== 'undefined' && (lknCieloRestSettings.rest_url || lknCieloRestSettings.root))
            ? (lknCieloRestSettings.rest_url || lknCieloRestSettings.root)
            : (typeof wpApiSettings !== 'undefined' && wpApiSettings.root ? wpApiSettings.root : null);

        const nonce = (typeof lknCieloRestSettings !== 'undefined' && lknCieloRestSettings.nonce)
            ? lknCieloRestSettings.nonce
            : (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce ? wpApiSettings.nonce : '');

        // Margem de segurança: renova um pouco antes do vencimento para evitar 401
        // durante a autenticação 3DS.
        const RENEW_MARGIN_SECONDS = 30;
        // Intervalo para tentar novamente quando a renovação falhar.
        const RETRY_DELAY_MS = 30000;

        const lknWcGatewayCieloRenewToken = () => {
            const expiresInput = document.querySelector('#expires_in');
            const tokenInput = document.querySelector('.bpmpi_accesstoken');

            if (!restRoot) {
                console.warn('lknCieloRestSettings.rest_url not available, skipping access token renewal');
                return;
            }

            if (!expiresInput || !tokenInput) {
                // Campos ainda não renderizados (checkout por blocos / ajax) — tenta de novo em breve.
                setTimeout(lknWcGatewayCieloRenewToken, RETRY_DELAY_MS);
                return;
            }

            let expiresInSeconds = parseInt(expiresInput.value, 10);

            // Sem valor válido, assume renovação em 60s como fallback.
            if (!expiresInSeconds || expiresInSeconds <= 0) {
                expiresInSeconds = 60;
            }

            // Renova antes do vencimento para nunca entregar token expirado ao MPI.
            const renewDelayMs = Math.max(0, (expiresInSeconds - RENEW_MARGIN_SECONDS)) * 1000;

            setTimeout(() => {
                $.ajax({
                    url: restRoot + 'lknWCGatewayCielo/getAcessToken',
                    contentType: 'application/json',
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': nonce
                    },
                    success: function (response) {
                        if (response && response.access_token) {
                            expiresInput.value = response.expires_in;
                            tokenInput.value = response.access_token;
                            lknWcGatewayCieloRenewToken();
                        } else {
                            // Token não gerado (credenciais/API) — tenta de novo em breve.
                            console.error('Access token renewal returned an empty token.');
                            setTimeout(lknWcGatewayCieloRenewToken, RETRY_DELAY_MS);
                        }
                    },
                    error: function (error) {
                        console.error('Error getting access token:', error);
                        // Não desiste: se falhar, o token pode expirar e quebrar o 3DS.
                        setTimeout(lknWcGatewayCieloRenewToken, RETRY_DELAY_MS);
                    }
                });
            }, renewDelayMs);
        };

        lknWcGatewayCieloRenewToken();

        let mutationCalled = false;

        const radioInputCieloDebitId = 'radio-control-wc-payment-method-options-lkn_cielo_debit';
        const observer = new MutationObserver((mutationsList) => {
            const radioInputCieloDebit = document.getElementById(radioInputCieloDebitId);
            if (radioInputCieloDebit && radioInputCieloDebit.checked && !mutationCalled) {
                mutationCalled = true;
                lknWcGatewayCieloRenewToken()
            }
        })

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    });
})(jQuery);
