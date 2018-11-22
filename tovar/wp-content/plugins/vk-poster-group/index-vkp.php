<?php
/*
  Plugin Name: VK Poster Group
  Plugin URI: http://www.zixn.ru/plagin-vk-poster-group.html
  Description: Добавляет ваши записи на страницу группы Вконтакте, простой и удобный кроспостинг в социальную сеть
  Version: 1.7.1
  Author: Djo
  Author URI: http://zixn.ru
 */

/*  Copyright 2018  Djo  (email: izm@zixn.ru)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 */
if (!defined('ABSPATH')) {
    exit;
}
add_action('wp_loaded', 'vkpg_plugin_init_core');

/**
 * Инициализация всего плагина
 */
function vkpg_plugin_init_core() {
require_once (WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/inc/vkp-core-class.php');
require_once (WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/inc/vkp-function-class.php'); //Основной функционал плагина
//require_once (WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/inc/javascript-class.php');
$vkpbase = new VKPOSTERBASE();
$vkpfun=new VKPOSTERFUNCTION;
//$vkpjs=new vkpost\jsClass();
register_deactivation_hook(__FILE__, array($vkpbase, 'deactivationPlugin'));
}




