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
?>
<div class="wrap sgsvg-wrap">
	<h1><?php esc_html_e( 'SaniGlyph SVG', 'saniglyph-svg' ); ?></h1>
	<p class="description sgsvg-intro"><?php esc_html_e( 'SVG files are sanitized before they are stored. Choose who can upload them and how they are rendered.', 'saniglyph-svg' ); ?></p>

	<div id="sgsvg-status" class="notice sgsvg-status" role="status" aria-live="polite" hidden>
		<p><span class="dashicons" aria-hidden="true"></span> <span class="sgsvg-status-text"></span></p>
	</div>

	<form id="sgsvg-settings-form" method="post" action="options.php">
		<?php settings_fields( 'sgsvg_settings_group' ); ?>

		<h2><?php esc_html_e( 'SVG handling', 'saniglyph-svg' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sanitization', 'saniglyph-svg' ); ?></th>
					<td>
						<p><strong><?php esc_html_e( 'Always on', 'saniglyph-svg' ); ?></strong></p>
						<p class="description sgsvg-help"><?php esc_html_e( 'SVG markup is checked against the supported element and attribute allowlist before storage. Files that fail validation are rejected.', 'saniglyph-svg' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sgsvg_enable_optimization"><?php esc_html_e( 'Safe optimization', 'saniglyph-svg' ); ?></label></th>
					<td>
						<label for="sgsvg_enable_optimization">
							<input id="sgsvg_enable_optimization" type="checkbox" name="sgsvg_settings[enable_optimization]" value="1" <?php checked( 1, $sgsvg_settings['enable_optimization'] ); ?>>
							<?php esc_html_e( 'Reduce harmless whitespace between SVG elements', 'saniglyph-svg' ); ?>
						</label>
						<p class="description sgsvg-help"><?php esc_html_e( 'This does not rewrite artwork, styling, geometry, text spacing, or accessibility content.', 'saniglyph-svg' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sgsvg_enable_inline_rendering"><?php esc_html_e( 'Inline rendering', 'saniglyph-svg' ); ?></label></th>
					<td>
						<label for="sgsvg_enable_inline_rendering">
							<input id="sgsvg_enable_inline_rendering" type="checkbox" name="sgsvg_settings[enable_inline_rendering]" value="1" <?php checked( 1, $sgsvg_settings['enable_inline_rendering'] ); ?>>
							<?php esc_html_e( 'Allow the shortcode and block to render sanitized SVG markup inline', 'saniglyph-svg' ); ?>
						</label>
						<p class="description sgsvg-help"><?php esc_html_e( 'Leave this off to use the standard image output. Inline output is useful when you need to style the SVG or access it in the page DOM.', 'saniglyph-svg' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<hr>

		<h2><?php esc_html_e( 'Upload permissions', 'saniglyph-svg' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select the user roles allowed to upload SVG files. Users must also have the WordPress upload_files capability.', 'saniglyph-svg' ); ?></p>
		<fieldset class="sgsvg-role-fieldset">
			<legend class="screen-reader-text"><?php esc_html_e( 'Roles allowed to upload SVG files', 'saniglyph-svg' ); ?></legend>
			<ul class="sgsvg-role-list">
				<?php foreach ( $sgsvg_wp_roles as $sgsvg_role_key => $sgsvg_role ) : ?>
					<li>
						<label for="sgsvg_role_<?php echo esc_attr( $sgsvg_role_key ); ?>">
							<input id="sgsvg_role_<?php echo esc_attr( $sgsvg_role_key ); ?>" type="checkbox" name="sgsvg_settings[allowed_roles][]" value="<?php echo esc_attr( $sgsvg_role_key ); ?>" <?php checked( in_array( $sgsvg_role_key, $sgsvg_allowed_roles, true ) ); ?>>
							<?php echo esc_html( translate_user_role( $sgsvg_role['name'] ) ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>

		<hr>

		<h2><?php esc_html_e( 'Shortcode', 'saniglyph-svg' ); ?></h2>
		<p><?php esc_html_e( 'When inline rendering is enabled, use this shortcode with the ID of an SVG attachment:', 'saniglyph-svg' ); ?></p>
		<p class="sgsvg-shortcode-row">
			<code id="sgsvg-shortcode">[saniglyph_svg id="123"]</code>
			<button type="button" class="button button-secondary" data-sgsvg-copy="#sgsvg-shortcode"><?php esc_html_e( 'Copy shortcode', 'saniglyph-svg' ); ?></button>
		</p>
		<p class="description"><?php esc_html_e( 'Optional attributes: class, width, and height.', 'saniglyph-svg' ); ?></p>

		<?php submit_button( __( 'Save Changes', 'saniglyph-svg' ), 'primary', 'submit', false, array( 'id' => 'sgsvg-save' ) ); ?>
	</form>
</div>
