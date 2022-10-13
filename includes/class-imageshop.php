<?php
/**
 * The main plugin class.
 */

declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Imageshop Media Library main class.
 */
class Imageshop {

	private static $instance;

	public $onboarding_completed = null;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		\add_action( 'admin_menu', array( $this, 'register_menu' ) );
		\add_action( 'admin_init', array( $this, 'register_settings' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Check the onboarding state, and if required settings are in place.
	 *
	 * @return bool
	 */
	public function onboarding_completed() {
		if ( null === $this->onboarding_completed ) {
			$completed = false;

			$interface = \get_option( 'imageshop_upload_interface', '' );
			$api_key   = \get_option( 'imageshop_api_key', '' );

			if ( ! empty( $interface ) && ! empty( $api_key ) ) {
				$completed = true;
			}

			$this->onboarding_completed = $completed;
		}

		return $this->onboarding_completed;
	}

	/**
	 * Return a singleton instance of this class.
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
		$screen = \get_current_screen();

		if ( 'settings_page_imageshop-settings' !== $screen->base ) {
			return;
		}

		\wp_enqueue_script(
			'imageshop-settings',
			plugins_url( '/assets/scripts/settings.js', IMAGESHOP_PLUGIN_BASE_NAME ),
			array( 'wp-api-fetch' ),
			'1.4.0',
			true
		);

		\wp_enqueue_style( 'flexboxgrid', \plugins_url( '/assets/styles/flexboxgrid.min.css', IMAGESHOP_PLUGIN_BASE_NAME ) );
		\wp_enqueue_style( 'imageshop-settings', \plugins_url( '/assets/styles/settings.css', IMAGESHOP_PLUGIN_BASE_NAME ), array( 'flexboxgrid' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		\register_setting( 'imageshop_settings', 'imageshop_api_key' );
		\register_setting( 'imageshop_settings', 'imageshop_upload_interface' );
	}

	/**
	 * Register settings page.
	 */
	public function register_setting_page() {
		include_once( IMAGESHOP_ABSPATH . '/admin/settings-page.php' );
	}

	/**
	 * Register menu.
	 */
	public function register_menu() {
		\add_options_page(
			\esc_html__( 'Imageshop DAM', 'imageshop-dam-connector' ),
			\esc_html__( 'Imageshop DAM', 'imageshop-dam-connector' ),
			'manage_options',
			'imageshop-settings',
			array( $this, 'register_setting_page' )
		);
	}
}
