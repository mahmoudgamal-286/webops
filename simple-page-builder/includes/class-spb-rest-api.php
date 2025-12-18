<?php
/**
 * REST API endpoints for MAHMOUD GAMAL plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPB_REST_API {

	const ROUTE_NAMESPACE = 'webops/pagebuilder/v1';
	const ROUTE_NAMESPACE_ALIAS = 'pagebuilder/v1';

	/**
	 * Register routes.
	 */
	public static function register_routes() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/create-pages',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_pages_handler' ),
				'permission_callback' => array( 'SPB_Auth', 'permission_callback' ),
			)
		);
		// Assessment-required alias namespace for backward compatibility.
		register_rest_route(
			self::ROUTE_NAMESPACE_ALIAS,
			'/create-pages',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_pages_handler' ),
				'permission_callback' => array( 'SPB_Auth', 'permission_callback' ),
			)
		);
	}

	/**
	 * Handle create pages request.
	 */
	public static function create_pages_handler( WP_REST_Request $request ) {
		$start_time  = microtime( true );
		$key_record  = SPB_Auth::get_authenticated_key();

		if ( ! $key_record ) {
			return new WP_Error( 'spb_unauthorized', __( 'Unauthorized.', 'simple-page-builder' ), array( 'status' => 401 ) );
		}

		// Enforce permissions (create_pages).
		$permissions = array();
		if ( ! empty( $key_record->permissions ) ) {
			$decoded = json_decode( $key_record->permissions, true );
			if ( is_array( $decoded ) ) {
				$permissions = $decoded;
			}
		}
		if ( empty( $permissions['create_pages'] ) ) {
			return new WP_Error( 'spb_forbidden', __( 'API key lacks create_pages permission.', 'simple-page-builder' ), array( 'status' => 403 ) );
		}

		// Try to get JSON params from request.
		$payload = $request->get_json_params();
		
		// Fallback: If get_json_params() returns null or empty, try reading raw body.
		if ( empty( $payload ) || ! is_array( $payload ) ) {
			$raw_body = $request->get_body();
			if ( ! empty( $raw_body ) ) {
				$decoded = json_decode( $raw_body, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
					$payload = $decoded;
				}
			}
		}
		
		if ( ! is_array( $payload ) || empty( $payload ) ) {
			return new WP_Error( 'spb_invalid_payload', __( 'Payload must be a non-empty JSON array of pages.', 'simple-page-builder' ), array( 'status' => 400 ) );
		}

		$created = array();
		foreach ( $payload as $page ) {
			$title   = isset( $page['title'] ) ? sanitize_text_field( $page['title'] ) : '';
			$content = isset( $page['content'] ) ? wp_kses_post( $page['content'] ) : '';

			if ( empty( $title ) || empty( $content ) ) {
				continue;
			}

			$postarr = array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			);

			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			add_post_meta( $post_id, '_spb_created_by_key', absint( $key_record->id ), true );
			add_post_meta( $post_id, '_spb_created_by_name', sanitize_text_field( $key_record->key_name ), true );

			$created[] = array(
				'id'  => $post_id,
				'url' => get_permalink( $post_id ),
			);

			SPB_Webhook::send_notification(
				$post_id,
				$title,
				get_permalink( $post_id ),
				$key_record->key_name
			);
		}

		$response_time = microtime( true ) - $start_time;
		$status        = $created ? 'success' : 'failed';

		SPB_DB_Manager::increment_request_count( $key_record->id );
		SPB_DB_Manager::log_request(
			array(
				'key_id'        => $key_record->id,
				'endpoint'      => '/create-pages',
				'status'        => $status,
				'response_time' => $response_time,
				'ip'            => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'pages_created' => count( $created ),
			)
		);

		return new WP_REST_Response(
			array(
				'status'  => $status,
				'created' => $created,
			),
			201
		);
	}
}

