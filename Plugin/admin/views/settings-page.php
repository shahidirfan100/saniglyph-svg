<?php
/**
 * Settings page view.
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sgsvg_settings            = sgsvg_get_settings();
$sgsvg_allowed_roles       = (array) $sgsvg_settings['allowed_roles'];
$sgsvg_wp_roles            = wp_roles()->roles;
$sgsvg_sanitizer_available = SGSVG_SVG_Sanitizer::get_instance()->is_available();
?>
<div class="wrap sgsvg-wrap">
	<div class="sgsvg-hero">
		<div class="sgsvg-brand-mark" aria-hidden="true">
			<img src="<?php echo esc_url( SGSVG_PLUGIN_URL . 'admin/images/saniglyph-logo.png' ); ?>" alt="">
		</div>
		<div class="sgsvg-hero-copy">
			<div class="sgsvg-eyebrow"><?php esc_html_e( 'SVG security & performance', 'saniglyph-svg' ); ?></div>
			<h1><?php esc_html_e( 'SaniGlyph SVG', 'saniglyph-svg' ); ?></h1>
			<p><?php esc_html_e( 'Secure SVG uploads without turning your Media Library into a security exception.', 'saniglyph-svg' ); ?></p>
		</div>
		<div class="sgsvg-version">v<?php echo esc_html( SGSVG_VERSION ); ?></div>
	</div>

	<div class="sgsvg-health-grid" aria-label="<?php esc_attr_e( 'Security overview', 'saniglyph-svg' ); ?>">
		<div class="sgsvg-health-card<?php echo $sgsvg_sanitizer_available ? '' : ' is-warning'; ?>">
			<span class="dashicons <?php echo $sgsvg_sanitizer_available ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
			<div><strong><?php esc_html_e( 'Sanitization', 'saniglyph-svg' ); ?></strong><span><?php echo esc_html( $sgsvg_sanitizer_available ? __( 'Always on', 'saniglyph-svg' ) : __( 'DOM required', 'saniglyph-svg' ) ); ?></span></div>
		</div>
		<div class="sgsvg-health-card">
			<span class="dashicons dashicons-lock" aria-hidden="true"></span>
			<div><strong><?php esc_html_e( 'Remote references', 'saniglyph-svg' ); ?></strong><span><?php esc_html_e( 'Blocked', 'saniglyph-svg' ); ?></span></div>
		</div>
		<div class="sgsvg-health-card">
			<span class="dashicons dashicons-media-code" aria-hidden="true"></span>
			<div><strong><?php esc_html_e( 'Upload format', 'saniglyph-svg' ); ?></strong><span><?php esc_html_e( 'Plain SVG only', 'saniglyph-svg' ); ?></span></div>
		</div>
	</div>

	<div id="sgsvg-status" class="sgsvg-status" role="status" aria-live="polite"><span class="dashicons" aria-hidden="true"></span><span class="sgsvg-status-text"></span></div>

	<form id="sgsvg-settings-form" method="post" action="options.php">
		<?php settings_fields( 'sgsvg_settings_group' ); ?>

		<div class="sgsvg-layout">
			<main class="sgsvg-main">
				<section class="sgsvg-card">
					<div class="sgsvg-card-head">
						<div><span class="sgsvg-kicker"><?php esc_html_e( 'Security', 'saniglyph-svg' ); ?></span><h2><?php esc_html_e( 'Upload protection', 'saniglyph-svg' ); ?></h2></div>
						<span class="sgsvg-pill sgsvg-pill-success"><?php esc_html_e( 'Always enabled', 'saniglyph-svg' ); ?></span>
					</div>
					<div class="sgsvg-locked-setting">
						<div class="sgsvg-setting-icon"><span class="dashicons dashicons-shield" aria-hidden="true"></span></div>
						<div><strong><?php esc_html_e( 'Fail-closed SVG sanitization', 'saniglyph-svg' ); ?></strong><p><?php esc_html_e( 'Unsafe or unsupported SVGs are rejected before storage. Valid artwork is preserved instead of silently stripping fills, styles, gradients, or exporter-specific presentation markup.', 'saniglyph-svg' ); ?></p></div>
					</div>
				</section>

				<section class="sgsvg-card">
					<div class="sgsvg-card-head"><div><span class="sgsvg-kicker"><?php esc_html_e( 'Features', 'saniglyph-svg' ); ?></span><h2><?php esc_html_e( 'Rendering & optimization', 'saniglyph-svg' ); ?></h2></div></div>
					<div class="sgsvg-setting-row">
						<div class="sgsvg-setting-copy"><label for="sgsvg_enable_optimization"><?php esc_html_e( 'Safe optimization', 'saniglyph-svg' ); ?></label><p><?php esc_html_e( 'Optionally reduce harmless inter-element whitespace without changing fills, styles, geometry, text spacing, or accessibility content.', 'saniglyph-svg' ); ?></p></div>
						<label class="sgsvg-switch"><input id="sgsvg_enable_optimization" type="checkbox" name="sgsvg_settings[enable_optimization]" value="1" <?php checked( 1, $sgsvg_settings['enable_optimization'] ); ?>><span aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Enable safe optimization', 'saniglyph-svg' ); ?></span></label>
					</div>
					<div class="sgsvg-setting-row">
						<div class="sgsvg-setting-copy"><label for="sgsvg_enable_inline_rendering"><?php esc_html_e( 'Inline rendering', 'saniglyph-svg' ); ?></label><p><?php esc_html_e( 'Allow the shortcode and block to place sanitized SVG markup directly in page HTML. Keep disabled unless you need inline styling or DOM access.', 'saniglyph-svg' ); ?></p></div>
						<label class="sgsvg-switch"><input id="sgsvg_enable_inline_rendering" type="checkbox" name="sgsvg_settings[enable_inline_rendering]" value="1" <?php checked( 1, $sgsvg_settings['enable_inline_rendering'] ); ?>><span aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Enable inline rendering', 'saniglyph-svg' ); ?></span></label>
					</div>
				</section>

				<section class="sgsvg-card">
					<div class="sgsvg-card-head"><div><span class="sgsvg-kicker"><?php esc_html_e( 'Access', 'saniglyph-svg' ); ?></span><h2><?php esc_html_e( 'Who can upload SVG files?', 'saniglyph-svg' ); ?></h2><p><?php esc_html_e( 'Users must also have WordPress’ upload_files capability.', 'saniglyph-svg' ); ?></p></div></div>
					<div class="sgsvg-role-grid">
						<?php foreach ( $sgsvg_wp_roles as $sgsvg_role_key => $sgsvg_role ) : ?>
							<?php $sgsvg_checked = in_array( $sgsvg_role_key, $sgsvg_allowed_roles, true ); ?>
							<label class="sgsvg-role<?php echo $sgsvg_checked ? ' is-active' : ''; ?>" for="sgsvg_role_<?php echo esc_attr( $sgsvg_role_key ); ?>">
								<input id="sgsvg_role_<?php echo esc_attr( $sgsvg_role_key ); ?>" type="checkbox" name="sgsvg_settings[allowed_roles][]" value="<?php echo esc_attr( $sgsvg_role_key ); ?>" <?php checked( $sgsvg_checked ); ?>>
								<span class="sgsvg-role-check"><span class="dashicons dashicons-yes" aria-hidden="true"></span></span>
								<span><strong><?php echo esc_html( translate_user_role( $sgsvg_role['name'] ) ); ?></strong><small><?php echo esc_html( $sgsvg_role_key ); ?></small></span>
							</label>
						<?php endforeach; ?>
					</div>
				</section>
			</main>

			<aside class="sgsvg-side">
				<section class="sgsvg-card sgsvg-usage-card">
					<span class="sgsvg-kicker"><?php esc_html_e( 'Quick use', 'saniglyph-svg' ); ?></span>
					<h2><?php esc_html_e( 'Inline SVG shortcode', 'saniglyph-svg' ); ?></h2>
					<p><?php esc_html_e( 'Enable inline rendering, then use an SVG attachment ID.', 'saniglyph-svg' ); ?></p>
					<div class="sgsvg-code-row"><code id="sgsvg-shortcode">[saniglyph_svg id="123"]</code><button type="button" class="sgsvg-icon-button" data-sgsvg-copy="#sgsvg-shortcode" aria-label="<?php esc_attr_e( 'Copy shortcode', 'saniglyph-svg' ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button></div>
					<p class="sgsvg-muted"><?php esc_html_e( 'Optional attributes: class, width, height.', 'saniglyph-svg' ); ?></p>
				</section>

				<section class="sgsvg-card sgsvg-note-card">
					<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
					<div><strong><?php esc_html_e( 'Why sanitization cannot be disabled', 'saniglyph-svg' ); ?></strong><p><?php esc_html_e( 'SVG is executable XML-like content. Allowing unsanitized uploads would defeat the plugin’s security boundary.', 'saniglyph-svg' ); ?></p></div>
				</section>
			</aside>
		</div>

		<div class="sgsvg-savebar">
			<div><strong><?php esc_html_e( 'SaniGlyph SVG', 'saniglyph-svg' ); ?></strong><span><?php esc_html_e( 'Security-first defaults, no external services.', 'saniglyph-svg' ); ?></span></div>
			<button id="sgsvg-save" type="submit" class="button button-primary sgsvg-save-button"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Save settings', 'saniglyph-svg' ); ?></button>
		</div>
	</form>
</div>
