<?php if ( ! defined('ABSPATH') ) { exit; } // Защита от прямого вызова скрипта
function yfym_export_page() { 
 /* wp_deregister_style(); */ ?>
	<style>.wp-admin select {padding: 2px !important;} .woocommerce table.form-table input[type="text"], .woocommerce table.form-table select, .woocommerce table.form-table input[type="number"] {width: 240px !important;}</style>	
 <?php
 /* получить все атрибуты вукомерца */
 function get_attributes() {
	$result = array();
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    if (count($attribute_taxonomies) > 0) {
	 $i = 0;
     foreach($attribute_taxonomies as $one_tax ) {
		/**
		* $one_tax->attribute_id => 6
		* $one_tax->attribute_name] => слаг (на инглише или русском)
		* $one_tax->attribute_label] => Еще один атрибут (это как раз название)
		* $one_tax->attribute_type] => select 
		* $one_tax->attribute_orderby] => menu_order
		* $one_tax->attribute_public] => 0			
		*/
		$result[$i]['id'] = $one_tax->attribute_id;
		$result[$i]['name'] = $one_tax->attribute_label;
		$i++;
     }
    }
	return $result;
 }
 /* end получить все атрибуты вукомерца */
 
if (is_multisite()) { 
 $status_sborki = (int)get_blog_option(get_current_blog_id(), 'yfym_status_sborki');
 
 if (isset($_REQUEST['yfym_submit_send_stat'])) {
	// пользователь нажал кнопку "отправки статистики работы плагина"
	$mail_content = "status_sborki: ".$status_sborki. PHP_EOL;
	$mail_content .= "УРЛ: ".get_site_url(). PHP_EOL;
	$mail_content .= "УРЛ YML-фида: ".urldecode(get_blog_option(get_current_blog_id(), 'yfym_file_url')). PHP_EOL;
	$mail_content .= "Временный файл: ".urldecode(get_blog_option(get_current_blog_id(), 'yfym_file_file')). PHP_EOL;	
	$mail_content .= "Режим мультисайта: включен". PHP_EOL;
	$mail_content .= "Версия плагина: 1.4.3". PHP_EOL;;
	$mail_content .= "Версия WP: ".get_bloginfo('version'). PHP_EOL;
	$woo_version = yfym_get_woo_version_number();
	$mail_content .= "Версия WC: ".$woo_version. PHP_EOL;
	if (isset($_REQUEST['yfym_its_ok'])) {
		$mail_content .= PHP_EOL ."Помог ли плагин: ".sanitize_text_field($_REQUEST['yfym_its_ok']);
	}
	if (isset($_POST['yfym_email'])) {		
		$mail_content .= PHP_EOL ."Почта: ".sanitize_text_field($_POST['yfym_email']);
	}
	if (isset($_POST['yfym_message'])) {		
		$mail_content .= PHP_EOL ."Сообщение: ".sanitize_text_field($_POST['yfym_message']);
	}	
	
	$yfym_errors = get_blog_option(get_current_blog_id(), 'yfym_errors');
	$mail_content .= PHP_EOL ."Ошибки: ".$yfym_errors;
	
	$argsp = array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1 );
	$products = new WP_Query($argsp);
	$vsegotovarov = $products->found_posts;
	$mail_content .= PHP_EOL ."Число товаров на выгрузку: ". $vsegotovarov;	
	
	wp_mail('pt070@yandex.ru', 'Cтатистика о работе плагина YML for WP', $mail_content);
 } 
 
 if (isset($_REQUEST['yfym_submit_reset'])) {
  if (!empty($_POST) && check_admin_referer('yfym_nonce_action','yfym_nonce_field')) { 
	delete_blog_option(get_current_blog_id(), 'yfym_version');
	delete_blog_option(get_current_blog_id(), 'yfym_status_cron');
	delete_blog_option(get_current_blog_id(), 'yfym_whot_export');
	delete_blog_option(get_current_blog_id(), 'yfym_skip_missing_products');
	delete_blog_option(get_current_blog_id(), 'yfym_skip_backorders_products');
	delete_blog_option(get_current_blog_id(), 'yfym_ufup');	
	delete_blog_option(get_current_blog_id(), 'yfym_status_sborki');
	delete_blog_option(get_current_blog_id(), 'yfym_date_sborki');
	delete_blog_option(get_current_blog_id(), 'yfym_type_sborki');
	delete_blog_option(get_current_blog_id(), 'yfym_vendor');
	delete_blog_option(get_current_blog_id(), 'yfym_model');
	delete_blog_option(get_current_blog_id(), 'yfym_params_arr');
	delete_blog_option(get_current_blog_id(), 'yfym_add_in_name_arr');		
	delete_blog_option(get_current_blog_id(), 'yfym_no_group_id_arr');
	delete_blog_option(get_current_blog_id(), 'yfym_product_tag_arr');
	delete_blog_option(get_current_blog_id(), 'yfym_file_url');
	delete_blog_option(get_current_blog_id(), 'yfym_file_file');
	delete_blog_option(get_current_blog_id(), 'yfym_magazin_type');
	delete_blog_option(get_current_blog_id(), 'yfym_pickup');
	delete_blog_option(get_current_blog_id(), 'yfym_store');
	delete_blog_option(get_current_blog_id(), 'yfym_delivery');
	delete_blog_option(get_current_blog_id(), 'yfym_delivery_options');
	delete_blog_option(get_current_blog_id(), 'yfym_delivery_cost');
	delete_blog_option(get_current_blog_id(), 'yfym_delivery_days');
	delete_blog_option(get_current_blog_id(), 'yfym_order_before');		
	delete_blog_option(get_current_blog_id(), 'yfym_sales_notes_cat');
	delete_blog_option(get_current_blog_id(), 'yfym_sales_notes');
	delete_blog_option(get_current_blog_id(), 'yfym_price_from');
	delete_blog_option(get_current_blog_id(), 'yfym_oldprice');
	delete_blog_option(get_current_blog_id(), 'yfym_desc');
	delete_blog_option(get_current_blog_id(), 'yfym_barcode');
	delete_blog_option(get_current_blog_id(), 'yfym_expiry');
	delete_blog_option(get_current_blog_id(), 'yfym_downloadable');	
	delete_blog_option(get_current_blog_id(), 'yfym_age');
	delete_blog_option(get_current_blog_id(), 'yfym_country_of_origin');
	delete_blog_option(get_current_blog_id(), 'yfym_manufacturer_warranty');
	delete_blog_option(get_current_blog_id(), 'yfym_shop_name');
	delete_blog_option(get_current_blog_id(), 'yfym_company_name');
	delete_blog_option(get_current_blog_id(), 'yfym_adult');
	delete_blog_option(get_current_blog_id(), 'yfym_step_export');
	delete_blog_option(get_current_blog_id(), 'yfym_errors');	

	add_blog_option(get_current_blog_id(),'yfym_errors', '');
	add_blog_option(get_current_blog_id(),'yfym_version', '1.4.3');
	add_blog_option(get_current_blog_id(),'yfym_status_cron', 'off');
	add_blog_option(get_current_blog_id(),'yfym_whot_export', 'all'); // что выгружать (все или там где галка)
	add_blog_option(get_current_blog_id(),'yfym_skip_missing_products', '0');
	add_blog_option(get_current_blog_id(),'yfym_skip_backorders_products', '0');
	add_blog_option(get_current_blog_id(),'yfym_ufup', '0');		
	add_blog_option(get_current_blog_id(),'yfym_status_sborki', '-1'); // статус сборки файла
	add_blog_option(get_current_blog_id(),'yfym_date_sborki', 'unknown'); // дата последней сборки
	add_blog_option(get_current_blog_id(),'yfym_type_sborki', 'yml'); // тип собираемого файла yml или xls	
	add_blog_option(get_current_blog_id(),'yfym_vendor', 'none'); // тип плагина магазина
	add_blog_option(get_current_blog_id(),'yfym_model', 'none'); // атрибут model магазина	
	add_blog_option(get_current_blog_id(),'yfym_params_arr', '');
	add_blog_option(get_current_blog_id(),'yfym_add_in_name_arr', '');	
	add_blog_option(get_current_blog_id(),'yfym_no_group_id_arr', '');	
	add_blog_option(get_current_blog_id(),'yfym_product_tag_arr', ''); // id меток таксономии product_tag
	add_blog_option(get_current_blog_id(),'yfym_file_url', ''); // урл до файла
	add_blog_option(get_current_blog_id(),'yfym_file_file', ''); // 
	add_blog_option(get_current_blog_id(),'yfym_magazin_type', 'woocommerce'); // тип плагина магазина 
	add_blog_option(get_current_blog_id(),'yfym_pickup', 'true');
	add_blog_option(get_current_blog_id(),'yfym_store', 'false');
	add_blog_option(get_current_blog_id(),'yfym_delivery', 'false');
	add_blog_option(get_current_blog_id(),'yfym_delivery_options', '0');
	add_blog_option(get_current_blog_id(),'yfym_delivery_cost', '0');
	add_blog_option(get_current_blog_id(),'yfym_delivery_days', '32');
	add_blog_option(get_current_blog_id(),'yfym_order_before', '');
	add_blog_option(get_current_blog_id(),'yfym_sales_notes_cat', 'off');
	add_blog_option(get_current_blog_id(),'yfym_sales_notes', '');
	add_blog_option(get_current_blog_id(),'yfym_price_from', 'no'); // разрешить "цена от"	
	add_blog_option(get_current_blog_id(),'yfym_oldprice', 'no');
	
	add_blog_option(get_current_blog_id(),'yfym_desc', 'full');
	add_blog_option(get_current_blog_id(),'yfym_barcode', 'off');
	add_blog_option(get_current_blog_id(),'yfym_expiry', 'off');
	add_blog_option(get_current_blog_id(),'yfym_downloadable', 'off');
	add_blog_option(get_current_blog_id(),'yfym_age', 'off');	
	add_blog_option(get_current_blog_id(),'yfym_country_of_origin', 'off');
	add_blog_option(get_current_blog_id(),'yfym_manufacturer_warranty', 'off');	
	add_blog_option(get_current_blog_id(),'yfym_adult', 'no');
	
	add_blog_option(get_current_blog_id(), 'yfym_step_export', '500');
	
	// $res = get_site_url();
	$blog_title = get_bloginfo('name');
	add_blog_option(get_current_blog_id(),'yfym_shop_name', $blog_title);
	add_blog_option(get_current_blog_id(),'yfym_company_name', $blog_title);	
  }
 }
 if (isset($_REQUEST['yfym_submit_action'])) {
  if (!empty($_POST) && check_admin_referer('yfym_nonce_action','yfym_nonce_field')) {
	do_action('yfym_prepend_submit_action');

	if (isset($_POST['yfym_skip_missing_products'])) {
		update_blog_option(get_current_blog_id(), 'yfym_skip_missing_products', sanitize_text_field($_POST['yfym_skip_missing_products']));
	} else {
		update_blog_option(get_current_blog_id(), 'yfym_skip_missing_products', '0');
	}
	if (isset($_POST['yfym_skip_backorders_products'])) {
		update_blog_option(get_current_blog_id(), 'yfym_skip_backorders_products', sanitize_text_field($_POST['yfym_skip_backorders_products']));
	} else {
		update_blog_option(get_current_blog_id(), 'yfym_skip_backorders_products', '0');
	}	
	if (isset($_POST['yfym_ufup'])) {
		update_blog_option(get_current_blog_id(), 'yfym_ufup', sanitize_text_field($_POST['yfym_ufup']));
	} else {
		update_blog_option(get_current_blog_id(), 'yfym_ufup', '0');
	}
	
	update_blog_option(get_current_blog_id(), 'yfym_desc', sanitize_text_field($_POST['yfym_desc']));
	update_blog_option(get_current_blog_id(), 'yfym_barcode', sanitize_text_field($_POST['yfym_barcode']));
	update_blog_option(get_current_blog_id(), 'yfym_expiry', sanitize_text_field($_POST['yfym_expiry']));
	update_blog_option(get_current_blog_id(), 'yfym_downloadable', sanitize_text_field($_POST['yfym_downloadable']));
	update_blog_option(get_current_blog_id(), 'yfym_age', sanitize_text_field($_POST['yfym_age']));
	update_blog_option(get_current_blog_id(), 'yfym_country_of_origin', sanitize_text_field($_POST['yfym_country_of_origin']));
	update_blog_option(get_current_blog_id(), 'yfym_manufacturer_warranty', sanitize_text_field($_POST['yfym_manufacturer_warranty']));
  
	update_blog_option(get_current_blog_id(), 'yfym_whot_export', sanitize_text_field($_POST['yfym_whot_export']));
	update_blog_option(get_current_blog_id(), 'yfym_pickup', sanitize_text_field($_POST['yfym_pickup']));
	update_blog_option(get_current_blog_id(), 'yfym_sales_notes_cat', sanitize_text_field($_POST['yfym_sales_notes_cat']));
	update_blog_option(get_current_blog_id(), 'yfym_sales_notes', sanitize_text_field($_POST['yfym_sales_notes']));	
	update_blog_option(get_current_blog_id(), 'yfym_delivery', sanitize_text_field($_POST['yfym_delivery']));
	update_blog_option(get_current_blog_id(), 'yfym_store', sanitize_text_field($_POST['yfym_store']));	
	
	$yfym_delivery_cost = (int)sanitize_text_field($_POST['yfym_delivery_cost']);
	if ($yfym_delivery_cost >= 0 ) {
		update_blog_option(get_current_blog_id(), 'yfym_delivery_cost', $yfym_delivery_cost); 
	}
	
	if (isset($_POST['yfym_delivery_options'])) {
		update_blog_option(get_current_blog_id(), 'yfym_delivery_options', sanitize_text_field($_POST['yfym_delivery_options']));
		} else {
		update_blog_option(get_current_blog_id(), 'yfym_delivery_options', '0');
	}
	update_blog_option(get_current_blog_id(), 'yfym_delivery_days', sanitize_text_field($_POST['yfym_delivery_days']));	
	update_blog_option(get_current_blog_id(), 'yfym_order_before', sanitize_text_field($_POST['yfym_order_before']));	
	update_blog_option(get_current_blog_id(), 'yfym_shop_name', sanitize_text_field($_POST['yfym_shop_name']));
	update_blog_option(get_current_blog_id(), 'yfym_company_name', sanitize_text_field($_POST['yfym_company_name']));
	update_blog_option(get_current_blog_id(), 'yfym_adult', sanitize_text_field($_POST['yfym_adult']));	
	update_blog_option(get_current_blog_id(), 'yfym_vendor', sanitize_text_field($_POST['yfym_vendor']));
	update_blog_option(get_current_blog_id(), 'yfym_model', sanitize_text_field($_POST['yfym_model']));
	if (isset($_POST['yfym_params_arr'])) {
		update_blog_option(get_current_blog_id(), 'yfym_params_arr', serialize($_POST['yfym_params_arr']));
	} else {update_blog_option(get_current_blog_id(), 'yfym_params_arr', '');}
	if (isset($_POST['yfym_add_in_name_arr'])) {
		update_blog_option(get_current_blog_id(), 'yfym_add_in_name_arr', serialize($_POST['yfym_add_in_name_arr']));
	} else {update_blog_option(get_current_blog_id(), 'yfym_add_in_name_arr', '');}
	if (isset($_POST['yfym_no_group_id_arr'])) {
		update_blog_option(get_current_blog_id(), 'yfym_no_group_id_arr', serialize($_POST['yfym_no_group_id_arr']));
	} else {update_blog_option(get_current_blog_id(), 'yfym_no_group_id_arr', '');}
	update_blog_option(get_current_blog_id(), 'yfym_price_from', sanitize_text_field($_POST['yfym_price_from']));	
	update_blog_option(get_current_blog_id(), 'yfym_oldprice', sanitize_text_field($_POST['yfym_oldprice']));
	update_blog_option(get_current_blog_id(), 'yfym_step_export', sanitize_text_field($_POST['yfym_step_export']));	
	
	$arr_maybe = array("off", "hourly", "six_hours", "twicedaily", "daily");
	$yfym_run_cron = sanitize_text_field($_POST['yfym_run_cron']);
	if (in_array($yfym_run_cron, $arr_maybe)) {		
		update_blog_option(get_current_blog_id(), 'yfym_status_cron', $yfym_run_cron);
		if ($yfym_run_cron == 'off') {
			// отключаем крон
			wp_clear_scheduled_hook('yfym_cron_period');
			update_blog_option(get_current_blog_id(), 'yfym_status_cron', 'off');
			
			wp_clear_scheduled_hook('yfym_cron_sborki');
			update_blog_option(get_current_blog_id(), 'yfym_status_sborki', '-1');
		} else {
			$recurrence = $yfym_run_cron;
			wp_clear_scheduled_hook('yfym_cron_period');
			wp_schedule_event( time(), $recurrence, 'yfym_cron_period');
			error_log('yfym_cron_period внесен в список заданий. line 150', 0);
		}
	} else {
		error_log('Крон '.$yfym_run_cron.' не зарегистрирован. line 153', 0);
	}
  }
 } 
 
 $yfym_status_cron = get_blog_option(get_current_blog_id(), 'yfym_status_cron');
 $yfym_whot_export = get_blog_option(get_current_blog_id(), 'yfym_whot_export'); 
 $yfym_desc = get_blog_option(get_current_blog_id(), 'yfym_desc');
 $yfym_shop_name = get_blog_option(get_current_blog_id(), 'yfym_shop_name');
 $yfym_company_name = get_blog_option(get_current_blog_id(), 'yfym_company_name');
 $yfym_adult = get_blog_option(get_current_blog_id(), 'yfym_adult'); 	
 $yfym_step_export = get_blog_option(get_current_blog_id(), 'yfym_step_export'); 
 $yfym_no_group_id_arr = unserialize(get_blog_option(get_current_blog_id(), 'yfym_no_group_id_arr'));
 $yfym_skip_missing_products = get_blog_option(get_current_blog_id(), 'yfym_skip_missing_products');
 $yfym_skip_backorders_products = get_blog_option(get_current_blog_id(), 'yfym_skip_backorders_products');
 $yfym_ufup = get_blog_option(get_current_blog_id(), 'yfym_ufup');
 $yfym_delivery = get_blog_option(get_current_blog_id(), 'yfym_delivery'); 
 $yfym_delivery_cost = get_blog_option(get_current_blog_id(), 'yfym_delivery_cost'); 
 $yfym_delivery_days = get_blog_option(get_current_blog_id(), 'yfym_delivery_days');
 $yfym_order_before = get_blog_option(get_current_blog_id(), 'yfym_order_before');
 $yfym_delivery_options = get_blog_option(get_current_blog_id(), 'yfym_delivery_options');
 $yfym_pickup = get_blog_option(get_current_blog_id(), 'yfym_pickup'); 
 $yfym_price_from = get_blog_option(get_current_blog_id(), 'yfym_price_from'); 
 $yfym_oldprice = get_blog_option(get_current_blog_id(), 'yfym_oldprice'); 
 $barcode = get_blog_option(get_current_blog_id(), 'yfym_barcode');
 $expiry = get_blog_option(get_current_blog_id(), 'yfym_expiry');
 $downloadable = get_blog_option(get_current_blog_id(), 'yfym_downloadable');
 $age = get_blog_option(get_current_blog_id(), 'yfym_age');
 
 $model = get_blog_option(get_current_blog_id(), 'yfym_model'); 			 
 $vendor = get_blog_option(get_current_blog_id(), 'yfym_vendor'); 			 
 $country_of_origin = get_blog_option(get_current_blog_id(), 'yfym_country_of_origin'); 
 $manufacturer_warranty = get_blog_option(get_current_blog_id(), 'yfym_manufacturer_warranty'); 
 $sales_notes_cat = get_blog_option(get_current_blog_id(), 'yfym_sales_notes_cat');
 $sales_notes = htmlspecialchars(get_blog_option(get_current_blog_id(), 'yfym_sales_notes'));
 $params_arr = unserialize(get_blog_option(get_current_blog_id(), 'yfym_params_arr'));
 $add_in_name_arr = unserialize(get_blog_option(get_current_blog_id(), 'yfym_add_in_name_arr'));
 $yfym_store = get_blog_option(get_current_blog_id(), 'yfym_store');
 $yfym_file_url = urldecode(get_blog_option(get_current_blog_id(), 'yfym_file_url'));
 $yfym_date_sborki = get_blog_option(get_current_blog_id(), 'yfym_date_sborki'); 
 
} else { /* ------- обычный сайт ------- */
	
 $status_sborki = (int)get_option( 'yfym_status_sborki');
 if (isset($_REQUEST['yfym_submit_send_stat'])) {
	// пользователь нажал кнопку "отправки статистики работы плагина"
	$mail_content = "status_sborki: ".$status_sborki. PHP_EOL;
	$mail_content .= "УРЛ: ".get_site_url(). PHP_EOL;
	$mail_content .= "УРЛ YML-фида: ".urldecode(get_option('yfym_file_url')). PHP_EOL;
	$mail_content .= "Режим мультисайта: отключен". PHP_EOL;
	$mail_content .= "Временный файл: ".urldecode(get_option('yfym_file_file')). PHP_EOL;
	$mail_content .= "Версия плагина: 1.4.3". PHP_EOL;;
	$mail_content .= "Версия WP: ".get_bloginfo('version'). PHP_EOL;
	$woo_version = yfym_get_woo_version_number();
	$mail_content .= "Версия WC: ".$woo_version. PHP_EOL;
	if (isset($_POST['yfym_its_ok'])) {
		$mail_content .= PHP_EOL ."Помог ли плагин: ".sanitize_text_field($_POST['yfym_its_ok']);
	}
	if (isset($_POST['yfym_email'])) {		
		$mail_content .= PHP_EOL ."Почта: ".sanitize_text_field($_POST['yfym_email']);
	}
	if (isset($_POST['yfym_message'])) {		
		$mail_content .= PHP_EOL ."Сообщение: ".sanitize_text_field($_POST['yfym_message']);
	}
	$yfym_errors = get_option('yfym_errors');
	$mail_content .= PHP_EOL ."Ошибки: ".$yfym_errors;
	
	$argsp = array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1 );
	$products = new WP_Query($argsp);
	$vsegotovarov = $products->found_posts;
	$mail_content .= PHP_EOL ."Число товаров на выгрузку: ". $vsegotovarov;	
	wp_mail('pt070@yandex.ru', 'Cтатистика о работе плагина YML for WP', $mail_content);
 }
 
 if (isset($_REQUEST['yfym_submit_reset'])) {
  if (!empty($_POST) && check_admin_referer('yfym_nonce_action','yfym_nonce_field')) { 
	delete_option('yfym_version');
	delete_option('yfym_status_cron');
	delete_option('yfym_whot_export');
	delete_option('yfym_skip_missing_products');
	delete_option('yfym_skip_backorders_products');
	delete_option('yfym_ufup');	
	delete_option('yfym_status_sborki');
	delete_option('yfym_date_sborki');
	delete_option('yfym_type_sborki');
	delete_option('yfym_vendor');
	delete_option('yfym_model');
	delete_option('yfym_params_arr');
	delete_option('yfym_add_in_name_arr');
	delete_option('yfym_no_group_id_arr');
	delete_option('yfym_product_tag_arr');
	delete_option('yfym_file_url');
	delete_option('yfym_file_file');
	delete_option('yfym_magazin_type');
	delete_option('yfym_pickup');
	delete_option('yfym_store');
	delete_option('yfym_delivery');
	delete_option('yfym_delivery_cost');
	delete_option('yfym_delivery_days');
	delete_option('yfym_order_before');
	delete_option('yfym_delivery_options');
	delete_option('yfym_sales_notes_cat');
	delete_option('yfym_sales_notes');
	delete_option('yfym_price_from');
	delete_option('yfym_oldprice');
	delete_option('yfym_desc');
	delete_option('yfym_barcode');
	delete_option('yfym_expiry');
	delete_option('yfym_downloadable');
	delete_option('yfym_age');
	delete_option('yfym_country_of_origin');
	delete_option('yfym_manufacturer_warranty');
	delete_option('yfym_shop_name');
	delete_option('yfym_company_name');
	delete_option('yfym_adult');
	delete_option('yfym_step_export');
	delete_option('yfym_errors');
	
	add_option('yfym_errors', '');
	add_option('yfym_version', '1.4.3');
	add_option('yfym_status_cron', 'off');
	add_option('yfym_whot_export', 'all'); // что выгружать (все или там где галка)
	add_option('yfym_skip_missing_products', '0');
	add_option('yfym_skip_backorders_products', '0');
	add_option('yfym_ufup', '0');
	add_option('yfym_status_sborki', '-1'); // статус сборки файла
	add_option('yfym_date_sborki', 'unknown'); // дата последней сборки
	add_option('yfym_type_sborki', 'yml'); // тип собираемого файла yml или xls	
	add_option('yfym_vendor', 'none'); // тип плагина магазина
	add_option('yfym_model', 'none'); // атрибут model магазина	
	add_option('yfym_params_arr', '');
	add_option('yfym_add_in_name_arr', '');
	add_option('yfym_no_group_id_arr', '');
	add_option('yfym_product_tag_arr', ''); // id меток таксономии product_tag
	add_option('yfym_file_url', ''); // урл до файла
	add_option('yfym_file_file', ''); // 
	add_option('yfym_magazin_type', 'woocommerce'); // тип плагина магазина 
	add_option('yfym_pickup', 'true');
	add_option('yfym_store', 'false');
	add_option('yfym_delivery', 'false');
	add_option('yfym_delivery_options', '0');
	add_option('yfym_delivery_cost', '0');
	add_option('yfym_delivery_days', '32');
	add_option('yfym_order_before', '');
	add_option('yfym_sales_notes_cat', 'off');
	add_option('yfym_sales_notes', '');
	add_option('yfym_price_from', 'no'); // разрешить "цена от"	
	add_option('yfym_oldprice', 'no');
	add_option('yfym_desc', 'full');
	add_option('yfym_barcode', 'off');	
	add_option('yfym_expiry', 'off');
	add_option('yfym_downloadable', 'off');
	add_option('yfym_age', 'off');
	add_option('yfym_country_of_origin', 'off');
	add_option('yfym_manufacturer_warranty', 'off');
	add_option('yfym_adult', 'no');
	
	add_option('yfym_step_export', '500');
	
	// $res = get_site_url();
	$blog_title = get_bloginfo('name');
	add_option('yfym_shop_name', $blog_title);
	add_option('yfym_company_name', $blog_title);	
  }
 }
 if (isset($_REQUEST['yfym_submit_action'])) {
  if (!empty($_POST) && check_admin_referer('yfym_nonce_action','yfym_nonce_field')) {
	do_action('yfym_prepend_submit_action');
 
	if (isset($_POST['yfym_skip_missing_products'])) {
		update_option('yfym_skip_missing_products', sanitize_text_field($_POST['yfym_skip_missing_products']));
	} else {
		update_option('yfym_skip_missing_products', '0');
	}
	if (isset($_POST['yfym_skip_backorders_products'])) {
		update_option('yfym_skip_backorders_products', sanitize_text_field($_POST['yfym_skip_backorders_products']));
	} else {
		update_option('yfym_skip_backorders_products', '0');
	}	
	if (isset($_POST['yfym_ufup'])) {
		update_option('yfym_ufup', sanitize_text_field($_POST['yfym_ufup']));
	} else {
		update_option('yfym_ufup', '0');
	} 
	update_option('yfym_desc', sanitize_text_field($_POST['yfym_desc']));
	update_option('yfym_barcode', sanitize_text_field($_POST['yfym_barcode']));
	update_option('yfym_expiry', sanitize_text_field($_POST['yfym_expiry']));
	update_option('yfym_downloadable', sanitize_text_field($_POST['yfym_downloadable']));
	update_option('yfym_age', sanitize_text_field($_POST['yfym_age']));
	update_option('yfym_country_of_origin', sanitize_text_field($_POST['yfym_country_of_origin']));
	update_option('yfym_manufacturer_warranty', sanitize_text_field($_POST['yfym_manufacturer_warranty']));
  
	update_option('yfym_whot_export', sanitize_text_field($_POST['yfym_whot_export']));
	update_option('yfym_pickup', sanitize_text_field($_POST['yfym_pickup']));
	
	update_option('yfym_sales_notes_cat', sanitize_text_field($_POST['yfym_sales_notes_cat']));		
	update_option('yfym_sales_notes', sanitize_text_field($_POST['yfym_sales_notes']));	
	update_option('yfym_delivery', sanitize_text_field($_POST['yfym_delivery']));
	update_option('yfym_store', sanitize_text_field($_POST['yfym_store']));	

	
	update_option('yfym_order_before', sanitize_text_field($_POST['yfym_order_before']));	
	
	$yfym_delivery_cost = (int)sanitize_text_field($_POST['yfym_delivery_cost']);
	if ($yfym_delivery_cost >= 0 ) {
		update_option('yfym_delivery_cost', $yfym_delivery_cost); 
	}
	
	update_option('yfym_delivery_days', sanitize_text_field($_POST['yfym_delivery_days']));	
	if (isset($_POST['yfym_delivery_options'])) {
		update_option('yfym_delivery_options', sanitize_text_field($_POST['yfym_delivery_options']));
	} else {
		update_option('yfym_delivery_options', '0');
	}
	
	update_option('yfym_shop_name', sanitize_text_field($_POST['yfym_shop_name']));
	update_option('yfym_company_name', sanitize_text_field($_POST['yfym_company_name']));
	update_option('yfym_adult', sanitize_text_field($_POST['yfym_adult']));	
	update_option('yfym_vendor', sanitize_text_field($_POST['yfym_vendor']));
	update_option('yfym_model', sanitize_text_field($_POST['yfym_model']));
	if (isset($_POST['yfym_params_arr'])) {
		update_option('yfym_params_arr', serialize($_POST['yfym_params_arr']));
	} else {update_option('yfym_params_arr', '');}
	if (isset($_POST['yfym_add_in_name_arr'])) {
		update_option('yfym_add_in_name_arr', serialize($_POST['yfym_add_in_name_arr']));
		} else {update_option('yfym_add_in_name_arr', '');}	
	if (isset($_POST['yfym_no_group_id_arr'])) {
		update_option('yfym_no_group_id_arr', serialize($_POST['yfym_no_group_id_arr']));
	} else {update_option('yfym_no_group_id_arr', '');}	
	update_option('yfym_price_from', sanitize_text_field($_POST['yfym_price_from']));
	update_option('yfym_oldprice', sanitize_text_field($_POST['yfym_oldprice']));
	update_option('yfym_step_export', sanitize_text_field($_POST['yfym_step_export']));
	
	
	$arr_maybe = array("off", "hourly", "six_hours", "twicedaily", "daily");
	$yfym_run_cron = sanitize_text_field($_POST['yfym_run_cron']);
	if (in_array($yfym_run_cron, $arr_maybe)) {		
		update_option( 'yfym_status_cron', $yfym_run_cron);
		if ($yfym_run_cron == 'off') {
			// отключаем крон
			wp_clear_scheduled_hook('yfym_cron_period');
			update_option( 'yfym_status_cron', 'off');
			
			wp_clear_scheduled_hook('yfym_cron_sborki');
			update_option( 'yfym_status_sborki', '-1');
		} else {
			$recurrence = $yfym_run_cron;
			wp_clear_scheduled_hook('yfym_cron_period');
			wp_schedule_event( time(), $recurrence, 'yfym_cron_period');
			error_log('yfym_cron_period внесен в список заданий. line 624', 0);
		}
	} else {
		error_log('Крон '.$yfym_run_cron.' не зарегистрирован. line 627', 0);
	}
  }
 } 
 

 $yfym_status_cron = get_option('yfym_status_cron');
 $yfym_whot_export = get_option('yfym_whot_export'); 
 $yfym_desc = get_option('yfym_desc');
 $yfym_shop_name = get_option('yfym_shop_name');
 $yfym_company_name = get_option('yfym_company_name');
 $yfym_adult = get_option('yfym_adult'); 	
 $yfym_step_export = get_option('yfym_step_export'); 
 $yfym_no_group_id_arr = unserialize(get_option('yfym_no_group_id_arr')); 
 $yfym_skip_missing_products = get_option('yfym_skip_missing_products');
 $yfym_skip_backorders_products = get_option('yfym_skip_backorders_products');
 $yfym_ufup = get_option('yfym_ufup'); 
 $yfym_delivery = get_option('yfym_delivery'); 
 $yfym_delivery_options = get_option('yfym_delivery_options');
 $yfym_delivery_cost = get_option('yfym_delivery_cost'); 
 $yfym_delivery_days = get_option('yfym_delivery_days'); 
 $yfym_order_before = get_option('yfym_order_before');
 $yfym_pickup = get_option('yfym_pickup'); 
 $yfym_price_from = get_option('yfym_price_from'); 
 $yfym_oldprice = get_option('yfym_oldprice'); 
 $barcode = get_option('yfym_barcode'); 
 $expiry = get_option('yfym_expiry'); 
 $downloadable = get_option('yfym_downloadable');
 $age = get_option('yfym_age');
 $model = get_option('yfym_model'); 			 
 $vendor = get_option('yfym_vendor'); 			 
 $country_of_origin = get_option('yfym_country_of_origin'); 
 $manufacturer_warranty = get_option('yfym_manufacturer_warranty'); 
 $sales_notes_cat = get_option('yfym_sales_notes_cat');
 $sales_notes = htmlspecialchars(get_option('yfym_sales_notes'));
 $params_arr = unserialize(get_option('yfym_params_arr'));
 $add_in_name_arr = unserialize(get_option('yfym_add_in_name_arr')); 
 $yfym_store = get_option('yfym_store');
 $yfym_file_url = urldecode(get_option('yfym_file_url'));
 $yfym_date_sborki = get_option('yfym_date_sborki');
} ?>
 <div class="wrap">
  <h1><?php _e('Exporter Yandex Market', 'yfym'); ?></h1>
 	<?php $woo_version = yfym_get_woo_version_number();
	if ($woo_version <= 3.0 ) {
		print '<div class="notice notice-error is-dismissible"><p>'. __('For the plugin to function correctly, you need a version of WooCommerce 3.0 and higher! You have version ', 'yfym'). $woo_version . __(' installed. Please, update WooCommerce', 'yfym'). '! <a href="https://icopydoc.ru/minimalnye-trebovaniya-dlya-raboty-yml-for-yandex-market/">'. __('Learn More', 'yfym'). '</a>.</p></div>';		
	}
	/* if (defined('ALTERNATE_WP_CRON')) {
	 if (ALTERNATE_WP_CRON == true) {
		print '<div class="notice notice-error is-dismissible"><p>'. __('The plugin does not work correctly because you turned off the CRON with the help of the ', 'yfym'). 'ALTERNATE_WP_CRON.</p></div>';	
	 }
	} */
	if (defined('DISABLE_WP_CRON')) {
	 if (DISABLE_WP_CRON == true) {
		print '<div class="notice notice-error is-dismissible"><p>'. __('The plugin does not work correctly because you turned off the CRON with the help of the ', 'yfym'). 'DISABLE_WP_CRON.</p></div>';
	 }
	}	   
	if (!is_plugin_active('yml-for-yandex-market-pro/yml-for-yandex-market-pro.php')) {
		print '<div class="notice notice-warning is-dismissible"><p>'. __('Go to Yml for Yandex Market Pro and', 'yfym'). ' <span style="color: red;">'. __('keep the advertising budget', 'yfym'). '</span> '. __('on Yandex', 'yfym'). '! <a href="'.ADMIN_COOKIE_PATH.'/admin.php?page=yfymextensions">'. __('Learn More', 'yfym'). '</a>.</p></div>';
	} ?>   
  <div id="dashboard-widgets-wrap"><div id="dashboard-widgets" class="metabox-holder">	
	<div id="postbox-container-1" class="postbox-container"><div class="meta-box-sortables" >
     <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" enctype="multipart/form-data">	
	 <div class="postbox">
	   <div class="inside">
	    <h1><?php _e('Main parameters', 'yfym'); ?></h1>
		<table class="form-table"><tbody>
		 <tr>
			<th scope="row"><label for="yfym_run_cron"><?php _e('Automatic file creation', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_run_cron">	
					<option value="off" <?php selected( $yfym_status_cron, 'off' ); ?>><?php _e( 'Off', 'yfym'); ?></option>
					<option value="hourly" <?php selected( $yfym_status_cron, 'hourly' )?> ><?php _e('Hourly', 'yfym'); ?></option>
					<option value="six_hours" <?php selected( $yfym_status_cron, 'six_hours' ); ?> ><?php _e('Every six hours', 'yfym'); ?></option>	
					<option value="twicedaily" <?php selected( $yfym_status_cron, 'twicedaily' )?> ><?php _e('Twice a day', 'yfym'); ?></option>
					<option value="daily" <?php selected( $yfym_status_cron, 'daily' )?> ><?php _e('Daily', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('The refresh interval on your feed', 'yfym'); ?></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_ufup"><?php _e('Update feed when updating products', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<input type="checkbox" name="yfym_ufup" <?php checked($yfym_ufup, 'on' ); ?>/>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_whot_export"><?php _e('Whot export', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_whot_export">
					<option value="all" <?php selected($yfym_whot_export, 'all' ); ?>><?php _e( 'Simple & Variable products', 'yfym'); ?></option>
					<option value="simple" <?php selected( $yfym_whot_export, 'simple' ); ?>><?php _e( 'Only simple products', 'yfym'); ?></option>
					<?php do_action('yfym_after_whot_export_option'); ?>
				</select><br />
				<span class="description"><?php _e('Whot export', 'yfym'); ?></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_desc"><?php _e('Description of the product', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_desc">
				<option value="excerpt" <?php selected($yfym_desc, 'excerpt'); ?>><?php _e('Excerpt description', 'yfym'); ?></option>
				<option value="full" <?php selected($yfym_desc, 'full'); ?>><?php _e('Full description', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('The source of the description', 'yfym'); ?>
				</span>
			</td>
		 </tr>		 
		 <tr>
			<th scope="row"><label for="yfym_shop_name"><?php _e('Shop name', 'yfym'); ?></label></th>
			<td class="overalldesc">
			 <input maxlength="20" type="text" name="yfym_shop_name" value="<?php echo $yfym_shop_name; ?>" /><br />
			 <span class="description"><?php _e('Required element', 'yfym'); ?> <strong>name</strong>. <?php _e('The short name of the store should not exceed 20 characters', 'yfym'); ?>.</span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_company_name"><?php _e('Company name', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<input type="text" name="yfym_company_name" value="<?php echo $yfym_company_name; ?>" /><br />
				<span class="description"><?php _e('Required element', 'yfym'); ?> <strong>company</strong>. <?php _e('Full name of the company that owns the store', 'yfym'); ?>.</span>
			</td>
		 </tr>	 
		 <tr>
			<th scope="row"><label for="yfym_adult"><?php _e('Adult Market', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_adult">	
				<option value="no" <?php selected($yfym_adult, 'no'); ?>><?php _e('No', 'yfym'); ?></option>
				<option value="yes" <?php selected($yfym_adult, 'yes'); ?>><?php _e('Yes', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>adult</strong></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_step_export"><?php _e('Step of export', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_step_export">
				<option value="80" <?php selected($yfym_step_export, '80'); ?>>80</option>
				<option value="200" <?php selected($yfym_step_export, '200'); ?>>200</option>
				<option value="300" <?php selected($yfym_step_export, '300'); ?>>300</option>
				<option value="450" <?php selected($yfym_step_export, '450'); ?>>450</option>
				<option value="500" <?php selected($yfym_step_export, '500'); ?>>500</option>
				<option value="800" <?php selected($yfym_step_export, '800'); ?>>800</option>
				<option value="1000" <?php selected($yfym_step_export, '1000'); ?>>1000</option>
				<?php do_action('yfym_step_export_option'); ?>
				</select><br />
				<span class="description"><?php _e('The value affects the speed of file creation', 'yfym'); ?>. <?php _e('If you have any problems with the generation of the file - try to reduce the value in this field', 'yfym'); ?>. <?php _e('More than 500 can only be installed on powerful servers', 'yfym'); ?>.</span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_no_group_id_arr"><?php _e('Categories of variable products for which group_id is not allowed', 'yfym'); ?></label></th>
			<td class="overalldesc">				
			 <select style="width: 100%;" name="yfym_no_group_id_arr[]" size="8" multiple>
		  <?php 		  
			 $args = array('taxonomy' => 'product_cat','hide_empty' => false,);
			 $terms = get_terms($args); $count = count($terms);
			 if ($count > 0) : foreach ($terms as $term) : $catid = $term->term_id; ?>
				<option value="<?php echo $catid; ?>"<?php if (!empty($yfym_no_group_id_arr)) { foreach ($yfym_no_group_id_arr as $value) {selected($value, $catid);}} ?>><?php $level = count(get_ancestors($catid, 'product_cat')); switch ($level) {case 1: echo '-'; break; case 2: echo '--'; break; case 3: echo '---'; break; case 4: echo '----'; break;} echo $term->name; ?></option>
			 <?php endforeach; endif; ?>
			 </select><br />
			 <span class="description"><?php _e('According to Yandex Market rules in this field you need to mark ALL categories of products not related to "Clothes, Shoes and Accessories", "Furniture", "Cosmetics, perfumes and care", "Baby products", "Accessories for portable electronics". Ie categories for which it is forbidden to use the attribute group_id', 'yfym'); ?>.</span>
			</td>
		 </tr>
		 <tr>
			 <th scope="row"><label for="yfym_add_in_name_arr"><?php _e('Add the following attributes to the name', 'yfym'); ?></label></th>
			<td class="overalldesc">				
			 <select style="width: 100%;" name="yfym_add_in_name_arr[]" size="8" multiple>
				<?php foreach (get_attributes() as $attribute) : ?>	
					<option value="<?php echo $attribute['id']; ?>"<?php if (!empty($add_in_name_arr)) { foreach ($add_in_name_arr as $value) {selected($value, $attribute['id']);}} ?>><?php echo $attribute['name']; ?></option>
				<?php endforeach; ?>
			 </select><br />
			 <span class="description"><?php _e('It works only for variable products that are not in the category "Clothes, Shoes and Accessories", "Furniture", "Cosmetics, perfumes and care", "Baby products", "Accessories for portable electronics"', 'yfym'); ?></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_skip_missing_products"><?php _e('Skip missing products', 'yfym'); ?> (<?php _e('except for products for which a pre-order is permitted', 'yfym'); ?>.)</label></th>
			<td class="overalldesc">
				<input type="checkbox" name="yfym_skip_missing_products" <?php checked($yfym_skip_missing_products, 'on' ); ?>/>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_skip_backorders_products"><?php _e('Skip backorders products', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<input type="checkbox" name="yfym_skip_backorders_products" <?php checked($yfym_skip_backorders_products, 'on' ); ?>/>
			</td>
		 </tr>
		 <?php do_action('yfym_after_step_export'); ?>		 
		</tbody></table>
	   </div>
	 </div>
	 <?php do_action('yfym_before_pad'); ?>
	 <div class="postbox">
	   <div class="inside">
	    <h1><?php _e('Price and delivery', 'yfym'); ?></h1>
		<table class="form-table"><tbody>
		 <tr>
			<th scope="row"><label for="yfym_pickup"><?php _e('Pickup', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_pickup">
				<option value="true" <?php selected( $yfym_pickup, 'true' ); ?>><?php _e( 'True', 'yfym'); ?></option>
				<option value="false" <?php selected( $yfym_pickup, 'false' ); ?>><?php _e( 'False', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>pickup</strong>. <?php _e('Option to get order from pickup point', 'yfym'); ?>.</span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_price_from"><?php _e('Price from', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_price_from">
					<option value="yes" <?php selected( $yfym_price_from, 'yes' ); ?>><?php _e( 'Yes', 'yfym'); ?></option>
					<option value="no" <?php selected( $yfym_price_from, 'no' ); ?>><?php _e( 'No', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('Apply the setting Price from', 'yfym'); ?> <strong>from="true"</strong> <?php _e('attribute of', 'yfym'); ?> <strong>price</strong><br /><strong><?php _e('Example', 'yfym'); ?>:</strong><br /><code>&lt;price from=&quot;true&quot;&gt;2000&lt;/price&gt;</code></span>
				</td>
		 </tr>	
		 <tr>
			<th scope="row"><label for="yfym_oldprice"><?php _e('Old price', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_oldprice">
					<option value="yes" <?php selected( $yfym_oldprice, 'yes' ); ?>><?php _e( 'Yes', 'yfym'); ?></option>
					<option value="no" <?php selected( $yfym_oldprice, 'no' ); ?>><?php _e( 'No', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>oldprice</strong>. <?php _e('In oldprice indicates the old price of the goods, which must necessarily be higher than the new price (price)', 'yfym'); ?>.</span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_delivery"><?php _e('Delivery', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_delivery">
					<option value="true" <?php selected( $yfym_delivery, 'true' ); ?>><?php _e( 'True', 'yfym'); ?></option>
					<option value="false" <?php selected( $yfym_delivery, 'false' ); ?>><?php _e( 'False', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>delivery</strong>. <?php _e('The delivery item must be set to false if the item is prohibited to sell remotely (jewelry, medicines)', 'yfym'); ?>.<br />
				<a target="_blank" href="//yandex.ru/support/partnermarket/delivery.html"><?php _e('Read more on Yandex', 'yfym'); ?></a>
				</span>
			</td>
		 </tr> 
		 <tr>
			<th scope="row"><label for="yfym_delivery_options"><?php _e('Use delivery-options', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<input type="checkbox" name="yfym_delivery_options" <?php checked($yfym_delivery_options, 'on' ); ?>/><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>delivery-options</strong> <a target="_blank" href="//yandex.ru/support/partnermarket/elements/delivery-options.html#structure"><?php _e('Read more on Yandex', 'yfym'); ?></a></span>			
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_delivery_cost"><?php _e('Delivery cost', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<input min="0" type="number" name="yfym_delivery_cost" value="<?php echo $yfym_delivery_cost; ?>" /><br />
				<span class="description"><?php _e('Required element', 'yfym'); ?> <strong>cost</strong> <?php _e('of attribute', 'yfym'); ?> <strong>delivery-option</strong></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_delivery_days"><?php _e('Delivery days', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<input type="text" name="yfym_delivery_days" value="<?php echo $yfym_delivery_days; ?>" /><br />
				<span class="description"><?php _e('Required element', 'yfym'); ?> <strong>days</strong> <?php _e('of attribute', 'yfym'); ?> <strong>delivery-option</strong></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_order_before"><?php _e('The time', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<input type="text" name="yfym_order_before" value="<?php echo $yfym_order_before; ?>" /><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>order-before</strong> <?php _e('of attribute', 'yfym'); ?> <strong>delivery-option</strong>.<br /><?php _e('The time in which you need to place an order to get it at this time', 'yfym'); ?></span>
			</td>
		 </tr>
		</tbody></table>
	   </div>
	 </div>
	 
	 
	 <div class="postbox">
	   <div class="inside">
		<h1><?php _e('Optional element', 'yfym'); ?></h1>
		<table class="form-table"><tbody>
		 <tr>
			<th scope="row"><label for="yfym_barcode"><?php _e('Barcode', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_barcode">		 
				<option value="off" <?php selected($barcode, 'none'); ?>
				><?php _e( 'None', 'yfym'); ?></option>
				<option value="sku" <?php selected($barcode, 'sku'); ?>
				><?php _e( 'Substitute from SKU', 'yfym'); ?></option>
				<?php foreach (get_attributes() as $attribute) : ?>	
				<option value="<?php echo $attribute['id']; ?>" <?php selected( $barcode, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	<?php endforeach; ?>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>barcode</strong></span>
			</td>
		 </tr>			 
		 <tr>
			 <th scope="row"><label for="yfym_expiry"><?php _e('Shelf life / service life', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_expiry">		 
				<option value="off" <?php selected($expiry, 'none'); ?>
				><?php _e( 'None', 'yfym'); ?></option>
				<?php foreach (get_attributes() as $attribute) : ?>	
				<option value="<?php echo $attribute['id']; ?>" <?php selected( $expiry, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	<?php endforeach; ?>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>expiry</strong>.<br /><?php _e('Shelf life / service life. expiry date / service life', 'yfym'); ?>.</span><br /><a target="_blank" href="//yandex.ru/support/partnermarket/offers.html"><?php _e('Read more on Yandex', 'yfym'); ?></a>					
			</td>
		 </tr>		 

		 <tr>
			 <th scope="row"><label for="yfym_downloadable"><?php _e('Mark downloadable products', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_downloadable">		 
				<option value="off" <?php selected($downloadable, 'none'); ?>
				><?php _e( 'None', 'yfym'); ?></option>
				<option value="on" <?php selected($downloadable, 'on'); ?>
				><?php _e( 'On', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>downloadable</strong></span>
			</td>
		 </tr>

		 <tr>
			 <th scope="row"><label for="yfym_age"><?php _e('Age', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_age">		 
				<option value="off" <?php selected($age, 'none'); ?>
				><?php _e( 'None', 'yfym'); ?></option>
				<?php foreach (get_attributes() as $attribute) : ?>	
				<option value="<?php echo $attribute['id']; ?>" <?php selected( $age, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	<?php endforeach; ?>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>age</strong></span>
			</td>
		 </tr>
		 
		 <tr>
			<th scope="row"><label for="yfym_model"><?php _e('Model', 'yfym'); ?></label></th>
			<td class="overalldesc">
			 <select name="yfym_model">		 
				<option value="off" <?php selected($model, 'none' ); ?>
				><?php _e( 'None', 'yfym'); ?></option>
				<?php foreach (get_attributes() as $attribute) : ?>	
				<option value="<?php echo $attribute['id']; ?>" <?php selected( $model, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	
				<?php endforeach; ?>
			 </select><br />
			 <span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>model</strong></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_vendor"><?php _e('Vendor', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_vendor">		 
				<option value="off" <?php selected($vendor, 'none' ); ?>
				><?php _e('None', 'yfym'); ?></option>
				<?php foreach (get_attributes() as $attribute) : ?>	
				<option value="<?php echo $attribute['id']; ?>" <?php selected( $vendor, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	
				<?php endforeach; ?>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>vendor</strong></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_country_of_origin"><?php _e('Country of origin', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_country_of_origin">
				<option value="off" <?php selected($country_of_origin, 'none'); ?>
				><?php _e( 'None', 'yfym'); ?></option>
				<?php foreach (get_attributes() as $attribute) : ?>	
				<option value="<?php echo $attribute['id']; ?>" <?php selected( $country_of_origin, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	
				<?php endforeach; ?>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>country_of_origin</strong>. <?php _e('This element indicates the country where the product was manufactured', 'yfym'); ?>.<br /><a href="//partner.market.yandex.ru/pages/help/Countries.pdf" target="_blank"><?php _e('A list of possible values', 'yfym'); ?></a>.</span>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="yfym_manufacturer_warrant"><?php _e('Manufacturer warrant', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_manufacturer_warranty">	 
					<option value="off" <?php selected($manufacturer_warranty, 'off'); ?>
					><?php _e( 'None', 'yfym'); ?></option>
					<?php foreach (get_attributes() as $attribute) : ?>	
					<option value="<?php echo $attribute['id']; ?>" <?php selected( $manufacturer_warranty, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	
					<?php endforeach; ?>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>manufacturer_warranty</strong>. <?php _e("This element is used for products that have an official manufacturer's warranty", 'yfym'); ?>.<ul><li>false — <?php _e('Product does not have an official warranty', 'yfym'); ?></li><li>true — <?php _e('Product has an official warranty', 'yfym'); ?></li></ul></span>
			</td>
		</tr>
		<?php do_action('yfym_after_manufacturer_warranty'); ?>	
		 <tr>
			<th scope="row"><label for="yfym_sales_notes"><?php _e('Sales notes', 'yfym'); ?></label><br /><span style="color: red;"><?php _e('Attention!', 'yfym'); ?></span> <a target="_blank" href="https://icopydoc.ru/sales-notes/"><?php _e('Read more about how this works', 'yfym'); ?></a></th>
			<td class="overalldesc">			
				<select name="yfym_sales_notes_cat">		 
				<option value="off" <?php selected($sales_notes_cat, 'none'); ?>
				><?php _e('Disable use of Sales notes', 'yfym'); ?></option>
				<?php foreach (get_attributes() as $attribute) : ?>	
				<option value="<?php echo $attribute['id']; ?>" <?php selected( $sales_notes_cat, $attribute['id'] ); ?>><?php echo $attribute['name']; ?></option>	
				<?php endforeach; ?>
				</select>
				<p><?php _e('If the attribute from the select is absent from the product, then substitute', 'yfym'); ?>:</p>	
				<textarea maxlength="50" style="width: 100%;" name="yfym_sales_notes" type="text" id="yfym_sales_notes" value="<?php echo $sales_notes; ?>" placeholder="<?php _e('Sales notes', 'yfym'); ?>" class="form-required" /><?php echo $sales_notes; ?></textarea></p>
				<span class="description">
				<p><?php _e('Optional element', 'yfym'); ?> <strong>Sales notes</strong></p>
				<p><span style="color: red;"><?php _e('Attention!', 'yfym'); ?></span> <?php _e('The text may be up to 50 characters in length. Also in the item is forbidden to specify the terms of delivery and price reduction (discount on merchandise)', 'yfym'); ?>.</p> 
				<p><a target="_blank" href="//yandex.ru/support/partnermarket/elements/sales_notes.html"><?php _e('Read more on Yandex', 'yfym'); ?></a></p></span>
			</td>
		 </tr>		 
		 <tr>
			<th scope="row"><label for="yfym_params_arr"><?php _e('Include these attributes in the values Param', 'yfym'); ?></label></th>
			<td class="overalldesc">			
			 <select style="width: 100%;" name="yfym_params_arr[]" size="8" multiple>
				<?php foreach (get_attributes() as $attribute) : ?>	
					<option value="<?php echo $attribute['id']; ?>"<?php if (!empty($params_arr)) { foreach ($params_arr as $value) {selected($value, $attribute['id']);}} ?>><?php echo $attribute['name']; ?></option>
				<?php endforeach; ?>
			 </select><br />
			 <span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>param</strong></span>
			</td>
		 </tr>
		 <tr>
			<th scope="row"><label for="yfym_store"><?php _e('Store', 'yfym'); ?></label></th>
			<td class="overalldesc">
				<select name="yfym_store">	
				<option value="true" <?php selected( $yfym_store, 'true' ); ?>><?php _e('True', 'yfym'); ?></option>
				<option value="false" <?php selected( $yfym_store, 'false' ); ?>><?php _e('False', 'yfym'); ?></option>
				</select><br />
				<span class="description"><?php _e('Optional element', 'yfym'); ?> <strong>store</strong> 
				<ul>
					<li><?php _e('true', 'yfym'); ?> — <?php _e('The product can be purchased in retail stores', 'yfym'); ?></li>
					<li><?php _e('false', 'yfym'); ?> — <?php _e('the product cannot be purchased in retail stores', 'yfym'); ?></li>
				<ul>			
				<a target="_blank" href="//yandex.ru/support/partnermarket/delivery.html"><?php _e('Read more on Yandex', 'yfym'); ?></a>
				</span>
			</td>			
		 </tr>		 
		</tbody></table> 		
	   </div>
	 </div>
	 <div class="postbox">
	  <div class="inside">
		<table class="form-table"><tbody>
		 <tr>
			<th scope="row"><label for="button-primary"></label></th>
			<td class="overalldesc"><?php wp_nonce_field('yfym_nonce_action','yfym_nonce_field'); ?><input class="button-primary" type="submit" name="yfym_submit_action" value="<?php _e( 'Save', 'yfym'); ?>" /><br />
			<span class="description"><?php _e('Click to save the settings', 'yfym'); ?></span></td>
		 </tr>
		</tbody></table>
	  </div>
	 </div>
	 </form>
	</div></div>

	
	<div id="postbox-container-2" class="postbox-container"><div class="meta-box-sortables">
	 <div class="postbox">
	  <div class="inside">
		<?php if (empty($yfym_file_url)) : ?>
		<h1><?php _e( 'Generate your 1st YML feed!', 'yfym'); ?></h1>
		 <p><?php _e( 'In order to do that, select another menu entry (which differs from "off") in the box called "Automatic file creation". You can also change values in other boxes if necessary, then press "Save".', 'yfym'); ?></p>
		 <p><?php _e( 'After 1-7 minutes (depending on the number of products), the feed will be generated and a link will appear instead of this message.', 'yfym'); ?></p>
		<?php else : ?>		
		 <?php if ($status_sborki !== -1) : ?>
			<div><?php _e('We are working on automatic file creation. YML will be developed soon.', 'yfym'); ?></div>			
		 <?php else : ?>	
		 <h1><?php _e('Link to your feed', 'yfym'); ?></h1>			  
		<p><?php _e('Your YML feed here', 'yfym'); ?>:<br><a target="_blank" href="<?php echo $yfym_file_url; ?>"><?php echo $yfym_file_url; ?></a>
		<br><?php _e('Generated', 'yfym'); ?>: <?php echo $yfym_date_sborki; ?>
		</p>		
		<p><?php _e('By clicking on "Save" you will overwrite the upload file for Yandex Market.', 'yfym'); ?>
		<?php endif; ?>	
		<?php endif; ?>
		<br><?php _e('Please note that Yandex Market checks YML no more than 3 times a day! This means that the changes on the Yandex Market are not instantaneous!', 'yfym'); ?></p>
	  </div>
	 </div>
	 <div class="postbox">
	  <div class="inside">
	  <h1><?php _e('Please support the project!', 'yfym'); ?></h1>
	  <p><?php _e('Thank you for using the plugin', 'yfym'); ?> <strong>Yml for Yandex Market'</strong></p>
	  <p><?php _e('Please help make the plugin better', 'yfym'); ?> <a href="https://docs.google.com/forms/d/e/1FAIpQLSdmEXYIQzW-_Hj2mwvVbzKT8UUKaScJWQjDwcgI7Y5D0Xmchw/viewform" target="_blank" ><?php _e('answering 6 questions', 'yfym'); ?>!</a></p>
	  <p><?php _e('If this plugin useful to you, please support the project one way', 'yfym'); ?>:</p>
	  <ul>
		<li>- <a href="//wordpress.org/plugins/yml-for-yandex-market/" target="_blank"><?php _e('Leave a comment on the plugin page', 'yfym'); ?></a>.</li>
		<li>- <?php _e('Support the project financially. Even $1 is a help!', 'yfym'); ?><a href="https://icopydoc.ru/donate/" target="_blank"> <?php _e('Donate now', 'yfym'); ?></a>.</li>
		<li>- <?php _e('Noticed a bug or have an idea how to improve the quality of the plugin?', 'yfym'); ?> <a href="mailto:pt070@yandex.ru"><?php _e('Let me know', 'yfym'); ?></a>.</li>
	  </ul>
	  <p><?php _e('The author of the plugin Maxim Glazunov', 'yfym'); ?>.</p>
	  <p><span style="color: red;"><?php _e('Accept orders for individual revision of the plugin', 'yfym'); ?></span>:<br /><a href="mailto:pt070@yandex.ru"><?php _e('Leave a request', 'yfym'); ?></a>.</p>  
	  </p>
	  </div>
	 </div>	
	 <div class="postbox">
	  <div class="inside">
		<h1><?php _e('Reset plugin settings', 'yfym'); ?></h1>
		<p><?php _e('Reset plugin settings can be useful in the event of a problem', 'yfym'); ?>.</p>
		<form action="<?php echo $_SERVER['REQUEST_URI'];?>" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field('yfym_nonce_action','yfym_nonce_field'); ?><input class="button-primary" type="submit" name="yfym_submit_reset" value="<?php _e('Reset plugin settings', 'yfym'); ?>" />	 
		</form>
	  </div>
	 </div>
	 
	 <div class="postbox">
	  <div class="inside">
		<h1><?php _e('Send data about the work of the plugin', 'yfym'); ?></h1>
		<p><?php _e('Sending statistics you help make the plugin even better!', 'yfym'); ?>. <?php _e('The following data will be transferred', 'yfym'); ?>:</p>
		<ul>
			<li>- <?php _e('Site URL', 'yfym'); ?></li>
			<li>- <?php _e('File generation status', 'yfym'); ?></li>
			<li>- <?php _e('URL YML-feed', 'yfym'); ?></li>
			<li>- <?php _e('Is the multisite mode enabled?', 'yfym'); ?></li>
		</ul>
		<p><?php _e('The plugin helped you download the products to the Yandex Market', 'yfym'); ?>?</p>
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" enctype="multipart/form-data">
		 <p>
			<input type="radio" name="yfym_its_ok" value="yes"><?php _e( 'Yes', 'yfym'); ?><br />
			<input type="radio" name="yfym_its_ok" value="no"><?php _e( 'No', 'yfym'); ?><br />
		 </p>
		 <p><?php _e("If you don't mind to be contacted in case of problems, please enter your email address", "yfym"); ?>.</p>
		 <p><input type="email" name="yfym_email"></p>
		 <p><?php _e("Your message", "yfym"); ?>:</p>
		 <p><textarea rows="5" cols="40" name="yfym_message"></textarea></p>
		 <?php wp_nonce_field('yfym_nonce_action','yfym_nonce_field'); ?><input class="button-primary" type="submit" name="yfym_submit_send_stat" value="<?php _e('Send data', 'yfym'); ?>" />	 
		</form>
	  </div>
	 </div> 
	 
	</div></div>	
  </div></div>
 </div>	
<?php
} /* end функция настроек yfym_export_page */ ?>