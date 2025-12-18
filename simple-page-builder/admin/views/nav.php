<?php
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
?>
<h2 class="nav-tab-wrapper">
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'spb-page-builder', 'tab' => 'settings' ), admin_url( 'tools.php' ) ) ); ?>" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'simple-page-builder' ); ?></a>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'spb-page-builder', 'tab' => 'api-keys' ), admin_url( 'tools.php' ) ) ); ?>" class="nav-tab <?php echo $current_tab === 'api-keys' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'API Keys', 'simple-page-builder' ); ?></a>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'spb-page-builder', 'tab' => 'logs' ), admin_url( 'tools.php' ) ) ); ?>" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'API Activity', 'simple-page-builder' ); ?></a>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'spb-page-builder', 'tab' => 'pages' ), admin_url( 'tools.php' ) ) ); ?>" class="nav-tab <?php echo $current_tab === 'pages' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Created Pages', 'simple-page-builder' ); ?></a>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'spb-page-builder', 'tab' => 'docs' ), admin_url( 'tools.php' ) ) ); ?>" class="nav-tab <?php echo $current_tab === 'docs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'API Docs', 'simple-page-builder' ); ?></a>
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'spb-page-builder', 'tab' => 'test-auth' ), admin_url( 'tools.php' ) ) ); ?>" class="nav-tab <?php echo $current_tab === 'test-auth' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Test Auth', 'simple-page-builder' ); ?></a>
</h2>

