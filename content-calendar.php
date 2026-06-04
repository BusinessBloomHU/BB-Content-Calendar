<?php
/**
 * Plugin Name: Content Calendar
 * Plugin URI: https://megjelenesmentor.hu
 * Description: Tartalomnaptár – WordPress cikkek és social media tartalmak tervezése egy helyen, AI segítséggel
 * Version: 1.0.0
 * Author: BBloom Weblapok
 * Text Domain: content-calendar
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CC_VERSION', '1.0.0' );
define( 'CC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CC_URL', plugin_dir_url( __FILE__ ) );

require_once CC_PATH . 'includes/class-meta-box.php';
require_once CC_PATH . 'includes/class-calendar.php';
require_once CC_PATH . 'includes/class-ai-connector.php';
require_once CC_PATH . 'includes/class-settings.php';

add_action( 'plugins_loaded', function () {
	new CC_Meta_Box();
	new CC_Calendar();
	new CC_AI_Connector();
	new CC_Settings();
} );

register_activation_hook( __FILE__, function () {
	if ( get_option( 'cc_post_types' ) === false ) {
		update_option( 'cc_post_types', array( 'post' ) );
	}
} );
