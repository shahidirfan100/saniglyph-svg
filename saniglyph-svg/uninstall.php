<?php
/**
 * Remove SaniGlyph SVG settings on uninstall.
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sgsvg_settings' );
