document.addEventListener('DOMContentLoaded', function () {
  const lknCieloAdminPage = lknCieloFindGetParameter('section')

  if (lknCieloAdminPage && (lknCieloAdminPage === 'lkn_cielo_credit' || lknCieloAdminPage === 'lkn_cielo_debit' || lknCieloAdminPage === 'lkn_wc_cielo_pix') || lknCieloAdminPage === 'lkn_cielo_google_pay') {
    let observer = null

    // Bloco de destaque (mesmo padrão visual do plugin Rede)
    function createFeatureMessage(iconHtml, title, text, href) {
      const featureMessage = document.createElement(href ? 'a' : 'div')
      featureMessage.className = 'custom-feature-message'
      if (href) {
        featureMessage.href = href
        featureMessage.target = '_blank'
        featureMessage.rel = 'noopener noreferrer'
        featureMessage.style.textDecoration = 'none'
        featureMessage.style.color = 'inherit'
      }

      const infoIcon = document.createElement('span')
      infoIcon.className = 'feature-icon'
      infoIcon.innerHTML = iconHtml

      const contentDiv = document.createElement('div')
      contentDiv.className = 'feature-message-content'
      contentDiv.innerHTML = `<strong>${title}</strong><br>${text}`

      featureMessage.appendChild(infoIcon)
      featureMessage.appendChild(contentDiv)

      return featureMessage
    }

    // Cartão promocional do plugin "Link de Pagamento de Faturas"
    function createPromotionalCard() {
      const vars = (typeof lknCieloCardVars !== 'undefined') ? lknCieloCardVars : {}

      const promotionalCard = document.createElement('div')
      promotionalCard.className = 'woo-better-promotional-card'

      const backgroundDecor = document.createElement('div')
      backgroundDecor.className = 'promotional-card-background-decor'
      promotionalCard.appendChild(backgroundDecor)

      const cardContent = document.createElement('div')
      cardContent.className = 'promotional-card-content'

      const cardTitle = document.createElement('h3')
      cardTitle.className = 'promotional-card-title'
      cardTitle.textContent = 'Plugin Link de Pagamento de Faturas'

      const titleDivider = document.createElement('hr')
      titleDivider.className = 'promotional-card-title-divider'
      titleDivider.style.cssText = 'width: 100%; height: 1px; background: white; border: none; margin: 8px 0 16px 0;'

      const cardDescription = document.createElement('p')
      cardDescription.className = 'promotional-card-description'
      cardDescription.textContent = 'O Plugin Link de Pagamento oferece a solução completa para o seu negócio. Gere links personalizados, aceite pagamento em múltiplos cartões, configure cobranças recorrentes, crie orçamentos e venda diretamente pelo WhatsApp!'

      const buttonsContainer = document.createElement('div')
      buttonsContainer.className = 'promotional-card-buttons'

      const learnMoreButton = document.createElement('button')
      learnMoreButton.className = 'promotional-card-button learn-more'
      learnMoreButton.textContent = 'Saiba mais'
      learnMoreButton.addEventListener('click', function (e) {
        e.preventDefault()
        e.stopPropagation()
        window.open('https://br.wordpress.org/plugins/invoice-payment-for-woocommerce/', '_blank')
      })
      buttonsContainer.appendChild(learnMoreButton)

      if (!vars.invoice_plugin_installed) {
        const installButton = document.createElement('button')
        installButton.className = 'promotional-card-button install'
        installButton.textContent = 'Instalar'
        installButton.addEventListener('click', function (e) {
          e.preventDefault()
          e.stopPropagation()
          const installUrl = `/wp-admin/update.php?action=install-plugin&plugin=${vars.plugin_slug}&_wpnonce=${vars.install_nonce}`
          window.open(installUrl, '_blank')
        })
        buttonsContainer.appendChild(installButton)
      }

      cardContent.appendChild(cardTitle)
      cardContent.appendChild(titleDivider)
      cardContent.appendChild(cardDescription)
      cardContent.appendChild(buttonsContainer)
      promotionalCard.appendChild(cardContent)

      return promotionalCard
    }

    function createCards(targetDiv) {
      if (!targetDiv) return

      const featureMessage = createFeatureMessage(
        '✔️',
        'Google Pay e Tabela de Transações:',
        'Disponíveis exclusivamente para assinantes Pro e clientes de nossa hospedagem.'
      )
      targetDiv.append(featureMessage)
      targetDiv.append(createPromotionalCard())
    }

    observer = new MutationObserver(function () {
      const targetDiv = document.getElementById('lknBlocksSettingsLogo')
      if (targetDiv) {
        createCards(targetDiv)
        observer.disconnect() // Para o observer após encontrar o elemento
      }
    })

    // Inicia o observer no document.body
    observer.observe(document.body, { childList: true, subtree: true })
  }

  function lknCieloFindGetParameter(parameterName) {
    let result = null
    let tmp = []
    location.search
      .substr(1)
      .split('&')
      .forEach(function (item) {
        tmp = item.split('=')
        if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1])
      })
    return result
  }
})
