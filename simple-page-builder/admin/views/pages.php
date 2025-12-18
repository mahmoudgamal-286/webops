<?php
$current_tab = 'pages';
$pages       = SPB_Admin::get_created_pages();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Pages Created via API', 'simple-page-builder' ); ?></h1>
	<?php include SPB_PLUGIN_DIR . 'admin/views/nav.php'; ?>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Title', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'URL', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Created', 'simple-page-builder' ); ?></th>
				<th><?php esc_html_e( 'Created By', 'simple-page-builder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $pages as $page ) : ?>
				<tr>
					<td><?php echo esc_html( get_the_title( $page ) ); ?></td>
					<td><a href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'simple-page-builder' ); ?></a></td>
					<td><?php echo esc_html( get_the_date( '', $page ) ); ?></td>
					<td><?php echo esc_html( get_post_meta( $page->ID, '_spb_created_by_name', true ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

