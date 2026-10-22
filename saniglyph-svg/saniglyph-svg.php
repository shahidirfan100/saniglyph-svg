<?php
/**
 * Plugin Name: SaniGlyph SVG
 * Description: Fail-closed SVG validation that preserves approved artwork, with role controls, media previews, and securely escaped inline output.
 * Version: 1.1.9
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Shahid Irfan
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: saniglyph-svg
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SGSVG_VERSION', '1.1.9' );
define( 'SGSVG_PLUGIN_FILE', __FILE__ );
define( 'SGSVG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGSVG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SGSVG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Return default plugin settings.
 *
 * @return array<string,mixed>
 */
function sgsvg_get_default_settings() {
	return array(
		'enable_optimization'     => 1,
		'enable_inline_rendering' => 0,
		'allowed_roles'           => array( 'administrator' ),
	);
}

/**
 * Return normalized plugin settings.
 *
 * @return array<string,mixed>
 */
function sgsvg_get_settings() {
	$settings = get_option( 'sgsvg_settings', array() );

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args( $settings, sgsvg_get_default_settings() );
}

/**
 * Migrate settings from the pre-release Pro SVG Manager package.
 *
 * @return void
 */
function sgsvg_maybe_migrate_settings() {
	if ( false !== get_option( 'sgsvg_settings', false ) ) {
		return;
	}

	$legacy = get_option( 'psm_settings', false );
	if ( ! is_array( $legacy ) ) {
		return;
	}

	$legacy_keys = array( 'enable_optimization', 'enable_inline_rendering', 'allowed_roles' );
	if ( empty( array_intersect( array_keys( $legacy ), $legacy_keys ) ) ) {
		return;
	}

	$migrated = sgsvg_get_default_settings();

	if ( isset( $legacy['enable_optimization'] ) ) {
		$migrated['enable_optimization'] = empty( $legacy['enable_optimization'] ) ? 0 : 1;
	}
	if ( isset( $legacy['enable_inline_rendering'] ) ) {
		$migrated['enable_inline_rendering'] = empty( $legacy['enable_inline_rendering'] ) ? 0 : 1;
	}
	if ( ! empty( $legacy['allowed_roles'] ) && is_array( $legacy['allowed_roles'] ) ) {
		$requested = array_map( 'sanitize_key', $legacy['allowed_roles'] );
		$valid     = array_keys( wp_roles()->roles );
		$roles     = array_values( array_intersect( $requested, $valid ) );

		if ( ! empty( $roles ) ) {
			$migrated['allowed_roles'] = $roles;
		}
	}

	add_option( 'sgsvg_settings', $migrated );
}

require_once SGSVG_PLUGIN_DIR . 'includes/class-svg-sanitizer.php';
require_once SGSVG_PLUGIN_DIR . 'includes/class-svg-optimizer.php';
require_once SGSVG_PLUGIN_DIR . 'includes/class-svg-uploader.php';
require_once SGSVG_PLUGIN_DIR . 'includes/class-svg-renderer.php';

if ( is_admin() ) {
	require_once SGSVG_PLUGIN_DIR . 'admin/class-admin-settings.php';
}

/**
 * Initialize plugin services.
 *
 * @return void
 */
function sgsvg_init() {
	sgsvg_maybe_migrate_settings();
	SGSVG_SVG_Uploader::get_instance();
	SGSVG_SVG_Renderer::get_instance();

	if ( is_admin() ) {
		SGSVG_Admin_Settings::get_instance();
	}
}
add_action( 'plugins_loaded', 'sgsvg_init' );

/**
 * Set defaults on activation without overwriting existing settings.
 *
 * @return void
 */
function sgsvg_activate() {
	sgsvg_maybe_migrate_settings();
	add_option( 'sgsvg_settings', sgsvg_get_default_settings() );
}
register_activation_hook( __FILE__, 'sgsvg_activate' );

/**
 * Add a Settings link on the Plugins screen.
 *
 * @param string[] $links Existing plugin action links.
 * @return string[]
 */
function sgsvg_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=saniglyph-svg' ) ) . '">' . esc_html__( 'Settings', 'saniglyph-svg' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . SGSVG_PLUGIN_BASENAME, 'sgsvg_plugin_action_links' );
