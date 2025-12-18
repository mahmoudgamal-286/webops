<?php
/**
 * Admin pages and settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPB_Admin {

	/**
	 * Register menu under Tools.
	 */
	public static function register_menu() {
		add_management_page(
			__( 'MAHMOUD GAMAL', 'simple-page-builder' ),
			__( 'MAHMOUD GAMAL', 'simple-page-builder' ),
			'manage_options',
			'spb-page-builder',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting( 'spb_settings', 'spb_webhook_url', array( 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'spb_settings', 'spb_webhook_secret', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'spb_settings', 'spb_rate_limit', array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 100 ) );
		register_setting( 'spb_settings', 'spb_global_api_access', array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => true ) );
		register_setting( 'spb_settings', 'spb_key_expiration_default', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '90 days' ) );

		add_action( 'admin_post_spb_generate_key', array( __CLASS__, 'handle_generate_key' ) );
		add_action( 'admin_post_spb_revoke_key', array( __CLASS__, 'handle_revoke_key' ) );
		add_action( 'admin_post_spb_export_logs', array( __CLASS__, 'handle_export_logs' ) );
	}

	/**
	 * Render admin page with tabs.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'simple-page-builder' ) );
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';

		$view_path = SPB_PLUGIN_DIR . 'admin/views/' . $tab . '.php';
		if ( ! file_exists( $view_path ) ) {
			$view_path = SPB_PLUGIN_DIR . 'admin/views/settings.php';
		}

		include $view_path;
	}

	/**
	 * Handle key generation.
	 */
	public static function handle_generate_key() {
		check_admin_referer( 'spb_generate_key' );

		$name       = isset( $_POST['spb_key_name'] ) ? sanitize_text_field( wp_unslash( $_POST['spb_key_name'] ) ) : '';
		$expiration = isset( $_POST['spb_key_expiration'] ) ? sanitize_text_field( wp_unslash( $_POST['spb_key_expiration'] ) ) : '';

		$result = SPB_Auth::generate_new_key( $name, $expiration );

		// Store keys in transient for 5 minutes (safer than URL encoding).
		$transient_key = 'spb_new_key_' . $result['id'] . '_' . wp_generate_password( 16, false );
		set_transient( $transient_key, array(
			'api_key'    => $result['api_key'],
			'secret_key' => $result['secret_key_plain'],
			'key_id'     => $result['id'],
		), 300 ); // 5 minutes.

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'spb-page-builder',
					'tab'          => 'api-keys',
					'generated'    => 1,
					'token'        => $transient_key,
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Handle key revoke.
	 */
	public static function handle_revoke_key() {
		check_admin_referer( 'spb_revoke_key' );

		$key_id = isset( $_GET['key_id'] ) ? absint( $_GET['key_id'] ) : 0;
		if ( $key_id ) {
			SPB_DB_Manager::update_key_status( $key_id, 'revoked' );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'spb-page-builder',
					'tab'  => 'api-keys',
					'revoked' => 1,
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Export logs as CSV.
	 */
	public static function handle_export_logs() {
		check_admin_referer( 'spb_export_logs' );

		$logs = SPB_DB_Manager::get_logs();

		nocache_headers();
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="spb_api_logs.csv"' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Key ID', 'Timestamp', 'Endpoint', 'Status', 'Response Time', 'IP', 'Pages Created' ) );

		foreach ( $logs as $log ) {
			fputcsv(
				$output,
				array(
					$log->id,
					$log->key_id,
					$log->timestamp,
					$log->endpoint,
					$log->status,
					$log->response_time,
					$log->ip_address,
					$log->pages_created,
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Get pages created via API.
	 */
	public static function get_created_pages() {
		$query = new WP_Query(
			array(
				'post_type'      => 'page',
				'posts_per_page' => 50,
				'meta_key'       => '_spb_created_by_key',
			)
		);

		return $query->posts;
	}
}

