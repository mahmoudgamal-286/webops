<?php
/**
 * Webhook notification handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPB_Webhook {

	/**
	 * Register hooks for async processing.
	 */
	public static function register_hooks() {
		add_action( 'spb_webhook_retry', array( __CLASS__, 'process_scheduled' ), 10, 1 );
	}

	/**
	 * Send webhook with retry + HMAC signature.
	 */
	public static function send_notification( $page_id, $title, $url, $key_name ) {
		$webhook_url    = get_option( 'spb_webhook_url', '' );
		$webhook_secret = get_option( 'spb_webhook_secret', '' );

		if ( empty( $webhook_url ) || empty( $webhook_secret ) ) {
			return;
		}

		$payload = array(
			'page_id'   => $page_id,
			'title'     => $title,
			'url'       => $url,
			'key_name'  => $key_name,
			'timestamp' => gmdate( 'c' ),
		);

		$payload['key_id'] = (int) get_post_meta( $page_id, '_spb_created_by_key', true );

		// Immediate attempt; schedule retries if needed.
		$result = self::attempt_send( $payload, $webhook_url, $webhook_secret );
		if ( ! $result ) {
			self::schedule_retry( $payload, 1, 5 );
			self::schedule_retry( $payload, 2, 25 );
		}
	}

	/**
	 * Handle scheduled retries.
	 */
	public static function process_scheduled( $args ) {
		if ( empty( $args['payload'] ) || empty( $args['webhook_url'] ) || empty( $args['webhook_secret'] ) ) {
			return;
		}
		self::attempt_send( $args['payload'], $args['webhook_url'], $args['webhook_secret'], true );
	}

	/**
	 * Attempt to send webhook once and log outcome.
	 */
	private static function attempt_send( $payload, $webhook_url, $webhook_secret, $is_retry = false ) {
		$body      = wp_json_encode( $payload );
		$signature = hash_hmac( 'sha256', $body, $webhook_secret );
		$headers   = array(
			'Content-Type'        => 'application/json',
			'X-Webhook-Signature' => $signature,
		);

		$response = wp_remote_post(
			$webhook_url,
			array(
				'headers' => $headers,
				'body'    => $body,
				'timeout' => 10,
			)
		);

		$success = ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) < 300;

		SPB_DB_Manager::log_webhook(
			array(
				'key_id'        => isset( $payload['key_id'] ) ? (int) $payload['key_id'] : 0,
				'endpoint'      => 'webhook_notification',
				'status'        => $success ? 'success' : 'failed',
				'response_time' => 0,
				'ip'            => '',
				'pages_created' => 1,
			)
		);

		return $success;
	}

	/**
	 * Schedule retry with WP-Cron (non-blocking).
	 */
	private static function schedule_retry( $payload, $attempt, $delay_seconds ) {
		$webhook_url    = get_option( 'spb_webhook_url', '' );
		$webhook_secret = get_option( 'spb_webhook_secret', '' );

		if ( empty( $webhook_url ) || empty( $webhook_secret ) ) {
			return;
		}

		if ( ! wp_next_scheduled( 'spb_webhook_retry', array( array(
			'payload'       => $payload,
			'webhook_url'   => $webhook_url,
			'webhook_secret'=> $webhook_secret,
			'attempt'       => $attempt,
		) ) ) ) {
			wp_schedule_single_event(
				time() + $delay_seconds,
				'spb_webhook_retry',
				array(
					array(
						'payload'        => $payload,
						'webhook_url'    => $webhook_url,
						'webhook_secret' => $webhook_secret,
						'attempt'        => $attempt,
					),
				)
			);
		}
	}
}

