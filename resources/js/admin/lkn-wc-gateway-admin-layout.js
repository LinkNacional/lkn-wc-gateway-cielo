(function ($) {
  $(window).load(function () {
    // Selecionar os elementos
    let lknWcCieloCreditBlocksSettingsLayoutMenuVar = 1
    const mainForm = document.querySelector('#mainform')
    const fistH1 = mainForm.querySelector('h1')
    const submitP = mainForm.querySelector('p.submit')
    const tables = mainForm.querySelectorAll('table')

    if (mainForm && fistH1 && submitP && tables) {
      // Criar uma nova div
      const newDiv = document.createElement('div')
      newDiv.id = 'lknWcCieloCreditBlocksSettingsLayoutDiv'

      const parentFlexDiv = document.createElement('div')
      parentFlexDiv.id = 'lknWcCieloBlocksSettingsFlexContainer'
      parentFlexDiv.style.display = 'flex'
      parentFlexDiv.style.flexDirection = 'row' // opcional: padrão
      parentFlexDiv.style.gap = '1px'
      parentFlexDiv.style.flexWrap = 'wrap'
      parentFlexDiv.style.position = 'relative'
      parentFlexDiv.style.justifyContent = 'center'

      const logoDiv = document.createElement('div')
      logoDiv.id = 'lknBlocksSettingsLogo'
      logoDiv.style.minWidth = '30%'
      logoDiv.style.height = '100%'
      logoDiv.style.display = 'flex'
      logoDiv.style.flexDirection = 'column'
      logoDiv.style.justifyContent = 'start'
      logoDiv.style.alignItems = 'center'
      logoDiv.style.backgroundColor = 'transparent'
      logoDiv.style.borderRadius = '10px'
      logoDiv.style.padding = '30px 24px'
      logoDiv.style.position = 'sticky'
      logoDiv.style.top = '110px'

      // Acessar o próximo elemento após fistH1
      let currentElement = fistH1 // Começar com fistH1

      // Mover fistH1 e todos os elementos entre fistH1 e submitP para a nova div
      while (currentElement && currentElement !== submitP.nextElementSibling) {
        const nextElement = currentElement.nextElementSibling // Armazenar o próximo elemento antes de mover
        newDiv.appendChild(currentElement) // Mover o elemento atual para a nova div
        currentElement = nextElement // Atualizar currentElement para o próximo
      }

      // Mover submitP para a nova div
      newDiv.appendChild(submitP)

      // Mover a div existente para dentro da nova div pai
      parentFlexDiv.appendChild(newDiv)
      parentFlexDiv.appendChild(logoDiv)

      // Adicionar a nova estrutura flex ao formulário
      mainForm.appendChild(parentFlexDiv)

      const subTitles = mainForm.querySelectorAll('.wc-settings-sub-title')
      const descriptionElement = mainForm.querySelector('p')
      const divElement = document.createElement('div')
      if (subTitles && descriptionElement) {
        // Criar a div que irá conter os novos elementos <p>
        divElement.id = 'lknWcCieloCreditBlocksSettingsLayoutMenu'
        const aElements = []
        subTitles.forEach((subTitle, index) => {
          // Criar um novo elemento <a> e adicionar o elemento <p> a ele
          const aElement = document.createElement('a')
          aElement.textContent = subTitle.textContent
          aElement.href = '#' + subTitle.textContent
          aElement.className = 'nav-tab'
          aElement.onclick = (event) => {
            // Verificar se é a aba Transactions/Transações
            const tabText = subTitle.textContent.toLowerCase();
            if (tabText === 'transactions' || tabText === 'transações') {
              event.preventDefault();
              event.stopPropagation();
              
              // Usar URL do wp_localize_script
              const analyticsUrl = lknWcCieloTranslationsInput.analytics_url;

              // Abrir em nova aba
              window.open(analyticsUrl, '_blank');
              return false;
            }
            
            lknWcCieloCreditBlocksSettingsLayoutMenuVar = index + 1
            aElements.forEach((pElement, indexP) => {
              if (indexP == index) {
                aElements[index].className = 'nav-tab nav-tab-active'
              } else {
                aElements[indexP].className = 'nav-tab'
              }
            })
            changeLayout()
          }

          // Adicionar o novo elemento <a> à div
          divElement.appendChild(aElement)
          aElements.push(aElement)

          // Remover o subtítulo original
          subTitle.parentNode.removeChild(subTitle)
        })

        aElements[0].className = 'nav-tab nav-tab-active'

        // Inserir a div após o segundo ou primeiro <p>
        const pElements = mainForm.querySelectorAll('p:not([class])')
        const nodeArray = Array.from(pElements)
        const lastNode = nodeArray[nodeArray.length - 1]
        if (lastNode) {
          lastNode.parentNode.insertBefore(divElement, lastNode.nextSibling)
        }

        tables.forEach((table, index) => {
          if (index != 0 && index != 1) {
            table.style.display = 'none'
          }
          table.menuIndex = index
        })

        function changeLayout() {
          const current = lknWcCieloCreditBlocksSettingsLayoutMenuVar
          tables.forEach((table, index) => {
            // Seção 1 (Geral) compreende as tabelas 0 e 1; as demais seções são
            // 1:1 com a tabela de mesmo índice. Genérico para cobrir TODAS as abas
            // (inclusive a última, "Extras"), sem o limite fixo de cases.
            const show = (current === 1) ? (index === 0 || index === 1) : (index === current)
            table.style.display = show ? 'table' : 'none'
          })
        }

        const select = document.querySelector('select[name^="woocommerce_lkn_"][name$="_env"]')
        if (select) {
          const desc = document.createElement('p')
          desc.classList.add('description')
          desc.style.marginTop = '8px'
          select.parentNode.appendChild(desc)

          function updateDesc() {
            const val = select.value
            desc.textContent = lknWcCieloTranslations[val] || ''
          }

          select.addEventListener('change', updateDesc)
          updateDesc()
        }

        document.querySelectorAll('.form-table > tbody > tr').forEach(tr => {
          // As linhas de campos ocultos da seção "Fields" não passam pelo transform.
          if (tr.classList.contains('lkn-fields-hidden-row')) {
            return;
          }
          const label = tr.querySelector('th label')
          const helpTip = tr.querySelector('.woocommerce-help-tip')
          const forminp = tr.querySelector('.forminp')
          const legend = tr.querySelector('.forminp legend')
          const fieldset = tr.querySelector('.forminp fieldset')
          const titledesc = tr.querySelector('.titledesc')

          if (titledesc && label) {
            label.style.fontSize = '20px'
            label.style.color = '#121519'

            titledesc.style.verticalAlign = 'middle'
          }

          if (label && helpTip && titledesc) {
            const helpText = helpTip.getAttribute('aria-label')
            if (helpText) {
              const p = document.createElement('p')
              p.textContent = helpText
              p.style.margin = '5px 0 10px'
              p.style.fontSize = '13px'
              p.style.color = '#343B45'
              label.after(p)
            }

            helpTip.remove()
          }

          if (forminp && legend && label && fieldset) {
            fieldset.style.display = 'flex'
            fieldset.style.flexDirection = 'column'
            fieldset.style.width = '100%'
            fieldset.style.flex = '1'

            const titleText = label.textContent.trim()

            // Cria divs para header e body
            const headerDiv = document.createElement('div')
            headerDiv.className = 'lkn-header-cart'
            headerDiv.style.minHeight = '44px'

            const bodyDiv = document.createElement('div')
            bodyDiv.className = 'lkn-body-cart'
            bodyDiv.style.display = 'flex'
            bodyDiv.style.flexDirection = 'column'
            bodyDiv.style.alignItems = 'start'
            bodyDiv.style.justifyContent = 'center'
            bodyDiv.style.minHeight = '90px'
            bodyDiv.style.paddingLeft = '4px'
            bodyDiv.style.color = '#2C3338'

            // Cria título interno
            const titleInside = document.createElement('div')
            titleInside.textContent = titleText
            titleInside.style.fontWeight = 'bold'
            titleInside.style.fontSize = '16px'
            titleInside.style.margin = '6px 4px'

            // Cria descrição vazia
            const descBlock = document.createElement('div')
            descBlock.className = 'description-title'

            // Cria linha divisória
            const divider = document.createElement('div')
            divider.style.borderTop = '1px solid #ccc'
            divider.style.margin = '8px 0'
            divider.style.width = '100%'

            // Move o legend para o header
            headerDiv.appendChild(legend)
            headerDiv.appendChild(titleInside)
            headerDiv.appendChild(descBlock)
            headerDiv.appendChild(divider)

            // Move os demais elementos do fieldset para body (exceto o legend que já foi removido)
            const childrenToMove = []
            fieldset.childNodes.forEach(node => {
              if (node !== legend) {
                childrenToMove.push(node)
              }
            })

            childrenToMove.forEach(node => bodyDiv.appendChild(node))
            const brElement = bodyDiv.querySelector('br')
            if (brElement) {
              brElement.remove()
            }

            const pElement = bodyDiv.querySelector('p')
            if (!pElement) {
              const p = document.createElement('p')
              p.classList.add('description')
              p.style.marginTop = '8px'
              bodyDiv.appendChild(p)
            }

            const inputElement = bodyDiv.querySelector('input[type="text"], input[type="number"], input[type="password"], input[type="button"], input[type="checkbox"], select, textarea')
            if (inputElement) {
              // Só aplica estilos de largura se não for botão
              if (inputElement.type !== 'button' && inputElement.type !== 'checkbox') {
                inputElement.style.minWidth = '200px'
                inputElement.style.width = '100%'
                inputElement.style.maxWidth = '400px'
              }
              
              const titleText = inputElement.getAttribute('data-title-description')
              if (titleText) {
                descBlock.innerHTML = titleText
              }

              const numberLabel = inputElement.getAttribute('type-number-label') ? inputElement.getAttribute('type-number-label') : false;
              if (numberLabel) {
                  inputElement.style.marginRight = '10px'
                  inputElement.outerHTML = `<div style="display: flex;">${inputElement.outerHTML}<label style="color: #2C3338;">${numberLabel}</label></div>`;
              }

              const btnCopy = inputElement.getAttribute('btn-copy')
              if (btnCopy){

                const button = document.createElement('button')
                button.type = 'button'
                button.textContent = 'Copiar'
                button.classList.add('lkn-btn-copy-input')

                const copyContainer = document.createElement('div')
                copyContainer.classList.add('lkn-input-copy-container')

                const div = document.createElement('div');
                div.classList.add('lkn-input-btn-input-container');

                const label = document.createElement('label')
                label.classList.add('lkn-input-copy-label')
                label.textContent = btnCopy

                inputElement.style.flex = '1'

                bodyDiv.insertBefore(copyContainer, inputElement)

                copyContainer.appendChild(label)
                copyContainer.appendChild(div)
                div.append(inputElement)
                div.append(button)

                button.id = 'lkn-btn-copy-'+inputElement.id;
                
                button.addEventListener('click', () =>{
                  navigator.clipboard.writeText(inputElement.value)

                  button.textContent = 'Copiado!'
                  button.disabled = true;
                  button.style.cursor = 'default';
                  setTimeout(() => {
                    button.textContent = 'Copiar'
                    button.disabled = false;
                    button.style.cursor = 'pointer';
                  }, 2000);
                })

                if(inputElement.getAttribute('type') === 'password')
                {
                  button.style.display = 'none';
                }
              }

              // Lógica para merge-inputs (versão melhorada baseada em merge-top)
              const mergeInputs = inputElement.getAttribute('merge-inputs')
              if (mergeInputs) {
                const titleSetting = tr.querySelector('th label')
                const inputParent = document.querySelector('#' + mergeInputs)
                
                if (inputParent) {
                  const parentBodyCart = inputParent.closest('div.lkn-body-cart')
                  
                  if (parentBodyCart) {
                    // Procura ou cria o container de campos agrupados
                    let containerCampos = parentBodyCart.querySelector('.lkn-cielo-container-campos')
                    
                    if (!containerCampos) {
                      containerCampos = document.createElement('div')
                      containerCampos.classList.add('lkn-cielo-container-campos')
                      containerCampos.style.marginTop = '15px'
                      containerCampos.style.padding = '10px'
                      containerCampos.style.backgroundColor = '#f9f9f9'
                      containerCampos.style.border = '1px solid #e5e5e5'
                      containerCampos.style.borderRadius = '4px'
                      parentBodyCart.appendChild(containerCampos)
                    }
                    
                    // Cria o wrapper para o campo filho
                    const fieldWrapper = document.createElement('div')
                    fieldWrapper.classList.add('lkn-cielo-merged-field')
                    fieldWrapper.style.marginBottom = '10px'
                    fieldWrapper.style.paddingBottom = '10px'
                    fieldWrapper.style.borderBottom = '1px solid #e0e0e0'
                    
                    // Adiciona o título do campo
                    if (titleSetting) {
                      const fieldTitle = document.createElement('div')
                      fieldTitle.classList.add('lkn-cielo-merged-field-title')
                      fieldTitle.innerHTML = titleSetting.textContent
                      fieldTitle.style.fontWeight = 'bold'
                      fieldTitle.style.fontSize = '14px'
                      fieldTitle.style.color = '#2c3338'
                      fieldTitle.style.marginBottom = '8px'
                      fieldWrapper.appendChild(fieldTitle)
                    }
                    
                    // Move o conteúdo do campo
                    const copyContainer = inputElement.closest('.lkn-input-copy-container')
                    if (copyContainer) {
                      fieldWrapper.appendChild(copyContainer)
                    } else {
                      // Se não há copy container, move o input diretamente
                      const fieldContent = document.createElement('div')
                      fieldContent.appendChild(inputElement)
                      fieldWrapper.appendChild(fieldContent)
                    }
                    
                    containerCampos.appendChild(fieldWrapper)
                    tr.style.display = 'none'
                  }
                }
              }
              
              // Lógica para merge-top (nova implementação baseada no outro plugin)
              const mergeTop = inputElement.getAttribute('merge-top')
              
              if (mergeTop) {
                const parentInput = document.getElementById(mergeTop)
                
                if (parentInput) {
                  const parentBodyCart = parentInput.closest('div.lkn-body-cart')
                  const currentFieldset = tr.querySelector('fieldset')
                  
                  if (parentBodyCart && currentFieldset) {
                    // Aplica margin-left -4px para alinhar
                    currentFieldset.style.marginLeft = '-4px'
                    
                    // Move o fieldset para o parent
                    parentBodyCart.appendChild(currentFieldset)
                    
                    // Oculta a linha original
                    tr.style.display = 'none'
                  }
                }
              }

              const btnGenerateNew = inputElement.getAttribute('btn-generate-new')
              if (btnGenerateNew) {
                const mergeContainer = inputElement.closest('div.lkn-merge-inputs')
                if(mergeContainer){
                  const generateButton = document.createElement('button')
                  generateButton.type = 'button'
                  generateButton.textContent = btnGenerateNew
                  generateButton.classList.add('lkn-btn-generate-new')
                  mergeContainer.appendChild(generateButton)
                  let id = 'lkn-btn-generate-'+inputElement.id
                  generateButton.id = id
                }
              }
            }

            const checkboxInput = bodyDiv.querySelector('input[type="checkbox"]')
            if (checkboxInput) {
              const titleText = checkboxInput.getAttribute('data-title-description')
              if (titleText) {
                descBlock.innerHTML = titleText
              }
            }

            const checkboxLabel = bodyDiv.querySelector('label')

            if (checkboxLabel) {
              const input = checkboxLabel.querySelector('input')
              const nameAttr = input?.getAttribute('name')
              const checked = input?.checked

              // Oculta o checkbox original
              if (input) {
                input.style.display = 'none'
                checkboxLabel.style.display = 'none'

                // Cria os rádios
                const radioYes = document.createElement('label')
                radioYes.innerHTML = `
                <input type="radio" name="${nameAttr}-control" value="1" ${checked ? 'checked' : ''} ${checkboxInput.id === 'woocommerce_lkn_cielo_debit_fake_layout' || checkboxInput.id === 'woocommerce_lkn_cielo_credit_fake_layout' ? 'disabled' : ''}>
                  ${checkboxInput.id.includes('fake_layout') || checkboxInput.id.includes('checkout_layout') ? lknWcCieloTranslationsInput.modern : lknWcCieloTranslationsInput.enable}
                `

                const radioNo = document.createElement('label')
                radioNo.innerHTML = `
                <input type="radio" name="${nameAttr}-control" value="0" ${!checked ? 'checked' : ''} ${checkboxInput.id === 'woocommerce_lkn_cielo_debit_fake_layout' || checkboxInput.id === 'woocommerce_lkn_cielo_credit_fake_layout' ? 'disabled' : ''}>
                  ${checkboxInput.id.includes('fake_layout') || checkboxInput.id.includes('checkout_layout') ? lknWcCieloTranslationsInput.standard : lknWcCieloTranslationsInput.disable}
                `

                if (checkboxInput.id.includes('fake_layout') || checkboxInput.id.includes('checkout_layout')) {
                  radioYes.className = 'radio-tooltip'
                  radioYes.setAttribute('data-layout', 'modern')

                  radioNo.className = 'radio-tooltip'
                  radioNo.setAttribute('data-layout', 'standard')
                }

                const radioYesInput = radioYes.querySelector('input')
                const radioNoInput = radioNo.querySelector('input')

                // Vincula os eventos para controlar o checkbox oculto
                radioYesInput.addEventListener('change', () => {
                  if (radioYesInput.checked) input.checked = true
                })

                radioNoInput.addEventListener('change', () => {
                  if (radioNoInput.checked) input.checked = false
                })

                // Adiciona os radios
                bodyDiv.insertBefore(radioNo, bodyDiv.firstChild)
                bodyDiv.insertBefore(radioYes, bodyDiv.firstChild)

                // Preview do layout abaixo do campo (substitui o antigo tooltip de hover).
                const isLayoutField = checkboxInput.id.includes('fake_layout') || checkboxInput.id.includes('checkout_layout')
                if (isLayoutField && typeof lknWcCieloTranslationsInput !== 'undefined') {
                  const modernImg = lknWcCieloTranslationsInput.mordernVersion
                  const standardImg = lknWcCieloTranslationsInput.standardVersion
                  const compactImg = lknWcCieloTranslationsInput.compactVersion
                  const isProValid = !!lknWcCieloTranslationsInput.isProValid

                  const preview = document.createElement('div')

                  if (isProValid) {
                    // PRO ativo: preview único que segue a opção escolhida.
                    preview.className = 'lkn-cielo-layout-preview'
                    const link = document.createElement('a')
                    link.className = 'thickbox'
                    link.rel = 'lkn-cielo-layout-gallery'
                    link.style.display = 'block'
                    link.style.cursor = 'zoom-in'
                    const img = document.createElement('img')
                    img.style.cursor = 'zoom-in'
                    link.appendChild(img)
                    const updatePreview = () => {
                      const isModern = input.checked
                      const src = isModern ? modernImg : standardImg
                      const label = isModern ? lknWcCieloTranslationsInput.modern : lknWcCieloTranslationsInput.standard
                      img.src = src
                      img.alt = label
                      // Abre em lightbox (Thickbox do WordPress) para ver a imagem.
                      link.href = src
                      link.title = label
                    }
                    radioYesInput.addEventListener('change', updatePreview)
                    radioNoInput.addEventListener('change', updatePreview)
                    updatePreview()
                    preview.appendChild(link)
                  } else {
                    // Sem PRO: exibe os 3 layouts (Padrão, moderno e compacto)
                    // como demonstração, igual ao campo de layout do débito.
                    preview.className = 'lkn-cielo-layout-preview lkn-cielo-layout-preview--both'
                    const makeItem = (src, label) => {
                      if (!src) return null
                      const item = document.createElement('div')
                      item.className = 'lkn-cielo-layout-preview__item'
                      const link = document.createElement('a')
                      link.className = 'thickbox'
                      link.rel = 'lkn-cielo-layout-gallery'
                      link.href = src
                      link.title = label
                      link.style.display = 'block'
                      link.style.cursor = 'zoom-in'
                      const img = document.createElement('img')
                      img.src = src
                      img.alt = label
                      img.style.cursor = 'zoom-in'
                      link.appendChild(img)
                      const cap = document.createElement('p')
                      cap.className = 'lkn-cielo-layout-preview__cap'
                      cap.textContent = label
                      item.appendChild(link)
                      item.appendChild(cap)
                      return item
                    }
                    const addItem = (src, label) => {
                      const item = makeItem(src, label)
                      if (item) preview.appendChild(item)
                    }
                    addItem(standardImg, lknWcCieloTranslationsInput.standard)
                    addItem(modernImg, lknWcCieloTranslationsInput.modern)
                    addItem(compactImg, lknWcCieloTranslationsInput.compact || 'Compact')
                  }

                  bodyDiv.appendChild(preview)
                }
              }
            }

            // Preview do layout quando o campo é um <select> (gateway de débito).
            // O checkbox de layout do crédito continua usando o bloco acima.
            const layoutSelect = bodyDiv.querySelector('select[id*="checkout_layout"]')
            if (layoutSelect && typeof lknWcCieloTranslationsInput !== 'undefined') {
              // Tipo de checkout (Blocos/Gutenberg x Shortcode/Clássico): define
              // qual conjunto de imagens de layout é exibido.
              const layoutGatewayId = lknWcCieloTranslationsInput.gateway_id || 'lkn_cielo_debit'
              const modeSelect = document.getElementById('woocommerce_' + layoutGatewayId + '_checkout_type')
                || document.querySelector('select[id$="_checkout_type"]')
              const getLayoutMode = () => {
                const v = modeSelect ? String(modeSelect.value || '') : ''
                return v === 'classic' ? 'classic' : 'blocks'
              }
              // Fallback para os dados antigos (caso o localize não traga layoutVersions
              // — ex.: gateway de crédito, que mantém as imagens padrão).
              const legacyLayoutImages = {
                standard: lknWcCieloTranslationsInput.standardVersion,
                modern: lknWcCieloTranslationsInput.mordernVersion,
                compact: lknWcCieloTranslationsInput.compactVersion
              }
              const layoutImagesByMode = lknWcCieloTranslationsInput.layoutVersions || null
              const getLayoutImages = () => {
                if (layoutImagesByMode) {
                  return layoutImagesByMode[getLayoutMode()] || layoutImagesByMode.blocks || legacyLayoutImages
                }
                return legacyLayoutImages
              }
              const layoutLabels = {
                standard: lknWcCieloTranslationsInput.standard,
                modern: lknWcCieloTranslationsInput.modern,
                compact: lknWcCieloTranslationsInput.compact || 'Compact'
              }
              const layoutOrder = ['standard', 'modern', 'compact']
              const isLayoutProValid = !!lknWcCieloTranslationsInput.isProValid

              const layoutPreview = document.createElement('div')

              if (isLayoutProValid) {
                layoutPreview.className = 'lkn-cielo-layout-preview'
                const link = document.createElement('a')
                link.className = 'thickbox'
                link.rel = 'lkn-cielo-layout-gallery'
                link.style.display = 'block'
                link.style.cursor = 'zoom-in'
                const layoutImg = document.createElement('img')
                layoutImg.style.cursor = 'zoom-in'
                link.appendChild(layoutImg)
                const updateLayoutPreview = () => {
                  const val = layoutSelect.value
                  const imgs = getLayoutImages()
                  const src = imgs[val] || imgs.standard
                  layoutImg.src = src
                  layoutImg.alt = layoutLabels[val] || ''
                  // Abre em lightbox (Thickbox do WordPress) para ver a imagem.
                  link.href = src
                  link.title = layoutLabels[val] || ''
                }
                // O select é "enhanced select" (select2), que dispara 'change' via
                // jQuery — o addEventListener nativo nem sempre captura. Bind nos dois.
                layoutSelect.addEventListener('change', updateLayoutPreview)
                if (window.jQuery) {
                  window.jQuery(layoutSelect).on('change select2:select', updateLayoutPreview)
                }
                // Trocar o tipo de checkout (Block x Shortcode/Clássico) também
                // atualiza a imagem exibida.
                if (modeSelect) {
                  modeSelect.addEventListener('change', updateLayoutPreview)
                  if (window.jQuery) {
                    window.jQuery(modeSelect).on('change select2:select', updateLayoutPreview)
                  }
                }
                updateLayoutPreview()
                layoutPreview.appendChild(link)
              } else {
                layoutPreview.className = 'lkn-cielo-layout-preview lkn-cielo-layout-preview--both'
                const renderLayoutItems = () => {
                  layoutPreview.innerHTML = ''
                  const imgs = getLayoutImages()
                  layoutOrder.forEach((key) => {
                    const src = imgs[key]
                    if (!src) return
                    const item = document.createElement('div')
                    item.className = 'lkn-cielo-layout-preview__item'
                    const link = document.createElement('a')
                    link.className = 'thickbox'
                    link.rel = 'lkn-cielo-layout-gallery'
                    link.href = src
                    link.title = layoutLabels[key]
                    link.style.display = 'block'
                    link.style.cursor = 'zoom-in'
                    const img = document.createElement('img')
                    img.src = src
                    img.alt = layoutLabels[key]
                    img.style.cursor = 'zoom-in'
                    link.appendChild(img)
                    const cap = document.createElement('p')
                    cap.className = 'lkn-cielo-layout-preview__cap'
                    cap.textContent = layoutLabels[key]
                    item.appendChild(link)
                    item.appendChild(cap)
                    layoutPreview.appendChild(item)
                  })
                }
                if (modeSelect) {
                  modeSelect.addEventListener('change', renderLayoutItems)
                  if (window.jQuery) {
                    window.jQuery(modeSelect).on('change select2:select', renderLayoutItems)
                  }
                }
                renderLayoutItems()
              }

              bodyDiv.appendChild(layoutPreview)
            }

            if(checkboxInput) {
            const mergeCheckbox = checkboxInput.getAttribute('merge-checkbox') ? checkboxInput.getAttribute('merge-checkbox') : false;
              if (mergeCheckbox) {
                const parentInput = document.getElementById(mergeCheckbox).closest('div.lkn-body-cart');
                if (parentInput) {
                    checkboxInput.style.display = 'block';
                    checkboxInput.style.margin = '0px';
                    checkboxInput.style.marginRight = '5px';
                    const labelCheckbox = checkboxInput.closest('label');
                    labelCheckbox.style.display = 'flex';
                    tr.style.display = 'none';
                    parentInput.appendChild(labelCheckbox);
                }
              }
            }

            // Campos PRO: marcados com lkn-is-pro="true" (travados) ou
            // lkn-pro-badge="true" (selo "PRO", porém editável — usado nos campos fake
            // do plano gratuito). Ambos recebem o link/selo "PRO" no título.
            const proLockedInput = (inputElement && inputElement.getAttribute('lkn-is-pro') === 'true')
              || (checkboxInput && checkboxInput.getAttribute('lkn-is-pro') === 'true')

            const proBadgeInput = (inputElement && inputElement.getAttribute('lkn-pro-badge') === 'true')
              || (checkboxInput && checkboxInput.getAttribute('lkn-pro-badge') === 'true')
              || !!bodyDiv.querySelector('[lkn-pro-badge="true"]')

            if (proLockedInput || proBadgeInput) {
              headerDiv.style.position = 'relative'

              const proLink = document.createElement('a')
              proLink.className = 'lkn-cielo-become-pro'
              proLink.href = 'https://www.linknacional.com.br/wordpress/woocommerce/cielo/'
              proLink.target = '_blank'
              proLink.rel = 'noopener noreferrer'
              proLink.textContent = (typeof lknWcCieloTranslationsInput !== 'undefined' && lknWcCieloTranslationsInput.becomePRO)
                ? lknWcCieloTranslationsInput.becomePRO
                : 'PRO'
              titleInside.appendChild(proLink)

              // Só bloqueia de fato os controles dos campos exclusivos do PRO (lkn-is-pro).
              // Os campos com lkn-pro-badge permanecem editáveis (são fakes/simulação).
              if (proLockedInput) {
                bodyDiv.querySelectorAll('input, select, textarea').forEach(el => {
                  el.disabled = true
                })
              }
            }

            // Limpa o fieldset e insere os novos containers
            fieldset.innerHTML = ''
            fieldset.appendChild(headerDiv)
            fieldset.appendChild(bodyDiv)

            // Estiliza o forminp
            forminp.style.display = 'flex'
            forminp.style.flexDirection = 'column'
            forminp.style.alignItems = 'flex-start'
            forminp.style.backgroundColor = 'white'
            forminp.style.padding = '10px 30px'
            forminp.style.borderRadius = '4px'
            forminp.style.boxSizing = 'border-box'
            forminp.style.border = '1px solid #DFDFDF'
            forminp.style.width = '100%'
          }
        })
        const installmentSelect = document.querySelector('select[id$="interest_or_discount"]')
        let installmentValue = 'interest'
        let installInput = ''
        if (installmentSelect) {
          installmentValue = installmentSelect.value
        }

        if (installmentValue === 'interest') {
          installInput = document.querySelector('input[id$="installment_interest"]')
        } else if (installmentValue === 'discount') {
          installInput = document.querySelector('input[id$="installment_discount"]')
        }

        if (installInput && installInput.checked) {
          const discountBlocks = document.querySelectorAll(`input[name^="woocommerce_lkn_cielo_"][name$="${installmentValue}"]`)

          const lknBody = installInput.closest('.lkn-body-cart')
          if (lknBody) {
            const pInstallments = lknBody.querySelector('.description')

            if (pInstallments) {
              pInstallments.style.marginBottom = '40px'
            }
          }

          if (discountBlocks.length > 0) {
            discountBlocks.forEach(discountBlock => {
              const trComponent = discountBlock.closest('tr')
              if (!trComponent) return

              const fieldset = trComponent.querySelector('fieldset')
              if (!fieldset) return

              const lknBody = installInput.closest('.lkn-body-cart')
              if (!lknBody) return

              // Evita o erro se fieldset contém o lknBody (estrutura cíclica)
              if (!fieldset.contains(lknBody)) {
                fieldset.style.marginLeft = '-4px'
                lknBody.appendChild(fieldset)
                trComponent.remove()
              }
            })
          }
        }

        const observer = new MutationObserver(function () {
          const selects = document.querySelectorAll('.select2.select2-container')
          if (selects.length > 0) {
            selects.forEach(select => {
              select.style.setProperty('min-width', '200px', 'important')
              select.style.width = '100%'
              select.style.maxWidth = '400px'
            })

            // Aqui você pode executar qualquer lógica necessária com o elemento encontrado

            // Para o observer
            observer.disconnect()
          }
        })

        // Configura o observer para observar mudanças no document.body
        observer.observe(document.body, { childList: true, subtree: true })

        // Caso o formulário tenha um campo inválido, força o click no menu em que o campo inválido está
        mainForm.addEventListener('invalid', function (event) {
          const invalidField = event.target
          if (invalidField) {
            // Remove a classe is-busy do botão de salvar quando há erro de validação
            const saveButton = document.querySelector('button[name="save"]')
            if (saveButton) {
              saveButton.classList.remove('is-busy')
            }
            
            let parentNode = invalidField.parentNode
            while (parentNode && parentNode.tagName !== 'TABLE') {
              parentNode = parentNode.parentNode
            }
            if (parentNode) {
              // Força o click no menu em que o campo inválido está
              aElements[parentNode.menuIndex - 1].click()
            }
          }
        }, true)

        const urlHash = window.location.hash
        if (urlHash) {
          const targetElement = aElements.find(a => a.href.endsWith(urlHash))
          if (targetElement) {
            targetElement.click()
          }
        }
      }

      const hrElement = document.createElement('hr')
      hrElement.style.margin = '2px 0 40px'
      hrElement.style.width = '100%'
      hrElement.classList.add('lkn-wc-cielo-hr')
      divElement.parentElement.insertBefore(hrElement, divElement.nextSibling)
      lknWcCieloValidateMerchantInputs()
    }

    // 1. Seletores
    const debugOn = document.querySelector('input[name$="_debug-control"][value="1"]');
    const debugOff = document.querySelector('input[name$="_debug-control"][value="0"]');
    const logsOff = document.querySelector('input[name$="_show_order_logs-control"][value="0"]');
    const logsRow = document.querySelector('input[name$="_show_order_logs-control"]')?.closest('tr');
    const sendConfigsInput = document.querySelector('input[id^="woocommerce_lkn_"][id$="_send_configs"]');

    // Licença PRO ativa? Define se o botão de suporte WhatsApp é funcional (verde)
    // ou apenas decorativo (cinza) no plano gratuito.
    const lknProLicenseActive = !!(typeof lknWcCieloTranslationsInput !== 'undefined' && lknWcCieloTranslationsInput.isProValid);

    // Seletores do PRO
    const proOn = document.querySelector('input[name$="_debug_pro-control"][value="1"]');
    const proOff = document.querySelector('input[name$="_debug_pro-control"][value="0"]');

    // 2. Verifica os obrigatórios
    if (debugOn && debugOff && logsRow && logsOff && sendConfigsInput) {

      // --- PARTE A: Lógica do Botão WPP (Roda APENAS 1 vez ao iniciar) ---
      const initWppState = () => {
        // Verifica o estado inicial (como veio do banco de dados)
        const isDebugActive = debugOn.checked;
        const isProActive = lknProLicenseActive && (proOn ? proOn.checked : true);

        // Se estiver tudo Ativo no carregamento, habilita. Senão, bloqueia.
        if (isDebugActive && isProActive) {
          sendConfigsInput.disabled = false;
          sendConfigsInput.classList.remove('wpp-disabled');
        } else {
          sendConfigsInput.disabled = true;
          sendConfigsInput.classList.add('wpp-disabled');
          // Opcional: Adicionar tooltip explicando
          sendConfigsInput.title = "Para habilitar esta opção habilite o modo de depuração e salve.";
        }
      };

      // --- PARTE B: Lógica de UI (Logs e Radios) que reage ao clique ---
      const toggleUi = () => {
        const isDebugActive = debugOn.checked;

        if (isDebugActive) {
          // --- DEBUG LIGADO ---
          logsRow.style.display = ''; // Mostra logs

          // Libera os botões do PRO para edição
          if (proOn && proOff) {
            proOn.disabled = false;
            proOff.disabled = false;
          }
          
          // OBS: NÃO mexemos no sendConfigsInput aqui!

        } else {
          // --- DEBUG DESLIGADO ---
          logsRow.style.display = 'none';
          logsOff.click(); // Força Logs OFF

          // Bloqueia e desativa PRO
          if (proOn && proOff) {
            proOff.click();        
            proOn.disabled = true; 
            proOff.disabled = true;
          }
        }
      };

      // 3. Listeners (Apenas para a UI, não afeta mais o botão WPP)
      debugOn.addEventListener('change', toggleUi);
      debugOff.addEventListener('change', toggleUi);
      
      if (proOn && proOff) {
        proOn.addEventListener('change', toggleUi);
        proOff.addEventListener('change', toggleUi);
      }

      // 4. Execução Inicial
      initWppState(); // Define o botão WPP uma única vez
      toggleUi();     // Ajusta a visualização dos logs/radios uma vez
    }

    // Lógica para customizar o botão de suporte WhatsApp
    if (sendConfigsInput) {
      // Extrai o nome do gateway do id
      const idMatch = sendConfigsInput.id.match(/^woocommerce_lkn_(.+)_send_configs$/);
      let gatewayName = '';
      if (idMatch && idMatch[1]) {
        gatewayName = idMatch[1].replace(/_/g, ' ');
        gatewayName = gatewayName.charAt(0).toUpperCase() + gatewayName.slice(1);
      }

      // Define o label do botão
      const supportLabel = lknWcCieloTranslations && lknWcCieloTranslations.sendConfigs ? lknWcCieloTranslations.sendConfigs : 'Suporte';
      sendConfigsInput.value = `${supportLabel}`.trim();

      // Plano gratuito: botão apenas decorativo (cinza, sem ação).
      if (!lknProLicenseActive) {
        sendConfigsInput.type = 'button';
        sendConfigsInput.disabled = true;
        sendConfigsInput.classList.add('wpp-disabled');
        sendConfigsInput.removeAttribute('onclick');
        sendConfigsInput.style.width = 'fit-content';
        sendConfigsInput.style.setProperty('padding', '10px 18px 10px 32px', 'important');
        sendConfigsInput.style.background = 'url("https://cdn.simpleicons.org/whatsapp/999") no-repeat 8px center/18px, #f0f0f1';
        sendConfigsInput.style.color = '#a7aaad';
        sendConfigsInput.style.fill = '#a7aaad';
        sendConfigsInput.style.border = '1px solid #dcdcde';
        sendConfigsInput.style.borderRadius = '2px';
        sendConfigsInput.style.fontWeight = 'bold';
        sendConfigsInput.style.cursor = 'not-allowed';
        sendConfigsInput.style.outline = 'none';
        sendConfigsInput.onmouseover = null;
        sendConfigsInput.onmouseout = null;
        sendConfigsInput.title = (typeof lknWcCieloTranslations !== 'undefined' && lknWcCieloTranslations.sendConfigsPro)
          ? lknWcCieloTranslations.sendConfigsPro
          : 'Available only in the PRO plan.';
      } else {

      // Adiciona o ícone do WhatsApp antes do texto
      sendConfigsInput.style.width = 'fit-content';
      sendConfigsInput.style.setProperty('padding-top', '10px', 'important');
      sendConfigsInput.style.setProperty('padding-bottom', '10px', 'important');
      sendConfigsInput.style.setProperty('padding-left', '32px', 'important');
      sendConfigsInput.style.setProperty('padding-right', '18px', 'important');
      sendConfigsInput.style.background = 'url("https://cdn.simpleicons.org/whatsapp/white") no-repeat 8px center/18px, #25d366';
      sendConfigsInput.style.color = '#fff';
      sendConfigsInput.style.fill = '#fff';
      sendConfigsInput.style.border = 'none';
      sendConfigsInput.style.borderRadius = '2px';
      sendConfigsInput.style.fontWeight = 'bold';
      sendConfigsInput.style.cursor = 'pointer';
      sendConfigsInput.style.outline = '#25d366';
      sendConfigsInput.style.transition = 'background 0.2s';
      sendConfigsInput.onmouseover = function() {
        this.style.backgroundColor = '#128c7e';
      };
      sendConfigsInput.onmouseout = function() {
        this.style.backgroundColor = '#25d366';
      };
      sendConfigsInput.style.backgroundColor = '#25d366';

      // Altera o tipo para button (opcional, se não for submit)
      sendConfigsInput.type = 'button';

      // Adiciona ação para abrir WhatsApp com mensagem formatada
      const whatsappNumber = lknWcCieloTranslationsInput && lknWcCieloTranslationsInput.whatsapp_number ? lknWcCieloTranslationsInput.whatsapp_number : '55999999999';
      const gatewayId = lknWcCieloTranslationsInput && lknWcCieloTranslationsInput.gateway_id ? lknWcCieloTranslationsInput.gateway_id : 'unknown_gateway';
      const siteDomain = lknWcCieloTranslationsInput && lknWcCieloTranslationsInput.site_domain ? lknWcCieloTranslationsInput.site_domain : window.location.hostname;
      sendConfigsInput.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Remove classes de animação imediatamente após o clique
        this.classList.remove('is-busy', 'components-button__busy-animation', 'animation');
        const settings = lknWcCieloTranslationsInput.gateway_settings || {};
        let message = '#suporte-info Olá! Preciso de suporte com meu gateway de pagamento Cielo. Estou com problemas na transação e segue os dados para verificação:';
        message += ` Gateway: ${gatewayId} | Site: ${siteDomain} | Plugin: lkn-wc-gateway-cielo v${lknWcCieloTranslationsInput.version_free} | Plugin dependente: ${lknWcCieloTranslationsInput.version_pro && lknWcCieloTranslationsInput.version_pro !== 'N/A' ? 'lkn-wc-gateway-cielo-pro v' + lknWcCieloTranslationsInput.version_pro : 'N/A'} | `;

        const sensitiveKeys = ['merchant_id', 'merchant_key', 'license', 'card_token'];

        Object.keys(settings).forEach(function(key) {
          if (key === 'general') return; // Ignora 'general'
          if (key === 'validate_license') return; // Ignora 'validate_license'
          if (key === 'pro') return; // Ignora 'pro'
          if (key === 'fake_license_field') return; // Ignora 'fake_license_field'
          if (key === 'fake_cardholder_field') return; // Ignora 'fake_cardholder_field'
          if (key === 'fake_layout') return; // Ignora 'fake_layout'
          if (key === 'fake_and_more_field') return; // Ignora 'fake_and_more_field',
          if (key === 'transactions') return; // Ignora 'transactions'

          let value = settings[key];

          // 1. Normalização de valores vazios/nulos
          if (value === undefined || value === null || value === '') {
            value = 'null';
          }

          // 2. Lógica de Censura Dinâmica
          if (sensitiveKeys.includes(key) && value !== 'null') {
            const strValue = String(value);
            const len = strValue.length;

            // Regra: Mostra no máximo 4, mas nunca mais que 1/3 da string para garantir segurança em strings curtas
            // Ex: Se tem 32 chars, mostra 4. Se tem 4 chars, mostra 1. Se tem 2, mostra 0.
            const keep = Math.min(4, Math.floor(len / 3)); 
            
            const start = strValue.slice(0, keep);
            const end = strValue.slice(-keep);
            // Se keep for 0, o slice(-0) pega tudo, então tratamos isso:
            const safeEnd = keep > 0 ? strValue.slice(-keep) : '';
            
            // O meio é preenchido com asteriscos fixos (***) ou baseados no tamanho real
            const middle = '*'.repeat(Math.max(1, len - (keep * 2)));

            value = `${start}${middle}${safeEnd}`;
          }

          message += ` ${key}: ${value} |`;
        });
        message += ' Aguardo retorno, obrigado!';
        window.open(`https://api.whatsapp.com/send/?phone=${whatsappNumber}&text=${encodeURIComponent(message)}`,'_blank');
      };
      }
    }

    const message = $('<p id="footer-left-lkn" class="alignleft"></p>')

    message.html('Saiba mais sobre nossos plugins, suporte e manutenção 24h para WordPress na <a href="https://www.linknacional.com.br/wordpress/plugins/" target="_blank">Link Nacional</a> | Avaliar esse plugin <a href="https://wordpress.org/support/plugin/lkn-wc-gateway-cielo/reviews/?filter=5#postform" target="_blank" class="give-rating-link" style="text-decoration:none;" data-rated="Obrigado :)">★★★★★</a>')

    message.css({
      'text-align': 'center',
      padding: '10px 0px',
      'font-size': '13px',
      color: '#666'
    })

    $('#lknWcCieloCreditBlocksSettingsLayoutDiv').append(message).css('display', 'table')

    // Aviso sobre juros na parcela 1x (aplicado após a geração do layout)
    lknWcCieloAddOnexInterestWarning()

    document.dispatchEvent(new Event('lknWcCieloFinishedAdminLayout'))

    // === Condição: "esconder seletor de tipo de cartão" (debit) ===
    // A opção só pode ser ativada quando o Modo Tipo de Cartão é de um único tipo
    // (only_credit/only_debit). Em "both" ela fica "travada" — aparência de disabled
    // (opacidade + pointer-events), SEM o atributo disabled, para o valor continuar
    // sendo enviado no submit (que só vale quando a opção realmente libera o recurso).
    ;(function () {
      const modeField = document.getElementById('woocommerce_lkn_cielo_debit_card_type_mode')
      const hideField = document.getElementById('woocommerce_lkn_cielo_debit_hide_card_type_selector')
      if (!modeField || !hideField) return

      const container = hideField.closest('.lkn-body-cart') || hideField.closest('fieldset')
      if (!container || container.getAttribute('data-lkn-hide-condition-init') === 'true') return
      container.setAttribute('data-lkn-hide-condition-init', 'true')

      const controls = container.querySelectorAll('input, select')

      const applyAvailability = () => {
        // Se o campo foi desabilitado pelo PRO (lkn-is-pro sem licença), não mexemos.
        if (hideField.hasAttribute('disabled')) return
        // Só libera quando o modo é EXPLICITAMENTE de um único tipo. Qualquer outro
        // valor (inclusive vazio/transitório do select2) mantém a opção travada.
        const singleType = modeField.value === 'only_credit' || modeField.value === 'only_debit'
        const fakeDisabled = !singleType
        container.style.opacity = fakeDisabled ? '0.5' : ''
        container.style.transition = 'opacity 0.2s'
        controls.forEach(el => {
          if (fakeDisabled) {
            el.setAttribute('data-lkn-fake-disabled', 'true')
            el.style.pointerEvents = 'none'
            el.style.cursor = 'not-allowed'
          } else {
            el.removeAttribute('data-lkn-fake-disabled')
            el.style.pointerEvents = ''
            el.style.cursor = ''
          }
        })

        // Quando travada, posiciona a opção em "Desativar" (valor 0) e desmarca
        // "Ativar", para o estado visual refletir que o recurso está desligado.
        if (fakeDisabled) {
          container.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.checked = radio.value === '0'
          })
          hideField.checked = false
        }
      }

      // Bloqueia a ativação (mouse/teclado) quando "fake disabled".
      const blockFakeDisabled = (event) => {
        if (hideField.getAttribute('data-lkn-fake-disabled') !== 'true') return
        if (event.type === 'keydown' && event.key !== ' ' && event.key !== 'Enter' && event.key !== 'Spacebar') return
        event.preventDefault()
        event.stopPropagation()
      }
      container.addEventListener('click', blockFakeDisabled, true)
      container.addEventListener('keydown', blockFakeDisabled, true)

      // O select2 dispara 'change' no <select> original e também 'select2:select';
      // cobrimos os dois + um observer para garantir o toggle em qualquer caminho.
      const onModeChange = () => applyAvailability()
      modeField.addEventListener('change', onModeChange)
      if (window.jQuery) {
        window.jQuery(modeField).on('change select2:select select2:unselect', onModeChange)
      }
      try {
        new MutationObserver(onModeChange).observe(modeField, { attributes: true, attributeFilter: ['value'] })
      } catch (e) { /* noop */ }

      applyAvailability()
      setTimeout(applyAvailability, 300)
    })()

    // === BIN: dependência dos campos de whitelist + select2 com opção custom ===
    ;(function () {
      const cb = document.getElementById('woocommerce_lkn_cielo_debit_brand_validation')
      if (!cb) return

      const fieldIds = [
        'woocommerce_lkn_cielo_debit_bin_allowed_brands',
        'woocommerce_lkn_cielo_debit_bin_allowed_card_types',
        'woocommerce_lkn_cielo_debit_bin_allowed_nationality',
        'woocommerce_lkn_cielo_debit_bin_allowed_corporate',
        'woocommerce_lkn_cielo_debit_bin_allowed_prepaid'
      ]

      const wrappers = fieldIds
        .map(id => {
          const el = document.getElementById(id)
          return el ? el.closest('fieldset') : null
        })
        .filter(Boolean)

      const applyDependency = () => {
        const on = cb.checked
        wrappers.forEach(w => {
          w.style.display = on ? '' : 'none'
        })
      }

      // A checkbox vira radios "-control"; reflete o estado no rádio e reaplica.
      document
        .querySelectorAll('input[name="woocommerce_lkn_cielo_debit_brand_validation-control"]')
        .forEach(radio => radio.addEventListener('change', applyDependency))
      cb.addEventListener('change', applyDependency)
      applyDependency()

      // select2 com tags:true para permitir valores customizados além dos predefinidos.
      if (window.jQuery && jQuery.fn.select2) {
        jQuery('.lkn-bin-tags-select').each(function () {
          const $el = jQuery(this)
          try {
            if ($el.data('select2')) {
              $el.select2('destroy')
            }
            $el.select2({
              tags: true,
              tokenSeparators: [',', ';'],
              width: '100%',
              placeholder: ''
            })
          } catch (e) {
            /* mantém o select nativo em caso de erro */
          }
        })
      }
    })()

    // === BIN: teste da consulta ao ATIVAR (confirma se o recurso está ativo na Cielo) ===
    // Ao clicar em "Ativar" o recurso de validação de BIN, abre um modal pedindo
    // um número de cartão para um teste rápido. Só habilita de fato se a consulta
    // responder — evita salvar o recurso ligado e quebrado. Depois do alerta de
    // resultado, mostra um indicador inline (✓ verde / ✗ vermelho + balão ~5s).
    ;(function () {
      if (typeof lknWcCieloTranslationsInput === 'undefined') return
      const cfg = lknWcCieloTranslationsInput.lknBinTest
      if (!cfg || !cfg.i18n) return

      // Campo "brand_validation" real (o "fake" de demonstração é ignorado).
      const prefix = 'woocommerce_lkn_cielo_' + cfg.gateway + '_brand_validation'
      const cb = document.getElementById(prefix)
      if (!cb) return

      const base = prefix + '-control'
      const enableRadio = document.querySelector('input[name="' + base + '"][value="1"]')
      const disableRadio = document.querySelector('input[name="' + base + '"][value="0"]')
      if (!enableRadio || !disableRadio) return

      const fieldset = cb.closest('fieldset')
      const t = cfg.i18n
      let programmatic = false

      // Máscara do BIN: só dígitos, no máximo 6, no formato "0000 00".
      function formatBin(raw) {
        const d = String(raw || '').replace(/\D/g, '').slice(0, 6)
        return d.length <= 4 ? d : d.slice(0, 4) + ' ' + d.slice(4)
      }

      // --- Indicador inline (bolinha ✓/✗ + balão) ao lado do título ----------
      // openBalloon: abre o balão por ~5s; no carregamento (F5) o indicador aparece
      // já parado (só a bolinha), reabrindo o balão no hover.
      function showStatus(success, openBalloon) {
        const header = fieldset ? fieldset.querySelector('.lkn-header-cart') : null
        const titleEl = header ? header.querySelector('div') : null
        if (!titleEl) return

        const previous = titleEl.querySelector('.lkn-cielo-bin-status')
        if (previous) previous.remove()

        const wrap = document.createElement('span')
        wrap.className = 'lkn-cielo-bin-status lkn-cielo-info-icon'
        wrap.style.color = success ? '#008a20' : '#d63638'

        const icon = document.createElement('span')
        icon.className = 'dashicons ' + (success ? 'dashicons-yes-alt' : 'dashicons-dismiss')
        icon.style.width = '18px'
        icon.style.height = '18px'
        icon.style.fontSize = '18px'

        const balloon = document.createElement('span')
        balloon.className = 'lkn-cielo-info-tooltip'
        balloon.setAttribute('role', 'tooltip')
        balloon.textContent = success ? t.statusActive : t.statusFailed

        wrap.appendChild(icon)
        wrap.appendChild(balloon)
        titleEl.appendChild(wrap)

        // O balão aparece por ~5s e depois se recolhe, mas o INDICADOR (✓/✗)
        // permanece no lugar indefinidamente (reabre o balão no hover).
        if (openBalloon !== false) {
          wrap.classList.add('is-open')
          setTimeout(function () {
            wrap.classList.remove('is-open')
          }, 5000)
        }
      }

      // --- Modal genérico ----------------------------------------------------
      let overlay = null
      function closeOverlay() {
        if (overlay) {
          overlay.remove()
          overlay = null
        }
      }
      function buildOverlay() {
        closeOverlay()
        overlay = document.createElement('div')
        overlay.className = 'lkn-cielo-modal-overlay'
        overlay.addEventListener('click', function (e) {
          if (e.target === overlay) closeOverlay()
        })
        const box = document.createElement('div')
        box.className = 'lkn-cielo-modal'
        overlay.appendChild(box)
        document.body.appendChild(overlay)
        return box
      }

      // --- Alerta de resultado (com link, quando falha) ----------------------
      function showResultAlert(success, message) {
        const box = buildOverlay()

        const title = document.createElement('h3')
        title.className = 'lkn-cielo-modal__title'
        title.style.color = success ? '#008a20' : '#d63638'
        title.textContent = success ? t.successTitle : t.errorTitle
        box.appendChild(title)

        if (message) {
          const text = document.createElement('p')
          text.className = 'lkn-cielo-modal__text'
          text.textContent = message
          box.appendChild(text)
        }

        if (!success) {
          const hint = document.createElement('p')
          hint.className = 'lkn-cielo-modal__text'
          hint.textContent = t.configHint
          box.appendChild(hint)

          const link = document.createElement('a')
          link.className = 'lkn-cielo-modal__link'
          link.href = cfg.cieloUrl
          link.target = '_blank'
          link.rel = 'noopener noreferrer'
          link.textContent = t.configLink
          box.appendChild(link)
        }

        const actions = document.createElement('div')
        actions.className = 'lkn-cielo-modal__actions'

        const ok = document.createElement('button')
        ok.type = 'button'
        ok.className = 'button button-primary'
        ok.textContent = t.close
        ok.addEventListener('click', function () {
          closeOverlay()
          showStatus(success)
        })
        actions.appendChild(ok)
        box.appendChild(actions)
      }

      // --- Modal do teste ----------------------------------------------------
      function openTestModal() {
        const box = buildOverlay()

        const title = document.createElement('h3')
        title.className = 'lkn-cielo-modal__title'
        title.textContent = t.modalTitle
        box.appendChild(title)

        const intro = document.createElement('p')
        intro.className = 'lkn-cielo-modal__text'
        intro.textContent = t.modalIntro
        box.appendChild(intro)

        const label = document.createElement('label')
        label.className = 'lkn-cielo-modal__label'
        label.textContent = t.digitsLabel
        box.appendChild(label)

        const input = document.createElement('input')
        input.type = 'text'
        input.className = 'lkn-cielo-modal__input'
        input.placeholder = t.digitsPh
        input.setAttribute('maxlength', '7')
        input.setAttribute('inputmode', 'numeric')
        input.setAttribute('autocomplete', 'off')
        box.appendChild(input)

        // Dica de sandbox (apenas no ambiente de teste) com cartões de teste.
        if (cfg.isSandbox && Array.isArray(cfg.sandboxCards) && cfg.sandboxCards.length) {
          const hint = document.createElement('p')
          hint.className = 'lkn-cielo-modal__text lkn-cielo-modal__sandbox'
          hint.textContent = t.sandboxHint
          box.appendChild(hint)

          const list = document.createElement('ul')
          list.className = 'lkn-cielo-modal__cards'
          cfg.sandboxCards.forEach(function (card) {
            const li = document.createElement('li')
            li.textContent = card.brand + ': ' + card.number
            li.title = card.number
            li.addEventListener('click', function () {
              input.value = formatBin(card.number)
              input.classList.remove('lkn-cielo-modal__input--error')
              input.focus()
            })
            list.appendChild(li)
          })
          box.appendChild(list)
        }

        const actions = document.createElement('div')
        actions.className = 'lkn-cielo-modal__actions'

        const cancel = document.createElement('button')
        cancel.type = 'button'
        cancel.className = 'button'
        cancel.textContent = t.cancel
        cancel.addEventListener('click', closeOverlay)

        const test = document.createElement('button')
        test.type = 'button'
        test.className = 'button button-primary'
        test.textContent = t.test
        test.addEventListener('click', function () {
          const digits = (input.value || '').replace(/\D/g, '')
          if (digits.length < 6) {
            input.classList.add('lkn-cielo-modal__input--error')
            input.focus()
            return
          }

          test.disabled = true
          cancel.disabled = true
          test.textContent = t.testing

          const body = new URLSearchParams()
          body.append('action', 'lkn_cielo_test_bin')
          body.append('nonce', cfg.nonce)
          body.append('gateway', cfg.gateway)
          body.append('digits', digits)

          fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
          })
            .then(function (response) { return response.json() })
            .then(function (res) {
              const success = !!(res && res.success)
              const message = (res && res.data && res.data.message) ? res.data.message : ''
              closeOverlay()
              showResultAlert(success, message)

              if (success) {
                // Habilita de fato (sem reabrir o modal).
                programmatic = true
                enableRadio.checked = true
                cb.checked = true
                enableRadio.dispatchEvent(new Event('change', { bubbles: true }))
                programmatic = false
              }
              // Falha: permanece desabilitado (já revertido ao abrir o modal).
            })
            .catch(function () {
              closeOverlay()
              showResultAlert(false, '')
            })
        })

        actions.appendChild(cancel)
        actions.appendChild(test)
        box.appendChild(actions)

        input.addEventListener('input', function () {
          const atEnd = input.selectionStart === input.value.length
          const formatted = formatBin(input.value)
          if (formatted !== input.value) {
            input.value = formatted
            if (atEnd) {
              input.setSelectionRange(formatted.length, formatted.length)
            }
          }
          input.classList.remove('lkn-cielo-modal__input--error')
        })
        input.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault()
            test.click()
          }
        })

        setTimeout(function () { input.focus() }, 50)
      }

      // Intercepta o "Ativar": reverte e pede o teste antes de habilitar.
      enableRadio.addEventListener('change', function () {
        if (programmatic) return
        if (!enableRadio.checked) return

        disableRadio.checked = true
        cb.checked = false
        disableRadio.dispatchEvent(new Event('change', { bubbles: true }))

        openTestModal()
      })

      // Mostra o indicador já no carregamento (F5) conforme o estado salvo:
      // 'active' (verde) ou 'failed' (vermelho). Sem balão automático.
      if (cfg.initialStatus === 'active') {
        showStatus(true, false)
      } else if (cfg.initialStatus === 'failed') {
        showStatus(false, false)
      }
    })()

    // === Parcelas: juros x desconto (campos reais e "fake") ===
    // Os checkboxes de juros/desconto são convertidos em rádios "-control" (o checkbox
    // original fica oculto), então reagimos por delegação ao evento change — cobrindo
    // rádios, checkboxes e o select (o select2 dispara 'change' no select nativo). Roda
    // tanto para os campos reais (PRO ativo) quanto para os "fake" (showcase do free).
    ;(function () {
      const sectionMatch = window.location.search.match(/[?&]section=([^&]+)/)
      const section = sectionMatch ? decodeURIComponent(sectionMatch[1]) : ''
      if (section !== 'lkn_cielo_credit' && section !== 'lkn_cielo_debit') return

      const base = 'woocommerce_' + section + '_'
      const baseEsc = base.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
      const suffixes = ['', '_fake']
      const noInterestLabel = (typeof lknWcCieloTranslationsInput !== 'undefined' && lknWcCieloTranslationsInput.noInterest)
        ? lknWcCieloTranslationsInput.noInterest
        : 'Sem juros'

      const isChecked = (key) => {
        const radios = document.querySelectorAll('input[name="' + key + '-control"]')
        if (radios.length) {
          for (let i = 0; i < radios.length; i++) {
            if (radios[i].checked) return radios[i].value === '1'
          }
          return false
        }
        const cb = document.getElementById(key)
        return !!(cb && cb.checked)
      }

      const setRow = (el, show) => {
        if (!el) return
        const row = el.closest('tr')
        if (row) row.style.display = show ? '' : 'none'
      }

      const apply = () => {
        suffixes.forEach((suffix) => {
          const sel = document.getElementById(base + 'interest_or_discount' + suffix)
          if (!sel) return

          const interestKey = base + 'installment_interest' + suffix
          const discountKey = base + 'installment_discount' + suffix
          const mode = sel.value
          const interestChecked = isChecked(interestKey)
          const discountChecked = isChecked(discountKey)

          // Limite de parcelas: exibe apenas os campos Nx cujo índice <= limite.
          const limitSel = document.getElementById(base + 'installment_limit' + suffix)
          const limit = limitSel ? (parseInt(limitSel.value, 10) || 18) : 18

          setRow(document.getElementById(interestKey), mode === 'interest')
          setRow(document.getElementById(discountKey), mode === 'discount')

          const nxRe = new RegExp('^' + baseEsc + '(\\d+)x' + suffix + '$')
          const nxDiscRe = new RegExp('^' + baseEsc + '(\\d+)x_discount' + suffix + '$')

          document.querySelectorAll('input[id^="' + base + '"]').forEach((el) => {
            const discM = el.id.match(nxDiscRe)
            const intM = el.id.match(nxRe)
            if (discM) {
              setRow(el, parseInt(discM[1], 10) <= limit && mode === 'discount' && discountChecked)
            } else if (intM) {
              setRow(el, parseInt(intM[1], 10) <= limit && mode === 'interest' && interestChecked)
            }
          })
        })
      }

      // Checkbox "Sem juros" nos campos de juros por parcela (reais e fake).
      const addNoInterest = (suffix) => {
        const nxRe = new RegExp('^' + baseEsc + '(\\d+)x' + suffix + '$')
        document.querySelectorAll('input[id^="' + base + '"]').forEach((input) => {
          const m = input.id.match(nxRe)
          if (!m) return
          const fieldset = input.closest('fieldset')
          if (!fieldset || fieldset.querySelector('.lkn-cielo-no-interest')) return

          const wrapper = document.createElement('div')
          wrapper.className = 'lkn-cielo-no-interest'
          wrapper.style.marginTop = '8px'
          wrapper.style.display = 'flex'
          wrapper.style.alignItems = 'center'
          wrapper.style.gap = '5px'

          const checkbox = document.createElement('input')
          checkbox.type = 'checkbox'
          checkbox.name = base + m[1] + 'x' + suffix + '_no_interest'
          checkbox.id = checkbox.name
          checkbox.value = '1'

          const label = document.createElement('label')
          label.htmlFor = checkbox.id
          label.textContent = noInterestLabel
          label.style.fontSize = '13px'
          label.style.color = '#666'

          wrapper.appendChild(checkbox)
          wrapper.appendChild(label)

          const body = input.closest('.lkn-body-cart') || fieldset
          body.appendChild(wrapper)

          checkbox.addEventListener('change', function () {
            if (this.checked) {
              input.readOnly = true
              input.value = 0
            } else {
              input.readOnly = false
              input.value = ''
            }
          })
          input.addEventListener('change', function (e) {
            if (e.target.value === '0' || e.target.value === 0) {
              checkbox.checked = true
              checkbox.dispatchEvent(new Event('change'))
            } else {
              checkbox.checked = false
            }
          })
          if (input.value === '0' || input.value === 0) {
            checkbox.checked = true
            checkbox.dispatchEvent(new Event('change'))
          }
        })
      }

      const onControlChange = function (e) {
        const t = e.target
        if (!t) return
        const name = t.name || t.id || ''
        if (name.indexOf(base + 'interest_or_discount') === 0 ||
            name.indexOf(base + 'installment_interest') === 0 ||
            name.indexOf(base + 'installment_discount') === 0 ||
            name.indexOf(base + 'installment_limit') === 0) {
          apply()
        }
      }

      // O select2 dispara 'change' via jQuery (não gera evento nativo), então usamos
      // a delegação do jQuery — que também cobre os rádios nativos (checkbox→-control).
      if (window.jQuery) {
        window.jQuery(document).on('change select2:select select2:unselect', onControlChange)
      } else {
        document.addEventListener('change', onControlChange)
      }

      suffixes.forEach(addNoInterest)
      apply()
      setTimeout(apply, 300)
    })()
  })

  function lknWcCieloAddOnexInterestWarning() {
    // Campo de juros da 1ª parcela (não o de desconto)
    const onexInput = document.querySelector('input[id$="_1x"]')
    if (!onexInput) return

    const bodyDiv = onexInput.closest('.lkn-body-cart')
    const fieldset = onexInput.closest('fieldset')
    const headerDiv = fieldset ? fieldset.querySelector('.lkn-header-cart') : null
    if (!bodyDiv || !headerDiv) return

    // Evita aplicar duas vezes
    if (fieldset.querySelector('.lkn-cielo-onex-warning')) return

    // Ícone de info + balão customizado (hover no desktop, toque/clique no mobile)
    const icon = document.createElement('span')
    icon.className = 'lkn-cielo-info-icon lkn-cielo-onex-warning'
    icon.setAttribute('tabindex', '0')
    icon.setAttribute('role', 'button')
    icon.setAttribute('aria-label', 'Informações sobre juros na primeira parcela')
    icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'

    const tooltip = document.createElement('span')
    tooltip.className = 'lkn-cielo-info-tooltip'
    tooltip.setAttribute('role', 'tooltip')
    tooltip.textContent = 'No Brasil, adicionar juros ou cobrar um valor maior no pagamento à vista (em 1 parcela) no cartão de crédito vai contra a lei e é considerado uma prática abusiva pelo Código de Defesa do Consumidor e órgãos de proteção (como os Procons).'
    icon.appendChild(tooltip)

    icon.addEventListener('mouseenter', function () { icon.classList.add('is-open') })
    icon.addEventListener('mouseleave', function () { icon.classList.remove('is-open') })
    icon.addEventListener('click', function (e) {
      e.stopPropagation()
      icon.classList.toggle('is-open')
    })
    icon.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault()
        icon.classList.toggle('is-open')
      }
    })

    document.addEventListener('click', function (e) {
      if (!icon.contains(e.target)) icon.classList.remove('is-open')
    })

    // Insere o ícone ao lado do título do card
    const titleEl = headerDiv.querySelector('div')
    if (titleEl) {
      titleEl.appendChild(icon)
    } else {
      headerDiv.appendChild(icon)
    }

    // Recomendação junto à descrição do campo
    const descP = bodyDiv.querySelector('p.description')
    const recommendation = document.createElement('p')
    recommendation.className = 'description lkn-cielo-onex-recommendation lkn-cielo-onex-warning'
    recommendation.textContent = 'Recomendação: Para pagamento à vista é sugerido que aplique sem juros.'
    if (descP) {
      descP.insertAdjacentElement('afterend', recommendation)
    } else {
      bodyDiv.appendChild(recommendation)
    }
  }

  function lknWcCieloValidateMerchantInputs() {
    const urlParams = new URLSearchParams(window.location.search)
    const sectionParam = urlParams.get('section')

    if (sectionParam) {
      const merchantIdInput = document.querySelector(`#woocommerce_${sectionParam}_merchant_id`)
      const merchantKeyInput = document.querySelector(`#woocommerce_${sectionParam}_merchant_key`)

      if (merchantIdInput && merchantKeyInput) {
        function validateInput(input, expectedLength, message) {
          const parent = input.parentElement
          let errorMsg = parent.querySelector('.validation-error')

          if (input.value.length !== expectedLength) {
            if (!errorMsg) {
              errorMsg = document.createElement('p')
              errorMsg.className = 'validation-error'
              errorMsg.style.color = 'red'
              errorMsg.style.fontWeight = '500'
              errorMsg.style.marginTop = '5px'
              errorMsg.style.fontSize = 'small'
              parent.appendChild(errorMsg)
            }
            errorMsg.textContent = message
          } else {
            if (errorMsg) errorMsg.remove()
          }
        }

        function validateFields() {
          validateInput(merchantIdInput, 36, 'O Merchant ID deve ter 36 caracteres.')
          validateInput(merchantKeyInput, 40, 'A Merchant Key deve ter 40 caracteres.')
        }

        merchantIdInput.addEventListener('input', validateFields)
        merchantKeyInput.addEventListener('input', validateFields)

        validateFields() // Valida ao carregar a página
      }
    }
  }

  $(document).ready(function () {
    const wpcontent = document.querySelector('#wpcontent')
    if (wpcontent) {
      wpcontent.style.height = 'unset'
    }
  })
})(jQuery)
