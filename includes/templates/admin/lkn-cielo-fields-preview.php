<?php
/**
 * Editor visual da seção "Fields" do gateway Cielo Débito/Crédito.
 *
 * Renderiza o "resultado": o formulário de checkout em duas camadas
 * (Blocks/Gutenberg e Classic/shortcode) e três templates (standard/modern/
 * compact), usando as MESMAS classes/estrutura do checkout real.
 *
 * Regras de edição (lápis):
 *  - Label: editável em todos os templates, EXCETO o select "tipo de cartão"
 *    no template moderno (que não usa label).
 *  - Placeholder: existe quando a label fica ACIMA do input — em TODOS os
 *    templates do clássico/shortcode e, nos blocos/Gutenberg, apenas no compacto
 *    (nos blocos, standard e modern usam a label flutuante dentro do input).
 *
 * IMPORTANTE: o preview não pode conter <p> sem classe — o script de layout do
 * painel move o menu de abas para depois do último <p> sem classe.
 *
 * Espera $gateway_id (string) definida pelo gateway que inclui este arquivo.
 *
 * @package Lkn\WCCieloPaymentGateway
 */

if (! defined('ABSPATH')) {
    exit();
}

$lkn_templates   = \Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::getCheckoutFieldTemplates();
$lkn_defs        = \Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::getCheckoutFieldDefinitions();
$lkn_text_fields = array('holder_name', 'card_number', 'expiry', 'cvc');
$lkn_asset       = plugin_dir_url(__FILE__) . '../../../resources/img/';

$lkn_label_val = function ($mode, $tpl, $field) use ($gateway_id) {
    return \Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::getFieldLabel($gateway_id, $tpl, $field, $mode);
};
$lkn_ph_val = function ($mode, $tpl, $field) use ($gateway_id) {
    return \Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::getFieldPlaceholder($gateway_id, $tpl, $field, $mode);
};

$lkn_pencil = function ($mode, $tpl, $field, $kind, $extra = '') {
    return sprintf(
        '<button type="button" class="lkn-edit-btn %1$s" data-lkn-field="%2$s" data-lkn-kind="%3$s" data-lkn-template="%4$s" data-lkn-mode="%5$s" title="%6$s" aria-label="%6$s"><span class="lkn-edit-btn__icon" aria-hidden="true">&#9998;</span></button>',
        esc_attr($extra),
        esc_attr($field),
        esc_attr($kind),
        esc_attr($tpl),
        esc_attr($mode),
        esc_attr__('Edit', 'lkn-wc-gateway-cielo')
    );
};

$lkn_edit_label = function ($mode, $tpl, $field, $class, $for, $editable = true, $icon = '') use ($lkn_label_val, $lkn_pencil) {
    return sprintf(
        '<label class="%1$s" for="%2$s"><span class="lkn-edit-text" data-lkn-field="%3$s" data-lkn-kind="label" data-lkn-template="%4$s" data-lkn-mode="%5$s">%6$s</span><span class="required">*</span>%7$s%8$s</label>',
        esc_attr($class),
        esc_attr($for),
        esc_attr($field),
        esc_attr($tpl),
        esc_attr($mode),
        esc_html($lkn_label_val($mode, $tpl, $field)),
        $editable ? $lkn_pencil($mode, $tpl, $field, 'label') : '',
        $icon
    );
};

// Botão de finalizar com o TEXTO editável (como as labels): o texto vem de
// getFieldLabel($gid, $tpl, 'button') e ganha um lápis. O lápis fica FORA do
// <button> (não dá para aninhar <button> em <button>), posicionado sobre o botão.
$lkn_btn = function ($mode, $tpl, $class, $extra = '') use ($lkn_label_val, $lkn_pencil) {
    return '<span class="lkn-preview-btn"><button type="button" class="' . esc_attr($class) . '" disabled' . $extra . '>'
        . '<span class="lkn-edit-text" data-lkn-field="button" data-lkn-kind="label" data-lkn-template="' . esc_attr($tpl) . '" data-lkn-mode="' . esc_attr($mode) . '">' . esc_html($lkn_label_val($mode, $tpl, 'button')) . '</span>'
        . '</button>' . $lkn_pencil($mode, $tpl, 'button', 'label', 'lkn-edit-btn--over') . '</span>';
};

$lkn_select = function ($opts, $class = '') {
    return sprintf('<select class="%s" disabled aria-disabled="true" tabindex="-1" style="background-color:#f0f0f1 !important;color:#767676 !important;pointer-events:none;cursor:not-allowed;">%s</select>', esc_attr($class), $opts);
};

$lkn_installments_opts = '';
for ($i = 1; $i <= 12; $i++) {
    $lkn_installments_opts .= sprintf('<option value="%1$d">%1$dx</option>', $i);
}
$lkn_card_type_opts = '<option value="Credit" selected>' . esc_html__('Credit card', 'lkn-wc-gateway-cielo') . '</option><option value="Debit">' . esc_html__('Debit card', 'lkn-wc-gateway-cielo') . '</option>';
$lkn_description    = __('Pay for your purchase with a debit card through', 'lkn-wc-gateway-cielo');

/**
 * Renderiza um preview.
 *
 * @param string $mode blocks|classic
 * @param string $tpl  standard|modern|compact
 */
$lkn_render = function ($mode, $tpl) use (
    $lkn_text_fields, $lkn_btn, $lkn_edit_label, $lkn_select, $lkn_pencil, $lkn_ph_val,
    $lkn_installments_opts, $lkn_card_type_opts, $lkn_description, $lkn_asset
) {
    $blocks   = 'blocks' === $mode;
    // Placeholder: todos os templates do clássico; nos blocos só o compacto.
    $has_ph   = $blocks ? ('compact' === $tpl) : true;
    // Sem label de "tipo de cartão" apenas no modern dos Blocos (o shortcode
    // modern mostra a label, como no checkout real).
    $type_lbl = ! ($blocks && 'modern' === $tpl);
    // No modelo moderno do SHORTCODE a label usa .field-label (negrito/uppercase,
    // como no checkout real); "tipo de cartão" e "parcelas" seguem o mesmo. Não
    // aplicamos no modern dos Blocos, onde a label é flutuante (o override do
    // preview posiciona .modern-field > .field-label dentro do input).
    $lkn_card_labels_class = (! $blocks && 'modern' === $tpl) ? 'field-label' : '';
    $ct_label = $type_lbl ? $lkn_edit_label($mode, $tpl, 'card_type', $lkn_card_labels_class, 'lkn-preview-type') : '';
    $installments_label = $lkn_edit_label($mode, $tpl, 'installments', $lkn_card_labels_class, 'lkn-preview-installments');

    $img = function ($name, $class, $alt = '') use ($lkn_asset) {
        return '<img src="' . esc_url($lkn_asset . $name) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" />';
    };
    $lock = $img('lock.svg', 'field-icon-img', 'Security');
    $cal  = $img('calendar.svg', 'field-icon-img', 'Calendar');
    $key  = $img('key.svg', 'field-icon-img', 'Security Code');
    $ph_attr = function ($field) use ($has_ph, $mode, $tpl, $lkn_ph_val) {
        if (! $has_ph) {
            return '';
        }
        return sprintf(
            ' placeholder="%s" data-lkn-ph-field="%s" data-lkn-mode="%s" data-lkn-template="%s"',
            esc_attr($lkn_ph_val($mode, $tpl, $field)),
            esc_attr($field),
            esc_attr($mode),
            esc_attr($tpl)
        );
    };
    $ph_pencil = function ($field) use ($has_ph, $mode, $tpl, $lkn_pencil) {
        return $has_ph ? $lkn_pencil($mode, $tpl, $field, 'placeholder', 'lkn-edit-btn--ph') : '';
    };

    // Linha de input no formato claro (label acima do input).
    $row = function ($field, $id, $label_class) use ($mode, $tpl, $lkn_edit_label, $ph_attr, $ph_pencil) {
        return $lkn_edit_label($mode, $tpl, $field, $label_class, $id)
            . '<div class="lkn-input-wrap"><input type="text" id="' . esc_attr($id) . '" class="lkn-wc-gateway-cielo-input"' . $ph_attr($field) . ' readonly />' . $ph_pencil($field) . '</div>';
    };
    // Campo no formato Blocks.
    $bx = function ($field, $id) use ($mode, $tpl, $lkn_edit_label, $ph_attr, $ph_pencil) {
        return '<div class="wc-block-components-text-input">'
            . '<input type="text" id="' . esc_attr($id) . '" class="wc-block-components-text-input__input"' . $ph_attr($field) . ' readonly />'
            . $lkn_edit_label($mode, $tpl, $field, '', $id) . $ph_pencil($field)
            . '</div>';
    };

    // Botão "Place order" no formato do checkout em Blocos (Gutenberg), igual ao
    // componente Button do WooCommerce (wc-block-components-button + #sendOrder).
    // O botão tem margin-top próprio, então não há espaçador antes dele.
    $order_button = '<div class="lkn-preview-order">'
        . $lkn_btn($mode, $tpl, 'wc-block-components-button wp-element-button contained', ' id="sendOrder"')
        . '</div>';

    // ---------- Blocks ----------
    if ($blocks) {
        echo '<div id="radio-control-wc-payment-method-options-lkn_cielo_debit__content" class="wc-block-components-radio-control-accordion-content">';
        if ('compact' === $tpl) {
            echo '<div class="lkn-debit-compact-layout">';
            echo '<div class="compact-row compact-row--top">';
            echo '<div class="compact-field compact-field--name">' . $lkn_edit_label($mode, $tpl, 'holder_name', 'compact-label', 'lkn-preview-holder') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-holder" class="compact-input"' . $ph_attr('holder_name') . ' readonly />' . $ph_pencil('holder_name') . '</div></div>';
            echo '<div class="compact-field compact-field--type">' . $ct_label . '<div class="compact-field-wrapper">' . $lkn_select($lkn_card_type_opts, 'compact-select') . '</div></div>';
            echo '</div>';
            echo '<div class="compact-row compact-row--card">';
            echo '<div class="compact-field compact-field--number">' . $lkn_edit_label($mode, $tpl, 'card_number', 'compact-label', 'lkn-preview-number') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-number" class="compact-input"' . $ph_attr('card_number') . ' readonly />' . $ph_pencil('card_number') . '</div></div>';
            echo '<div class="compact-field compact-field--exp">' . $lkn_edit_label($mode, $tpl, 'expiry', 'compact-label', 'lkn-preview-expiry') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-expiry" class="compact-input"' . $ph_attr('expiry') . ' readonly />' . $ph_pencil('expiry') . '<div class="compact-field-icon">' . $cal . '</div></div></div>';
            echo '<div class="compact-field compact-field--cvc">' . $lkn_edit_label($mode, $tpl, 'cvc', 'compact-label', 'lkn-preview-cvc') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-cvc" class="compact-input"' . $ph_attr('cvc') . ' readonly />' . $ph_pencil('cvc') . '<div class="compact-field-icon">' . $key . '</div></div></div>';
            echo '</div>';
            echo '<div class="compact-field compact-field--installments">' . $installments_label . '<div class="compact-field-wrapper">' . $lkn_select($lkn_installments_opts, 'compact-select') . '</div></div>';
            echo $order_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="lkn-cielo-credit-debit-description"><span class="lkn-cielo-credit-debit-description-text lkn-preview-description">' . esc_html($lkn_description) . '</span></div>';
            echo '</div>';
        } elseif ('modern' === $tpl) {
            echo '<div class="lkn-modern-layout"><div class="modern-form-fields">';
            echo '<div class="modern-field">' . $lkn_edit_label($mode, $tpl, 'holder_name', 'field-label', 'lkn-preview-holder') . '<div class="field-wrapper"><input type="text" id="lkn-preview-holder" class="field-input"' . $ph_attr('holder_name') . ' readonly />' . $ph_pencil('holder_name') . '</div></div>';
            echo '<div class="field-group"><div class="modern-field field-half">' . $lkn_edit_label($mode, $tpl, 'card_number', 'field-label', 'lkn-preview-number') . '<div class="field-wrapper"><input type="text" id="lkn-preview-number" class="field-input lkn-card-num"' . $ph_attr('card_number') . ' readonly />' . $ph_pencil('card_number') . '<div class="field-icon">' . $lock . '</div></div></div>';
            echo '<div class="modern-field field-half">' . $ct_label . $lkn_select($lkn_card_type_opts, 'field-select') . '</div></div>';
            echo '<div class="field-group"><div class="modern-field field-half">' . $lkn_edit_label($mode, $tpl, 'expiry', 'field-label', 'lkn-preview-expiry') . '<div class="field-wrapper"><input type="text" id="lkn-preview-expiry" class="field-input lkn-card-exp"' . $ph_attr('expiry') . ' readonly />' . $ph_pencil('expiry') . '<div class="field-icon">' . $cal . '</div></div></div>';
            echo '<div class="modern-field field-half">' . $lkn_edit_label($mode, $tpl, 'cvc', 'field-label', 'lkn-preview-cvc') . '<div class="field-wrapper"><input type="text" id="lkn-preview-cvc" class="field-input lkn-cvv"' . $ph_attr('cvc') . ' readonly />' . $ph_pencil('cvc') . '<div class="field-icon">' . $key . '</div></div></div></div>';
            echo '<div class="modern-field">' . $installments_label . $lkn_select($lkn_installments_opts, 'field-select') . '</div>';
            echo $order_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="lkn-cielo-credit-debit-description"><span class="lkn-cielo-credit-debit-description-text lkn-preview-description">' . esc_html($lkn_description) . '</span></div>';
            echo '</div></div>';
        } else {
            // Blocks Basic: mesma estrutura do checkout real, porém num container
            // neutro (NÃO usar a classe do layout moderno, que traz width:fit-content
            // e o visual de borda/fundo do moderno).
            echo '<div class="lkn-preview-blocks-fields">';
            foreach ($lkn_text_fields as $field) {
                echo $bx($field, 'lkn-preview-' . $field); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            echo '<div class="lkn-credit-debit-card-type-select lkn-credit-debit-card-field">' . $ct_label . $lkn_select($lkn_card_type_opts, 'lkn-cielo-credit-debit-custom-select') . '</div>';
            echo '<div class="lkn-select-type lkn-credit-debit-card-field">' . $installments_label . $lkn_select($lkn_installments_opts, 'lkn-cielo-credit-debit-select-input') . '</div>';
            echo $order_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="lkn-cielo-credit-debit-description"><span class="lkn-cielo-credit-debit-description-text lkn-preview-description">' . esc_html($lkn_description) . '</span></div>';
            echo '</div>';
        }
        echo '</div>';
        return;
    }

    // ---------- Classic ----------
    if ('compact' === $tpl) {
        echo '<fieldset id="wc-lkn_cielo_debit-cc-form" class="wc-credit-card-form wc-payment-form"><div class="cielo-debit-fields-wrapper"><div class="lkn-debit-compact-layout">';
        echo '<div class="compact-row compact-row--top">';
        echo '<div class="compact-field compact-field--name">' . $lkn_edit_label($mode, $tpl, 'holder_name', 'compact-label', 'lkn-preview-holder') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-holder" class="compact-input"' . $ph_attr('holder_name') . ' readonly />' . $ph_pencil('holder_name') . '</div></div>';
        echo '<div class="compact-field compact-field--type">' . $ct_label . '<div class="compact-field-wrapper">' . $lkn_select($lkn_card_type_opts, 'compact-select') . '</div></div>';
        echo '</div>';
        echo '<div class="compact-row compact-row--card">';
        echo '<div class="compact-field compact-field--number">' . $lkn_edit_label($mode, $tpl, 'card_number', 'compact-label', 'lkn-preview-number') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-number" class="compact-input"' . $ph_attr('card_number') . ' readonly />' . $ph_pencil('card_number') . '</div></div>';
        echo '<div class="compact-field compact-field--exp">' . $lkn_edit_label($mode, $tpl, 'expiry', 'compact-label', 'lkn-preview-expiry') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-expiry" class="compact-input"' . $ph_attr('expiry') . ' readonly />' . $ph_pencil('expiry') . '<div class="compact-field-icon">' . $cal . '</div></div></div>';
        echo '<div class="compact-field compact-field--cvc">' . $lkn_edit_label($mode, $tpl, 'cvc', 'compact-label', 'lkn-preview-cvc') . '<div class="compact-field-wrapper"><input type="text" id="lkn-preview-cvc" class="compact-input"' . $ph_attr('cvc') . ' readonly />' . $ph_pencil('cvc') . '<div class="compact-field-icon">' . $key . '</div></div></div>';
        echo '</div>';
        echo '<div class="compact-field compact-field--installments">' . $installments_label . '<div class="compact-field-wrapper">' . $lkn_select($lkn_installments_opts, 'compact-select') . '</div></div>';
        echo '<div class="payment-submit-section">' . $lkn_btn($mode, $tpl, 'cielo-submit-button') . '<p class="submit-description lkn-preview-description">' . esc_html($lkn_description) . '</p></div>';
        echo '</div></div></fieldset>';
        return;
    }

    if ('modern' === $tpl) {
        echo '<fieldset id="wc-lkn_cielo_debit-cc-form" class="wc-credit-card-form wc-payment-form"><div class="cielo-debit-fields-wrapper"><div class="lkn-modern-layout"><div class="modern-form-fields">';
        echo '<div class="modern-field">' . $lkn_edit_label($mode, $tpl, 'holder_name', 'field-label', 'lkn-preview-holder') . '<div class="field-wrapper"><input type="text" id="lkn-preview-holder" class="field-input"' . $ph_attr('holder_name') . ' readonly />' . $ph_pencil('holder_name') . '</div></div>';
        echo '<div class="field-group"><div class="modern-field field-half">' . $lkn_edit_label($mode, $tpl, 'card_number', 'field-label', 'lkn-preview-number') . '<div class="field-wrapper"><input type="text" id="lkn-preview-number" class="field-input lkn-card-num"' . $ph_attr('card_number') . ' readonly />' . $ph_pencil('card_number') . '<div class="field-icon">' . $lock . '</div></div></div>';
        echo '<div class="modern-field field-half">' . $ct_label . $lkn_select($lkn_card_type_opts, 'field-select') . '</div></div>';
        echo '<div class="field-group"><div class="modern-field field-half">' . $lkn_edit_label($mode, $tpl, 'expiry', 'field-label', 'lkn-preview-expiry') . '<div class="field-wrapper"><input type="text" id="lkn-preview-expiry" class="field-input lkn-card-exp"' . $ph_attr('expiry') . ' readonly />' . $ph_pencil('expiry') . '<div class="field-icon">' . $cal . '</div></div></div>';
        echo '<div class="modern-field field-half">' . $lkn_edit_label($mode, $tpl, 'cvc', 'field-label', 'lkn-preview-cvc') . '<div class="field-wrapper"><input type="text" id="lkn-preview-cvc" class="field-input lkn-cvv"' . $ph_attr('cvc') . ' readonly />' . $ph_pencil('cvc') . '<div class="field-icon">' . $key . '</div></div></div></div>';
        echo '<div class="modern-field">' . $installments_label . $lkn_select($lkn_installments_opts, 'field-select') . '</div>';
        echo '<div class="payment-submit-section">' . $lkn_btn($mode, $tpl, 'cielo-submit-button') . '<p class="submit-description lkn-preview-description">' . esc_html($lkn_description) . '</p></div>';
        echo '</div></div></div></fieldset>';
        return;
    }

    // Classic padrão.
    echo '<fieldset id="wc-lkn_cielo_debit-cc-form" class="wc-credit-card-form wc-payment-form">';
    echo '<div class="cielo-debit-fields-wrapper">';
    foreach ($lkn_text_fields as $field) {
        echo '<div class="form-row form-row-wide lkn-preview-textfield">' . $row($field, 'lkn-preview-' . $field, '') . '</div>';
    }
    echo '<div class="form-row form-row-wide">' . $ct_label . '<div class="lkn-input-wrap">' . $lkn_select($lkn_card_type_opts, 'input-select') . '</div></div>';
    echo '<div class="form-row form-row-wide">' . $installments_label . '<div class="lkn-input-wrap">' . $lkn_select($lkn_installments_opts, 'input-select') . '</div></div>';
    echo '</div>';
    echo '<p class="debit-card-description lkn-preview-description">' . esc_html($lkn_description) . '</p>';
    echo '</fieldset>';
};
?>
<div class="lkn-fields-editor__stage">
    <?php foreach (array('blocks', 'classic') as $lkn_mode) : ?>
        <?php foreach ($lkn_templates as $lkn_tpl => $lkn_tpl_label) : ?>
            <div class="lkn-fields-preview lkn-fields-preview--<?php echo esc_attr($lkn_mode . '-' . $lkn_tpl); ?>"
                 data-mode="<?php echo esc_attr($lkn_mode); ?>"
                 data-template="<?php echo esc_attr($lkn_tpl); ?>"
                 hidden>
                <?php $lkn_render($lkn_mode, $lkn_tpl); ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
