<?php
$current_tab = 'docs';
$endpoint    = rest_url( 'pagebuilder/v1/create-pages' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'API Documentation', 'simple-page-builder' ); ?></h1>
	<?php include SPB_PLUGIN_DIR . 'admin/views/nav.php'; ?>

	<h2><?php esc_html_e( 'Endpoint', 'simple-page-builder' ); ?></h2>
	<p><code><?php echo esc_html( $endpoint ); ?></code></p>

	<h2><?php esc_html_e( 'Authentication', 'simple-page-builder' ); ?></h2>
	<p><?php esc_html_e( 'You MUST send BOTH headers:', 'simple-page-builder' ); ?></p>
	<ul>
		<li><code>X-API-Key: YOUR_API_KEY</code></li>
		<li><code>X-API-Secret: YOUR_API_SECRET</code></li>
	</ul>
	<p><strong><?php esc_html_e( 'Both are required. Missing either will result in 401 Unauthorized.', 'simple-page-builder' ); ?></strong></p>

	<h2><?php esc_html_e( 'Payload', 'simple-page-builder' ); ?></h2>
	<p><?php esc_html_e( 'POST JSON array with objects containing title and content.', 'simple-page-builder' ); ?></p>

	<pre><code>{
  "0": {"title": "Page One", "content": "<p>Hello</p>"},
  "1": {"title": "Page Two", "content": "<p>World</p>"}
}</code></pre>

	<h2><?php esc_html_e( 'cURL Example', 'simple-page-builder' ); ?></h2>
	<pre><code>curl -X POST "<?php echo esc_html( $endpoint ); ?>" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "X-API-Secret: YOUR_API_SECRET" \
  -d '[{"title":"Sample Page","content":"<p>Body</p>"}]'</code></pre>

	<h2><?php esc_html_e( 'Webhook Signature Verification', 'simple-page-builder' ); ?></h2>
	<p><?php esc_html_e( 'Compute HMAC-SHA256 over the JSON payload using your Webhook Secret. Compare against the X-Webhook-Signature header.', 'simple-page-builder' ); ?></p>
	<pre><code>// Pseudo-code
signature = HMAC_SHA256(request_body, webhook_secret)
assert signature == request.headers["X-Webhook-Signature"]</code></pre>
</div>

