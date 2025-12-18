<?php
$current_tab = 'logs';

$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$from_filter   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
$to_filter     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
$key_filter    = isset( $_GET['key_id'] ) ? absint( $_GET['key_id'] ) : 0;

$logs = SPB_DB_Manager::get_logs(
	array(
		'status' => $status_filter,
		'from'   => $from_filter,
		'to'     => $to_filter,
		'key_id' => $key_filter,
	)
);

$all_keys = SPB_DB_Manager::get_keys();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'API Activity Logs', 'simple-page-builder' ); ?></h1>
	<?php include SPB_PLUGIN_DIR . 'admin/views/nav.php'; ?>

	<form method="get" action="">
		<input type="hidden" name="page" value="spb-page-builder" />
		<input type="hidden" name="tab" value="logs" />
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Status', 'simple-page-builder' ); ?></th>
				<td>
					<select name="status">
						<option value=""><?php esc_html_e( 'All', 'simple-page-builder' ); ?></option>
						<option value="success" <?php selected( $status_filter, 'success' ); ?>><?php esc_html_e( 'Success', 'simple-page-builder' ); ?></option>
						<option value="failed" <?php selected( $status_filter, 'failed' ); ?>><?php esc_html_e( 'Failed', 'simple-page-builder' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'API Key', 'simple-page-builder' ); ?></th>
				<td>
					<select name="key_id">
						<option value="0"><?php esc_html_e( 'All Keys', 'simple-page-builder' ); ?></option>
						<?php foreach ( $all_keys as $k ) : ?>
							<option value="<?php echo esc_attr( $k->id ); ?>" <?php selected( $key_filter, $k->id ); ?>>
								<?php echo esc_html( $k->key_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Date Range', 'simple-page-builder' ); ?></th>
				<td>
					<input type="date" name="from" value="<?php echo esc_attr( $from_filter ); ?>" />
					<input type="date" name="to" value="<?php echo esc_attr( $to_filter ); ?>" />
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Filter', 'simple-page-builder' ) ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'spb_export_logs' ); ?>
		<input type="hidden" name="action" value="spb_export_logs" />
		<?php submit_button( __( 'Export as CSV', 'simple-page-builder' ), 'secondary' ); ?>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Key ID', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Timestamp', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Endpoint', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Status', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Response Time', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'IP', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Pages Created', 'simple-page-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $logs as $log ) : ?>
				<tr>
					<td><?php echo esc_html( $log->id ); ?></td>
					<td><?php echo esc_html( $log->key_id ); ?></td>
					<td><?php echo esc_html( $log->timestamp ); ?></td>
					<td><?php echo esc_html( $log->endpoint ); ?></td>
					<td><?php echo esc_html( $log->status ); ?></td>
					<td><?php echo esc_html( $log->response_time ); ?></td>
					<td><?php echo esc_html( $log->ip_address ); ?></td>
					<td><?php echo esc_html( $log->pages_created ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php
$current_tab = 'logs';
$logs        = SPB_DB_Manager::get_logs();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'API Activity Logs', 'simple-page-builder' ); ?></h1>
	<?php include SPB_PLUGIN_DIR . 'admin/views/nav.php'; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'spb_export_logs' ); ?>
		<input type="hidden" name="action" value="spb_export_logs" />
		<?php submit_button( __( 'Export as CSV', 'simple-page-builder' ), 'secondary' ); ?>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Key ID', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Timestamp', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Endpoint', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Status', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Response Time', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'IP', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Pages Created', 'simple-page-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $logs as $log ) : ?>
				<tr>
					<td><?php echo esc_html( $log->id ); ?></td>
					<td><?php echo esc_html( $log->key_id ); ?></td>
					<td><?php echo esc_html( $log->timestamp ); ?></td>
					<td><?php echo esc_html( $log->endpoint ); ?></td>
					<td><?php echo esc_html( $log->status ); ?></td>
					<td><?php echo esc_html( $log->response_time ); ?></td>
					<td><?php echo esc_html( $log->ip_address ); ?></td>
					<td><?php echo esc_html( $log->pages_created ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

