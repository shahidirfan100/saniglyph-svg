<?php
/**
 * Conservative SVG optimizer.
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SGSVG_SVG_Optimizer {

	/** @var SGSVG_SVG_Optimizer|null */
	private static $instance = null;

	/** @return SGSVG_SVG_Optimizer */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Optimize sanitized SVG without changing geometry or accessibility text.
	 *
	 * @param string $svg_content Sanitized SVG.
	 * @return string
	 */
	public function optimize( $svg_content ) {
		$settings = sgsvg_get_settings();
		if ( empty( $settings['enable_optimization'] ) ) {
			return $svg_content;
		}

		$optimized = trim( $svg_content );

		// Inter-element whitespace is safe to collapse only when the SVG has no
		// text-bearing elements. This avoids changing spaces between <tspan> nodes
		// or accessibility text while still reducing common icon/logo markup.
		if ( ! preg_match( '/<(?:text|tspan|title|desc)\b/i', $optimized ) ) {
			$collapsed = preg_replace( '/>\s+</u', '><', $optimized );
			if ( is_string( $collapsed ) ) {
				$optimized = $collapsed;
			}
		}

		return $optimized;
	}

	/**
	 * Return simple byte-savings statistics.
	 *
	 * @param string $original  Original SVG.
	 * @param string $optimized Optimized SVG.
	 * @return array<string,int|float>
	 */
	public function get_stats( $original, $optimized ) {
		$original_size  = strlen( $original );
		$optimized_size = strlen( $optimized );
		$saved          = max( 0, $original_size - $optimized_size );

		return array(
			'original_size'  => $original_size,
			'optimized_size' => $optimized_size,
			'bytes_saved'    => $saved,
			'percent_saved'  => $original_size > 0 ? round( ( $saved / $original_size ) * 100, 2 ) : 0,
		);
	}
}
