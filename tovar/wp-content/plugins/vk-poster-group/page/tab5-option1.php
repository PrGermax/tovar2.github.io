<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<h2><?php _e('Расширенные настройки плагина  ' . VKPOSTERBASE::NAME_TITLE_PLUGIN_PAGE) ?></h2>
<span class="description">Для сохранения легкости настройки и работы плагина придуман режим с PRO настройками — для тех кому базового функционала мало.</span>
<?php
$vkFun = new VKPOSTERFUNCTION();
$vkposter_prooptions = get_option('vkposter_prooptions');
//print_r($vkposter_prooptions);
?>

<form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>
    <table class="form-table">
        <h3>PRO Настройки</h3>
        <tr valign="top">
            <th scope="row">ПРО?</th>
            <td>
                <input type="checkbox" name="vkposter_prooptions[chekpro]" <?php checked($vkposter_prooptions['chekpro'], 'on', 1); ?>/>
                <span class="description">Включить опции ПРО. Если галка стоит тогда все настройки ниже будут иметь смысл.</span>
            </td>
        </tr>
    </table>
    <fieldset>
        <legend>Опции для Woocommerce</legend>
        <span><?php echo (($vkFun->isWoo()) ? '' : '<div class="notice notice-success"><p>У вас не установлен Woocommerce</p></div>'); ?></span>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Итеграция с Woo</th>
                <td>
                    <input type="checkbox" name="vkposter_prooptions[woo_chek]" <?php checked($vkposter_prooptions['woo_chek'], 'on', 1); ?>/>
                    <span class="description">Включить поддержку Woocommerce.Если галка стоит тогда все настройки ниже будут иметь смысл. Данные настройки всего лишь добавляют указанные опции в запись, которая отправляется на стену VK.COM.</span>
                </td>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Цена</th>
                <td>
                    <input type="checkbox" name="vkposter_prooptions[woo_price]" <?php checked($vkposter_prooptions['woo_price'], 'on', 1); ?>/>
                    <span class="description">Отправлять на стену цену товара.</span>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Метки</th>
                <td>
                    <input type="checkbox" name="vkposter_prooptions[woo_met]" <?php checked($vkposter_prooptions['woo_met'], 'on', 1); ?>/>
                    <span class="description">Отправлять на стену метки товара в виде хэШтэга.</span>
                </td>
            </tr>

        </table>
    </fieldset>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="vkposter_prooptions" />
    <p class="submit">
        <input type="submit" class="btn btn-large btn-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
<?php
