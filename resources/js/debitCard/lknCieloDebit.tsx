import React from 'react';
import Cards from 'react-credit-cards';
import 'react-credit-cards/es/styles-compiled.css';

const lknDCsettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const lknDCLabelCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.title)
const lknDCDescriptionCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.description)
const lknDCAccessTokenCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessToken)
const lknDCAccessTokenExpiration = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessTokenExpiration)
const lknDCshowCard = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.showCard)
const lknDCActiveInstallmentCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.activeInstallment)
const lknDCUrlCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.url)
const lknDCTotalCartCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.totalCart)
const lknDCOrderNumberCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.orderNumber)
const lknDCDirScript3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScript3DS)
const lknDCInstallmentLimitCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installmentLimit)
const lknDCInstallmentMinAmount = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installmentMinAmount)
const lknCC3DSinstallmentsCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installments)
const lknDCDirScriptConfig3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScriptConfig3DS)
const lknDCTranslationsDebitCielo = lknDCsettingsCielo.translations
const lknDCNonceCieloDebit = lknDCsettingsCielo.nonceCieloDebit
const lknDCTranslationsCielo = lknDCsettingsCielo.translations
const lknDCBec = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.bec)
const lknDCClientIp = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.client_ip)
const lknDCUserGuest = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.user_guest)
const lknDCAuthMethod = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.authentication_method)
const lknDCClient = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.client)

const lknDCHideCheckoutButton = () => {
  const lknDCElement = document.querySelectorAll('.wc-block-components-checkout-place-order-button')

  if (lknDCElement && lknDCElement[0]) {
    lknDCElement[0].style.display = 'none';
  }
}

const lknDCInitCieloPaymentForm = () => {
  document.addEventListener('DOMContentLoaded', lknDCHideCheckoutButton)
  lknDCHideCheckoutButton()

  // Load Cielo 3DS Config Script FIRST
  const scriptUrl = lknDCDirScriptConfig3DSCielo;
  const existingScript = document.querySelector(`script[src="${scriptUrl}"]`);
  if (!existingScript) {
    const script = document.createElement('script');
    script.src = scriptUrl;
    script.async = false; // Load synchronously
    script.onload = () => {
      // Only load BP.Mpi after config script is loaded
      const scriptUrlBpmpi = lknDCDirScript3DSCielo;
      const existingScriptBpmpi = document.querySelector(`script[src="${scriptUrlBpmpi}"]`);

      if (!existingScriptBpmpi) {
        const scriptBpmpi = document.createElement('script');
        scriptBpmpi.src = scriptUrlBpmpi;
        scriptBpmpi.async = true;
        document.body.appendChild(scriptBpmpi);
      }
    };
    document.body.appendChild(script);
  } else {
    // Config script already exists, load BP.Mpi
    const scriptUrlBpmpi = lknDCDirScript3DSCielo;
    const existingScriptBpmpi = document.querySelector(`script[src="${scriptUrlBpmpi}"]`);

    if (!existingScriptBpmpi) {
      const scriptBpmpi = document.createElement('script');
      scriptBpmpi.src = scriptUrlBpmpi;
      scriptBpmpi.async = true;
      document.body.appendChild(scriptBpmpi);
    }
  }
}

const lknDCContentCielo = (props) => {
  const wcComponents = window.wc.blocksComponents
  const { eventRegistration, emitResponse } = props
  const { onPaymentSetup } = eventRegistration

  const [options, setOptions] = window.wp.element.useState([])
  const [cardBinState, setCardBinState] = window.wp.element.useState(0)
  const [cardTypeOptions, setCardTypeOptions] = window.wp.element.useState([
    { key: 'Credit', label: lknDCTranslationsCielo.creditCard },
    { key: 'Debit', label: lknDCTranslationsCielo.debitCard }
  ])
  const [debitObject, setdebitObject] = window.wp.element.useState({
    lkn_dc_cardholder_name: '',
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: '',
    lkn_cc_dc_installments: '1', // Definir padrão como 1 parcela
    lkn_cc_type: 'Credit',
    lkn_save_debit_credit_card: false
  })
  const [focus, setFocus] = window.wp.element.useState('')

  const formatDebitCardNumber = value => {
    if (value?.length > 24) return debitObject.lkn_dcno
    // Remove caracteres não numéricos
    const cleanedValue = value?.replace(/\D/g, '')
    // Adiciona espaços a cada quatro dígitos
    const formattedValue = cleanedValue?.replace(/(.{4})/g, '$1 ')?.trim()
    return formattedValue
  }

  const updatedebitObject = (key, value) => {

    switch (key) {
      case 'lkn_dc_cardholder_name':
        // Atualiza o estado
        setdebitObject({
          ...debitObject,
          [key]: value
        })

        break
      case 'lkn_dc_expdate':
        if (value.length > 7) return

        // Verifica se o valor é uma data válida (MM/YY)
        const isValidDate = /^\d{2}\/\d{2}$/.test(value);
        if (!isValidDate) {
          // Remove caracteres não numéricos
          const cleanedValue = value?.replace(/\D/g, '');
          let formattedValue = cleanedValue?.replace(/^(.{2})(.{2})$/, '$1 / $2');

          // Se o tamanho da string for 6 (MMYYYY), formate para MM / YY
          if (cleanedValue.length === 6) {
            formattedValue = cleanedValue?.replace(/^(.{2})(.{2})(.{2})$/, '$1 / $3');
          }

          // Atualiza o estado
          setdebitObject({
            ...debitObject,
            [key]: formattedValue
          })
        }
        return
      case 'lkn_dc_cvc':
        if (value.length > 8) return
      case 'lkn_dcno':
        if (value.length > 7) {
          var cardBin = value.replace(' ', '').substring(0, 6);
          var restUrl = (typeof window.lknCieloDebitConfig !== 'undefined' && window.lknCieloDebitConfig.rest_url)
            ? window.lknCieloDebitConfig.rest_url
            : (window.location.origin + '/wp-json/');
          var url = restUrl + 'lknWCGatewayCielo/checkCard?cardbin=' + cardBin;

          if (cardBin !== cardBinState) {
            setCardBinState(cardBin);  // Mova o setCardBinState para antes da requisição

            fetch(url, {
              method: 'GET',
              headers: {
                'Accept': 'application/json',
                'X-WP-Nonce': window.lknCieloDebitConfig.nonce
              }
            })
              .then(response => {
                if (!response.ok) {
                  throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
              })
              .then(data => {
                if ('Crédito' == data.CardType) {
                  setCardTypeOptions([
                    { key: 'Credit', label: lknDCTranslationsCielo.creditCard },
                  ]);
                  setdebitObject(prevState => ({
                    ...prevState,
                    lkn_cc_type: 'Credit'
                  }));
                } else if ('Débito' == data.CardType) {
                  setCardTypeOptions([
                    { key: 'Debit', label: lknDCTranslationsCielo.debitCard }
                  ]);
                  setdebitObject(prevState => ({
                    ...prevState,
                    lkn_cc_type: 'Debit'
                  }));
                } else {
                  setCardTypeOptions([
                    { key: 'Credit', label: lknDCTranslationsCielo.creditCard },
                    { key: 'Debit', label: lknDCTranslationsCielo.debitCard }
                  ]);
                }
              })
              .catch(error => {
                console.error('Erro:', error);
              });
          }
          setdebitObject(prevState => ({
            ...prevState,
            lkn_dcno: value
          }));
        }
        break;

      default:
        break
    }
    setdebitObject({
      ...debitObject,
      [key]: value
    })
  }

  window.wp.element.useEffect(() => {
    const lknDCElement = document.querySelectorAll('.wc-block-components-checkout-place-order-button')

    if (lknDCElement && lknDCElement[0]) {
      // Hides the checkout button on cielo debit select
      lknDCElement[0].style.display = 'none';

      // Shows the checkout button on payment change
      return () => {
        lknDCElement[0].style.display = '';
      };
    }
  })


  const handleButtonClick = () => {
    // Verifica se todos os campos do debitObject estão preenchidos
    const allFieldsFilled = Object.keys(debitObject)
      .filter(key => key !== 'lkn_dc_cardholder_name')
      .every(key => debitObject[key].trim() !== '');

    // Seleciona os lknDCElements dos campos de entrada
    const cardNumberInput = document.getElementById('lkn_dcno');
    const expDateInput = document.getElementById('lkn_dc_expdate');
    const cvvInput = document.getElementById('lkn_dc_cvc');
    const cardHolder = document.getElementById('lkn_dc_cardholder_name');

    // Remove classes de erro e mensagens de validação existentes
    cardNumberInput?.classList.remove('has-error');
    expDateInput?.classList.remove('has-error');
    cvvInput?.classList.remove('has-error');
    cardHolder?.classList.remove('has-error')

    if (allFieldsFilled) {
      lknDCProccessButton();
    } else {
      // Adiciona classes de erro aos campos vazios
      if (debitObject.lkn_dcno.trim() === '') {
        const parentDiv = cardNumberInput?.parentElement;
        parentDiv?.classList.add('has-error');
      }

      if (debitObject.lkn_dc_expdate.trim() === '') {
        const parentDiv = expDateInput?.parentElement;
        parentDiv?.classList.add('has-error');
      }

      if (debitObject.lkn_dc_cvc.trim() === '') {
        const parentDiv = cvvInput?.parentElement;
        parentDiv?.classList.add('has-error');
      }
    }
  };

  window.wp.element.useEffect(() => {
    lknDCInitCieloPaymentForm()
    const unsubscribe = onPaymentSetup(async () => {
      const Button3dsEnviar = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')

      const paymentCavv = Button3dsEnviar?.getAttribute('data-payment-cavv');
      const paymentEci = Button3dsEnviar?.getAttribute('data-payment-eci');
      const paymentReferenceId = Button3dsEnviar?.getAttribute('data-payment-ref_id');
      const paymentVersion = Button3dsEnviar?.getAttribute('data-payment-version');
      const paymentXid = Button3dsEnviar?.getAttribute('data-payment-xid');
      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: { //TODO Corrigir campos faltando
            lkn_dcno: debitObject.lkn_dcno,
            lkn_dc_cardholder_name: debitObject.lkn_dc_cardholder_name,
            lkn_dc_expdate: debitObject.lkn_dc_expdate,
            lkn_dc_cvc: debitObject.lkn_dc_cvc,
            nonce_lkn_cielo_debit: lknDCNonceCieloDebit,
            lkn_cielo_3ds_cavv: paymentCavv,
            lkn_cielo_3ds_eci: paymentEci,
            lkn_cielo_3ds_ref_id: paymentReferenceId,
            lkn_cielo_3ds_version: paymentVersion,
            lkn_cielo_3ds_xid: paymentXid,
            lkn_cc_dc_installments: debitObject.lkn_cc_dc_installments,
            lkn_cc_type: debitObject.lkn_cc_type,
            lkn_save_debit_credit_card: debitObject.lkn_save_debit_credit_card,
          },
        },
      };
    }
    );

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe();
    };
  }, [debitObject,
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    onPaymentSetup
  ]);

  const calculateInstallments = (lknDCTotalCartCielo) => {
    const installmentMin = parseFloat(lknDCInstallmentMinAmount);
    // Verifica se 'lknCCActiveInstallmentCielo' é 'yes' e o valor total é maior que 10
    if (lknDCActiveInstallmentCielo === 'yes' && lknDCTotalCartCielo > 10) {
      const maxInstallments = lknDCInstallmentLimitCielo; // Limita o parcelamento até 12 vezes, deixei fixo para teste

      for (let index = 1; index <= maxInstallments; index++) {
        const installmentAmount = (lknDCTotalCartCielo / index).toLocaleString('pt-BR', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });

        let nextInstallmentAmount = parseFloat(lknDCTotalCartCielo) / index

        if (nextInstallmentAmount < installmentMin) {
          break;
        }

        let formatedInterest = false
        for (let t = 0; t < lknCC3DSinstallmentsCielo.length; t++) {
          const installmentObj = lknCC3DSinstallmentsCielo[t]
          if (installmentObj.isDiscount == true && installmentObj.id === index) {
            nextInstallmentAmount = (lknDCTotalCartCielo - lknDCTotalCartCielo * (parseFloat(installmentObj.interest) / 100)) / index;
            formatedInterest = new Intl.NumberFormat('pt-br', {
              style: 'currency',
              currency: 'BRL'
            }).format(nextInstallmentAmount);
          } else if (installmentObj.id === index) {
            nextInstallmentAmount = (lknDCTotalCartCielo + lknDCTotalCartCielo * (parseFloat(installmentObj.interest) / 100)) / index;
            formatedInterest = new Intl.NumberFormat('pt-br', {
              style: 'currency',
              currency: 'BRL'
            }).format(nextInstallmentAmount);
          }
        }
        if (formatedInterest) {
          setOptions(prevOptions => [...prevOptions, {
            key: index,
            label: `${index}x de ${formatedInterest}`
          }]);
        } else if (lknDCsettingsCielo.activeDiscount == 'yes') {
          setOptions(prevOptions => [...prevOptions, {
            key: index,
            label: `${index}x de R$ ${installmentAmount}`
          }]);
        } else {
          setOptions(prevOptions => [...prevOptions, {
            key: index,
            label: `${index}x de R$ ${installmentAmount} sem juros`
          }]);
        }
      }
    } else {
      setOptions(prevOptions => [...prevOptions, {
        key: '1',
        label: `1x de R$ ${lknDCTotalCartCielo} (à vista)`
      }])
    }
  }

  window.wp.element.useEffect(() => {
    calculateInstallments(lknDCTotalCartCielo);

    const intervalId = setInterval(() => {
      var targetNode = document.querySelector('.wc-block-formatted-money-amount.wc-block-components-formatted-money-amount.wc-block-components-totals-footer-item-tax-value');
      // Configuração do observer: quais mudanças serão observadas
      if (targetNode) {
        var config = { childList: true, subtree: true, characterData: true };

        var changeValue = () => {
          setOptions([])
          // Remover tudo exceto os números e a vírgula
          let valorNumerico = targetNode.textContent.replace(/[^\d,]/g, '');

          // Substituir a vírgula por um ponto
          valorNumerico = valorNumerico.replace(',', '.');

          // Converter para número
          valorNumerico = parseFloat(valorNumerico);

          calculateInstallments(valorNumerico)
        }

        changeValue()

        // Função de callback que será executada quando ocorrerem mudanças
        var callback = function (mutationsList, observer) {
          for (var mutation of mutationsList) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
              changeValue()
            }
          }
        };

        // Cria uma instância do observer e o conecta ao nó alvo
        var observer = new MutationObserver(callback);
        observer.observe(targetNode, config);

        clearInterval(intervalId);
      }
    }, 500);
  }, [])


  return (
    <>
      {lknDCshowCard !== 'no' && (
        <Cards
          number={debitObject.lkn_dcno}
          name={debitObject.lkn_dc_cardholder_name}
          expiry={(debitObject.lkn_dc_expdate).replace(/\s+/g, '')}
          cvc={debitObject.lkn_dc_cvc}
          placeholders={{
            name: 'NOME',
            expiry: 'MM/ANO',
            cvc: 'CVC',
            number: '•••• •••• •••• ••••'
          }}
          locale={{ valid: 'VÁLIDO ATÉ' }}
          focused={focus}
        />
      )}

      <wcComponents.TextInput
        id="lkn_dc_cardholder_name"
        label={lknDCTranslationsDebitCielo.cardHolder}
        value={debitObject.lkn_dc_cardholder_name}
        autocomplete="cc-name"
        onChange={(value) => {
          updatedebitObject('lkn_dc_cardholder_name', value)
        }}
        required
        onFocus={() => setFocus('name')}
      />

      <wcComponents.TextInput
        id="lkn_dcno"
        label={lknDCTranslationsDebitCielo.cardNumber}
        value={debitObject.lkn_dcno}
        autocomplete="cc-number"
        onChange={(value) => {
          updatedebitObject('lkn_dcno', formatDebitCardNumber(value))
        }}
        required
        onFocus={() => setFocus('number')}
      />

      <wcComponents.SortSelect
        id="lkn_cc_type"
        value={debitObject.lkn_cc_type}
        onChange={(event) => {
          updatedebitObject('lkn_cc_type', event.target.value)
        }}
        className="lkn-type-card-select"
        options={cardTypeOptions}
      />

      <wcComponents.TextInput
        id="lkn_dc_expdate"
        label={lknDCTranslationsDebitCielo.cardExpiryDate}
        value={debitObject.lkn_dc_expdate}
        autocomplete="cc-exp"
        onChange={(value) => {
          updatedebitObject('lkn_dc_expdate', value)
        }}
        required
        onFocus={() => setFocus('expiry')}
      />

      <wcComponents.TextInput
        id="lkn_dc_cvc"
        label={lknDCTranslationsDebitCielo.securityCode}
        value={debitObject.lkn_dc_cvc}
        autocomplete="cc-csc"
        onChange={(value) => {
          updatedebitObject('lkn_dc_cvc', value)
        }}
        required
        onFocus={() => setFocus('cvc')}
      />

      {(lknDCActiveInstallmentCielo === 'yes' && debitObject.lkn_cc_type == 'Credit') && (
        <wcComponents.SortSelect
          id="lkn_cc_dc_installments"
          label={lknDCTranslationsCielo.installments}
          value={debitObject.lkn_cc_dc_installments}
          className="lkn-cielo-credit-debit-custom-select"
          onChange={(event) => {
            updatedebitObject('lkn_cc_dc_installments', event.target.value)
          }}
          options={options}
        />
      )}

      {debitObject.lkn_cc_type === 'Credit' && (
        <wcComponents.CheckboxControl
          id="lkn_save_debit_credit_card"
          label={lknDCTranslationsCielo.saveCard}
          checked={debitObject.lkn_save_debit_credit_card || false}
          onChange={isChecked => {
            updatedebitObject('lkn_save_debit_credit_card', isChecked)
          }}
        />
      )}

      <div style={{ marginBottom: '25px', width: '100%' }}></div>

      <div style={{ display: 'flex', justifyContent: 'center' }}>
        <wcComponents.Button
          id="sendOrder"
          onClick={handleButtonClick}
        >
          <span>Finalizar pedido</span>
        </wcComponents.Button>
      </div>

      <div style={{ margin: '2px', width: '100%' }}></div>

      <div className="lkn-cielo-credit-debit-description">
        <p style={{ textAlign: "center", widht: "100%" }}>{lknDCDescriptionCielo}</p>
      </div>

      <div>
        <input type="hidden" name="lkn_auth_enabled" className="bpmpi_auth" value="true" />
        <input type="hidden" name="lkn_auth_enabled_notifyonly" className="bpmpi_auth_notifyonly" value="true" />
        <input type="hidden" name="lkn_auth_suppresschallenge" className="bpmpi_auth_suppresschallenge" value="false" />
        <input type="hidden" name="lkn_access_token" className="bpmpi_accesstoken" value={lknDCAccessTokenCielo} />
        <input type="hidden" name="lkn_expires_in" id="expires_in" value={lknDCAccessTokenExpiration} />
        <input type="hidden" size="50" name="lkn_order_number" className="bpmpi_ordernumber" value={lknDCOrderNumberCielo} />
        <input type="hidden" name="lkn_currency" className="bpmpi_currency" value="BRL" />
        <input type="hidden" size="50" id="lkn_cielo_3ds_value" name="lkn_amount" className="bpmpi_totalamount" value={lknDCTotalCartCielo} />
        <input type="hidden" size="2" name="lkn_installments" className="bpmpi_installments" value="1" />
        <input type="hidden" name="lkn_payment_method" className="bpmpi_paymentmethod" value={debitObject.lkn_cc_type} />
        <input type="hidden" id="lkn_bpmpi_cardnumber" className="bpmpi_cardnumber" />
        <input type="hidden" id="lkn_bpmpi_expmonth" maxLength="2" name="lkn_card_expiry_month" className="bpmpi_cardexpirationmonth" />
        <input type="hidden" id="lkn_bpmpi_expyear" maxLength="4" name="lkn_card_expiry_year" className="bpmpi_cardexpirationyear" />
        <input type="hidden" id="lkn_bpmpi_default_card" name="lkn_default_card" className="bpmpi_default_card" value="false" />
        <input type="hidden" id="lkn_bpmpi_order_recurrence" name="lkn_order_recurrence" className="bpmpi_order_recurrence" value="false" />
        <input type="hidden" size="50" className="bpmpi_order_productcode" value="PHY" />
        <input type="hidden" size="50" className="bpmpi_transaction_mode" value="S" />
        <input type="hidden" size="50" className="bpmpi_merchant_url" value={lknDCUrlCielo} />
        <input type="hidden" size="14" id="lkn_bpmpi_billto_customerid" name="lkn_card_customerid" className="bpmpi_billto_customerid" value={lknDCClient.billing_document} />
        <input type="hidden" size="120" id="lkn_bpmpi_billto_contactname" name="lkn_card_contactname" className="bpmpi_billto_contactname" value={lknDCClient.name} />
        <input type="hidden" size="15" id="lkn_bpmpi_billto_phonenumber" name="lkn_card_phonenumber" className="bpmpi_billto_phonenumber" value={lknDCClient.billing_phone} />
        <input type="hidden" size="255" id="lkn_bpmpi_billto_email" name="lkn_card_email" className="bpmpi_billto_email" value={lknDCClient.email} />
        <input type="hidden" size="60" id="lkn_bpmpi_billto_street1" name="lkn_card_billto_street1" className="bpmpi_billto_street1" value={lknDCClient.billing_address_1} />
        <input type="hidden" size="60" id="lkn_bpmpi_billto_street2" name="lkn_card_billto_street2" className="bpmpi_billto_street2" value={lknDCClient.billing_address_2} />
        <input type="hidden" size="50" id="lkn_bpmpi_billto_city" name="lkn_card_billto_city" className="bpmpi_billto_city" value={lknDCClient.billing_city} />
        <input type="hidden" size="2" id="lkn_bpmpi_billto_state" name="lkn_card_billto_state" className="bpmpi_billto_state" value={lknDCClient.billing_state} />
        <input type="hidden" size="8" id="lkn_bpmpi_billto_zipcode" name="lkn_card_billto_zipcode" className="bpmpi_billto_zipcode" value={lknDCClient.billing_postcode} />
        <input type="hidden" size="2" id="lkn_bpmpi_billto_country" name="lkn_card_billto_country" className="bpmpi_billto_country" value={lknDCClient.billing_country} />
        <input type="hidden" id="lkn_bpmpi_shipto_sameasbillto" name="lkn_card_shipto_sameasbillto" className="bpmpi_shipto_sameasbillto" value="true" />
        <input type="hidden" id="lkn_bpmpi_useraccount_guest" name="lkn_card_useraccount_guest" className="bpmpi_useraccount_guest" value={lknDCUserGuest} />
        <input type="hidden" id="lkn_bpmpi_useraccount_authenticationmethod" name="lkn_card_useraccount_authenticationmethod" className="bpmpi_useraccount_authenticationmethod" value={lknDCAuthMethod} />
        <input type="hidden" size="45" id="lkn_bpmpi_device_ipaddress" name="lkn_card_device_ipaddress" className="bpmpi_device_ipaddress" value={lknDCClientIp} />
        <input type="hidden" size="7" id="lkn_bpmpi_device_channel" name="lkn_card_device_channel" className="bpmpi_device_channel" value="Browser" />
        {/* Browser info fields for 3DS ELO compliance - populated by JS before bpmpi_authenticate() */}
        <input type="hidden" id="lkn_bpmpi_device_useragent" name="lkn_card_device_useragent" className="bpmpi_device_useragent" />
        <input type="hidden" id="lkn_bpmpi_device_screenwidth" name="lkn_card_device_screenwidth" className="bpmpi_device_screenwidth" />
        <input type="hidden" id="lkn_bpmpi_device_screenheight" name="lkn_card_device_screenheight" className="bpmpi_device_screenheight" />
        <input type="hidden" id="lkn_bpmpi_device_colordepth" name="lkn_card_device_colordepth" className="bpmpi_device_colordepth" />
        <input type="hidden" id="lkn_bpmpi_device_timezone" name="lkn_card_device_timezone" className="bpmpi_device_timezone" />
        <input type="hidden" id="lkn_bpmpi_device_javaenabled" name="lkn_card_device_javaenabled" className="bpmpi_device_javaenabled" />
        <input type="hidden" size="10" id="lkn_bpmpi_brand_establishment_code" name="lkn_card_brand_establishment_code" className="bpmpi_brand_establishment_code" value={lknDCBec} />
        <input type="hidden" id="lkn_cavv" name="lkn_cielo_3ds_cavv" value />
        <input type="hidden" id="lkn_eci" name="lkn_cielo_3ds_eci" value />
        <input type="hidden" id="lkn_ref_id" name="lkn_cielo_3ds_ref_id" value />
        <input type="hidden" id="lkn_version" name="lkn_cielo_3ds_version" value />
        <input type="hidden" id="lkn_xid" name="lkn_cielo_3ds_xid" value />
      </div>
    </>
  )
}

const Lkn_DC_Block_Gateway_Cielo = {
  name: 'lkn_cielo_debit',
  label: lknDCLabelCielo,
  content: window.wp.element.createElement(lknDCContentCielo),
  edit: window.wp.element.createElement(lknDCContentCielo),
  canMakePayment: () => true,
  ariaLabel: lknDCLabelCielo,
  supports: {
    features: lknDCsettingsCielo.supports
  }
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_DC_Block_Gateway_Cielo)
