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
          tables.forEach((table, index) => {
            switch (lknWcCieloCreditBlocksSettingsLayoutMenuVar) {
              case 1:
                if (index == 0 || index == 1) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 2:
                if (index == 2) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 3:
                if (index == 3) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 4:
                if (index == 4) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 5:
                if (index == 5) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 6:
                if (index == 6) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 7:
                if (index == 7) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
            }
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
              }
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

    // Seletores do PRO
    const proOn = document.querySelector('input[name$="_debug_pro-control"][value="1"]');
    const proOff = document.querySelector('input[name$="_debug_pro-control"][value="0"]');

    // 2. Verifica os obrigatórios
    if (debugOn && debugOff && logsRow && logsOff && sendConfigsInput) {

      // --- PARTE A: Lógica do Botão WPP (Roda APENAS 1 vez ao iniciar) ---
      const initWppState = () => {
        // Verifica o estado inicial (como veio do banco de dados)
        const isDebugActive = debugOn.checked;
        const isProActive = proOn ? proOn.checked : true;

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
