<?php
/**
 * Database manager for Simple Page Builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPB_DB_Manager {

	/**
	 * Install or update DB schema.
	 */
	public static function db_install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$api_keys_table  = $wpdb->prefix . 'spb_api_keys';
		$api_logs_table  = $wpdb->prefix . 'spb_api_logs';

		$api_keys_sql = "CREATE TABLE {$api_keys_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			key_name VARCHAR(191) NOT NULL,
			hashed_key TEXT NOT NULL,
			secret_key TEXT NOT NULL,
			status ENUM('active','revoked') NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME NULL,
			last_used DATETIME NULL,
			request_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
			permissions TEXT NULL,
			PRIMARY KEY (id),
			INDEX status_idx (status),
			INDEX expires_at_idx (expires_at)
		) {$charset_collate};";

		$api_logs_sql = "CREATE TABLE {$api_logs_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			key_id BIGINT UNSIGNED NOT NULL,
			timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			endpoint VARCHAR(191) NOT NULL,
			status ENUM('success','failed') NOT NULL,
			response_time FLOAT NULL,
			ip_address VARCHAR(100) NULL,
			pages_created INT UNSIGNED DEFAULT 0,
			PRIMARY KEY (id),
			INDEX key_id_idx (key_id),
			CONSTRAINT fk_spb_logs_key FOREIGN KEY (key_id)
				REFERENCES {$api_keys_table}(id) ON DELETE CASCADE
		) {$charset_collate};";

		dbDelta( $api_keys_sql );
		dbDelta( $api_logs_sql );
	}

	/**
	 * Insert API key row.
	 */
	public static function insert_key( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'spb_api_keys';
		$permissions = isset( $data['permissions'] ) ? wp_json_encode( $data['permissions'] ) : null;
		$wpdb->insert(
			$table,
			array(
				'key_name'      => $data['key_name'],
				'hashed_key'    => $data['hashed_key'],
				'secret_key'    => $data['secret_key'],
				'status'        => isset( $data['status'] ) ? $data['status'] : 'active',
				'created_at'    => current_time( 'mysql' ),
				'expires_at'    => $data['expires_at'],
				'permissions'   => $permissions,
				'request_count' => 0,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Update key status.
	 */
	public static function update_key_status( $key_id, $status ) {
		global $wpdb;
		$table = $wpdb->prefix . 'spb_api_keys';

		return $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => $key_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Fetch all keys.
	 */
	public static function get_keys( $statuses = array( 'active', 'revoked' ) ) {
		global $wpdb;
		$table    = $wpdb->prefix . 'spb_api_keys';
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$query        = $wpdb->prepare( "SELECT * FROM {$table} WHERE status IN ({$placeholders})", $statuses );

		return $wpdb->get_results( $query );
	}

	/**
	 * Get active keys only.
	 */
	public static function get_active_keys() {
		return self::get_keys( array( 'active' ) );
	}

	/**
	 * Increment request count for key.
	 */
	public static function increment_request_count( $key_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'spb_api_keys';

		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET request_count = request_count + 1, last_used = %s WHERE id = %d",
				current_time( 'mysql' ),
				$key_id
			)
		);
	}

	/**
	 * Count key requests in last hour.
	 */
	public static function count_requests_last_hour( $key_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'spb_api_logs';

		$since = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE key_id = %d AND timestamp >= %s",
				$key_id,
				$since
			)
		);
	}

	/**
	 * Log API request.
	 */
	public static function log_request( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'spb_api_logs';

		$wpdb->insert(
			$table,
			array(
				'key_id'        => $data['key_id'],
				'endpoint'      => $data['endpoint'],
				'status'        => $data['status'],
				'response_time' => $data['response_time'],
				'ip_address'    => isset( $data['ip'] ) ? $data['ip'] : '',
				'pages_created' => isset( $data['pages_created'] ) ? $data['pages_created'] : 0,
				'timestamp'     => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%f', '%s', '%d', '%s' )
		);
	}

	/**
	 * Log webhook attempt (uses same log table).
	 */
	public static function log_webhook( $data ) {
		$data['endpoint'] = isset( $data['endpoint'] ) ? $data['endpoint'] : 'webhook_notification';
		$data['pages_created'] = isset( $data['pages_created'] ) ? $data['pages_created'] : 0;
		self::log_request( $data );
	}

	/**
	 * Fetch API logs with optional filters.
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'spb_api_logs';
		$where = array();
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['from'] ) ) {
			$where[] = 'timestamp >= %s';
			$params[] = $args['from'];
		}

		if ( ! empty( $args['to'] ) ) {
			$where[] = 'timestamp <= %s';
			$params[] = $args['to'];
		}

		if ( ! empty( $args['key_id'] ) ) {
			$where[] = 'key_id = %d';
			$params[] = $args['key_id'];
		}

		$where_sql = '';
		if ( $where ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		$query = "SELECT * FROM {$table} {$where_sql} ORDER BY timestamp DESC LIMIT 200";
		if ( $params ) {
			return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
		}

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

