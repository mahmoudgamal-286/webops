<?php
/**
 * Core bootstrap for MAHMOUD GAMAL plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPB_Core {

	/**
	 * Initializes plugin hooks.
	 */
	public function init() {
		$this->includes();

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'rest_api_init', array( 'SPB_REST_API', 'register_routes' ) );
		add_action( 'init', array( 'SPB_Webhook', 'register_hooks' ) );
		add_action( 'admin_menu', array( 'SPB_Admin', 'register_menu' ) );
		add_action( 'admin_init', array( 'SPB_Admin', 'register_settings' ) );
		add_filter( 'rest_authentication_errors', array( 'SPB_Auth', 'rest_authentication' ), 20, 1 );
	}

	/**
	 * Includes required class files.
	 */
	private function includes() {
		require_once SPB_PLUGIN_DIR . 'includes/class-spb-db-manager.php';
		require_once SPB_PLUGIN_DIR . 'includes/class-spb-auth.php';
		require_once SPB_PLUGIN_DIR . 'includes/class-spb-rest-api.php';
		require_once SPB_PLUGIN_DIR . 'includes/class-spb-webhook.php';
		require_once SPB_PLUGIN_DIR . 'includes/class-spb-admin.php';
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'simple-page-builder', false, dirname( plugin_basename( SPB_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Runs on plugin activation.
	 */
	public static function activate() {
		SPB_DB_Manager::db_install();
	}

	/**
	 * Runs on plugin deactivation.
	 */
	public static function deactivate() {
		// Placeholder for future cleanup tasks.
	}
}

