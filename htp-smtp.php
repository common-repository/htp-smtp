<?php
/**
 * Plugin Name:       HTP SMTP
 * Plugin URI:        https://hutanatu.com/plugin/htp-smtp/
 * Description:       HTP SMTP can help us to send emails via SMTP instead of the PHP mail() function and email logger built-in.
 * Version:           1.1.3
 * Author:            HuTaNaTu
 * Author URI:        https://hutanatu.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       htp-smtp
 */

use HTP_SMTP\Main;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/vendor/autoload.php';
if ( ! defined( 'HTP_SMTP_FILE' ) ) {
	define( 'HTP_SMTP_FILE', __FILE__ );
}
if ( ! defined( 'HTP_SMTP_DIR' ) ) {
	define( 'HTP_SMTP_DIR', trailingslashit( plugin_dir_path( HTP_SMTP_FILE ) ) );
}
if ( ! defined( 'HTP_SMTP_URL' ) ) {
	define( 'HTP_SMTP_URL', trailingslashit( plugins_url( '', HTP_SMTP_FILE ) ) );
}

function htp_smtp() {
	return Main::instance();
}

$GLOBALS['htp-smtp'] = htp_smtp();