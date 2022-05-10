<?php
declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Imageshop Media Library main class.
 */
class Imageshop {

	private static $instance;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate all needed classes.
	 */
	public function start() {
		Helpers::get_instance();
		Attachment::get_instance();
		Library::get_instance();
		Onboarding::get_instance();
		Search::get_instance();
		Sync::get_instance();
	}

	/**
	 * Enqueue scripts.
	 */
	public function register_assets() {
		$screen = get_current_screen();

		if ( 'settings_page_imageshop-settings' !== $screen->base ) {
			return;
		}

		wp_enqueue_script(
			'isml-core-js',
			ISML_PLUGIN_DIR_URL . '/assets/scripts/core.js',
			array( 'wp-api-fetch' ),
			'1.4.0',
			true
		);

		wp_enqueue_style( 'isml-flexboxgrid', ISML_PLUGIN_DIR_URL . '/assets/styles/flexboxgrid.min.css' );
		wp_enqueue_style( 'isml-core-css', ISML_PLUGIN_DIR_URL . '/assets/styles/core.css' );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'isml_settings', 'isml_api_key' );
		register_setting( 'isml_settings', 'isml_storage_file_only' );
	}

	/**
	 * Register settings page.
	 */
	public function register_setting_page() {
		include_once( ISML_ABSPATH . '/admin/isml-settings-page.php' );
	}

	/**
	 * Register menu.
	 */
	public function register_menu() {
		add_options_page(
			esc_html__( 'Imageshop Sync', 'imageshop' ),
			esc_html__( 'Imageshop Sync', 'imageshop' ),
			'manage_options',
			'imageshop-settings',
			array( $this, 'register_setting_page' )
		);
	}
}
