document.addEventListener('lknWcCieloFinishedAdminLayout', function () {
  // Pix description
  function disableFieldWithNotice(fieldId) {
    const field = document.getElementById(fieldId)
    if (!field) return
    
    field.disabled = true
    field.style.maxWidth = '400px'
    field.style.backgroundColor = '#f0f0f0'
    field.style.color = '#999'
    
    const parent = field.parentElement
    if (parent) {
      const notice = document.createElement('span')
      notice.textContent = lknCieloProSettingsVars.proOnly
      notice.style.color = 'red'
      notice.style.fontWeight = 'bold'
      notice.style.marginLeft = '10px'
      if(field.type == 'checkbox'){
        
        parent.parentElement.querySelectorAll('input').forEach(input => {
          input.parentElement.style.opacity = '0.5'
          input.disabled = true
        })
        notice.style.marginBottom = '20px'
        parent.parentElement.appendChild(notice)
      }else{
        parent.appendChild(notice)
      }
    }
  }
  
  // Lista de IDs dos campos a desabilitar
  const proOnlyFields = [
    'woocommerce_lkn_wc_cielo_pix_description',
    'woocommerce_lkn_wc_cielo_pix_payment_complete_status',
    'woocommerce_lkn_wc_cielo_pix_pix_layout',
    'woocommerce_lkn_wc_cielo_pix_layout_location',
    'woocommerce_lkn_wc_cielo_pix_show_button'
  ]

  // Com o PRO instalado e a licença ativa a description é editável: o PHP
  // persiste o valor do lojista e só restaura o estado original (frase padrão,
  // resolvida no idioma do site) quando a licença não está ativa.
  const proLicenseActive = !!(
    typeof lknWcCieloTranslationsInput !== 'undefined' &&
    lknWcCieloTranslationsInput.isProValid
  )
  const fieldsToDisable = proLicenseActive
    ? proOnlyFields.filter(fieldId => fieldId !== 'woocommerce_lkn_wc_cielo_pix_description')
    : proOnlyFields

  // Aplicar para todos
  setTimeout(function() {
    fieldsToDisable.forEach(disableFieldWithNotice)
  }, 1000)
  
})
