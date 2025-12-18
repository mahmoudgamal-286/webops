<?php
/**
 * Plugin Name: MAHMOUD GAMAL
 * Plugin URI: https://github.com/mahmoudgamal
 * Description: Bulk page creation via secure REST API with API key authentication, rate limiting, and webhook notifications.
 * Version: 1.0.0
 * Author: MAHMOUD GAMAL
 * Author URI: https://github.com/mahmoudgamal
 * License: GPL v2 or later
 * Text Domain: mahmoud-gamal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPB_PLUGIN_VERSION', '1.0.0' );
define( 'SPB_PLUGIN_FILE', __FILE__ );
define( 'SPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load core.
require_once SPB_PLUGIN_DIR . 'includes/class-spb-core.php';

// Initialize plugin.
$spb_core = new SPB_Core();
$spb_core->init();

// Activation / deactivation hooks.
register_activation_hook( SPB_PLUGIN_FILE, array( 'SPB_Core', 'activate' ) );
register_deactivation_hook( SPB_PLUGIN_FILE, array( 'SPB_Core', 'deactivate' ) );

