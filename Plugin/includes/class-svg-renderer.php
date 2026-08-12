<?php
/**
 * Inline SVG renderer and block registration.
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SGSVG_SVG_Renderer {

	/** @var SGSVG_SVG_Renderer|null */
	private static $instance = null;

	/** @return SGSVG_SVG_Renderer */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'saniglyph_svg', array( $this, 'inline_svg_shortcode' ) );
		add_action( 'init', array( $this, 'register_svg_block' ) );
	}

	/**
	 * Render [saniglyph_svg id="123" class="my-icon" width="100" height="100"].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function inline_svg_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'class'  => '',
				'width'  => '',
				'height' => '',
			),
			$atts,
			'saniglyph_svg'
		);

		return $this->render_inline_svg( absint( $atts['id'] ), $atts );
	}

	/**
	 * Render a sanitized SVG attachment inline, or fall back to a normal image.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $args          Render arguments.
	 * @return string
	 */
	public function render_inline_svg( $attachment_id, $args = array() ) {
		if ( ! $attachment_id ) {
			return '';
		}

		$settings = sgsvg_get_settings();
		if ( empty( $settings['enable_inline_rendering'] ) || 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
			return wp_get_attachment_image( $attachment_id, 'full', false, $this->image_attributes( $args ) );
		}

		$path = get_attached_file( $attachment_id );
		if ( ! is_string( $path ) || ! is_readable( $path ) ) {
			return '';
		}

		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_string( $content ) ) {
			return '';
		}

		$sanitizer = SGSVG_SVG_Sanitizer::get_instance();
		$content   = $sanitizer->sanitize( $content );
		if ( false === $content ) {
			return '';
		}

		$class = isset( $args['className'] ) ? $args['className'] : ( isset( $args['class'] ) ? $args['class'] : '' );
		$class = $this->sanitize_classes( $class );
		$width = isset( $args['width'] ) ? $this->sanitize_dimension( $args['width'] ) : '';
		$height = isset( $args['height'] ) ? $this->sanitize_dimension( $args['height'] ) : '';

		// Preserve the validated SVG exactly when no presentation override was
		// requested. Running valid SVG through HTML-oriented KSES can normalize or
		// remove SVG-specific presentation details and make artwork render black.
		if ( '' === $class && '' === $width && '' === $height ) {
			return $content;
		}

		$content = $this->apply_root_attributes( $content, $class, $width, $height );
		if ( false === $content ) {
			return '';
		}

		// apply_root_attributes() uses the same DOM parser after the source has
		// already passed the strict SVG validator. Validate once more after mutation
		// and return only if the resulting SVG still satisfies the security rules.
		$content = $sanitizer->sanitize( $content );
		return false === $content ? '' : $content;
	}


	/**
	 * Safely apply presentation attributes to the SVG root without creating
	 * duplicate XML attributes.
	 *
	 * @param string $content Sanitized SVG XML.
	 * @param string $class   Sanitized class list.
	 * @param string $width   Sanitized width.
	 * @param string $height  Sanitized height.
	 * @return string|false
	 */
	private function apply_root_attributes( $content, $class, $width, $height ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return false;
		}

		$previous = libxml_use_internal_errors( true );
		libxml_clear_errors();
		$dom    = new DOMDocument();
		$loaded = $dom->loadXML( $content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->localName ) ) {
			return false;
		}

		$root = $dom->documentElement;
		if ( $class ) {
			$existing = $this->sanitize_classes( $root->getAttribute( 'class' ) );
			$merged   = $this->sanitize_classes( trim( $existing . ' ' . $class ) );
			if ( $merged ) {
				$root->setAttribute( 'class', $merged );
			}
		}
		if ( $width ) {
			$root->setAttribute( 'width', $width );
		}
		if ( $height ) {
			$root->setAttribute( 'height', $height );
		}

		$xml = $dom->saveXML( $root );
		return is_string( $xml ) && '' !== trim( $xml ) ? $xml : false;
	}

	/**
	 * Register the dynamic Inline SVG block from block.json.
	 *
	 * @return void
	 */
	public function register_svg_block() {
		$block_dir = SGSVG_PLUGIN_DIR . 'blocks/inline-svg';
		if ( function_exists( 'register_block_type' ) && file_exists( $block_dir . '/block.json' ) ) {
			register_block_type(
				$block_dir,
				array(
					'render_callback' => array( $this, 'render_svg_block' ),
				)
			);
		}
	}

	/**
	 * Dynamic block callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_svg_block( $attributes ) {
		$attachment_id = isset( $attributes['attachmentId'] ) ? absint( $attributes['attachmentId'] ) : 0;
		return $this->render_inline_svg( $attachment_id, $attributes );
	}

	/**
	 * Build attributes for the normal image fallback.
	 *
	 * @param array $args Render arguments.
	 * @return array
	 */
	private function image_attributes( $args ) {
		$attributes = array();
		$class      = isset( $args['className'] ) ? $args['className'] : ( isset( $args['class'] ) ? $args['class'] : '' );

		if ( $class ) {
			$attributes['class'] = $this->sanitize_classes( $class );
		}
		if ( ! empty( $args['width'] ) ) {
			$attributes['width'] = $this->sanitize_dimension( $args['width'] );
		}
		if ( ! empty( $args['height'] ) ) {
			$attributes['height'] = $this->sanitize_dimension( $args['height'] );
		}
		return array_filter( $attributes );
	}

	/** @param string $classes Class list. @return string */
	private function sanitize_classes( $classes ) {
		$clean = array();
		foreach ( preg_split( '/\s+/', (string) $classes ) as $class ) {
			$class = sanitize_html_class( $class );
			if ( $class ) {
				$clean[] = $class;
			}
		}
		return implode( ' ', array_unique( $clean ) );
	}

	/** @param string $dimension SVG dimension. @return string */
	private function sanitize_dimension( $dimension ) {
		$dimension = trim( (string) $dimension );
		return preg_match( '/^[0-9]+(?:\.[0-9]+)?(?:px|em|rem|%|vw|vh)?$/i', $dimension ) ? $dimension : '';
	}
}
