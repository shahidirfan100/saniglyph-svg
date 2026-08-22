<?php
/**
 * SVG upload integration.
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SGSVG_SVG_Uploader {

	/** @var SGSVG_SVG_Uploader|null */
	private static $instance = null;

	/** @return SGSVG_SVG_Uploader */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'upload_mimes', array( $this, 'enable_svg_upload' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_mime_type_svg' ), 10, 4 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_svg_upload' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( $this, 'sanitize_svg_upload' ) );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'fix_svg_thumb_display' ), 10, 3 );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'add_svg_dimensions' ), 10, 2 );
	}

	/**
	 * Allow only plain .svg uploads for permitted users. Compressed .svgz is intentionally unsupported.
	 *
	 * @param array<string,string> $mimes MIME map.
	 * @return array<string,string>
	 */
	public function enable_svg_upload( $mimes ) {
		if ( $this->user_can_upload_svg() && SGSVG_SVG_Sanitizer::get_instance()->is_available() ) {
			$mimes['svg'] = 'image/svg+xml';
		}
		return $mimes;
	}

	/**
	 * Correct WordPress file type detection after the temporary SVG has passed sanitization.
	 *
	 * @param array  $data     File data.
	 * @param string $file     File path.
	 * @param string $filename Original filename.
	 * @param array  $mimes    Allowed MIME types.
	 * @return array
	 */
	public function fix_mime_type_svg( $data, $file, $filename, $mimes ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( 'svg' !== $extension || ! $this->user_can_upload_svg() || ! SGSVG_SVG_Sanitizer::get_instance()->is_available() ) {
			return $data;
		}

		if ( is_readable( $file ) && SGSVG_SVG_Sanitizer::get_instance()->validate_svg_file( $file ) ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}

		return $data;
	}

	/**
	 * Sanitize SVG uploads before WordPress moves them into the uploads directory.
	 *
	 * @param array $file Uploaded file data.
	 * @return array
	 */
	public function sanitize_svg_upload( $file ) {
		$filename  = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( 'svg' !== $extension ) {
			return $file;
		}

		if ( ! $this->user_can_upload_svg() ) {
			$file['error'] = __( 'You do not have permission to upload SVG files.', 'saniglyph-svg' );
			return $file;
		}

		if ( ! SGSVG_SVG_Sanitizer::get_instance()->is_available() ) {
			$file['error'] = __( 'SVG uploads are disabled because the PHP DOM extension is not available on this server.', 'saniglyph-svg' );
			return $file;
		}

		$file_path = isset( $file['tmp_name'] ) ? $file['tmp_name'] : '';
		if ( ! is_string( $file_path ) || ! is_readable( $file_path ) ) {
			$file['error'] = __( 'The SVG upload could not be read.', 'saniglyph-svg' );
			return $file;
		}

		$raw_svg = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_string( $raw_svg ) ) {
			$file['error'] = __( 'The SVG upload could not be read.', 'saniglyph-svg' );
			return $file;
		}

		$sanitizer = SGSVG_SVG_Sanitizer::get_instance();
		$clean_svg = $sanitizer->sanitize( $raw_svg );

		if ( false === $clean_svg ) {
			$file['error'] = __( 'This SVG could not be safely sanitized and was blocked.', 'saniglyph-svg' );
			return $file;
		}

		$clean_svg = SGSVG_SVG_Optimizer::get_instance()->optimize( $clean_svg );

		// Defense in depth: optimization must never be able to reintroduce unsafe markup.
		$clean_svg = $sanitizer->sanitize( $clean_svg );
		if ( false === $clean_svg ) {
			$file['error'] = __( 'This SVG failed the final security check and was blocked.', 'saniglyph-svg' );
			return $file;
		}

		$bytes = file_put_contents( $file_path, $clean_svg, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === $bytes ) {
			$file['error'] = __( 'WordPress could not save the sanitized SVG.', 'saniglyph-svg' );
			return $file;
		}

		$file['type'] = 'image/svg+xml';
		return $file;
	}

	/**
	 * Check role configuration and the core upload_files capability.
	 *
	 * @return bool
	 */
	private function user_can_upload_svg() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		$settings      = sgsvg_get_settings();
		$allowed_roles = array_map( 'sanitize_key', (array) $settings['allowed_roles'] );
		$user          = wp_get_current_user();

		return ! empty( array_intersect( (array) $user->roles, $allowed_roles ) );
	}

	/**
	 * Add dimensions to Media Library response so SVG previews render consistently.
	 *
	 * @param array   $response   Attachment response.
	 * @param WP_Post $attachment Attachment object.
	 * @param array   $meta       Attachment metadata.
	 * @return array
	 */
	public function fix_svg_thumb_display( $response, $attachment, $meta ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( empty( $response['mime'] ) || 'image/svg+xml' !== $response['mime'] ) {
			return $response;
		}

		$width  = ! empty( $meta['width'] ) ? (int) $meta['width'] : 0;
		$height = ! empty( $meta['height'] ) ? (int) $meta['height'] : 0;

		if ( ! $width || ! $height ) {
			$dimensions = $this->read_svg_dimensions( get_attached_file( $attachment->ID ) );
			$width      = $dimensions['width'];
			$height     = $dimensions['height'];
		}

		$response['sizes']['full'] = array(
			'url'         => $response['url'],
			'width'       => $width,
			'height'      => $height,
			'orientation' => $width >= $height ? 'landscape' : 'portrait',
		);

		return $response;
	}

	/**
	 * Populate SVG attachment width and height from width/height or viewBox.
	 *
	 * @param array|false $metadata      Attachment metadata.
	 * @param int         $attachment_id Attachment ID.
	 * @return array|false
	 */
	public function add_svg_dimensions( $metadata, $attachment_id ) {
		if ( 'image/svg+xml' !== get_post_mime_type( $attachment_id ) ) {
			return $metadata;
		}

		$dimensions = $this->read_svg_dimensions( get_attached_file( $attachment_id ) );
		if ( $dimensions['width'] > 0 && $dimensions['height'] > 0 ) {
			if ( ! is_array( $metadata ) ) {
				$metadata = array();
			}
			$metadata['width']  = $dimensions['width'];
			$metadata['height'] = $dimensions['height'];
		}

		return $metadata;
	}

	/**
	 * Read dimensions from a sanitized SVG file.
	 *
	 * @param string|false $path SVG file path.
	 * @return array{width:int,height:int}
	 */
	private function read_svg_dimensions( $path ) {
		$empty = array( 'width' => 0, 'height' => 0 );
		if ( ! is_string( $path ) || ! is_readable( $path ) || ! SGSVG_SVG_Sanitizer::get_instance()->is_available() ) {
			return $empty;
		}

		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_string( $content ) ) {
			return $empty;
		}

		$previous = libxml_use_internal_errors( true );
		libxml_clear_errors();
		$dom    = new DOMDocument();
		$loaded = $dom->loadXML( $content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->localName ) ) {
			return $empty;
		}

		$root   = $dom->documentElement;
		$width  = $this->numeric_dimension( $root->getAttribute( 'width' ) );
		$height = $this->numeric_dimension( $root->getAttribute( 'height' ) );

		if ( ( ! $width || ! $height ) && $root->hasAttribute( 'viewBox' ) ) {
			$parts = preg_split( '/[\s,]+/', trim( $root->getAttribute( 'viewBox' ) ) );
			if ( is_array( $parts ) && 4 === count( $parts ) ) {
				$width  = abs( (float) $parts[2] );
				$height = abs( (float) $parts[3] );
			}
		}

		return array(
			'width'  => $width > 0 ? (int) max( 1, round( $width ) ) : 0,
			'height' => $height > 0 ? (int) max( 1, round( $height ) ) : 0,
		);
	}

	/**
	 * Read a leading numeric value from an SVG dimension.
	 *
	 * @param string $value Dimension value.
	 * @return float
	 */
	private function numeric_dimension( $value ) {
		if ( preg_match( '/^\s*([0-9]+(?:\.[0-9]+)?)/', $value, $matches ) ) {
			return (float) $matches[1];
		}
		return 0.0;
	}
}
