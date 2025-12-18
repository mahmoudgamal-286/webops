<?php
$current_tab = 'api-keys';
$keys        = SPB_DB_Manager::get_keys();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'API Keys', 'simple-page-builder' ); ?></h1>
	<?php include SPB_PLUGIN_DIR . 'admin/views/nav.php'; ?>
	<h2><?php esc_html_e( 'Generate New API Key', 'simple-page-builder' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'spb_generate_key' ); ?>
		<input type="hidden" name="action" value="spb_generate_key" />
		<table class="form-table">
			<tr>
				<th><label for="spb_key_name"><?php esc_html_e( 'Key Name', 'simple-page-builder' ); ?></label></th>
				<td><input type="text" name="spb_key_name" id="spb_key_name" required class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="spb_key_expiration"><?php esc_html_e( 'Expiration (e.g. +90 days)', 'simple-page-builder' ); ?></label></th>
				<td><input type="text" name="spb_key_expiration" id="spb_key_expiration" class="regular-text" value="<?php echo esc_attr( get_option( 'spb_key_expiration_default', '90 days' ) ); ?>" /></td>
			</tr>
		</table>
		<?php submit_button( __( 'Generate Key', 'simple-page-builder' ) ); ?>
	</form>

	<?php if ( isset( $_GET['generated'] ) && isset( $_GET['token'] ) ) : ?>
		<?php
		$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
		$key_data = get_transient( $token );
		
		if ( ! $key_data || ! is_array( $key_data ) ) {
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Error: Key data expired or invalid. Please generate a new key.', 'simple-page-builder' ) . '</strong></p></div>';
		} else {
			$new_api_key    = $key_data['api_key'];
			$new_secret_key = $key_data['secret_key'];
			$key_id         = $key_data['key_id'];
			
			// Verify the key was stored correctly.
			$verification_status = '';
			$all_keys = SPB_DB_Manager::get_keys( array( 'active', 'revoked' ) );
			$found_key = null;
			foreach ( $all_keys as $key ) {
				if ( $key->id === $key_id ) {
					$found_key = $key;
					break;
				}
			}
			
			if ( $found_key ) {
				// Trim stored hashes (in case of whitespace).
				$stored_hash = trim( $found_key->hashed_key );
				$stored_secret_hash = trim( $found_key->secret_key );
				
				$key_verify = password_verify( $new_api_key, $stored_hash );
				$secret_verify = password_verify( $new_secret_key, $stored_secret_hash );
				
				// Additional debug: test if password_verify works at all.
				$test_hash = password_hash( $new_api_key, PASSWORD_DEFAULT );
				$test_verify = password_verify( $new_api_key, $test_hash );
				
				if ( $key_verify && $secret_verify ) {
					$verification_status = '<div style="background: #d1e7dd; padding: 10px; margin: 10px 0; border-left: 4px solid #00a32a;"><strong>✓ Verification:</strong> Key and Secret verified successfully in database!</div>';
				} else {
					$debug_info = 'Key verify: ' . ( $key_verify ? 'PASS' : 'FAIL' ) . ', Secret verify: ' . ( $secret_verify ? 'PASS' : 'FAIL' );
					$debug_info .= '<br>Test verify (new hash): ' . ( $test_verify ? 'PASS' : 'FAIL' );
					$debug_info .= '<br>Stored hash length: ' . strlen( $stored_hash ) . ', Stored secret length: ' . strlen( $stored_secret_hash );
					$verification_status = '<div style="background: #f8d7da; padding: 10px; margin: 10px 0; border-left: 4px solid #d63638;"><strong>✗ Verification Failed:</strong><br>' . $debug_info . '</div>';
				}
			}
		}
		?>
		<?php if ( $key_data && is_array( $key_data ) ) : ?>
		<div class="notice notice-success" style="border-left-color: #00a32a;">
			<p><strong style="font-size: 16px;"><?php esc_html_e( '✓ API Key created successfully!', 'simple-page-builder' ); ?></strong></p>
			<?php echo wp_kses_post( $verification_status ); ?>
			<p style="color: #d63638; font-weight: bold;"><?php esc_html_e( '⚠️ IMPORTANT: Copy these values NOW. They will NOT be shown again after you leave this page!', 'simple-page-builder' ); ?></p>
			
			<div style="background: #f0f0f1; padding: 15px; margin: 15px 0; border: 2px solid #2271b1; border-radius: 4px;">
				<p style="margin-top: 0;"><strong><?php esc_html_e( 'API Key (64 characters):', 'simple-page-builder' ); ?></strong></p>
				<div style="display: flex; gap: 10px; align-items: center;">
					<input type="text" id="spb_new_api_key" readonly value="<?php echo esc_attr( $new_api_key ); ?>" style="flex: 1; font-family: monospace; font-size: 13px; padding: 8px; background: white; border: 1px solid #ccc;" />
					<button type="button" class="button button-primary" onclick="copyToClipboard('spb_new_api_key', 'spb_copy_key_msg')" id="spb_copy_key_btn"><?php esc_html_e( 'Copy', 'simple-page-builder' ); ?></button>
				</div>
				<p id="spb_copy_key_msg" style="margin: 5px 0 0 0; color: #00a32a; font-weight: bold; display: none;"><?php esc_html_e( '✓ Copied!', 'simple-page-builder' ); ?></p>
				
				<p style="margin-top: 20px;"><strong><?php esc_html_e( 'API Secret (32 characters):', 'simple-page-builder' ); ?></strong></p>
				<div style="display: flex; gap: 10px; align-items: center;">
					<input type="text" id="spb_new_secret_key" readonly value="<?php echo esc_attr( $new_secret_key ); ?>" style="flex: 1; font-family: monospace; font-size: 13px; padding: 8px; background: white; border: 1px solid #ccc;" />
					<button type="button" class="button button-primary" onclick="copyToClipboard('spb_new_secret_key', 'spb_copy_secret_msg')" id="spb_copy_secret_btn"><?php esc_html_e( 'Copy', 'simple-page-builder' ); ?></button>
				</div>
				<p id="spb_copy_secret_msg" style="margin: 5px 0 0 0; color: #00a32a; font-weight: bold; display: none;"><?php esc_html_e( '✓ Copied!', 'simple-page-builder' ); ?></p>
			</div>
			
			<p><strong><?php esc_html_e( 'Next steps:', 'simple-page-builder' ); ?></strong></p>
			<ol>
				<li><?php esc_html_e( 'Copy both the API Key and Secret above', 'simple-page-builder' ); ?></li>
				<li><?php esc_html_e( 'Go to the "Test Auth" tab to verify they work', 'simple-page-builder' ); ?></li>
				<li><?php esc_html_e( 'Use them in your API requests', 'simple-page-builder' ); ?></li>
			</ol>
		</div>
		<?php endif; ?>
		<script>
		function copyToClipboard(inputId, msgId) {
			var input = document.getElementById(inputId);
			input.select();
			input.setSelectionRange(0, 99999); // For mobile devices
			document.execCommand('copy');
			
			var msg = document.getElementById(msgId);
			msg.style.display = 'block';
			setTimeout(function() {
				msg.style.display = 'none';
			}, 2000);
		}
		</script>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Existing Keys', 'simple-page-builder' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Preview', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Status', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Created', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'simple-page-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $keys as $key ) : ?>
				<tr>
					<td><?php echo esc_html( $key->key_name ); ?></td>
					<td><?php echo esc_html( substr( $key->hashed_key, 0, 4 ) ); ?>****</td>
					<td><?php echo esc_html( ucfirst( $key->status ) ); ?></td>
					<td><?php echo esc_html( $key->created_at ); ?></td>
					<td><?php echo esc_html( $key->expires_at ? $key->expires_at : __( 'None', 'simple-page-builder' ) ); ?></td>
					<td>
						<?php if ( 'active' === $key->status ) : ?>
							<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'spb_revoke_key', 'key_id' => $key->id ), admin_url( 'admin-post.php' ) ), 'spb_revoke_key' ) ); ?>"><?php esc_html_e( 'Revoke', 'simple-page-builder' ); ?></a>
						<?php else : ?>
							<span><?php esc_html_e( 'Revoked', 'simple-page-builder' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

