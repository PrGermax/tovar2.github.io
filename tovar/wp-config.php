<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе
 * установки. Необязательно использовать веб-интерфейс, можно
 * скопировать файл в "wp-config.php" и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки MySQL
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** Параметры MySQL: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define('DB_NAME', 'tovar');

/** Имя пользователя MySQL */
define('DB_USER', 'yanora');

/** Пароль к базе данных MySQL */
define('DB_PASSWORD', 'password');

/** Имя сервера MySQL */
define('DB_HOST', '127.0.0.1');

/** Кодировка базы данных для создания таблиц. */
define('DB_CHARSET', 'utf8mb4');

/** Схема сопоставления. Не меняйте, если не уверены. */
define('DB_COLLATE', '');

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу.
 * Можно сгенерировать их с помощью {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными. Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'co$6]+,Y]vU4d.E-V$koTWjF5B)~mvhZYwFq^o5u1_7JoVflik&6&Bg{KIpTGKvD');
define('SECURE_AUTH_KEY',  '==b9p/,2bZY hJd+?J5vxjb#6+G#a1^Gihc9WP,r@ay0GJY#hozoROB1slrs@%3E');
define('LOGGED_IN_KEY',    '7&#i_B3e~I8m9KV4oBi.:6/H-TtY#Fpiv/(z*82{H!*w-~fqtpw~yKMz:Gg(jj ,');
define('NONCE_KEY',        '2RPe%Me7qff3;]G(T>:fVtTXPL)>s}idmU4Iya>T:%KJoV`p(<,]pyDA/Cv6xB%9');
define('AUTH_SALT',        'A>oT r>pfI%CZ-Z._tdxR2fxQS`V#FHW&$_xj3FTIu`}bg+@|]tG#l0G_eN+@Q/C');
define('SECURE_AUTH_SALT', 'M))m;|f+y/!TcgvT V1{W,eH/a|WX.Z0}dG[Rm3Jv4H[R$,3N{,4n`KU#]L&o@,K');
define('LOGGED_IN_SALT',   'c5%KU!AS)LhiU^S2X9_,jcveNpb5det&~cd@L(upE<Bl99wv]o}yoS<[o## lfXt');
define('NONCE_SALT',       'N1u:}S;ds50Cqi+IPcUDd@DK|9|f[>0n&9IxT|BhnmFIN<H4-M^[TGG//Qq($I,g');

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix  = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в Кодексе.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Инициализирует переменные WordPress и подключает файлы. */
require_once(ABSPATH . 'wp-settings.php');
