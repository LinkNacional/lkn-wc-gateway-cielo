<?php
if (!defined('ABSPATH')) {
    exit();
}
?>

<div id="lknCieloWoocommerceSettingsCard" style="background-image: url('<?php echo esc_url($backgrounds['right']); ?>'), url('<?php echo esc_url($backgrounds['left']); ?>'); display:none;">
    <div id="lknCieloWoocommerceDivLogo">
        <div>
            <?php //phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
            <img src="<?php echo esc_url($logo); ?>" alt="Logo">
            <?php //phpcs:enable ?>
        </div>
        <p><?php echo esc_attr($versions); ?></p>
    </div>
    <div id="lknCieloWoocommerDivContent">
        <div id="lknCieloWoocommerDivLinks">
            <div>
                <a target="_blank" href=<?php echo esc_url('https://www.linknacional.com.br/wordpress/woocommerce/cielo/?utm=plugin'); ?>>
                    <b>•</b><?php esc_html_e('Documentation', 'lkn-wc-gateway-cielo'); ?>
                </a>
                <a target="_blank" href=<?php echo esc_url('https://www.linknacional.com.br/wordpress/planos/?utm=plugin'); ?>>
                    <b>•</b><?php esc_html_e('Hosting', 'lkn-wc-gateway-cielo'); ?>
                </a>
            </div>
            <div>
                <a target="_blank" href=<?php echo esc_url('https://cliente.linknacional.com.br/solicitar/wordpress-woo-gratis/?utm=plugin'); ?>>
                    <b>•</b><?php esc_html_e('WP Plugin', 'lkn-wc-gateway-cielo'); ?>
                </a>
                <a target="_blank" href=<?php echo esc_url('https://www.linknacional.com.br/wordpress/suporte/'); ?>>
                    <b>•</b><?php esc_html_e('Suporte WP', 'lkn-wc-gateway-cielo'); ?>
                </a>
            </div>
        </div>
        <div class="LknWcCieloSupportLinks">
            <div id="lknWcCieloStarsDiv">
                <a target="_blank" href=<?php echo esc_url('https://br.wordpress.org/plugins/lkn-wc-gateway-cielo/#reviews'); ?>>
                    <p><?php esc_html_e('Rate Plugin', 'lkn-wc-gateway-cielo'); ?></p>
                    <div class="LknWcCieloStars">
                        <span class="dashicons dashicons-star-filled lkn-stars"></span>
                        <span class="dashicons dashicons-star-filled lkn-stars"></span>
                        <span class="dashicons dashicons-star-filled lkn-stars"></span>
                        <span class="dashicons dashicons-star-filled lkn-stars"></span>
                        <span class="dashicons dashicons-star-filled lkn-stars"></span>
                    </div>
                </a>
            </div>
            <div class="LknWcCieloContactLinks">
                <a href=<?php echo esc_url('https://chat.whatsapp.com/IjzHhDXwmzGLDnBfOibJKO'); ?> target="_blank">
                    <?php //phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                    <img src="<?php echo esc_url($whatsapp); ?>" alt="Whatsapp Icon" class="LknWcCieloContactIcon">
                    <?php //phpcs:enable ?>
                </a>
                <a href=<?php echo esc_url('https://t.me/wpprobr'); ?> target="_blank">
                    <?php //phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                    <img src="<?php echo esc_url($telegram); ?>" alt="Telegram Icon" class="LknWcCieloContactIcon">
                    <?php //phpcs:enable ?>
                </a>
            </div>
        </div>
    </div>
</div>
