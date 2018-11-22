<?php defined( 'ABSPATH' ) OR exit;
/*
Plugin Name: Yml for Yandex Market
Description: Подключите свой магазин к Яндекс Маркету и выгружайте товары, получая новых клиентов!
Tags: yml, yandex, market, export, woocommerce
Author: Maxim Glazunov
Author URI: https://icopydoc.ru
License: GPLv2
Version: 1.4.3
Text Domain: yml-for-yandex-market
Domain Path: /languages/
WC requires at least: 3.0.0
WC tested up to: 3.4.5
*/
/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : djdiplomat@yandex.ru)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
function deleteGET($url, $whot = 'url') {
 $url = str_replace("&amp;", "&", $url); // Заменяем сущности на амперсанд, если требуется
 list($url_part, $get_part) = array_pad(explode("?", $url), 2, ""); // Разбиваем URL на 2 части: до знака ? и после
 if ($whot == 'url') {
	return $url_part; // Возвращаем URL без get-параметров (до знака вопроса)
 } else if ($whot == 'get') {
	return $get_part; // Возвращаем get-параметры (без знака вопроса)
 } else {
	return false;
 }
}
register_activation_hook(__FILE__, array('YmlforYandexMarket', 'on_activation'));
register_deactivation_hook(__FILE__, array('YmlforYandexMarket', 'on_deactivation'));
register_uninstall_hook(__FILE__, array('YmlforYandexMarket', 'on_uninstall'));
add_action('plugins_loaded', array('YmlforYandexMarket', 'init'));
add_action('plugins_loaded', 'yfym_load_plugin_textdomain'); // load translation
function yfym_load_plugin_textdomain() {
 load_plugin_textdomain('yfym', false, dirname(plugin_basename(__FILE__)).'/languages/');
}
class YmlforYandexMarket {
 protected static $instance;
 public static function init() {
	is_null( self::$instance ) AND self::$instance = new self;
	return self::$instance;
 }
	
 public function __construct() {
	// yfym_DIR contains /home/p135/www/site.ru/wp-content/plugins/myplagin/
	define('yfym_DIR', plugin_dir_path(__FILE__)); 
	// yfym_URL contains http://site.ru/wp-content/plugins/myplagin/
	define('yfym_URL', plugin_dir_url(__FILE__));
	
	add_action('admin_menu', array($this, 'add_admin_menu' ));
	add_filter('upload_mimes', array($this, 'yfym_add_mime_types'));
	
	add_filter('cron_schedules', array($this, 'cron_add_seventy_sec'));
	add_filter('cron_schedules', array($this, 'cron_add_six_hours'));	
	if( defined('DOING_CRON') && DOING_CRON ){		 
	 add_action('yfym_cron_sborki', array($this, 'yfym_do_this_seventy_sec'));	 
	 add_action('yfym_cron_period', array($this, 'yfym_do_this_event'));
	}
	
	add_action('admin_notices', array($this, 'yfym_admin_notices_function'));
	add_action('save_post_product', array($this, 'yfym_save_post_product_function'), 10, 3);
 }
 
 function yfym_save_post_product_function ($post_id, $post, $update) {
	if (wp_is_post_revision($post_id)) return; // если это ревизия
	if (is_multisite()) { 
	 $yfym_ufup = get_blog_option(get_current_blog_id(), 'yfym_ufup');
	 if ($yfym_ufup !== 'on') {return;}
	 $status_sborki = (int)get_blog_option(get_current_blog_id(), 'yfym_status_sborki');
	 $yfym_status_cron = get_blog_option(get_current_blog_id(), 'yfym_status_cron');
	} else {
	 $yfym_ufup = get_option('yfym_ufup');
	 if ($yfym_ufup !== 'on') {return;}
	 $status_sborki = (int)get_option('yfym_status_sborki');
	 $yfym_status_cron = get_option('yfym_status_cron');
	}	
	if ($status_sborki > -1) {return;} // если идет сборка файла
	//if ($yfym_status_cron == 'off') {return;} // сборка отключена настройками
	
	$recurrence = $yfym_status_cron;
	wp_clear_scheduled_hook('yfym_cron_period');
	wp_schedule_event( time(), $recurrence, 'yfym_cron_period');
	error_log('yfym_cron_period внесен в список заданий. line 90', 0);	
	return;
 }
 
 
 /* функции крона */
 public function yfym_do_this_seventy_sec() {
	if (is_multisite()) { 
		$log = get_blog_option(get_current_blog_id(), 'yfym_status_sborki');
	} else {
		$log = get_option('yfym_status_sborki');
	}
	error_log('Крон yfym_do_this_seventy_sec запущен. log = '.$log, 0);
	$this->yfym_construct_yml(); // делаем что-либо каждые 70 сек
 }
 public function yfym_do_this_event() {
	error_log('Крон yfym_do_this_event включен. Делаем что-то каждый час', 0);
	if (is_multisite()) {
		$step_export = (int)get_blog_option(get_current_blog_id(), 'yfym_step_export');
		if ($step_export == 0) {$step_export = 500;}
		update_blog_option(get_current_blog_id(), 'yfym_status_sborki', $step_export);
	} else {
		$step_export = (int)get_option('yfym_step_export');
		if ($step_export == 0) {$step_export = 500;}		
		update_option('yfym_status_sborki', $step_export);
	}
	wp_clear_scheduled_hook( 'yfym_cron_sborki' );
	wp_schedule_event(time(), 'seventy_sec', 'yfym_cron_sborki');
 }
 /* end функции крона */
 
 // Срабатывает при активации плагина (вызывается единожды)
 public static function on_activation() {  	
	global $wpdb;
	if (is_multisite()) {
		// Устанавливаем опции по умолчанию (будут храниться в таблице настроек WP)
		add_blog_option(get_current_blog_id(), 'yfym_version', '1.4.3');
		add_blog_option(get_current_blog_id(), 'yfym_status_cron', 'off');
		add_blog_option(get_current_blog_id(), 'yfym_step_export', '500');
		add_blog_option(get_current_blog_id(), 'yfym_status_sborki', '-1'); // статус сборки файла
		add_blog_option(get_current_blog_id(), 'yfym_date_sborki', 'unknown'); // дата последней сборки
		add_blog_option(get_current_blog_id(), 'yfym_type_sborki', 'yml'); // тип собираемого файла yml или xls
		add_blog_option(get_current_blog_id(), 'yfym_file_url', ''); // урл до файла
		add_blog_option(get_current_blog_id(), 'yfym_file_file', ''); // путь до файла
		add_blog_option(get_current_blog_id(), 'yfym_magazin_type', 'woocommerce'); // тип плагина магазина 
		add_blog_option(get_current_blog_id(), 'yfym_vendor', 'none'); // тип плагина магазина
		add_blog_option(get_current_blog_id(), 'yfym_whot_export', 'all'); // что выгружать (все или там где галка)
		add_blog_option(get_current_blog_id(), 'yfym_skip_missing_products', '0');

		$blog_title = get_bloginfo('name');
		add_blog_option(get_current_blog_id(), 'yfym_shop_name', $blog_title);
		add_blog_option(get_current_blog_id(), 'yfym_company_name', $blog_title);		
		add_blog_option(get_current_blog_id(), 'yfym_adult', 'no');
		add_blog_option(get_current_blog_id(), 'yfym_desc', 'full');
		add_blog_option(get_current_blog_id(), 'yfym_price_from', 'no'); // разрешить "цена от"
		add_blog_option(get_current_blog_id(), 'yfym_oldprice', 'no');
		add_blog_option(get_current_blog_id(), 'yfym_params_arr', '');
		add_blog_option(get_current_blog_id(), 'yfym_add_in_name_arr', '');
		add_blog_option(get_current_blog_id(), 'yfym_no_group_id_arr', '');
		add_blog_option(get_current_blog_id(), 'yfym_product_tag_arr', ''); // id меток таксономии product_tag
		add_blog_option(get_current_blog_id(), 'yfym_store', 'false');
		add_blog_option(get_current_blog_id(), 'yfym_delivery_options', '0');
		add_blog_option(get_current_blog_id(), 'yfym_delivery', 'false');
		add_blog_option(get_current_blog_id(), 'yfym_delivery_cost', '0');
		add_blog_option(get_current_blog_id(), 'yfym_delivery_days', '32');
		add_blog_option(get_current_blog_id(), 'yfym_order_before', '');		
		add_blog_option(get_current_blog_id(), 'yfym_sales_notes_cat', 'off');
		add_blog_option(get_current_blog_id(), 'yfym_sales_notes', '');
		add_blog_option(get_current_blog_id(), 'yfym_model', 'none'); // атрибут model магазина			
		add_blog_option(get_current_blog_id(), 'yfym_pickup', 'true');		
		add_blog_option(get_current_blog_id(), 'yfym_barcode', 'off');	
		add_blog_option(get_current_blog_id(), 'yfym_expiry', 'off');
		add_blog_option(get_current_blog_id(), 'yfym_downloadable', 'off');
		add_blog_option(get_current_blog_id(), 'yfym_age', 'off');
		add_blog_option(get_current_blog_id(), 'yfym_country_of_origin', 'off');
		add_blog_option(get_current_blog_id(), 'yfym_manufacturer_warranty', 'off');
		add_blog_option(get_current_blog_id(), 'yfym_errors', '');
	} else {
		// Устанавливаем опции по умолчанию (будут храниться в таблице настроек WP)
		add_option('yfym_version', '1.4.3');
		add_option('yfym_status_cron', 'off');
		add_option('yfym_step_export', '500');
		add_option('yfym_status_sborki', '-1'); // статус сборки файла
		add_option('yfym_date_sborki', 'unknown'); // дата последней сборки
		add_option('yfym_type_sborki', 'yml'); // тип собираемого файла yml или xls
		add_option('yfym_file_url', ''); // урл до файла
		add_option('yfym_file_file', ''); // путь до файла
		add_option('yfym_magazin_type', 'woocommerce'); // тип плагина магазина 
		add_option('yfym_vendor', 'none'); // тип плагина магазина
		add_option('yfym_whot_export', 'all'); // что выгружать (все или там где галка)
		add_option('yfym_skip_missing_products', '0');

		$blog_title = get_bloginfo('name');
		add_option('yfym_shop_name', $blog_title);
		add_option('yfym_company_name', $blog_title);
		add_option('yfym_adult', 'no');
		add_option('yfym_desc', 'full');
		add_option('yfym_price_from', 'no'); // разрешить "цена от"
		add_option('yfym_oldprice', 'no');
		add_option('yfym_params_arr', '');
		add_option('yfym_add_in_name_arr', '');
		add_option('yfym_no_group_id_arr', '');
		add_option('yfym_product_tag_arr', ''); // id меток таксономии product_tag
		add_option('yfym_store', 'false');
		add_option('yfym_delivery', 'false');
		add_option('yfym_delivery_options', '0');
		add_option('yfym_delivery_cost', '0');
		add_option('yfym_delivery_days', '32');
		add_option('yfym_order_before', '');
		add_option('yfym_sales_notes_cat', 'off');
		add_option('yfym_sales_notes', '');
		add_option('yfym_model', 'none'); // атрибут model магазина
		add_option('yfym_pickup', 'true');
		add_option('yfym_barcode', 'off');
		add_option('yfym_expiry', 'off');
		add_option('yfym_downloadable', 'off');
		add_option('yfym_age', 'off');	
		add_option('yfym_country_of_origin', 'off');
		add_option('yfym_manufacturer_warranty', 'off');
		add_option('yfym_errors', '');
	}
 }
 
 // Срабатывает при отключении плагина (вызывается единожды)
 public static function on_deactivation() {
	wp_clear_scheduled_hook('yfym_cron_period');
	wp_clear_scheduled_hook('yfym_cron_sborki');	
 } 
 
 //Срабатывает при удалении плагина (вызывается единожды)
 public static function on_uninstall() {

	if (is_multisite()) {
		delete_blog_option(get_current_blog_id(), 'yfym_version');
		delete_blog_option(get_current_blog_id(), 'yfym_status_cron');
		delete_blog_option(get_current_blog_id(), 'yfym_whot_export');
		delete_blog_option(get_current_blog_id(), 'yfym_skip_missing_products');
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
		delete_blog_option(get_current_blog_id(), 'yfym_delivery_cost');
		delete_blog_option(get_current_blog_id(), 'yfym_delivery_days');
		delete_blog_option(get_current_blog_id(), 'yfym_sales_notes_cat');
		delete_blog_option(get_current_blog_id(), 'yfym_sales_notes');
		delete_blog_option(get_current_blog_id(), 'yfym_price_from');	
		delete_blog_option(get_current_blog_id(), 'yfym_desc');
		delete_blog_option(get_current_blog_id(), 'yfym_barcode');
		delete_blog_option(get_current_blog_id(), 'yfym_expiry');
		delete_blog_option(get_current_blog_id(), 'yfym_downloadable');
		delete_blog_option(get_current_blog_id(), 'yfym_age');
		delete_blog_option(get_current_blog_id(), 'yfym_country_of_origin');
		delete_blog_option(get_current_blog_id(), 'yfym_manufacturer_warranty');
		delete_blog_option(get_current_blog_id(), 'yfym_adult');
		delete_blog_option(get_current_blog_id(), 'yfym_oldprice');
		delete_blog_option(get_current_blog_id(), 'yfym_step_export');
		delete_blog_option(get_current_blog_id(), 'yfym_errors');
	} else {
		delete_option('yfym_version');
		delete_option('yfym_status_cron');
		delete_option('yfym_whot_export');
		delete_option('yfym_skip_missing_products');
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
		delete_option('yfym_sales_notes_cat');
		delete_option('yfym_sales_notes');
		delete_option('yfym_price_from');	
		delete_option('yfym_desc');
		delete_option('yfym_barcode');
		delete_option('yfym_expiry');
		delete_option('yfym_downloadable');
		delete_option('yfym_age');
		delete_option('yfym_country_of_origin');
		delete_option('yfym_manufacturer_warranty');
		delete_option('yfym_adult');
		delete_option('yfym_oldprice');
		delete_option('yfym_step_export');
		delete_option('yfym_errors');
	}
 }
 
 // Register the management page
 public function add_admin_menu() {
	add_menu_page(null , __('Export Yandex Market', 'yfym'), 'manage_options', 'yfymexport', 'yfym_export_page', 'dashicons-redo', 51);
	require_once yfym_DIR.'/export.php'; // Подключаем файл настроек

	add_submenu_page( 'yfymexport', __('Add Extensions', 'yfym'), __('Extensions', 'yfym'), 'manage_options', 'yfymextensions', 'yfym_extensions_page' );
	require_once yfym_DIR.'/extensions.php'; // Подключаем файл настроек
 } 
 
 // Разрешим загрузку xml и csv файлов
 public function yfym_add_mime_types( $mimes ) {
	$mimes ['csv'] = 'text/csv';
	$mimes ['xml'] = 'text/xml';		
	return $mimes;
 }
 
 /* добавляем интервалы крон в 70 секунд и 6 часов */
 public function cron_add_seventy_sec($schedules) {
	$schedules['seventy_sec'] = array(
		'interval' => 70,
		'display' => '70 sec'
	);
	return $schedules;
 }
 public function cron_add_six_hours($schedules) {
	$schedules['six_hours'] = array(
		'interval' => 21600,
		'display' => '6 hours'
	);
	return $schedules;
 }
 /* end добавляем интервалы крон в 70 секунд и 6 часов */
 
 public static function yfym_construct_yml() {
  if (is_multisite()) {
	error_log('стартовала функция yfym_construct_yml для мультисайта', 0);	
	/* see https://yandex.ru/support/market-tech-requirements/index.html */
	$result_yml = '';
	$status_sborki = (int)get_blog_option(get_current_blog_id(), 'yfym_status_sborki');

	if ($status_sborki == -1 ) {	
		wp_clear_scheduled_hook('yfym_cron_sborki'); // файл уже собран. На всякий случай отключим крон сборки
		return;
	} 
	
	$step_export = (int)get_blog_option(get_current_blog_id(), 'yfym_step_export');
	if ($step_export == 0) {$step_export = 500;}
	
	if ($status_sborki == $step_export) { // начинаем сборку файла
		$unixtime = current_time('Y-m-d H:i'); // время в unix формате 
		update_option('yfym_date_sborki', $unixtime);		
		$shop_name = get_blog_option(get_current_blog_id(), 'yfym_shop_name');
		$company_name = get_blog_option(get_current_blog_id(), 'yfym_company_name');		
		$result_yml .= '<yml_catalog date="'.$unixtime.'">'.PHP_EOL;
		$result_yml .= "<shop>". PHP_EOL ."<name>$shop_name</name>".PHP_EOL;
		$result_yml .= "<company>$company_name</company>".PHP_EOL;
		$result_yml .= "<url>".home_url('/')."</url>".PHP_EOL;
		$result_yml .= "<platform>WordPress - Yml for Yandex Market</platform>".PHP_EOL;
		$result_yml .= "<version>".get_bloginfo('version')."</version>".PHP_EOL;
		
	
		/* общие параметры */
		$res = get_woocommerce_currency(); // получаем валюта магазина
		switch ($res) { /* RUR, USD, UAH, KZT */
			case "RUB":	$currencyId_yml = "RUR"; break;
			case "USD":	$currencyId_yml = "USD"; break;
			case "EUR":	$currencyId_yml = "EUR"; break;			
			case "UAH":	$currencyId_yml = "UAH"; break;
			case "KZT":	$currencyId_yml = "KZT"; break;
			case "BYN":	$currencyId_yml = "BYN"; break;			
			default: $currencyId_yml = "RUR"; 
		}
		$result_yml .= '<currencies>'. PHP_EOL .'<currency id="'.$currencyId_yml.'" rate="1"/>'. PHP_EOL .'</currencies>'.PHP_EOL;
		$terms = get_terms("product_cat");
		$count = count($terms);
		$result_yml .= '<categories>'.PHP_EOL;
		if ($count > 0) {			
			foreach ($terms as $term) {
				$result_yml .= '<category id="'.$term->term_id.'"';
				if ($term->parent !== 0) {
					$result_yml .= ' parentId="'.$term->parent.'"';
				}		
				$result_yml .= '>'.$term->name.'</category>'.PHP_EOL;
			}			
		}
		$result_yml = apply_filters('yfym_append_categories_filter', $result_yml);
		$result_yml .= '</categories>'.PHP_EOL; 
		 
		// $delivery_cost = get_blog_option(get_current_blog_id(), 'yfym_delivery_cost');
		$yfym_delivery_options = get_blog_option(get_current_blog_id(), 'yfym_delivery_options');
		if ($yfym_delivery_options == 'on') {
			$delivery_cost = get_blog_option(get_current_blog_id(), 'yfym_delivery_cost');
			$delivery_days = get_blog_option(get_current_blog_id(), 'yfym_delivery_days');
			$order_before = get_blog_option(get_current_blog_id(), 'yfym_order_before');
			if ($order_before == '') {$order_before_yml = '';} else {$order_before_yml = ' order-before="'.$order_before.'"';} 
			$result_yml .= '<delivery-options>'.PHP_EOL;
			$result_yml .= '<option cost="'.$delivery_cost.'" days="'.$delivery_days.'"'.$order_before_yml.'/>'.PHP_EOL;
			$result_yml .= '</delivery-options>	'.PHP_EOL;
		}	
		
		// $result_yml .= '<local_delivery_cost>'.$delivery_cost.'</local_delivery_cost>'.PHP_EOL;
		
		// магазин 18+
		$adult = get_blog_option(get_current_blog_id(), 'yfym_adult');
		if ($adult == 'yes') {$result_yml .= '<adult>true</adult>'.PHP_EOL;}		
		/* end общие параметры */
		
		do_action('yfym_before_offers');
		
		/* индивидуальные параметры товара */
		$result_yml .= '<offers>'.PHP_EOL;
		/* создаем файл или перезаписываем старый удалив содержимое */
		$result = yfym_write_file($result_yml, 'w+');
		if ($result !== true) {
			error_log('yfym_write_file вернула ошибку... line 791', 0);
			return; 
		}
	} 
	if ($status_sborki > 1) {
		$result_yml	= '';
		$offset = $status_sborki-$step_export;
		$whot_export = get_blog_option(get_current_blog_id(), 'yfym_whot_export');
		if ($whot_export == 'all' || $whot_export == 'simple') {
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => $step_export, // сколько выводить товаров
				'offset' => $offset,
				'relation' => 'AND'
			);
		} else {
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => $step_export, // сколько выводить товаров
				'offset' => $offset,
				'relation' => 'AND',
				'meta_query' => array(
					array(
						'key' => 'vygruzhat',
						'value' => 'on'
					)
				)
			);		
		}
		$args = apply_filters('yfym_query_arg_filter', $args);
		$featured_query = new WP_Query( $args );
		if ($featured_query->have_posts()) { 
		 while ($featured_query->have_posts()) { $featured_query->the_post();			
		  $postId = get_the_id(); // $featured_query->the_post()
		  global $product;
		  
		  // что выгружать
		  if ($product->is_type('variable')) {
			$yfym_whot_export = get_blog_option(get_current_blog_id(), 'yfym_whot_export');
			if ($yfym_whot_export == 'simple') {continue;}
		  }
		  
		  /* общие данные для вариативных и обычных товаров */		  
		  $res = get_woocommerce_currency(); // получаем валюта магазина
		  switch ($res) { /* RUR, USD, UAH, KZT */
			case "RUB":	$currencyId_yml = "RUR"; break;
			case "USD":	$currencyId_yml = "USD"; break;
			case "EUR":	$currencyId_yml = "EUR"; break;
			case "UAH":	$currencyId_yml = "UAH"; break;
			case "KZT":	$currencyId_yml = "KZT"; break;
			case "BYN":	$currencyId_yml = "BYN"; break;	
			default: $currencyId_yml = "RUR";
		  }
		  
		  // Возможность купить товар в розничном магазине. // true или false
		  $store = get_blog_option(get_current_blog_id(), 'yfym_store');
		  $result_yml_store = "<store>$store</store>".PHP_EOL;
		  
		  $pickup = get_blog_option(get_current_blog_id(), 'yfym_pickup');
		  $delivery = get_blog_option(get_current_blog_id(), 'yfym_delivery');		  
		  $result_yml_pickup = "<pickup>$pickup</pickup>".PHP_EOL;
		  $result_yml_delivery = "<delivery>$delivery</delivery>".PHP_EOL; /* !советуют false */
		  /*	
		  *	== delivery ==
		  *	Элемент, отражающий возможность доставки соответствующего товара.
		  *	«false» — товар не может быть доставлен («самовывоз»).
		  *	«true» — доставка товара осуществлятся в регионы, указанные 
		  *	во вкладке «Магазин» в разделе «Товары и цены». 
		  *	Стоимость доставки описывается в теге <local_delivery_cost>.
		  */	

		  $result_yml_name = get_the_title(); // название товара
		  $result_yml_name = apply_filters('yfym_change_name', $result_yml_name, get_the_id());
		  		  
		  // описание
		  $yfym_desc = get_blog_option(get_current_blog_id(), 'yfym_desc');
		  $result_yml_desc = '';
		  if ($yfym_desc == 'full') {
			$description_yml = get_the_content();			
		  } else {
			$description_yml = get_the_excerpt();
		  }		 
		  if (!empty($description_yml)) {		  
			$description_yml = strip_tags($description_yml, '<p>,<h3>,<ul>,<li>,<br/>,<br>');
			$result_yml_desc = "<description><![CDATA[".$description_yml."]]></description>".PHP_EOL;		
		  }
		  
		  $params_arr = unserialize(get_blog_option(get_current_blog_id(), 'yfym_params_arr'));
		  
		  // echo "Категории ".$product->get_categories();
		  $termini = get_the_terms($postId, 'product_cat');
		  $uzhe_est = array();
		  $result_yml_cat = '';
		  $catpostid = '';
		  foreach ($termini as $termin) {
			if (in_array($termin->term_taxonomy_id, $uzhe_est, true)) {continue;}
			$catpostid = $termin->term_taxonomy_id;
			$result_yml_cat .= '<categoryId>'.$termin->term_taxonomy_id.'</categoryId>'.PHP_EOL;
			$CurCategoryId = $termin->term_taxonomy_id; // запоминаем id категории для товара
			$uzhe_est[] = $termin->term_taxonomy_id;
			break; // т.к. у товара может быть лишь 1 категория - выходим досрочно.
			if ($termin->parent !== 0) { /* если корневая категория есть */
				if (in_array($termin->parent, $uzhe_est, true)) {continue;}
				$catpostid = $termin->parent;
				$result_yml_cat .= '<categoryId>'.$termin->parent.'</categoryId>'.PHP_EOL;
				$CurCategoryId = $termin->parent ; // запоминаем id категории для товара
				$uzhe_est[] = $termin->parent;
			}
		  }
		  $result_yml_cat = apply_filters('yfym_after_cat_filter', $result_yml_cat, $postId);
		  if ($result_yml_cat == '') {continue;}
		  /* $termin->ID - понятное дело, ID элемента
		  * $termin->slug - ярлык элемента
		  * $termin->term_group - значение term group
		  * $termin->term_taxonomy_id - ID самой таксономии
		  * $termin->taxonomy - название таксономии
		  * $termin->description - описание элемента
		  * $termin->parent - ID родительского элемента
		  * $termin->count - количество содержащихся в нем постов
		  */			  
		  /* end общие данные для вариативных и обычных товаров */
		  
		  /* Вариации */
		  // если вариация - нам нет смысла выгружать общее предложение
		  if ($product->is_type('variable')) {			
						
			$variations = array();
			if ($product->is_type('variable')) {
				$variations = $product->get_available_variations();
				$variation_count = count($variations);
			} 
			for ($i = 0; $i<$variation_count; $i++) {			 		
			 $offer_id = (($product->is_type('variable')) ? $variations[$i]['variation_id'] : $product->get_id());
			 $offer = new WC_Product_Variation($offer_id); // получим вариацию

			 /*
			 * $offer->get_price() - актуальная цена (равна sale_price или regular_price если sale_price пуст)
			 * $offer->get_regular_price() - обычная цена
			 * $offer->get_sale_price() - цена скидки
			 */
			 
			 $price_yml = $offer->get_price(); // цена вариации
			 // если цены нет - пропускаем вариацию 
			 if ($price_yml == 0 || empty($price_yml)) {continue;}

			 $yfym_skip_missing_products = get_blog_option(get_current_blog_id(), 'yfym_skip_missing_products');
			 if ($yfym_skip_missing_products == 'on') {
				if ($offer->is_in_stock() == false) {continue;}
			 }
			 
			 // пропускаем товары на предзаказ
			 $skip_backorders_products = get_blog_option(get_current_blog_id(), 'yfym_skip_backorders_products');
			 if ($skip_backorders_products == 'on') {
			  if ($offer->get_manage_stock() == true) { // включено управление запасом			  
				if (($offer->get_stock_quantity() < 1) && ($offer->get_backorders() !== 'no')) {continue;}
				//if (($offer->get_backorders() !== 'no') && ($offer->is_in_stock() == false)) {continue;}
			  }
			 }
			 
			 do_action('yfym_before_variable_offer');

			 if ($offer->get_manage_stock() == true) { // включено управление запасом
				 if ($offer->get_stock_quantity() > 0) {
					$available = 'true';
				} else {
					$available = 'false';
				}
			 } else { // отключено управление запасом
				if ($offer->is_in_stock() == true) {$available = 'true';} else {$available = 'false';}
			 }

		     // массив категорий для которых запрещен group_id
		     $no_group_id_arr = unserialize(get_blog_option(get_current_blog_id(), 'yfym_no_group_id_arr'));
		     if (empty($no_group_id_arr)) {
				// массив пуст. все категории выгружаем с group_id
				$gi = 'group_id="'.$product->get_id().'"';
				$result_yml_name_itog = $result_yml_name;
			 } else {
			  // если id текущей категории совпал со списком категорий без group_id
			  $CurCategoryId = (string)$CurCategoryId;
			  if (in_array($CurCategoryId, $no_group_id_arr)) {
				$gi = '';
				
				$add_in_name_arr = unserialize(get_blog_option(get_current_blog_id(), 'yfym_add_in_name_arr'));
				$attributes = $product->get_attributes(); // получили все атрибуты товара
				$param_at_name = '';
				foreach ($attributes as $param) {					
					if ($param->get_variation() == false) {
					// это обычный атрибут
					continue;
					$param_val = $product->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 } else { 
					// это атрибут вариации
					$param_val = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 }
				 // если этот параметр не нужно выгружать - пропускаем
				 $variation_id_string = (string)$param->get_id(); // важно, т.к. в настройках id как строки
				 if (!in_array($variation_id_string, $add_in_name_arr, true)) {continue;}
				 $param_name = wc_attribute_label(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 // если пустое имя атрибута или значение - пропускаем
				 if (empty($param_name) || empty($param_val)) {continue;}
				 $param_at_name .= $param_name.'-'.ucfirst(urldecode($param_val)).' ';
				}
				$param_at_name = trim($param_at_name);
				if ($param_at_name == '') {continue;}
				$result_yml_name_itog = $result_yml_name.' ('.$param_at_name.')';
			  } else {
				$gi = 'group_id="'.$product->get_id().'"';
				$result_yml_name_itog = $result_yml_name;
			  }
			 }
			 
			 $result_yml .= '<offer '.$gi.' id="'.$product->get_id().'var'.$offer_id.'" available="'.$available.'">'.PHP_EOL;
			do_action('yfym_prepend_variable_offer');	
			 /*$param_at_name = '';
			 // Param в вариациях
			 if (!empty($params_arr)) {
				$attributes = $product->get_attributes(); // получили все атрибуты товара		 
				foreach ($attributes as $param) {					
				 if ($param->get_variation() == false) {
					// это обычный атрибут
					$param_val = $product->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id())); 
				 } else { 
					// это атрибут вариации
					$param_val = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id())); 
				 }				
				 // если этот параметр не нужно выгружать - пропускаем
				 $variation_id_string = (string)$param->get_id(); // важно, т.к. в настройках id как строки
				 if (!in_array($variation_id_string, $params_arr, true)) {continue;}
				 $param_name = wc_attribute_label(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 // если пустое имя атрибута или значение - пропускаем
				 if (empty($param_name) || empty($param_val)) {continue;}
				 $result_yml .= '<param name="'.$param_name.'">'.ucfirst(urldecode($param_val)).'</param>'.PHP_EOL;
				 $param_at_name .= ucfirst(urldecode($param_val)).' ';
				}	
			 }
			 $param_at_name = trim($param_at_name); */
			 
			 $result_yml .= "<name>".$result_yml_name_itog."</name>".PHP_EOL;
			 // $result_yml .= "<name>".$result_yml_name." (".$param_at_name.")</name>".PHP_EOL;

			 // Описание.
			 $description_yml = $offer->get_description();
			 if (!empty($description_yml)) {
				$description_yml = strip_tags($description_yml, '<p>,<h3>,<ul>,<li>,<br/>,<br>');			
				$result_yml .= '<description><![CDATA['.$description_yml.']]></description>'.PHP_EOL;
			 } else {
				// если у вариации нет своего описания - пробуем подставить общее
				if (!empty($result_yml_desc)) {$result_yml .= $result_yml_desc;}
			 }
			 
			 $thumb_yml = get_the_post_thumbnail_url($offer->get_id(), 'full');
			 if (empty($thumb_yml)) {
				$thumb_id = get_post_thumbnail_id();			 
				$thumb_url = wp_get_attachment_image_src($thumb_id,'full', true);	
				$thumb_yml = $thumb_url[0]; /* урл оригинал миниатюры товара */
				$picture_yml = '<picture>'.deleteGET($thumb_yml).'</picture>'.PHP_EOL;
			 } else {
				$picture_yml = '<picture>'.deleteGET($thumb_yml).'</picture>'.PHP_EOL;
			 }
			 $picture_yml = apply_filters('yfym_pic_variable_offer_filter', $picture_yml, $product);
			 $result_yml .= $picture_yml;			 
			 
			 $result_yml .= "<url>".htmlspecialchars(get_permalink($offer->get_id()))."</url>".PHP_EOL;
			 
			 $yfym_price_from = get_blog_option(get_current_blog_id(), 'yfym_price_from');
			 if ($yfym_price_from == 'yes') {
				$result_yml .= "<price from='true'>".$price_yml."</price>".PHP_EOL;
			 } else {
				$result_yml .= "<price>".$price_yml."</price>".PHP_EOL;
			 }
			 // старая цена
			 $yfym_oldprice = get_blog_option(get_current_blog_id(), 'yfym_oldprice');
			 if ($yfym_oldprice == 'yes') {
			  $sale_price = $offer->get_sale_price();
			  if ($sale_price > 0 && !empty($sale_price)) {
				$oldprice_yml = $offer->get_regular_price();
				$result_yml .= "<oldprice>".$oldprice_yml."</oldprice>".PHP_EOL;
			  }
			 }			 
			 $result_yml .= '<currencyId>'.$currencyId_yml.'</currencyId>'.PHP_EOL;			 
			 			 
			 // штрихкод			 
			 $yfym_barcode = get_blog_option(get_current_blog_id(), 'yfym_barcode');
			 if ($yfym_barcode !== 'none') {
				switch ($yfym_barcode) { /* off, sku, или id */
				 case "off":	
					// выгружать штрихкод нет нужды
				 break; 
				 case "sku":
					// выгружать из артикула
					$sku_yml = $offer->get_sku(); // артикул
					if (!empty($sku_yml)) {
					  $result_yml .= "<barcode>".$sku_yml."</barcode>".PHP_EOL;
					} else {
					  // своего артикула у вариации нет. Пробуем подставить общий sku
					  $sku_yml = $product->get_sku();
					  if (!empty($sku_yml)) {
						$result_yml .= "<barcode>".$sku_yml."</barcode>".PHP_EOL;
					  }
					}
				 break;
				 default:
					$yfym_barcode_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($yfym_barcode));					
					if (!empty($yfym_barcode_yml)) {
						$result_yml .= '<barcode>'.urldecode($yfym_barcode_yml).'</barcode>'.PHP_EOL;
					}
				}
			 }
			 
			 $weight_yml = $offer->get_weight(); // вес
			 if (!empty($weight_yml)) {
				$weight_yml = round(wc_get_weight($weight_yml, 'kg'), 3);
				$result_yml .= "<weight>".$weight_yml."</weight>".PHP_EOL;
			 }
			 
			 $dimensions = $offer->get_dimensions();
			 if (!empty($dimensions)) {
			  $length_yml = $offer->get_length();
			  if (!empty($length_yml)) {$length_yml = round(wc_get_dimension($length_yml, 'cm'), 3);}
			   
			  $width_yml = $offer->get_width();
			  if (!empty($length_yml)) {$width_yml = round(wc_get_dimension($width_yml, 'cm'), 3);}
			  
			  $height_yml = $offer->get_height();
			  if (!empty($length_yml)) {$height_yml = round(wc_get_dimension($height_yml, 'cm'), 3);}		  
			   
			  if (($length_yml > 0) && ($width_yml > 0) && ($height_yml > 0)) {
				$result_yml .= '<dimensions>'.$length_yml.'/'.$width_yml.'/'.$height_yml.'</dimensions>'.PHP_EOL;
			  }
			 }			 

			 
			 $expiry = get_blog_option(get_current_blog_id(), 'yfym_expiry');
			 if (!empty($expiry) && $expiry !== 'off') {	
				$expiry_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($expiry));
				if (!empty($expiry_yml)) {	
					$result_yml .= "<expiry>".ucfirst(urldecode($expiry_yml))."</expiry>".PHP_EOL;		
				}
			 }
			 $age = get_blog_option(get_current_blog_id(), 'yfym_age');
			 if (!empty($age) && $age !== 'off') {	
				$age_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($age));
				if (!empty($age_yml)) {	
					$result_yml .= "<age>".ucfirst(urldecode($age_yml))."</age>".PHP_EOL;		
				} else {
					$age_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($age));
					if (!empty($age_yml)) {	
					 $result_yml .= "<age>".ucfirst(urldecode($age_yml))."</age>".PHP_EOL;		
					}		
				}
			 }
			 $downloadable = get_blog_option(get_current_blog_id(), 'yfym_downloadable');
			 if (!empty($downloadable) && $downloadable !== 'off') {
				if ($offer->is_downloadable('yes')) {
					$result_yml .= "<downloadable>true</downloadable>".PHP_EOL;	
				} else {
					$result_yml .= "<downloadable>false</downloadable>".PHP_EOL;							
				}
			 }
			 
			 // страна производитель
			 $country_of_origin = get_blog_option(get_current_blog_id(), 'yfym_country_of_origin');
			 if (!empty($country_of_origin) && $country_of_origin !== 'off') {			
				$country_of_origin_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($country_of_origin));
				if (!empty($country_of_origin_yml)) {	
					$result_yml .= "<country_of_origin>".ucfirst(urldecode($country_of_origin_yml))."</country_of_origin>".PHP_EOL;		
				}
			 }

			 
		  $sales_notes_cat = get_blog_option(get_current_blog_id(), 'yfym_sales_notes_cat');
		  if (!empty($sales_notes_cat) && $sales_notes_cat !== 'off') {	
		   $sales_notes_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($sales_notes_cat));
		   if (empty($sales_notes_yml)) {
			$sales_notes_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($sales_notes_cat));
		   }    
		   if (!empty($sales_notes_yml)) {	
			$result_yml .= "<sales_notes>".ucfirst(urldecode($sales_notes_yml))."</sales_notes>".PHP_EOL;		
		   } else {
			$sales_notes = get_blog_option(get_current_blog_id(), 'yfym_sales_notes');
			if (!empty($sales_notes)) {
				$result_yml .= "<sales_notes>$sales_notes</sales_notes>".PHP_EOL;
			}
		   }
		  }			 
			 /*$sales_notes = get_blog_option(get_current_blog_id(), 'yfym_sales_notes');
			 if (!empty($sales_notes)) {
				$result_yml .= "<sales_notes>$sales_notes</sales_notes>".PHP_EOL;
			 }*/			 
			
			 // гарантия
			 $manufacturer_warranty = get_blog_option(get_current_blog_id(), 'yfym_manufacturer_warranty');
			 if (!empty($manufacturer_warranty) && $manufacturer_warranty !== 'off') {
				$manufacturer_warranty_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($manufacturer_warranty));
				if (!empty($manufacturer_warranty_yml)) {	
					$result_yml .= "<manufacturer_warranty>".urldecode($manufacturer_warranty_yml)."</manufacturer_warranty>".PHP_EOL;
				} else {$manufacturer_warranty_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($manufacturer_warranty));
					if (!empty($manufacturer_warranty_yml)) {	
						$result_yml .= "<manufacturer_warranty>".urldecode($manufacturer_warranty_yml)."</manufacturer_warranty>".PHP_EOL;
					}
				}					
			 }

			 $vendor = get_blog_option(get_current_blog_id(), 'yfym_vendor');
			 if ($vendor !== 'none') {
				$vendor_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($vendor));
				if (!empty($vendor_yml)) {
				 $result_yml .= '<vendor>'.ucfirst(urldecode($vendor_yml)).'</vendor>'.PHP_EOL;
				} else {$vendor_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($vendor));
					if (!empty($vendor_yml)) {
						$result_yml .= '<vendor>'.ucfirst(urldecode($vendor_yml)).'</vendor>'.PHP_EOL;
					}
				}
			 }	
			 $model = get_blog_option(get_current_blog_id(), 'yfym_model');
			 if ($model !== 'none') {
				$model_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($model));
				if (!empty($model_yml)) {				 
				 $result_yml .= '<model>'.ucfirst(urldecode($model_yml)).'</model>'.PHP_EOL;
				} else {$model_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($model));
					if (!empty($model_yml)) {				 
						$result_yml .= '<model>'.ucfirst(urldecode($model_yml)).'</model>'.PHP_EOL;
					}
				}
			 }
			 
			 $result_yml .= $result_yml_store;
			 $result_yml .= $result_yml_pickup;
			 $result_yml .= $result_yml_delivery;
			 $result_yml .= $result_yml_cat; // Категории			 

			 do_action('yfym_append_variable_offer');
			 $result_yml = apply_filters('yfym_append_variable_offer_filter', $result_yml, $product, $offer);	 
			 
			 $result_yml .= '</offer>'.PHP_EOL;

			 do_action('yfym_after_variable_offer');			 
			}
			continue; // все вариации выгрузили - переходим к след товару
		  } // end if ($product->is_type('variable'))
		  /* end Вариации */				  
		  // если цена не указана - пропускаем товар
		  $price_yml = $product->get_price();
		  if ($price_yml == 0 || empty($price_yml)) {continue;}	
		  
		  $yfym_skip_missing_products = get_blog_option(get_current_blog_id(), 'yfym_skip_missing_products');
		  if ($yfym_skip_missing_products == 'on') {
			if ($product->is_in_stock() == false) {continue;}
		  }
		  
		  // пропускаем товары на предзаказ
		  $skip_backorders_products = get_blog_option(get_current_blog_id(), 'yfym_skip_backorders_products');
		  if ($skip_backorders_products == 'on') {
			if ($product->get_manage_stock() == true) { // включено управление запасом			  
				if (($product->get_stock_quantity() < 1) && ($product->get_backorders() !== 'no')) {continue;}
				//if (($offer->get_backorders() !== 'no') && ($offer->is_in_stock() == false)) {continue;}
			} else {
				if ($product->get_stock_status() !== 'instock') {continue;}
			}
		  }  /*$skip_backorders_products = get_option('yfym_skip_backorders_products');
		  if ($skip_backorders_products == 'on') {
			if (($offer->get_backorders() !== 'no') && ($offer->is_in_stock() == false)) {continue;}
		  }*/
		  
		  do_action('yfym_before_simple_offer');
		  
		  /* Обычный товар */
		  if ($product->get_manage_stock() == true) { // включено управление запасом
			  if ($product->get_stock_quantity() > 0) {
				$available = 'true';
			} else {
				$available = 'false';
			}
	      } else { // отключено управление запасом
			 if ($product->is_in_stock() == true) {$available = 'true';} else {$available = 'false';}
	      }
		  
		  $offer_type = '';
		  $offer_type = apply_filters('yfym_offer_type_filter', $offer_type, $catpostid);
		  $result_yml .= '<offer '.$offer_type.' id="'.get_the_ID().'" available="'.$available.'">'.PHP_EOL;
		  do_action('yfym_prepend_simple_offer');
		  $params_arr = unserialize(get_blog_option(get_current_blog_id(), 'yfym_params_arr'));		  
		  if (!empty($params_arr)) {		
			$attributes = $product->get_attributes();				
			foreach ($attributes as $param) {
			 // проверка на вариативность атрибута не нужна
			 $param_val = $product->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id()));		
			 // если этот параметр не нужно выгружать - пропускаем
			 $variation_id_string = (string)$param->get_id(); // важно, т.к. в настройках id как строки
			 if (!in_array($variation_id_string, $params_arr, true)) {continue;}
			 $param_name = wc_attribute_label(wc_attribute_taxonomy_name_by_id($param->get_id()));
			 // если пустое имя атрибута или значение - пропускаем
			 if (empty($param_name) || empty($param_val)) {continue;}
			 $result_yml .= '<param name="'.$param_name.'">'.ucfirst(urldecode($param_val)).'</param>'.PHP_EOL;
			}
		  }
		  			
		  $result_yml .= "<name>".$result_yml_name."</name>".PHP_EOL;
			
		  // описание
		  $result_yml .= $result_yml_desc;
		  
		  $thumb_id = get_post_thumbnail_id();
		  $thumb_url = wp_get_attachment_image_src($thumb_id,'full', true);	
		  $thumb_yml = $thumb_url[0]; /* урл оригинал миниатюры товара */
		  $picture_yml = '<picture>'.deleteGET($thumb_yml).'</picture>'.PHP_EOL;		  
		  $picture_yml = apply_filters('yfym_pic_simple_offer_filter', $picture_yml, $product);
		  $result_yml .= $picture_yml;
		  
		  $url_yml = get_permalink(); // урл товара
		  $result_yml .= "<url>".$url_yml."</url>".PHP_EOL;
		  
		  $yfym_price_from = get_blog_option(get_current_blog_id(), 'yfym_price_from');
		  if ($yfym_price_from == 'yes') {
			$result_yml .= "<price from='true'>".$price_yml."</price>".PHP_EOL;
		  } else {
			$result_yml .= "<price>".$price_yml."</price>".PHP_EOL;
		  }
		  // старая цена
		  $yfym_oldprice = get_blog_option(get_current_blog_id(), 'yfym_oldprice');
			if ($yfym_oldprice == 'yes') {
				$sale_price = $product->get_sale_price();
			if ($sale_price > 0 && !empty($sale_price)) {
				$oldprice_yml = $product->get_regular_price();
				$result_yml .= "<oldprice>".$oldprice_yml."</oldprice>".PHP_EOL;
			}
		  }			  
		  
		  $result_yml .= '<currencyId>'.$currencyId_yml.'</currencyId>'.PHP_EOL;		  
		  
		  // штрихкод			 
		  $yfym_barcode = get_blog_option(get_current_blog_id(), 'yfym_barcode');
		  if ($yfym_barcode !== 'none') {
			switch ($yfym_barcode) { /* off, sku, или id */
			 case "off":	
				// выгружать штрихкод нет нужды
			 break; 
			 case "sku":
				// выгружать из артикула
				$sku_yml = $product->get_sku();
				if (!empty($sku_yml)) {
					$result_yml .= "<barcode>".$sku_yml."</barcode>".PHP_EOL;
				}			
			 break;
			 default:
				$yfym_barcode_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($yfym_barcode));				
				if (!empty($yfym_barcode_yml)) {
					$result_yml .= '<barcode>'.urldecode($yfym_barcode_yml).'</barcode>'.PHP_EOL;
				}
			}
		  }			  

		  $weight_yml = $product->get_weight(); // вес
		  if (!empty($weight_yml)) {
			$weight_yml = round(wc_get_weight($weight_yml, 'kg'), 3);
			$result_yml .= "<weight>".$weight_yml."</weight>".PHP_EOL;
		  }
		  
		  $dimensions = $product->get_dimensions();
		  if (!empty($dimensions)) {
		   $length_yml = $product->get_length();
		   if (!empty($length_yml)) {$length_yml = round(wc_get_dimension($length_yml, 'cm'), 3);}
		  
		   $width_yml = $product->get_width();
		   if (!empty($length_yml)) {$width_yml = round(wc_get_dimension($width_yml, 'cm'), 3);}
		   
		   $height_yml = $product->get_height();
		   if (!empty($length_yml)) {$height_yml = round(wc_get_dimension($height_yml, 'cm'), 3);}		  
		   
		   if (($length_yml > 0) && ($width_yml > 0) && ($height_yml > 0)) {
			$result_yml .= '<dimensions>'.$length_yml.'/'.$width_yml.'/'.$height_yml.'</dimensions>'.PHP_EOL;
		   }
		  }		  

			 $expiry = get_blog_option(get_current_blog_id(), 'yfym_expiry');
			 if (!empty($expiry) && $expiry !== 'off') {	
				$expiry_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($expiry));
				if (!empty($expiry_yml)) {	
					$result_yml .= "<expiry>".ucfirst(urldecode($expiry_yml))."</expiry>".PHP_EOL;		
				}
			 }
			 $age = get_blog_option(get_current_blog_id(), 'yfym_age');
			 if (!empty($age) && $age !== 'off') {	
				$age_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($age));
				if (!empty($age_yml)) {	
					$result_yml .= "<age>".ucfirst(urldecode($age_yml))."</age>".PHP_EOL;		
				}
			 }
			 $downloadable = get_blog_option(get_current_blog_id(), 'yfym_downloadable');
			 if (!empty($downloadable) && $downloadable !== 'off') {
				if ($product->is_downloadable('yes')) {
					$result_yml .= "<downloadable>true</downloadable>".PHP_EOL;	
				} else {
					$result_yml .= "<downloadable>false</downloadable>".PHP_EOL;							
				}
			 }
		  
		  $sales_notes_cat = get_blog_option(get_current_blog_id(), 'yfym_sales_notes_cat');
		  if (!empty($sales_notes_cat) && $sales_notes_cat !== 'off') {	
		   $sales_notes_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($sales_notes_cat));
		   if (!empty($sales_notes_yml)) {	
			$result_yml .= "<sales_notes>".ucfirst(urldecode($sales_notes_yml))."</sales_notes>".PHP_EOL;		
		   } else {
			$sales_notes = get_blog_option(get_current_blog_id(), 'yfym_sales_notes');
			if (!empty($sales_notes)) {
				$result_yml .= "<sales_notes>$sales_notes</sales_notes>".PHP_EOL;
			}
		   }
		  }			  
		  
		  // страна производитель
		  $country_of_origin = get_blog_option(get_current_blog_id(), 'yfym_country_of_origin');
		  if (!empty($country_of_origin) && $country_of_origin !== 'off') {			
		  $country_of_origin_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($country_of_origin));
			if (!empty($country_of_origin_yml)) {	
				$result_yml .= "<country_of_origin>".ucfirst(urldecode($country_of_origin_yml))."</country_of_origin>".PHP_EOL;		
			}				
		  }
		  		  
		  // гарантия
		  $manufacturer_warranty = get_blog_option(get_current_blog_id(), 'yfym_manufacturer_warranty');
		  if (!empty($manufacturer_warranty) && $manufacturer_warranty !== 'off') {
			$manufacturer_warranty_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($manufacturer_warranty));
			if (!empty($manufacturer_warranty_yml)) {	
				$result_yml .= "<manufacturer_warranty>".urldecode($manufacturer_warranty_yml)."</manufacturer_warranty>".PHP_EOL;
			}					
		  }
		  
		  global $wpdb;
		  $vendor = get_blog_option(get_current_blog_id(), 'yfym_vendor');
		  if ($vendor !== 'none') {
			$vendor_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($vendor));
			if (!empty($vendor_yml)) {
			 $result_yml .= '<vendor>'.$vendor_yml.'</vendor>'.PHP_EOL;
			}
		  }			
		  $model = get_blog_option(get_current_blog_id(), 'yfym_model');
		  if ($model !== 'none') {
			$model_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($model));
			if (!empty($model_yml)) {				
			 $result_yml .= '<model>'.$model_yml.'</model>'.PHP_EOL;
			}
		  }		
		  
		  // если offer_type пуст, то можно выгружать vendorCode
		  if ($offer_type =='') {
		   $sku_yml = $product->get_sku(); // артикул
		   if ($sku_yml !== '') {
			$result_yml .= "<vendorCode>".$sku_yml."</vendorCode>".PHP_EOL;
		   }		  
		  }
		  
		  // do_action('yfym_after_sku_simple_offer');
		  
		  $result_yml .= $result_yml_store;
		  $result_yml .= $result_yml_pickup;
		  $result_yml .= $result_yml_delivery;
		  $result_yml .= $result_yml_cat; // Категории
		  
		  do_action('yfym_append_simple_offer'); $args = apply_filters('yfym_query_arg_filter', $args);
		  $result_yml = apply_filters('yfym_append_simple_offer_filter', $result_yml, $product);	 
		  
		  $result_yml .= '</offer>'.PHP_EOL;
		  
		  do_action('yfym_after_simple_offer');
		 } /* end while */ 

		 /* создаем файл или перезаписываем старый удалив содержимое */
		 $result = yfym_write_file($result_yml,'a');
		 if ($result == true) {
			// увеличиваем счетчик статуса сборки
			$status_sborki = $status_sborki + $step_export;
			error_log('status_sborki увеличен на '.$step_export.' и равен '.$status_sborki, 0);
			update_option('yfym_status_sborki', $status_sborki);
		 } else {
			error_log('yfym_write_file вернула ошибку... line 848', 0);
			return;
		 }		 
		 
		 wp_reset_query(); /* Remember to reset */
		} else {
		 // если постов нет, пишем концовку файла
		 $result_yml .= "</offers>". PHP_EOL; 
		 $result_yml = apply_filters('yfym_after_offers_filter', $result_yml);
		 $result_yml .= "</shop>". PHP_EOL ."</yml_catalog>";
		 /* создаем файл или перезаписываем старый удалив содержимое */
		 $result = yfym_write_file($result_yml,'a');
		 yfym_rename_file();		 
		 // выставляем статус сборки в "готово"
		 $status_sborki = -1;
		 if ($result == true) {
			update_option('yfym_status_sborki', $status_sborki);
			// останавливаем крон сборки
			wp_clear_scheduled_hook( 'yfym_cron_sborki' );
		 } else {
			error_log('yfym_write_file вернула ошибку... Я не смог записать концовку файла...', 0);
			return;
		 }
		}
	}  	 
  } else { // обычный сайт (не мультисайт)
	error_log('Стартовала функция yfym_construct_yml для обычного сайта', 0);
	/* see https://yandex.ru/support/market-tech-requirements/index.html */
	$result_yml = '';
	$status_sborki = (int)get_option('yfym_status_sborki');

	if ($status_sborki == -1 ) {	
		wp_clear_scheduled_hook('yfym_cron_sborki'); // файл уже собран. На всякий случай отключим крон сборки
		return;
	} 
	
	$step_export = (int)get_option('yfym_step_export');
	if ($step_export == 0) {$step_export = 500;}
	
	if ($status_sborki == $step_export) { // начинаем сборку файла
		$unixtime = current_time('Y-m-d H:i'); // время в unix формате 
		update_option('yfym_date_sborki', $unixtime);		
		$shop_name = get_option('yfym_shop_name');
		$company_name = get_option('yfym_company_name');		
		$result_yml .= '<yml_catalog date="'.$unixtime.'">'.PHP_EOL;
		$result_yml .= "<shop>". PHP_EOL ."<name>$shop_name</name>".PHP_EOL;
		$result_yml .= "<company>$company_name</company>".PHP_EOL;
		$result_yml .= "<url>".home_url('/')."</url>".PHP_EOL;
		$result_yml .= "<platform>WordPress - Yml for Yandex Market</platform>".PHP_EOL;
		$result_yml .= "<version>".get_bloginfo('version')."</version>".PHP_EOL;
	
		/* общие параметры */
		$res = get_woocommerce_currency(); // получаем валюта магазина
		switch ($res) { /* RUR, USD, UAH, KZT */
			case "RUB":	$currencyId_yml = "RUR"; break;
			case "USD":	$currencyId_yml = "USD"; break;
			case "EUR":	$currencyId_yml = "EUR"; break;			
			case "UAH":	$currencyId_yml = "UAH"; break;
			case "KZT":	$currencyId_yml = "KZT"; break;
			case "BYN":	$currencyId_yml = "BYN"; break;	
			default: $currencyId_yml = "RUR"; 
		}
		$result_yml .= '<currencies>'. PHP_EOL .'<currency id="'.$currencyId_yml.'" rate="1"/>'. PHP_EOL .'</currencies>'.PHP_EOL;
		$terms = get_terms("product_cat");
		$count = count($terms);	
		$result_yml .= '<categories>'.PHP_EOL;
		if ($count > 0) {			
			foreach ($terms as $term) {
				$result_yml .= '<category id="'.$term->term_id.'"';
				if ($term->parent !== 0) {
					$result_yml .= ' parentId="'.$term->parent.'"';
				}		
				$result_yml .= '>'.$term->name.'</category>'.PHP_EOL;
			}			
		}
		$result_yml = apply_filters('yfym_append_categories_filter', $result_yml);
		$result_yml .= '</categories>'.PHP_EOL;
		
		
		$yfym_delivery_options = get_option('yfym_delivery_options');
		if ($yfym_delivery_options == 'on') {
			$delivery_cost = get_option('yfym_delivery_cost');
			$delivery_days = get_option('yfym_delivery_days');
			$order_before = get_option('yfym_order_before');
			if ($order_before == '') {$order_before_yml = '';} else {$order_before_yml = ' order-before="'.$order_before.'"';}  
			$result_yml .= '<delivery-options>'.PHP_EOL;
			$result_yml .= '<option cost="'.$delivery_cost.'" days="'.$delivery_days.'"'.$order_before_yml.'/>'.PHP_EOL;
			$result_yml .= '</delivery-options>	'.PHP_EOL;
		}
		// $result_yml .= '<local_delivery_cost>'.$delivery_cost.'</local_delivery_cost>'.PHP_EOL;
		// $delivery_days = get_option('yfym_delivery_days');
		// $result_yml .= '<local_delivery_days>'.$delivery_days.'</local_delivery_days>'.PHP_EOL;
		
		// магазин 18+
		$adult = get_option('yfym_adult');
		if ($adult == 'yes') {$result_yml .= '<adult>true</adult>'.PHP_EOL;}		
		/* end общие параметры */
		
		do_action('yfym_before_offers');
		
		/* индивидуальные параметры товара */
		$result_yml .= '<offers>'.PHP_EOL;
		/* создаем файл или перезаписываем старый удалив содержимое */
		$result = yfym_write_file($result_yml, 'w+');
		if ($result !== true) {
			error_log('yfym_write_file вернула ошибку... line 940', 0);
			return; 
		}
	} 
	if ($status_sborki > 1) {
		$result_yml	= '';
		$offset = $status_sborki-$step_export;
		$whot_export = get_option('yfym_whot_export');
		if ($whot_export == 'all' || $whot_export == 'simple') {
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => $step_export, // сколько выводить товаров
				'offset' => $offset,
				'relation' => 'AND'
			);
		} else {
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => $step_export, // сколько выводить товаров
				'offset' => $offset,
				'relation' => 'AND',
				'meta_query' => array(
					array(
						'key' => 'vygruzhat',
						'value' => 'on'
					)
				)
			);		
		}
		$args = apply_filters('yfym_query_arg_filter', $args);
		$featured_query = new WP_Query( $args );
		if ($featured_query->have_posts()) { 
		 while ($featured_query->have_posts()) { $featured_query->the_post();			
		  $postId = get_the_id(); // $featured_query->the_post()
		  global $product;
		  
		  // что выгружать
		  if ($product->is_type('variable')) {
			$yfym_whot_export = get_option('yfym_whot_export');
			if ($yfym_whot_export == 'simple') {continue;}
		  }
		  
		  /* общие данные для вариативных и обычных товаров */		  
		  $res = get_woocommerce_currency(); // получаем валюта магазина
		  switch ($res) { /* RUR, USD, UAH, KZT */
			case "RUB":	$currencyId_yml = "RUR"; break;
			case "USD":	$currencyId_yml = "USD"; break;
			case "EUR":	$currencyId_yml = "EUR"; break;
			case "UAH":	$currencyId_yml = "UAH"; break;
			case "KZT":	$currencyId_yml = "KZT"; break;
			case "BYN":	$currencyId_yml = "BYN"; break;	
			default: $currencyId_yml = "RUR";
		  }
		  
		  // Возможность купить товар в розничном магазине. // true или false
		  $store = get_option('yfym_store');
		  $result_yml_store = "<store>$store</store>".PHP_EOL;
		  
		  $pickup = get_option('yfym_pickup');
		  $delivery = get_option('yfym_delivery');		  
		  $result_yml_pickup = "<pickup>$pickup</pickup>".PHP_EOL;
		  $result_yml_delivery = "<delivery>$delivery</delivery>".PHP_EOL; /* !советуют false */
		  /*	
		  *	== delivery ==
		  *	Элемент, отражающий возможность доставки соответствующего товара.
		  *	«false» — товар не может быть доставлен («самовывоз»).
		  *	«true» — доставка товара осуществлятся в регионы, указанные 
		  *	во вкладке «Магазин» в разделе «Товары и цены». 
		  *	Стоимость доставки описывается в теге <local_delivery_cost>.
		  */	

		  $result_yml_name = get_the_title(); // название товара
		  $result_yml_name = apply_filters('yfym_change_name', $result_yml_name, get_the_id());
		  
		  // описание
		  $yfym_desc = get_option('yfym_desc');
		  $result_yml_desc = '';
		  if ($yfym_desc == 'full') {
			$description_yml = get_the_content();			
		  } else {
			$description_yml = get_the_excerpt();
		  }		 
		  if (!empty($description_yml)) {
			$description_yml = strip_tags($description_yml, '<p>,<h3>,<ul>,<li>,<br/>,<br>');			
			$result_yml_desc = "<description><![CDATA[".$description_yml."]]></description>".PHP_EOL;		
		  }
		  
		  $params_arr = unserialize(get_option('yfym_params_arr'));
		  
		  // echo "Категории ".$product->get_categories();
		  $termini = get_the_terms($postId, 'product_cat');
		  $uzhe_est = array();
		  $result_yml_cat = '';
		  $catpostid = '';
		  foreach ($termini as $termin) {
			if (in_array($termin->term_taxonomy_id, $uzhe_est, true)) {continue;}
			$catpostid = $termin->term_taxonomy_id;
			$result_yml_cat .= '<categoryId>'.$termin->term_taxonomy_id.'</categoryId>'.PHP_EOL;
			$CurCategoryId = $termin->term_taxonomy_id; // запоминаем id категории для товара
			$uzhe_est[] = $termin->term_taxonomy_id;
			break; // т.к. у товара может быть лишь 1 категория - выходим досрочно.
			if ($termin->parent !== 0) { /* если корневая категория есть */
				if (in_array($termin->parent, $uzhe_est, true)) {continue;}
				$catpostid = $termin->parent;
				$result_yml_cat .= '<categoryId>'.$termin->parent.'</categoryId>'.PHP_EOL;
				$CurCategoryId = $termin->parent ; // запоминаем id категории для товара
				$uzhe_est[] = $termin->parent;
			}
		  }
		  $result_yml_cat = apply_filters('yfym_after_cat_filter', $result_yml_cat, $postId);
		  if ($result_yml_cat == '') {continue;}
		  /* $termin->ID - понятное дело, ID элемента
		  * $termin->slug - ярлык элемента
		  * $termin->term_group - значение term group
		  * $termin->term_taxonomy_id - ID самой таксономии
		  * $termin->taxonomy - название таксономии
		  * $termin->description - описание элемента
		  * $termin->parent - ID родительского элемента
		  * $termin->count - количество содержащихся в нем постов
		  */			  
		  /* end общие данные для вариативных и обычных товаров */
		  
		  /* Вариации */
		  // если вариация - нам нет смысла выгружать общее предложение
		  if ($product->is_type('variable')) {			
						
			$variations = array();
			if ($product->is_type('variable')) {
				$variations = $product->get_available_variations();
				$variation_count = count($variations);
			} 
			for ($i = 0; $i<$variation_count; $i++) {			 		
			 $offer_id = (($product->is_type('variable')) ? $variations[$i]['variation_id'] : $product->get_id());
			 $offer = new WC_Product_Variation($offer_id); // получим вариацию

			 /*
			 * $offer->get_price() - актуальная цена (равна sale_price или regular_price если sale_price пуст)
			 * $offer->get_regular_price() - обычная цена
			 * $offer->get_sale_price() - цена скидки
			 */
			 
			 $price_yml = $offer->get_price(); // цена вариации
			 // если цены нет - пропускаем вариацию 
			 if ($price_yml == 0 || empty($price_yml)) {continue;}

			 // пропуск товаров, которых нет в наличии
			 $yfym_skip_missing_products = get_option('yfym_skip_missing_products');
			 if ($yfym_skip_missing_products == 'on') {
				if ($offer->is_in_stock() == false) {continue;}
			 }
			 
			 // пропускаем товары на предзаказ
			 $skip_backorders_products = get_option('yfym_skip_backorders_products');
			 if ($skip_backorders_products == 'on') {
			  if ($offer->get_manage_stock() == true) { // включено управление запасом			  
				if (($offer->get_stock_quantity() < 1) && ($offer->get_backorders() !== 'no')) {continue;}
				//if (($offer->get_backorders() !== 'no') && ($offer->is_in_stock() == false)) {continue;}
			  }
			 }
			 
			 do_action('yfym_before_variable_offer');

			 if ($offer->get_manage_stock() == true) { // включено управление запасом
				 if ($offer->get_stock_quantity() > 0) {
					$available = 'true';
				} else {
					$available = 'false';
				}
			 } else { // отключено управление запасом
				if ($offer->is_in_stock() == true) {$available = 'true';} else {$available = 'false';}
			 }

		     // массив категорий для которых запрещен group_id
		     $no_group_id_arr = unserialize(get_option('yfym_no_group_id_arr'));
		     if (empty($no_group_id_arr)) {
				// массив пуст. все категории выгружаем с group_id
				$gi = 'group_id="'.$product->get_id().'"';
				$result_yml_name_itog = $result_yml_name;
			 } else {
			  // если id текущей категории совпал со списком категорий без group_id
			  $CurCategoryId = (string)$CurCategoryId;
			  if (in_array($CurCategoryId, $no_group_id_arr)) {
				$gi = '';
				
				$add_in_name_arr = unserialize(get_option('yfym_add_in_name_arr'));
				$attributes = $product->get_attributes(); // получили все атрибуты товара
				$param_at_name = '';
				foreach ($attributes as $param) {					
					if ($param->get_variation() == false) {
					// это обычный атрибут
					continue;
					$param_val = $product->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 } else { 
					// это атрибут вариации
					$param_val = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 }
				 // если этот параметр не нужно выгружать - пропускаем
				 $variation_id_string = (string)$param->get_id(); // важно, т.к. в настройках id как строки
				 if (!in_array($variation_id_string, $add_in_name_arr, true)) {continue;}
				 $param_name = wc_attribute_label(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 // если пустое имя атрибута или значение - пропускаем
				 if (empty($param_name) || empty($param_val)) {continue;}
				 $param_at_name .= $param_name.'-'.ucfirst(urldecode($param_val)).' ';
				}
				$param_at_name = trim($param_at_name);
				if ($param_at_name == '') {continue;}
				$result_yml_name_itog = $result_yml_name.' ('.$param_at_name.')';
			  } else {
				$gi = 'group_id="'.$product->get_id().'"';
				$result_yml_name_itog = $result_yml_name;
			  }
			 }
			 
			 $result_yml .= '<offer '.$gi.' id="'.$product->get_id().'var'.$offer_id.'" available="'.$available.'">'.PHP_EOL;
			 do_action('yfym_prepend_variable_offer');	
			 /*$param_at_name = '';
			 // Param в вариациях
			 if (!empty($params_arr)) {
				$attributes = $product->get_attributes(); // получили все атрибуты товара		 
				foreach ($attributes as $param) {					
				 if ($param->get_variation() == false) {
					// это обычный атрибут
					$param_val = $product->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id())); 
				 } else { 
					// это атрибут вариации
					$param_val = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id())); 
				 }				
				 // если этот параметр не нужно выгружать - пропускаем
				 $variation_id_string = (string)$param->get_id(); // важно, т.к. в настройках id как строки
				 if (!in_array($variation_id_string, $params_arr, true)) {continue;}
				 $param_name = wc_attribute_label(wc_attribute_taxonomy_name_by_id($param->get_id()));
				 // если пустое имя атрибута или значение - пропускаем
				 if (empty($param_name) || empty($param_val)) {continue;}
				 $result_yml .= '<param name="'.$param_name.'">'.ucfirst(urldecode($param_val)).'</param>'.PHP_EOL;
				 $param_at_name .= ucfirst(urldecode($param_val)).' ';
				}	
			 }
			 $param_at_name = trim($param_at_name);*/
			 
			 $result_yml .= "<name>".$result_yml_name_itog."</name>".PHP_EOL;
			 // $result_yml .= "<name>".$result_yml_name." (".$param_at_name.")</name>".PHP_EOL;

			 // Описание.
			 $description_yml = $offer->get_description();
			 if (!empty($description_yml)) {
				$description_yml = strip_tags($description_yml, '<p>,<h3>,<ul>,<li>,<br/>,<br>');			
				$result_yml .= '<description><![CDATA['.$description_yml.']]></description>'.PHP_EOL;
			 } else {
				// если у вариации нет своего описания - пробуем подставить общее
				if (!empty($result_yml_desc)) {$result_yml .= $result_yml_desc;}
			 }
			 
			 $thumb_yml = get_the_post_thumbnail_url($offer->get_id(), 'full');
			 if (empty($thumb_yml)) {
				$thumb_id = get_post_thumbnail_id();			 
				$thumb_url = wp_get_attachment_image_src($thumb_id,'full', true);	
				$thumb_yml = $thumb_url[0]; /* урл оригинал миниатюры товара */
				$picture_yml = '<picture>'.deleteGET($thumb_yml).'</picture>'.PHP_EOL;
			 } else {
				$picture_yml = '<picture>'.deleteGET($thumb_yml).'</picture>'.PHP_EOL;
			 }
			 $picture_yml = apply_filters('yfym_pic_variable_offer_filter', $picture_yml, $product);
			 $result_yml .= $picture_yml;	
			 
			 $result_yml .= "<url>".htmlspecialchars(get_permalink($offer->get_id()))."</url>".PHP_EOL;
			 
			 $yfym_price_from = get_option('yfym_price_from');
			 if ($yfym_price_from == 'yes') {
				$result_yml .= "<price from='true'>".$price_yml."</price>".PHP_EOL;
			 } else {
				$result_yml .= "<price>".$price_yml."</price>".PHP_EOL;
			 }
			 // старая цена
			 $yfym_oldprice = get_option('yfym_oldprice');
			 if ($yfym_oldprice == 'yes') {
			  $sale_price = $offer->get_sale_price();
			  if ($sale_price > 0 && !empty($sale_price)) {
				$oldprice_yml = $offer->get_regular_price();
				$result_yml .= "<oldprice>".$oldprice_yml."</oldprice>".PHP_EOL;
			  }
			 }			 
			 $result_yml .= '<currencyId>'.$currencyId_yml.'</currencyId>'.PHP_EOL;			 
			 			 
			 // штрихкод			 
			 $yfym_barcode = get_option('yfym_barcode');
			 if ($yfym_barcode !== 'none') {
				switch ($yfym_barcode) { /* off, sku, или id */
				 case "off":	
					// выгружать штрихкод нет нужды
				 break; 
				 case "sku":
					// выгружать из артикула
					$sku_yml = $offer->get_sku(); // артикул
					if (!empty($sku_yml)) {
					  $result_yml .= "<barcode>".$sku_yml."</barcode>".PHP_EOL;
					} else {
					  // своего артикула у вариации нет. Пробуем подставить общий sku
					  $sku_yml = $product->get_sku();
					  if (!empty($sku_yml)) {
						$result_yml .= "<barcode>".$sku_yml."</barcode>".PHP_EOL;
					  }
					}
				 break;
				 default:
					$yfym_barcode_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($yfym_barcode));					
					if (!empty($yfym_barcode_yml)) {
						$result_yml .= '<barcode>'.urldecode($yfym_barcode_yml).'</barcode>'.PHP_EOL;
					}
				}
			 }
			 
			 $weight_yml = $offer->get_weight(); // вес
			 if (!empty($weight_yml)) {
				$weight_yml = round(wc_get_weight($weight_yml, 'kg'), 3);
				$result_yml .= "<weight>".$weight_yml."</weight>".PHP_EOL;
			 }

			 $dimensions = $offer->get_dimensions();
			 if (!empty($dimensions)) {
			  $length_yml = $offer->get_length();
			  if (!empty($length_yml)) {$length_yml = round(wc_get_dimension($length_yml, 'cm'), 3);}
			   
			  $width_yml = $offer->get_width();
			  if (!empty($length_yml)) {$width_yml = round(wc_get_dimension($width_yml, 'cm'), 3);}
			  
			  $height_yml = $offer->get_height();
			  if (!empty($length_yml)) {$height_yml = round(wc_get_dimension($height_yml, 'cm'), 3);}		  
			   
			  if (($length_yml > 0) && ($width_yml > 0) && ($height_yml > 0)) {
				$result_yml .= '<dimensions>'.$length_yml.'/'.$width_yml.'/'.$height_yml.'</dimensions>'.PHP_EOL;
			  }
			 }			 

			 
			 $expiry = get_option('yfym_expiry');
			 if (!empty($expiry) && $expiry !== 'off') {	
				$expiry_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($expiry));
				if (!empty($expiry_yml)) {	
					$result_yml .= "<expiry>".ucfirst(urldecode($expiry_yml))."</expiry>".PHP_EOL;		
				}
			 }
			 $age = get_option('yfym_age');
			 if (!empty($age) && $age !== 'off') {	
				$age_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($age));
				if (!empty($age_yml)) {	
					$result_yml .= "<age>".ucfirst(urldecode($age_yml))."</age>".PHP_EOL;		
				} else {
					$age_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($age));
					if (!empty($age_yml)) {	
					 $result_yml .= "<age>".ucfirst(urldecode($age_yml))."</age>".PHP_EOL;		
					}		
				}
			 }
			 $downloadable = get_option('yfym_downloadable');
			 if (!empty($downloadable) && $downloadable !== 'off') {
				if ($offer->is_downloadable('yes')) {
					$result_yml .= "<downloadable>true</downloadable>".PHP_EOL;	
				} else {
					$result_yml .= "<downloadable>false</downloadable>".PHP_EOL;							
				}
			 }
			 
			 // страна производитель
			 $country_of_origin = get_option('yfym_country_of_origin');
			 if (!empty($country_of_origin) && $country_of_origin !== 'off') {			
				$country_of_origin_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($country_of_origin));
				if (!empty($country_of_origin_yml)) {	
					$result_yml .= "<country_of_origin>".ucfirst(urldecode($country_of_origin_yml))."</country_of_origin>".PHP_EOL;		
				}				
			 }

			 

			 
		  $sales_notes_cat = get_option('yfym_sales_notes_cat');
		  if (!empty($sales_notes_cat) && $sales_notes_cat !== 'off') {	
		   $sales_notes_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($sales_notes_cat));
		   if (empty($sales_notes_yml)) {
			$sales_notes_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($sales_notes_cat));
		   }    
		   if (!empty($sales_notes_yml)) {	
			$result_yml .= "<sales_notes>".ucfirst(urldecode($sales_notes_yml))."</sales_notes>".PHP_EOL;		
		   } else {
			$sales_notes = get_option('yfym_sales_notes');
			if (!empty($sales_notes)) {
				$result_yml .= "<sales_notes>$sales_notes</sales_notes>".PHP_EOL;
			}
		   }
		  }	/*
			 $sales_notes = get_option('yfym_sales_notes');
			 if (!empty($sales_notes)) {
				$result_yml .= "<sales_notes>$sales_notes</sales_notes>".PHP_EOL;
			 }	*/		 
			
			 // гарантия
			 $manufacturer_warranty = get_option('yfym_manufacturer_warranty');
			 if (!empty($manufacturer_warranty) && $manufacturer_warranty !== 'off') {
				$manufacturer_warranty_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($manufacturer_warranty));
				if (!empty($manufacturer_warranty_yml)) {	
					$result_yml .= "<manufacturer_warranty>".urldecode($manufacturer_warranty_yml)."</manufacturer_warranty>".PHP_EOL;
				} else {$manufacturer_warranty_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($manufacturer_warranty));
					if (!empty($manufacturer_warranty_yml)) {	
						$result_yml .= "<manufacturer_warranty>".urldecode($manufacturer_warranty_yml)."</manufacturer_warranty>".PHP_EOL;
					}
				}					
			 }

			 $vendor = get_option('yfym_vendor');
			 if ($vendor !== 'none') {
				$vendor_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($vendor));
				if (!empty($vendor_yml)) {
				 $result_yml .= '<vendor>'.ucfirst(urldecode($vendor_yml)).'</vendor>'.PHP_EOL;
				} else {$vendor_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($vendor));
					if (!empty($vendor_yml)) {
						$result_yml .= '<vendor>'.ucfirst(urldecode($vendor_yml)).'</vendor>'.PHP_EOL;
					}
				}
			 }	
			 $model = get_option('yfym_model');
			 if ($model !== 'none') {
				$model_yml = $offer->get_attribute(wc_attribute_taxonomy_name_by_id($model));
				if (!empty($model_yml)) {				 
				 $result_yml .= '<model>'.ucfirst(urldecode($model_yml)).'</model>'.PHP_EOL;
				} else {$model_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($model));
					if (!empty($model_yml)) {				 
						$result_yml .= '<model>'.ucfirst(urldecode($model_yml)).'</model>'.PHP_EOL;
					}
				}
			 }
			 
			 $result_yml .= $result_yml_store;
			 $result_yml .= $result_yml_pickup;
			 $result_yml .= $result_yml_delivery;
			 $result_yml .= $result_yml_cat; // Категории			 

			 do_action('yfym_append_variable_offer');
			 $result_yml = apply_filters('yfym_append_variable_offer_filter', $result_yml, $product, $offer);	 
			 
			 $result_yml .= '</offer>'.PHP_EOL;

			 do_action('yfym_after_variable_offer');			 
			}
			continue; // все вариации выгрузили - переходим к след товару
		  } // end if ($product->is_type('variable'))
		  /* end Вариации */				  
		  // если цена не указана - пропускаем товар
		  $price_yml = $product->get_price();
		  if ($price_yml == 0 || empty($price_yml)) {continue;}
		  
		  // пропуск товаров, которых нет в наличии
		  $yfym_skip_missing_products = get_option('yfym_skip_missing_products');
		  if ($yfym_skip_missing_products == 'on') {
			if ($product->is_in_stock() == false) {continue;}
		  }		  

		  // пропускаем товары на предзаказ
		  $skip_backorders_products = get_option('yfym_skip_backorders_products');
		  if ($skip_backorders_products == 'on') {
			if ($product->get_manage_stock() == true) { // включено управление запасом			  
				if (($product->get_stock_quantity() < 1) && ($product->get_backorders() !== 'no')) {continue;}
				//if (($offer->get_backorders() !== 'no') && ($offer->is_in_stock() == false)) {continue;}
			} else {
				if ($product->get_stock_status() !== 'instock') {continue;}
			}
		  }  /*$skip_backorders_products = get_option('yfym_skip_backorders_products');
		  if ($skip_backorders_products == 'on') {
			if (($offer->get_backorders() !== 'no') && ($offer->is_in_stock() == false)) {continue;}
		  }*/
		  
		  do_action('yfym_before_simple_offer');
		  
		  /* Обычный товар */
		  if ($product->get_manage_stock() == true) { // включено управление запасом
			  if ($product->get_stock_quantity() > 0) {
				$available = 'true';
			} else {
				$available = 'false';
			}
	      } else { // отключено управление запасом
			 if ($product->is_in_stock() == true) {$available = 'true';} else {$available = 'false';}
	      }
		  		  
		  $offer_type = '';
		  $offer_type = apply_filters('yfym_offer_type_filter', $offer_type, $catpostid);
		  $result_yml .= '<offer '.$offer_type.' id="'.get_the_ID().'" available="'.$available.'">'.PHP_EOL;
		  do_action('yfym_prepend_simple_offer');
		  $params_arr = unserialize(get_option('yfym_params_arr'));		  
		  if (!empty($params_arr)) {		
			$attributes = $product->get_attributes();				
			foreach ($attributes as $param) {
			 // проверка на вариативность атрибута не нужна
			 $param_val = $product->get_attribute(wc_attribute_taxonomy_name_by_id($param->get_id()));		
			 // если этот параметр не нужно выгружать - пропускаем
			 $variation_id_string = (string)$param->get_id(); // важно, т.к. в настройках id как строки
			 if (!in_array($variation_id_string, $params_arr, true)) {continue;}
			 $param_name = wc_attribute_label(wc_attribute_taxonomy_name_by_id($param->get_id()));
			 // если пустое имя атрибута или значение - пропускаем
			 if (empty($param_name) || empty($param_val)) {continue;}
			 $result_yml .= '<param name="'.$param_name.'">'.ucfirst(urldecode($param_val)).'</param>'.PHP_EOL;
			}
		  }
		  			
		  $result_yml .= "<name>".$result_yml_name."</name>".PHP_EOL;
			
		  // описание
		  $result_yml .= $result_yml_desc;
		  
		  $thumb_id = get_post_thumbnail_id();
		  $thumb_url = wp_get_attachment_image_src($thumb_id,'full', true);	
		  $thumb_yml = $thumb_url[0]; /* урл оригинал миниатюры товара */
		  $picture_yml = '<picture>'.deleteGET($thumb_yml).'</picture>'.PHP_EOL;		  
		  $picture_yml = apply_filters('yfym_pic_simple_offer_filter', $picture_yml, $product);
		  $result_yml .= $picture_yml;
		   
		  $url_yml = get_permalink(); // урл товара
		  $result_yml .= "<url>".$url_yml."</url>".PHP_EOL;
		  
		  $yfym_price_from = get_option('yfym_price_from');
		  if ($yfym_price_from == 'yes') {
			$result_yml .= "<price from='true'>".$price_yml."</price>".PHP_EOL;
		  } else {
			$result_yml .= "<price>".$price_yml."</price>".PHP_EOL;
		  }
		  // старая цена
		  $yfym_oldprice = get_option('yfym_oldprice');
			if ($yfym_oldprice == 'yes') {
				$sale_price = $product->get_sale_price();
			if ($sale_price > 0 && !empty($sale_price)) {
				$oldprice_yml = $product->get_regular_price();
				$result_yml .= "<oldprice>".$oldprice_yml."</oldprice>".PHP_EOL;
			}
		  }			  
		  
		  $result_yml .= '<currencyId>'.$currencyId_yml.'</currencyId>'.PHP_EOL;		  
		  
		  // штрихкод			 
		  $yfym_barcode = get_option('yfym_barcode');
		  if ($yfym_barcode !== 'none') {
			switch ($yfym_barcode) { /* off, sku, или id */
			 case "off":	
				// выгружать штрихкод нет нужды
			 break; 
			 case "sku":
				// выгружать из артикула
				$sku_yml = $product->get_sku();
				if (!empty($sku_yml)) {
					$result_yml .= "<barcode>".$sku_yml."</barcode>".PHP_EOL;
				}			
			 break;
			 default:
				$yfym_barcode_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($yfym_barcode));				
				if (!empty($yfym_barcode_yml)) {
					$result_yml .= '<barcode>'.urldecode($yfym_barcode_yml).'</barcode>'.PHP_EOL;
				}
			}
		  }			  

		  $weight_yml = $product->get_weight(); // вес
		  if (!empty($weight_yml)) {
			$weight_yml = round(wc_get_weight($weight_yml, 'kg'), 3);
			$result_yml .= "<weight>".$weight_yml."</weight>".PHP_EOL;
		  }

		  $dimensions = $product->get_dimensions();
		  if (!empty($dimensions)) {
		   $length_yml = $product->get_length();
		   if (!empty($length_yml)) {$length_yml = round(wc_get_dimension($length_yml, 'cm'), 3);}
		   
		   $width_yml = $product->get_width();
		   if (!empty($length_yml)) {$width_yml = round(wc_get_dimension($width_yml, 'cm'), 3);}
		   
		   $height_yml = $product->get_height();
		   if (!empty($length_yml)) {$height_yml = round(wc_get_dimension($height_yml, 'cm'), 3);}		  
		   
		   if (($length_yml > 0) && ($width_yml > 0) && ($height_yml > 0)) {
			$result_yml .= '<dimensions>'.$length_yml.'/'.$width_yml.'/'.$height_yml.'</dimensions>'.PHP_EOL;
		   }
		  }

			 $expiry = get_option('yfym_expiry');
			 if (!empty($expiry) && $expiry !== 'off') {	
				$expiry_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($expiry));
				if (!empty($expiry_yml)) {	
					$result_yml .= "<expiry>".ucfirst(urldecode($expiry_yml))."</expiry>".PHP_EOL;		
				}
			 }
			 $age = get_option('yfym_age');
			 if (!empty($age) && $age !== 'off') {	
				$age_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($age));
				if (!empty($age_yml)) {	
					$result_yml .= "<age>".ucfirst(urldecode($age_yml))."</age>".PHP_EOL;		
				}
			 }
			 $downloadable = get_option('yfym_downloadable');
			 if (!empty($downloadable) && $downloadable !== 'off') {
				if ($product->is_downloadable('yes')) {
					$result_yml .= "<downloadable>true</downloadable>".PHP_EOL;	
				} else {
					$result_yml .= "<downloadable>false</downloadable>".PHP_EOL;							
				}
			 }
		  
		  $sales_notes_cat = get_option('yfym_sales_notes_cat');
		  if (!empty($sales_notes_cat) && $sales_notes_cat !== 'off') {	
		   $sales_notes_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($sales_notes_cat));
		   if (!empty($sales_notes_yml)) {	
			$result_yml .= "<sales_notes>".ucfirst(urldecode($sales_notes_yml))."</sales_notes>".PHP_EOL;		
		   } else {
			$sales_notes = get_option('yfym_sales_notes');
			if (!empty($sales_notes)) {
				$result_yml .= "<sales_notes>$sales_notes</sales_notes>".PHP_EOL;
			}
		   }
		  }			  
		  
		  // страна производитель
		  $country_of_origin = get_option('yfym_country_of_origin');
		  if (!empty($country_of_origin) && $country_of_origin !== 'off') {			
		  $country_of_origin_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($country_of_origin));
			if (!empty($country_of_origin_yml)) {	
				$result_yml .= "<country_of_origin>".ucfirst(urldecode($country_of_origin_yml))."</country_of_origin>".PHP_EOL;		
			}				
		  }
		  		  
		  // гарантия
		  $manufacturer_warranty = get_option('yfym_manufacturer_warranty');
		  if (!empty($manufacturer_warranty) && $manufacturer_warranty !== 'off') {
			$manufacturer_warranty_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($manufacturer_warranty));
			if (!empty($manufacturer_warranty_yml)) {	
				$result_yml .= "<manufacturer_warranty>".urldecode($manufacturer_warranty_yml)."</manufacturer_warranty>".PHP_EOL;
			}					
		  }
		  
		  global $wpdb;
		  $vendor = get_option('yfym_vendor');
		  if ($vendor !== 'none') {
			$vendor_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($vendor));
			if (!empty($vendor_yml)) {
			 $result_yml .= '<vendor>'.$vendor_yml.'</vendor>'.PHP_EOL;
			}
		  }			
		  $model = get_option('yfym_model');
		  if ($model !== 'none') {
			$model_yml = $product->get_attribute(wc_attribute_taxonomy_name_by_id($model));
			if (!empty($model_yml)) {				
			 $result_yml .= '<model>'.$model_yml.'</model>'.PHP_EOL;
			}
		  }			

		  // если offer_type пуст, то можно выгружать vendorCode
		  if ($offer_type =='') {
		   $sku_yml = $product->get_sku(); // артикул
		   if ($sku_yml !== '') {
			$result_yml .= "<vendorCode>".$sku_yml."</vendorCode>".PHP_EOL;
		   }		  
		  }
		  
		  // do_action('yfym_after_sku_simple_offer');
		  
		  $result_yml .= $result_yml_store;
		  $result_yml .= $result_yml_pickup;
		  $result_yml .= $result_yml_delivery;
		  $result_yml .= $result_yml_cat; // Категории
		  
		  do_action('yfym_append_simple_offer'); 
		  $result_yml = apply_filters('yfym_append_simple_offer_filter', $result_yml, $product);
		  
		  $result_yml .= '</offer>'.PHP_EOL;
		  
		  do_action('yfym_after_simple_offer');
		 } /* end while */ 

		 /* создаем файл или перезаписываем старый удалив содержимое */
		 $result = yfym_write_file($result_yml,'a');
		 if ($result == true) {
			// увеличиваем счетчик статуса сборки
			$status_sborki = $status_sborki + $step_export;
			error_log('status_sborki увеличен на '.$step_export.' и равен '.$status_sborki, 0);
			update_option('yfym_status_sborki', $status_sborki);
		 } else {
			error_log('yfym_write_file вернула ошибку... line 1438', 0);
			return;
		 }		 
		 
		 wp_reset_query(); /* Remember to reset */
		} else {
		 // если постов нет, пишем концовку файла
		 $result_yml .= "</offers>". PHP_EOL; 
		 $result_yml = apply_filters('yfym_after_offers_filter', $result_yml);
		 $result_yml .= "</shop>". PHP_EOL ."</yml_catalog>";
		 /* создаем файл или перезаписываем старый удалив содержимое */
		 $result = yfym_write_file($result_yml,'a');
		 yfym_rename_file();		 
		 // выставляем статус сборки в "готово"
		 $status_sborki = -1;
		 if ($result == true) {
			update_option('yfym_status_sborki', $status_sborki);
			// останавливаем крон сборки
			wp_clear_scheduled_hook( 'yfym_cron_sborki' );
		 } else {
			error_log('yfym_write_file вернула ошибку... Я не смог записать концовку файла...', 0);
			return;
		 }
		}
	}  	  
  }
 } /* end yfym_construct_yml */
 
 public function yfym_admin_notices_function() {
  if (is_multisite()) {
	if (get_blog_option(get_current_blog_id(), 'yfym_magazin_type') == 'woocommerce') { 
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			//Плагин woocommerce активен. Все ок.
		} else {
			print '<div class="notice error is-dismissible"><p>'. __('WooCommerce is not active!', 'yfym'). '.</p></div>';
		}
	}
	
	$status_sborki = (int)get_blog_option(get_current_blog_id(), 'yfym_status_sborki');
	
	if (get_blog_option(get_current_blog_id(), 'yfym_version') == false) {
		print '<div class="notice error is-dismissible"><p>'. __("Plugin YML for Yandex Market has been updated!", "yfym").' '.__("On the plugin settings page click on the", "yfym").' "'.__("Reset plugin settings", "yfym"). '".</p></div>';
	}
  } else {
	if (get_option('yfym_magazin_type') == 'woocommerce') { 
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			//Плагин woocommerce активен. Все ок.
		} else {
			print '<div class="notice error is-dismissible"><p>'. __('WooCommerce is not active!', 'yfym'). '.</p></div>';
		}
	}
	
	$status_sborki = (int)get_option('yfym_status_sborki');	

	if (get_option('yfym_version') == false) {
		print '<div class="notice error is-dismissible"><p>'. __("Plugin YML for Yandex Market has been updated!", "yfym").' '.__("On the plugin settings page click on the", "yfym").' "'.__("Reset plugin settings", "yfym"). '".</p></div>';
	}
  }
	if ($status_sborki !== -1) {	
		$count_posts = wp_count_posts('product');
		$vsegotovarov = $count_posts->publish;
		if (is_multisite()) {
			$step_export = (int)get_blog_option(get_current_blog_id(), 'yfym_step_export');
		} else {
			$step_export = (int)get_option('yfym_step_export');
		}
		if ($step_export == 0) {$step_export = 500;}		
		$vobrabotke = $status_sborki-$step_export;
		if ($vsegotovarov > $vobrabotke) {
			$vyvod = __('Progress', 'yfym').': '.$vobrabotke.' '. __('from', 'yfym').' '.$vsegotovarov.' '. __('products', 'yfym') .'.<br />'.__('If the progress indicators have not changed within 20 minutes, try reducing the "Step of export" in the plugin settings', 'yfym');
		} else {
			$vyvod = __('Prior to the completion of less than 70 seconds', 'yfym');
		}	
		print '<div class="updated notice notice-success is-dismissible"><p>'. __('We are working on automatic file creation. YML will be developed soon', 'yfym').'. '.$vyvod.'.</p></div>';
	}	
	if (isset($_REQUEST['yfym_submit_action'])) {
		$run_text = '';
		if (sanitize_text_field($_POST['yfym_run_cron']) !== 'off') {
			$run_text = '. '. __('Creating the feed is running. You can continue working with the website', 'yfym');
		}
		print '<div class="updated notice notice-success is-dismissible"><p>'. __('Updated', 'yfym'). $run_text .'.</p></div>';
	}
	if (isset($_REQUEST['yfym_submit_reset'])) {
		print '<div class="updated notice notice-success is-dismissible"><p>'. __('The settings have been reset', 'yfym'). '.</p></div>';		
	}
	if (isset($_REQUEST['yfym_submit_send_stat'])) {
		print '<div class="updated notice notice-success is-dismissible"><p>'. __('The data has been sent. Thank you', 'yfym'). '.</p></div>';		
	}	
	

 }
} /* end class YmlforYandexMarket */

 function yfym_get_woo_version_number() {
	// If get_plugins() isn't available, require it
	if (!function_exists('get_plugins')) {
	 require_once( ABSPATH . 'wp-admin/includes/plugin.php');
	}
	// Create the plugins folder and file variables
	$plugin_folder = get_plugins('/' . 'woocommerce');
	$plugin_file = 'woocommerce.php';
	
	// If the plugin version number is set, return it 
	if (isset( $plugin_folder[$plugin_file]['Version'] ) ) {
	 return $plugin_folder[$plugin_file]['Version'];
	} else {	
	 return NULL;
	}
 }

 function yfym_rename_file() {
	/* Перименовывает временный файл в основной. Возвращает true/false */
	if (is_multisite()) {
		$upload_dir = (object)wp_upload_dir();
		$filenamenew = $upload_dir->basedir."/feed-yml-".get_current_blog_id().".xml";
		$filenamenewurl = $upload_dir->baseurl."/feed-yml-".get_current_blog_id().".xml";		
		// $filenamenew = BLOGUPLOADDIR."feed-yml-".get_current_blog_id().".xml";
		// надо придумать как поулчить урл загрузок конкретного блога
	} else {
		$upload_dir = (object)wp_upload_dir();
		/*
		*   'path'    => '/home/site.ru/public_html/wp-content/uploads/2016/04',
		*	'url'     => 'http://site.ru/wp-content/uploads/2016/04',
		*	'subdir'  => '/2016/04',
		*	'basedir' => '/home/site.ru/public_html/wp-content/uploads',
		*	'baseurl' => 'http://site.ru/wp-content/uploads',
		*	'error'   => false,
		*/
		$filenamenew = $upload_dir->basedir."/feed-yml-0.xml";
		$filenamenewurl = $upload_dir->baseurl."/feed-yml-0.xml";
	}
	$filenameold = urldecode(get_option('yfym_file_file'));
	if (rename($filenameold, $filenamenew) === FALSE) {
		error_log('Не могу переименовать файл. line 1816', 0);
		return false;
	} else {		
		update_option('yfym_file_url', urlencode($filenamenewurl));
		error_log('Ура! Файл переименован. line 1820', 0);
		return true;
	}
 }

 function yfym_errors_log($message) {
	if (is_multisite()) {
		update_blog_option(get_current_blog_id(), 'yfym_errors', $message);
	} else {
		update_option('yfym_errors', $message);
	}
 }
 
 function yfym_write_file($result_yml, $cc) {
	/* $cc = 'w+' или 'a'; */	 
	error_log('Стартовала yfym_write_file c параметром cc = '.$cc, 0);
	if (is_multisite()) {
		$filename = urldecode(get_blog_option(get_current_blog_id(), 'yfym_file_file'));
	} else {
		$filename = urldecode(get_option('yfym_file_file'));		
	}
	if ($filename == '') {
		// ABSPATH."wp-content/uploads/feed-yml-".get_current_blog_id().".xml";}
		if (is_multisite()) {
			// $filename = BLOGUPLOADDIR."feed-yml-".get_current_blog_id()."-tmp.xml";
			$upload_dir = (object)wp_upload_dir(); // $upload_dir->basedir 
			$filename = $upload_dir->basedir."feed-yml-".get_current_blog_id()."-tmp.xml"; // $upload_dir->path
		} else {
			$upload_dir = (object)wp_upload_dir(); // $upload_dir->basedir 
			$filename = $upload_dir->basedir."feed-yml-0-tmp.xml"; // $upload_dir->path
		}
	}
		
	// if ((validate_file($filename) === 0)&&(file_exists($filename))) {
	if (file_exists($filename)) {
		// файл есть
		if (!$handle = fopen($filename, $cc)) {
			error_log('Не могу открыть файл. line 1855', 0);
			yfym_errors_log('Не могу открыть файл. line 1856');
		}
		if (fwrite($handle, $result_yml) === FALSE) {
			error_log('Не могу произвести запись в файл. line 1860', 0);
			yfym_errors_log('Не могу произвести запись в файл. line 1861');
		} else {
			error_log('Ура! Записали.. line 1863', 0);
			return true;
		}
		fclose($handle);		
	} else {
		error_log('Файла еще нет', 0);
		// файла еще нет
		// попытаемся создать файл
		if (is_multisite()) {
			$upload = wp_upload_bits( 'feed-yml-'.get_current_blog_id().'-tmp.xml', null, $result_yml ); // загружаем shop2_295221-yml в папку загрузок
		} else {
			$upload = wp_upload_bits( 'feed-yml-0-tmp.xml', null, $result_yml ); // загружаем shop2_295221-yml в папку загрузок
		}
		/*
		*	для работы с csv или xml требуется в плагине разрешить загрузку таких файлов
		*	$upload['file'] => '/var/www/wordpress/wp-content/uploads/2010/03/feed-yml.xml', // путь
		*	$upload['url'] => 'http://site.ru/wp-content/uploads/2010/03/feed-yml.xml', // урл
		*	$upload['error'] => false, // сюда записывается сообщение об ошибке в случае ошибки
		*/
		// проверим получилась ли запись
		if ($upload['error']) {
			error_log('Запись вызвала ошибку: '. $upload['error'], 0); 
			$err = 'Запись вызвала ошибку: '. $upload['error'];
			yfym_errors_log($err);
		} else {
			if (is_multisite()) {
				//update_blog_option(get_current_blog_id(), 'yfym_file_url', urlencode($upload['url']));
				update_blog_option(get_current_blog_id(), 'yfym_file_file', urlencode($upload['file']));
			} else {
				//update_option('yfym_file_url', urlencode($upload['url']));
				update_option('yfym_file_file', urlencode($upload['file']));
			}
			error_log('Запись удалась! Путь файла: '. $upload['file'] .'; УРЛ файла: '. $upload['url'], 0);
			return true;
		}
		
	}
 }
?>