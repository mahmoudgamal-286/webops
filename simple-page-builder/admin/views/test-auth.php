<?php
/**
 * Temporary test page for API authentication debugging.
 * This file should be removed in production.
 */

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Not allowed.', 'simple-page-builder' ) );
}

$current_tab = 'test-auth';
$test_result = '';

if ( isset( $_POST['spb_test_key'] ) && isset( $_POST['spb_test_secret'] ) ) {
	check_admin_referer( 'spb_test_auth' );
	
	$test_key    = trim( sanitize_text_field( wp_unslash( $_POST['spb_test_key'] ) ) );
	$test_secret = trim( sanitize_text_field( wp_unslash( $_POST['spb_test_secret'] ) ) );
	
	// Validate input lengths.
	$key_length    = strlen( $test_key );
	$secret_length = strlen( $test_secret );
	
	$validation_errors = array();
	if ( $key_length < 20 ) {
		$validation_errors[] = sprintf( 'API Key is too short (%d characters). Expected ~64 characters.', $key_length );
	}
	if ( $secret_length < 10 ) {
		$validation_errors[] = sprintf( 'API Secret is too short (%d characters). Expected ~32 characters.', $secret_length );
	}
	
	if ( ! empty( $validation_errors ) ) {
		$test_result = '<div class="notice notice-error"><p><strong>✗ Input Validation Failed:</strong></p><ul><li>' . implode( '</li><li>', array_map( 'esc_html', $validation_errors ) ) . '</li></ul></div>';
	} else {
		// Check all keys (active and revoked) to provide better feedback.
		$all_keys = SPB_DB_Manager::get_keys( array( 'active', 'revoked' ) );
		$found    = false;
		$match_key = false;
		$matched_key_record = null;
		$debug_info = array();
		
		// Debug: Show first 10 chars of input key.
		$debug_info[] = 'Input Key (first 10 chars): ' . substr( $test_key, 0, 10 ) . '...';
		$debug_info[] = 'Input Secret (first 10 chars): ' . substr( $test_secret, 0, 10 ) . '...';
		
		foreach ( $all_keys as $key ) {
			// Trim stored hash (in case of whitespace issues).
			$stored_hash = trim( $key->hashed_key );
			$hash_length = strlen( $stored_hash );
			
			// Try password_verify.
			$key_verify_result = password_verify( $test_key, $stored_hash );
			
			// Also try direct hash comparison for debugging.
			$test_hash = password_hash( $test_key, PASSWORD_DEFAULT );
			$direct_verify = password_verify( $test_key, $test_hash );
			
			// Debug: Store verification attempts with more details.
			$key_preview = substr( $stored_hash, 0, 30 ) . '...';
			$debug_info[] = sprintf(
				'Key ID %d (%s): password_verify = %s, Hash length = %d, Direct test verify = %s, Hash preview: %s',
				$key->id,
				$key->key_name,
				$key_verify_result ? 'TRUE' : 'FALSE',
				$hash_length,
				$direct_verify ? 'TRUE' : 'FALSE',
				$key_preview
			);
			
			if ( $key_verify_result ) {
				$match_key         = true;
				$matched_key_record = $key;
				
				if ( 'revoked' === $key->status ) {
					$test_result = sprintf(
						'<div class="notice notice-error"><p><strong>✗ Key is Revoked.</strong> The API Key matches key "%s" (ID: %d), but it has been revoked and cannot be used.</p><p>Please generate a new API key.</p></div>',
						esc_html( $key->key_name ),
						esc_html( $key->id )
					);
					$found = true;
					break;
				}
				
				// Check expiration.
				$now = current_time( 'timestamp' );
				$expires_at = $key->expires_at ? strtotime( $key->expires_at ) : null;
				if ( $expires_at && $expires_at < $now ) {
					$test_result = sprintf(
						'<div class="notice notice-error"><p><strong>✗ Key is Expired.</strong> The API Key matches key "%s" (ID: %d), but it expired on %s.</p><p>Please generate a new API key.</p></div>',
						esc_html( $key->key_name ),
						esc_html( $key->id ),
						esc_html( $key->expires_at )
					);
					$found = true;
					break;
				}
				
				// Check if secret exists.
				if ( empty( $key->secret_key ) ) {
					$test_result = sprintf(
						'<div class="notice notice-error"><p><strong>✗ This key has no Secret.</strong> The API Key matches key "%s" (ID: %d), but it was created before Secret authentication was required.</p><p>Please generate a new API key from the API Keys tab.</p></div>',
						esc_html( $key->key_name ),
						esc_html( $key->id )
					);
					$found = true;
					break;
				}
				
				// Verify secret.
				if ( password_verify( $test_secret, $key->secret_key ) ) {
					$test_result = sprintf(
						'<div class="notice notice-success"><p><strong>✓ Success!</strong> Both API Key and Secret are correct!</p><p>Key: %s (ID: %d, Status: %s, Created: %s)</p></div>',
						esc_html( $key->key_name ),
						esc_html( $key->id ),
						esc_html( ucfirst( $key->status ) ),
						esc_html( $key->created_at )
					);
					$found = true;
					break;
				} else {
					$test_result = sprintf(
						'<div class="notice notice-error"><p><strong>✗ Secret mismatch.</strong> The API Key is correct (matches "%s", ID: %d), but the Secret does not match.</p><p>Please verify you copied the Secret correctly. It should be exactly 32 characters.</p></div>',
						esc_html( $key->key_name ),
						esc_html( $key->id )
					);
					$found = true;
					break;
				}
			}
		}
		
		if ( ! $found ) {
			$all_keys_count = count( $all_keys );
			$active_count   = count( SPB_DB_Manager::get_active_keys() );
			
			// Show detailed debug info.
			$debug_html = '<ul>';
			$debug_html .= '<li>Key length: ' . esc_html( $key_length ) . ' characters</li>';
			$debug_html .= '<li>Secret length: ' . esc_html( $secret_length ) . ' characters</li>';
			$debug_html .= '<li>Total keys in database: ' . esc_html( $all_keys_count ) . ' (Active: ' . esc_html( $active_count ) . ')</li>';
			$debug_html .= '<li>Input Key (first 10): <code>' . esc_html( substr( $test_key, 0, 10 ) ) . '...</code></li>';
			$debug_html .= '<li>Input Key (last 10): <code>...' . esc_html( substr( $test_key, -10 ) ) . '</code></li>';
			$debug_html .= '</ul>';
			
			$debug_html .= '<p><strong>Verification attempts for each key:</strong></p><ul>';
			foreach ( $debug_info as $info ) {
				$debug_html .= '<li><code>' . esc_html( $info ) . '</code></li>';
			}
			$debug_html .= '</ul>';
			
			$test_result = sprintf(
				'<div class="notice notice-error"><p><strong>✗ API Key not found.</strong> The provided API Key does not match any key in the database.</p><p><strong>Debugging info:</strong></p>%s<p><strong>Possible causes:</strong></p><ul><li>You copied the key incorrectly (missing characters, extra spaces, or hidden characters)</li><li>You are using a key from a different WordPress installation</li><li>The key was deleted from the database</li><li>Character encoding issue (try copying again)</li></ul><p><strong>Solution:</strong></p><ol><li>Go to the API Keys tab</li><li>Generate a NEW key</li><li>Copy the key and secret IMMEDIATELY (they won\'t be shown again)</li><li>Test again with the new key</li></ol></div>',
				$debug_html
			);
		}
	}
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'API Authentication Test', 'simple-page-builder' ); ?></h1>
	<?php include SPB_PLUGIN_DIR . 'admin/views/nav.php'; ?>
	
	<div class="card">
		<h2><?php esc_html_e( 'Test API Key & Secret', 'simple-page-builder' ); ?></h2>
		<p><?php esc_html_e( 'Enter your API Key and Secret to verify they are correctly stored and can be authenticated.', 'simple-page-builder' ); ?></p>
		
		<?php echo wp_kses_post( $test_result ); ?>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'spb_test_auth' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="spb_test_key"><?php esc_html_e( 'API Key', 'simple-page-builder' ); ?></label></th>
					<td>
						<input type="text" name="spb_test_key" id="spb_test_key" class="regular-text code" value="" placeholder="Paste your 64-character API Key" />
						<p class="description"><?php esc_html_e( 'The full API Key (64 characters)', 'simple-page-builder' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="spb_test_secret"><?php esc_html_e( 'API Secret', 'simple-page-builder' ); ?></label></th>
					<td>
						<input type="text" name="spb_test_secret" id="spb_test_secret" class="regular-text code" value="" placeholder="Paste your 32-character Secret" />
						<p class="description"><?php esc_html_e( 'The full API Secret (32 characters)', 'simple-page-builder' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Test Authentication', 'simple-page-builder' ) ); ?>
		</form>
	</div>
	
	<div class="card" style="margin-top: 20px;">
		<h2><?php esc_html_e( 'All Keys in Database', 'simple-page-builder' ); ?></h2>
		<p><?php esc_html_e( 'List of all API keys (active and revoked) for reference:', 'simple-page-builder' ); ?></p>
		<?php
		$keys = SPB_DB_Manager::get_keys( array( 'active', 'revoked' ) );
		if ( empty( $keys ) ) {
			echo '<p><strong>' . esc_html__( 'No keys found in database.', 'simple-page-builder' ) . '</strong></p>';
			echo '<p>' . esc_html__( 'Go to the API Keys tab to generate your first key.', 'simple-page-builder' ) . '</p>';
		} else {
			echo '<table class="widefat striped">';
			echo '<thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Has Secret</th><th>Key Preview</th><th>Created</th><th>Expires</th><th>Requests</th></tr></thead>';
			echo '<tbody>';
			foreach ( $keys as $key ) {
				$has_secret = ! empty( $key->secret_key ) ? '✓ Yes' : '✗ No';
				$key_preview = substr( $key->hashed_key, 0, 8 ) . '...';
				$status_class = 'active' === $key->status ? '' : 'style="color: #d63638;"';
				printf(
					'<tr><td>%d</td><td>%s</td><td %s>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td><td>%s</td><td>%d</td></tr>',
					esc_html( $key->id ),
					esc_html( $key->key_name ),
					$status_class,
					esc_html( ucfirst( $key->status ) ),
					esc_html( $has_secret ),
					esc_html( $key_preview ),
					esc_html( $key->created_at ),
					esc_html( $key->expires_at ? $key->expires_at : 'Never' ),
					esc_html( $key->request_count )
				);
			}
			echo '</tbody></table>';
		}
		?>
	</div>
</div>

