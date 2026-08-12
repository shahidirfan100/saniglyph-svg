<?php
/**
 * Admin settings controller.
 *
 * @package SaniGlyphSVG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SGSVG_Admin_Settings {

	/** @var SGSVG_Admin_Settings|null */
	private static $instance = null;

	/** @return SGSVG_Admin_Settings */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_dom_requirement_notice' ) );
		add_action( 'wp_ajax_sgsvg_save_settings', array( $this, 'ajax_save_settings' ) );
	}

	/** @return void */
	public function add_settings_page() {
		add_options_page(
			__( 'SaniGlyph SVG Settings', 'saniglyph-svg' ),
			__( 'SaniGlyph SVG', 'saniglyph-svg' ),
			'manage_options',
			'saniglyph-svg',
			array( $this, 'render_settings_page' )
		);
	}

	/** @return void */
	public function register_settings() {
		register_setting(
			'sgsvg_settings_group',
			'sgsvg_settings',
			array(
				'type'              => 'array',
				'default'           => sgsvg_get_default_settings(),
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings and validate role keys against WordPress roles.
	 *
	 * @param mixed $input Raw option value.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$roles = array();

		if ( ! empty( $input['allowed_roles'] ) && is_array( $input['allowed_roles'] ) ) {
			$requested = array_map( 'sanitize_key', $input['allowed_roles'] );
			$valid     = array_keys( wp_roles()->roles );
			$roles     = array_values( array_intersect( $requested, $valid ) );
		}

		if ( empty( $roles ) ) {
			$roles = array( 'administrator' );
		}

		return array(
			'enable_optimization'     => empty( $input['enable_optimization'] ) ? 0 : 1,
			'enable_inline_rendering' => empty( $input['enable_inline_rendering'] ) ? 0 : 1,
			'allowed_roles'           => $roles,
		);
	}

	/**
	 * Load local assets only on the plugin settings screen.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_saniglyph-svg' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'sgsvg-admin', SGSVG_PLUGIN_URL . 'admin/css/admin-styles.css', array(), SGSVG_VERSION );
		wp_enqueue_script( 'sgsvg-admin', SGSVG_PLUGIN_URL . 'admin/js/admin-scripts.js', array(), SGSVG_VERSION, true );

		wp_localize_script(
			'sgsvg-admin',
			'sgsvgAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sgsvg_save_settings' ),
				'i18n'    => array(
					'saved'  => __( 'Settings saved.', 'saniglyph-svg' ),
					'error'  => __( 'Settings could not be saved. Please try again.', 'saniglyph-svg' ),
					'copied' => __( 'Shortcode copied.', 'saniglyph-svg' ),
				),
			)
		);
	}

	/**
	 * Warn administrators when secure SVG processing cannot run.
	 *
	 * @return void
	 */
	public function render_dom_requirement_notice() {
		if ( SGSVG_SVG_Sanitizer::get_instance()->is_available() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && ! in_array( $screen->id, array( 'plugins', 'settings_page_saniglyph-svg' ), true ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'SaniGlyph SVG:', 'saniglyph-svg' ) . '</strong> ' . esc_html__( 'The PHP DOM extension is required for secure SVG sanitization. SVG uploads remain disabled until DOM is enabled on the server.', 'saniglyph-svg' ) . '</p></div>';
	}

	/** @return void */
	public function ajax_save_settings() {
		check_ajax_referer( 'sgsvg_save_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to change these settings.', 'saniglyph-svg' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Every supported field is sanitized by sanitize_settings().
		$raw       = isset( $_POST['sgsvg_settings'] ) ? wp_unslash( $_POST['sgsvg_settings'] ) : array();
		$sanitized = $this->sanitize_settings( $raw );

		update_option( 'sgsvg_settings', $sanitized );
		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'saniglyph-svg' ) ) );
	}

	/** @return void */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include SGSVG_PLUGIN_DIR . 'admin/views/settings-page.php';
	}
}
