<?php
/**
 * SVG security validator.
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fail-closed SVG validator that preserves accepted files exactly as supplied.
 *
 * The earlier sanitizer removed unsupported nodes/attributes and then serialized
 * the DOM back to XML. That is safe in principle, but silently rewriting artwork
 * can change how exporter-specific SVGs render. SaniGlyph now takes a stricter
 * fidelity-first approach: validate the complete document against the allowlist;
 * if any unsafe/unsupported construct is found, reject the upload. If everything
 * is safe, return the original bytes unchanged.
 */
class SGSVG_SVG_Sanitizer {

	/** @var SGSVG_SVG_Sanitizer|null */
	private static $instance = null;

	/**
	 * Allowed SVG elements, stored lowercase for comparison.
	 *
	 * @var string[]
	 */
	private $allowed_elements = array(
		'svg', 'g', 'path', 'circle', 'rect', 'polygon', 'polyline', 'line', 'ellipse',
		'text', 'tspan', 'textpath', 'defs', 'use', 'symbol', 'lineargradient', 'radialgradient',
		'stop', 'clippath', 'mask', 'pattern', 'marker', 'filter', 'feblend', 'fecolormatrix',
		'fecomponenttransfer', 'fecomposite', 'feconvolvematrix', 'fediffuselighting',
		'fedisplacementmap', 'fedistantlight', 'feflood', 'fefunca', 'fefuncb', 'fefuncg',
		'fefuncr', 'fegaussianblur', 'femerge', 'femergenode', 'femorphology', 'feoffset',
		'fepointlight', 'fespecularlighting', 'fespotlight', 'fetile', 'feturbulence',
		'style', 'switch', 'title', 'desc', 'metadata',
	);

	/**
	 * Allowed attributes, stored lowercase for comparison.
	 *
	 * @var string[]
	 */
	private $allowed_attributes = array(
		'id', 'class', 'role', 'aria-label', 'aria-labelledby', 'aria-describedby', 'aria-hidden',
		'focusable', 'tabindex', 'version', 'baseprofile', 'xmlns', 'xmlns:xlink', 'viewbox', 'preserveaspectratio',
		'width', 'height', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
		'd', 'dx', 'dy', 'points', 'transform', 'opacity', 'fill', 'fill-opacity', 'fill-rule', 'clip-rule',
		'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap', 'stroke-linejoin',
		'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'vector-effect', 'clip-path', 'mask', 'filter',
		'marker-start', 'marker-mid', 'marker-end', 'style',
		'color', 'display', 'visibility', 'overflow', 'paint-order', 'shape-rendering', 'text-rendering',
		'image-rendering', 'color-rendering', 'color-interpolation', 'color-interpolation-filters', 'enable-background',
		'pathlength', 'offset', 'stop-color', 'stop-opacity', 'flood-color', 'flood-opacity', 'lighting-color',
		'gradientunits', 'gradienttransform', 'fx', 'fy', 'fr',
		'spreadmethod', 'patternunits', 'patterncontentunits', 'patterntransform', 'clippathunits',
		'maskunits', 'maskcontentunits', 'markerwidth', 'markerheight', 'markerunits', 'refx', 'refy',
		'orient', 'text-anchor', 'dominant-baseline', 'alignment-baseline', 'baseline-shift', 'font-family',
		'font-size', 'font-style', 'font-weight', 'font-variant', 'font-stretch', 'letter-spacing', 'word-spacing',
		'text-decoration', 'writing-mode', 'direction', 'textlength', 'lengthadjust', 'xml:space', 'href', 'xlink:href',
		'in', 'in2', 'result', 'mode', 'operator', 'values', 'type', 'stddeviation', 'basefrequency',
		'numoctaves', 'seed', 'stitchtiles', 'surfacescale', 'specularconstant', 'specularexponent',
		'diffuseconstant', 'kernelmatrix', 'kernelunitlength', 'order', 'targetx', 'targety', 'edgemode',
		'preservealpha', 'xchannelselector', 'ychannelselector', 'scale', 'bias', 'divisor', 'k1', 'k2', 'k3', 'k4',
	);

	/**
	 * SVG presentation properties allowed in inline styles and style blocks.
	 *
	 * @var string[]
	 */
	private $allowed_css_properties = array(
		'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity',
		'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray',
		'stroke-dashoffset', 'opacity', 'color', 'stop-color', 'stop-opacity',
		'flood-color', 'flood-opacity', 'lighting-color', 'clip-path', 'clip-rule',
		'mask', 'filter', 'marker-start', 'marker-mid', 'marker-end', 'paint-order',
		'vector-effect', 'visibility', 'display', 'overflow', 'shape-rendering',
		'text-rendering', 'image-rendering', 'color-rendering', 'color-interpolation',
		'color-interpolation-filters', 'font-family', 'font-size', 'font-style',
		'font-weight', 'font-variant', 'font-stretch', 'letter-spacing', 'word-spacing',
		'text-anchor', 'text-decoration', 'dominant-baseline', 'alignment-baseline',
		'baseline-shift', 'direction', 'writing-mode', 'transform', 'transform-origin',
		'pointer-events', 'isolation', 'mix-blend-mode', 'enable-background',
	);

	/**
	 * Return singleton instance.
	 *
	 * @return SGSVG_SVG_Sanitizer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Whether the required XML DOM parser is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'DOMDocument' );
	}

	/**
	 * Validate SVG markup and preserve accepted markup exactly.
	 *
	 * Unsafe or unsupported SVG is rejected instead of being silently modified.
	 * This preserves fills, strokes, CSS, gradients, exporter metadata/comments,
	 * whitespace, XML declaration, and other presentation details for valid files.
	 *
	 * @param string $svg_content Raw SVG XML.
	 * @return string|false Original SVG when safe, false when rejected.
	 */
	public function sanitize( $svg_content ) {
		if ( ! is_string( $svg_content ) || '' === trim( $svg_content ) || ! $this->is_available() ) {
			return false;
		}

		// Reject NUL-containing/UTF-16 style byte streams before textual security checks.
		if ( false !== strpos( $svg_content, "\0" ) ) {
			return false;
		}

		// DTDs/entities and processing instructions other than the XML declaration are
		// unnecessary for uploaded SVGs and increase the XML/CSS attack surface.
		if ( preg_match( '/<!DOCTYPE|<!ENTITY/i', $svg_content ) ) {
			return false;
		}
		if ( preg_match( '/<\?(?!xml(?:\s|\?>))/i', $svg_content ) ) {
			return false;
		}

		$previous = libxml_use_internal_errors( true );
		libxml_clear_errors();

		$dom                      = new DOMDocument();
		$dom->preserveWhiteSpace = true;
		$loaded                   = $dom->loadXML( $svg_content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded || ! $dom->documentElement || 'svg' !== strtolower( $dom->documentElement->localName ) ) {
			return false;
		}

		$namespace = $dom->documentElement->namespaceURI;
		if ( $namespace && 'http://www.w3.org/2000/svg' !== $namespace ) {
			return false;
		}

		if ( ! $this->validate_node( $dom->documentElement ) ) {
			return false;
		}

		// Security validation is complete. Return the original input, not a DOM
		// serialization, so a safe SVG keeps its original visual representation.
		return $svg_content;
	}

	/**
	 * Validate an SVG file by running the full validator.
	 *
	 * @param string $file_path File path.
	 * @return bool
	 */
	public function validate_svg_file( $file_path ) {
		if ( ! is_string( $file_path ) || ! is_readable( $file_path ) ) {
			return false;
		}

		$content = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return is_string( $content ) && false !== $this->sanitize( $content );
	}

	/**
	 * Return matching wp_kses rules for integrations that need them.
	 *
	 * @return array<string,array<string,bool>>
	 */
	public function get_kses_rules() {
		$attributes = array_fill_keys( $this->allowed_attributes, true );
		$rules      = array();

		foreach ( $this->allowed_elements as $tag ) {
			$rules[ $tag ] = $attributes;
		}

		return $rules;
	}

	/**
	 * Validate a node recursively without modifying it.
	 *
	 * @param DOMNode $node Current node.
	 * @return bool
	 */
	private function validate_node( $node ) {
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return false;
		}

		$namespace = $node->namespaceURI;
		$name      = strtolower( $node->localName );
		if ( ( $namespace && 'http://www.w3.org/2000/svg' !== $namespace ) || ! in_array( $name, $this->allowed_elements, true ) ) {
			return false;
		}

		if ( $node->hasAttributes() ) {
			foreach ( $node->attributes as $attribute ) {
				$attribute_name      = strtolower( $attribute->nodeName );
				$attribute_value     = trim( $attribute->nodeValue );
				$attribute_namespace = $attribute->namespaceURI;

				$invalid_namespace =
					( 'xlink:href' === $attribute_name && 'http://www.w3.org/1999/xlink' !== $attribute_namespace ) ||
					( 'xml:space' === $attribute_name && 'http://www.w3.org/XML/1998/namespace' !== $attribute_namespace );

				if (
					$invalid_namespace ||
					0 === strpos( $attribute_name, 'on' ) ||
					! in_array( $attribute_name, $this->allowed_attributes, true ) ||
					! $this->is_safe_attribute_value( $attribute_name, $attribute_value )
				) {
					return false;
				}
			}
		}

		if ( 'style' === $name && ! $this->is_safe_stylesheet( $node->textContent ) ) {
			return false;
		}

		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType ) {
				if ( ! $this->validate_node( $child ) ) {
					return false;
				}
				continue;
			}

			// Text, CDATA, and comments are inert once the containing style element
			// has passed CSS validation. Reject processing instructions and other nodes.
			if ( ! in_array( $child->nodeType, array( XML_TEXT_NODE, XML_CDATA_SECTION_NODE, XML_COMMENT_NODE ), true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate a style attribute without rewriting its declaration text.
	 *
	 * @param string $css Declaration list.
	 * @return bool
	 */
	private function is_safe_style_attribute( $css ) {
		$css = (string) $css;
		if ( '' === trim( $css ) ) {
			return false;
		}

		foreach ( explode( ';', $css ) as $declaration ) {
			$declaration = trim( $declaration );
			if ( '' === $declaration ) {
				continue;
			}

			$parts = explode( ':', $declaration, 2 );
			if ( 2 !== count( $parts ) ) {
				return false;
			}

			$property        = strtolower( trim( $parts[0] ) );
			$value           = trim( $parts[1] );
			$custom_property = (bool) preg_match( '/^--[a-z0-9_-]+$/', $property );

			if (
				( ! $custom_property && ! in_array( $property, $this->allowed_css_properties, true ) ) ||
				'' === $value ||
				! $this->is_safe_css_value( $value )
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate a simple SVG stylesheet while preserving its exact source text.
	 *
	 * At-rules and nested CSS are rejected. Common exporter class rules such as
	 * .st0{fill:#fff} and .cls-1{stroke:#000} are accepted unchanged.
	 *
	 * @param string $css Raw stylesheet.
	 * @return bool
	 */
	private function is_safe_stylesheet( $css ) {
		if ( ! is_string( $css ) ) {
			return false;
		}

		$check = preg_replace( '#/\*.*?\*/#s', '', $css );
		if ( ! is_string( $check ) ) {
			return false;
		}

		if ( '' === trim( $check ) ) {
			return true;
		}

		if ( preg_match( '/@|<|>|[\x00-\x08\x0B\x0C\x0E-\x1F]/u', $check ) ) {
			return false;
		}

		if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/u', $check, $matches, PREG_SET_ORDER ) ) {
			return false;
		}

		foreach ( $matches as $match ) {
			$selector = trim( $match[1] );
			if ( '' === $selector || ! preg_match( '/^[^{}@<>]+$/u', $selector ) ) {
				return false;
			}
			if ( ! $this->is_safe_style_attribute( $match[2] ) ) {
				return false;
			}
		}

		// Do not permit parser gaps: after removing every validated rule there must
		// be no executable or unexplained CSS left over.
		$remainder = preg_replace( '/[^{}]+\{[^{}]*\}/u', '', $check );
		return is_string( $remainder ) && '' === trim( $remainder );
	}

	/**
	 * Validate CSS values used in SVG presentation properties.
	 *
	 * @param string $value CSS property value.
	 * @return bool
	 */
	private function is_safe_css_value( $value ) {
		$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F<>]/', $decoded ) ) {
			return false;
		}

		if ( preg_match( '/(?:javascript|vbscript|data|progid)\s*:|expression\s*\(|@import|behavior\s*:|-moz-binding/i', $decoded ) ) {
			return false;
		}

		if ( false !== stripos( $decoded, 'url(' ) ) {
			$without_local_urls = preg_replace( '/url\(\s*["\']?#[A-Za-z0-9_.:-]+["\']?\s*\)/i', '', $decoded );
			if ( ! is_string( $without_local_urls ) || false !== stripos( $without_local_urls, 'url(' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Extend WordPress' inline CSS allowlist for integrations that use KSES.
	 *
	 * @param string[] $properties Core safe CSS properties.
	 * @return string[]
	 */
	public function filter_safe_style_css( $properties ) {
		return array_values( array_unique( array_merge( (array) $properties, $this->allowed_css_properties, array( '--*' ) ) ) );
	}

	/**
	 * Let KSES retain SVG CSS that this validator has already approved.
	 *
	 * @param bool   $allow_css       Core decision.
	 * @param string $css_test_string CSS declaration being checked.
	 * @return bool
	 */
	public function filter_kses_allow_svg_css( $allow_css, $css_test_string ) {
		if ( $allow_css ) {
			return true;
		}

		return $this->is_safe_style_attribute( $css_test_string );
	}

	/**
	 * Validate values that can carry URLs or executable CSS-like content.
	 *
	 * @param string $name  Lowercase attribute name.
	 * @param string $value Attribute value.
	 * @return bool
	 */
	private function is_safe_attribute_value( $name, $value ) {
		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value ) ) {
			return false;
		}

		$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( preg_match( '/(?:javascript|vbscript|data)\s*:|expression\s*\(|@import/i', $decoded ) ) {
			return false;
		}

		if ( 'xmlns' === $name ) {
			return 'http://www.w3.org/2000/svg' === $value;
		}

		if ( 'xmlns:xlink' === $name ) {
			return 'http://www.w3.org/1999/xlink' === $value;
		}

		if ( 'xml:space' === $name ) {
			return in_array( strtolower( $value ), array( 'default', 'preserve' ), true );
		}

		if ( 'style' === $name ) {
			return $this->is_safe_style_attribute( $value );
		}

		if ( 'href' === $name || 'xlink:href' === $name ) {
			return (bool) preg_match( '/^#[A-Za-z0-9_.:-]+$/', $value );
		}

		if ( false !== stripos( $decoded, 'url(' ) ) {
			// Permit only local fragment paint/filter references such as url(#grad).
			return $this->is_safe_css_value( $decoded );
		}

		return true;
	}
}
