<?php
/**
 * Authentication and API key utilities.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPB_Auth {

	/**
	 * Cached authenticated key for current request.
	 *
	 * @var array|null
	 */
	private static $authenticated_key = null;

	/**
	 * Generate new API key and store hashed version.
	 */
	public static function generate_new_key( $name, $expiration = null ) {
		$plain_key    = wp_generate_password( 64, false, false );
		$secret_plain = wp_generate_password( 32, false, false );
		
		// Use password_hash directly instead of wp_hash_password for better compatibility.
		$hashed_key   = password_hash( $plain_key, PASSWORD_DEFAULT );
		$hashed_secret = password_hash( $secret_plain, PASSWORD_DEFAULT );

		// Verify hash immediately after generation (debug check).
		if ( ! password_verify( $plain_key, $hashed_key ) ) {
			// If verification fails, regenerate hash (should never happen, but safety check).
			$hashed_key = password_hash( $plain_key, PASSWORD_DEFAULT );
		}
		if ( ! password_verify( $secret_plain, $hashed_secret ) ) {
			$hashed_secret = password_hash( $secret_plain, PASSWORD_DEFAULT );
		}

		$expires_at = $expiration ? gmdate( 'Y-m-d H:i:s', strtotime( $expiration ) ) : null;

		$key_id = SPB_DB_Manager::insert_key(
			array(
				'key_name'   => sanitize_text_field( $name ),
				'hashed_key' => $hashed_key,
				'secret_key' => $hashed_secret,
				'expires_at' => $expires_at,
				'permissions'=> array( 'create_pages' => true ),
			)
		);

		// Immediately verify the key was stored correctly by reading it back.
		if ( $key_id ) {
			global $wpdb;
			$table = $wpdb->prefix . 'spb_api_keys';
			$stored_key = $wpdb->get_row( $wpdb->prepare( "SELECT hashed_key, secret_key FROM {$table} WHERE id = %d", $key_id ) );
			
			if ( $stored_key ) {
				// Verify the stored hash matches what we just saved.
				$key_verify_after_storage = password_verify( $plain_key, $stored_key->hashed_key );
				$secret_verify_after_storage = password_verify( $secret_plain, $stored_key->secret_key );
				
				// If verification fails, log error (for debugging).
				if ( ! $key_verify_after_storage || ! $secret_verify_after_storage ) {
					error_log( 'SPB: Key storage verification failed for key ID ' . $key_id . '. Key verify: ' . ( $key_verify_after_storage ? 'PASS' : 'FAIL' ) . ', Secret verify: ' . ( $secret_verify_after_storage ? 'PASS' : 'FAIL' ) );
				}
			}
		}

		return array(
			'id'               => $key_id,
			'api_key'          => $plain_key,
			'secret_key_plain' => $secret_plain,
		);
	}

	/**
	 * REST authentication filter.
	 */
	public static function rest_authentication( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( strpos( $request_uri, '/webops/pagebuilder/v1' ) === false && strpos( $request_uri, '/pagebuilder/v1' ) === false ) {
			return $result;
		}

		if ( ! get_option( 'spb_global_api_access', true ) ) {
			return new WP_Error( 'spb_disabled', __( 'API access disabled.', 'simple-page-builder' ), array( 'status' => 403 ) );
		}

		$credentials = self::extract_credentials_from_headers();
		if ( empty( $credentials['api_key'] ) && empty( $credentials['api_secret'] ) ) {
			return new WP_Error(
				'spb_missing_key',
				__( 'API Key and Secret are required. Send both headers: X-API-Key and X-API-Secret.', 'simple-page-builder' ),
				array( 'status' => 401 )
			);
		}
		if ( empty( $credentials['api_key'] ) ) {
			return new WP_Error(
				'spb_missing_key',
				__( 'X-API-Key header is required.', 'simple-page-builder' ),
				array( 'status' => 401 )
			);
		}
		if ( empty( $credentials['api_secret'] ) ) {
			return new WP_Error(
				'spb_missing_key',
				__( 'X-API-Secret header is required.', 'simple-page-builder' ),
				array( 'status' => 401 )
			);
		}

		$key_record = self::validate_key( $credentials['api_key'], $credentials['api_secret'] );
		if ( is_wp_error( $key_record ) ) {
			return $key_record;
		}

		$rate_limit = (int) get_option( 'spb_rate_limit', 100 );
		$requests   = SPB_DB_Manager::count_requests_last_hour( $key_record->id );
		if ( $requests >= $rate_limit ) {
			return new WP_Error(
				'spb_rate_limited',
				__( 'Rate limit exceeded. Try again later.', 'simple-page-builder' ),
				array( 'status' => 429 )
			);
		}

		self::$authenticated_key = $key_record;
		return true;
	}

	/**
	 * Validate supplied key against stored hashes.
	 */
	public static function validate_key( $api_key, $api_secret ) {
		$active_keys = SPB_DB_Manager::get_active_keys();
		$now         = current_time( 'timestamp' );

		// Trim whitespace from inputs (common issue).
		$api_key    = trim( $api_key );
		$api_secret = trim( $api_secret );

		foreach ( $active_keys as $key ) {
			$expires_at = $key->expires_at ? strtotime( $key->expires_at ) : null;

			if ( $expires_at && $expires_at < $now ) {
				continue;
			}

			// Trim stored hash (in case of database issues).
			$stored_hash = trim( $key->hashed_key );
			$stored_secret_hash = trim( $key->secret_key );

			// Check if hashes are empty.
			if ( empty( $stored_hash ) || empty( $stored_secret_hash ) ) {
				continue;
			}

			// Verify API Key first using password_verify.
			$key_verified = false;
			if ( function_exists( 'password_verify' ) ) {
				$key_verified = password_verify( $api_key, $stored_hash );
			}
			
			// Fallback: If password_verify fails, try re-hashing with password_hash.
			if ( ! $key_verified ) {
				// Try re-hashing and comparing (this won't work for bcrypt, but helps debug).
				$test_hash = password_hash( $api_key, PASSWORD_DEFAULT );
				// For bcrypt, we can't compare hashes directly, so we verify again.
				$key_verified = password_verify( $api_key, $stored_hash );
			}

			if ( ! $key_verified ) {
				continue;
			}

			// Verify API Secret.
			$secret_verified = false;
			if ( function_exists( 'password_verify' ) ) {
				$secret_verified = password_verify( $api_secret, $stored_secret_hash );
			}
			
			// Fallback for secret.
			if ( ! $secret_verified ) {
				$test_secret_hash = password_hash( $api_secret, PASSWORD_DEFAULT );
				// For bcrypt, we can't compare hashes directly, so we verify again.
				$secret_verified = password_verify( $api_secret, $stored_secret_hash );
			}

			if ( $secret_verified ) {
				return $key;
			}
		}

		return new WP_Error( 'spb_invalid_key', __( 'Invalid or expired API Key/Secret. Please verify both headers are correct.', 'simple-page-builder' ), array( 'status' => 401 ) );
	}

	/**
	 * Extract key and secret from request headers.
	 */
	private static function extract_credentials_from_headers() {
		$api_key    = '';
		$api_secret = '';

		// Method 1: Try getallheaders() first (works on most servers, especially Apache).
		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
			if ( $headers && is_array( $headers ) ) {
				// Case-insensitive header lookup.
				foreach ( $headers as $name => $value ) {
					$lower_name = strtolower( $name );
					if ( $lower_name === 'x-api-key' ) {
						$api_key = sanitize_text_field( $value );
					}
					if ( $lower_name === 'x-api-secret' ) {
						$api_secret = sanitize_text_field( $value );
					}
				}
			}
		}

		// Method 2: Fallback to $_SERVER (for Nginx and servers where getallheaders() doesn't work).
		// WordPress converts headers to $_SERVER['HTTP_*'] format.
		if ( empty( $api_key ) && isset( $_SERVER['HTTP_X_API_KEY'] ) ) {
			$api_key = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_API_KEY'] ) );
		}
		if ( empty( $api_secret ) && isset( $_SERVER['HTTP_X_API_SECRET'] ) ) {
			$api_secret = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_API_SECRET'] ) );
		}

		// Method 3: Check REDIRECT_ prefixed headers (some proxy/rewrite setups).
		if ( empty( $api_key ) && isset( $_SERVER['REDIRECT_HTTP_X_API_KEY'] ) ) {
			$api_key = sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_X_API_KEY'] ) );
		}
		if ( empty( $api_secret ) && isset( $_SERVER['REDIRECT_HTTP_X_API_SECRET'] ) ) {
			$api_secret = sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_X_API_SECRET'] ) );
		}

		// Trim whitespace (common issue when copying/pasting).
		$api_key    = trim( $api_key );
		$api_secret = trim( $api_secret );

		return array(
			'api_key'    => $api_key,
			'api_secret' => $api_secret,
		);
	}

	/**
	 * Permission callback for routes.
	 */
	public static function permission_callback() {
		if ( self::$authenticated_key ) {
			return true;
		}
		return new WP_Error( 'spb_auth_required', __( 'Authentication required.', 'simple-page-builder' ), array( 'status' => 401 ) );
	}

	/**
	 * Get currently authenticated key record.
	 */
	public static function get_authenticated_key() {
		return self::$authenticated_key;
	}
}

