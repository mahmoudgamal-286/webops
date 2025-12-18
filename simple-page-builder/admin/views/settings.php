<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php include SPB_PLUGIN_DIR . 'admin/views/nav.php'; ?>

	<form action="options.php" method="post">
		<?php settings_fields( 'spb_settings' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Webhook URL', 'simple-page-builder' ); ?></th>
				<td><input type="url" name="spb_webhook_url" value="<?php echo esc_attr( get_option( 'spb_webhook_url', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Webhook Secret Key', 'simple-page-builder' ); ?></th>
				<td><input type="text" name="spb_webhook_secret" value="<?php echo esc_attr( get_option( 'spb_webhook_secret', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Rate Limit (Requests/Hour)', 'simple-page-builder' ); ?></th>
				<td><input type="number" name="spb_rate_limit" value="<?php echo esc_attr( get_option( 'spb_rate_limit', 100 ) ); ?>" class="small-text" min="1" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Global API Access', 'simple-page-builder' ); ?></th>
				<td><label><input type="checkbox" name="spb_global_api_access" value="1" <?php checked( get_option( 'spb_global_api_access', true ) ); ?> /> <?php esc_html_e( 'Enabled', 'simple-page-builder' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Key Expiration Default', 'simple-page-builder' ); ?></th>
				<td>
					<select name="spb_key_expiration_default">
						<?php
						$options = array( '30 days', '60 days', '90 days', '180 days', '365 days' );
						$current = get_option( 'spb_key_expiration_default', '90 days' );
						foreach ( $options as $opt ) {
							echo '<option value="' . esc_attr( $opt ) . '"' . selected( $current, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

