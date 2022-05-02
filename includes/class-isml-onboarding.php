<?php
/**
 *
 */

declare(strict_types=1);

namespace Dekode\WordPress\Imageshop_Media_Library_V2;

if ( ! class_exists( 'ISML_Onboarding' ) ) {

	class ISML_Onboarding {
		private static $instance;


		public function __construct() {
			$onboarding_completed = get_option( 'imageshop_onboarding_completed', false );

			if ( ! $onboarding_completed ) {
				add_action( 'admin_notices', array( $this, 'onboarding_notice' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'onboarding_styles' ) );
				add_action( 'rest_api_init', array( $this, 'onboarding_rest_endpoints' ) );
			}
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

		public function user_can_setup_plugin(): bool {
			return current_user_can( 'manage_options' );
		}

		public function onboarding_rest_endpoints() {
			register_rest_route(
				'imageshop/v1',
				'onboarding/token',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rest_test_token' ),
					'permission_callback' => array( $this, 'user_can_setup_plugin' ),
				)
			);

			register_rest_route(
				'imageshop/v1',
				'onboarding/interfaces',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_interfaces' ),
					'permission_callback' => array( $this, 'user_can_setup_plugin' ),
				)
			);

			register_rest_route(
				'imageshop/v1',
				'onboarding/set-interface',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rest_set_interface' ),
					'permission_callback' => array( $this, 'user_can_setup_plugin' ),
				)
			);

			register_rest_route(
				'imageshop/v1',
				'onboarding/import',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_import_media' ),
					'permission_callback' => array( $this, 'user_can_setup_plugin' ),
				)
			);

			register_rest_route(
				'imageshop/v1',
				'onboarding/completed',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_completed' ),
					'permission_callback' => array( $this, 'user_can_setup_plugin' ),
				)
			);
		}

		function rest_completed() {
			update_option( 'imageshop_onboarding_completed', true );

			return new \WP_REST_Response( true, 200 );
		}

		function rest_import_media() {
			$a = $this;
			// TODO: Add import functionality.
			return new \WP_REST_Response(
				array(
					'success' => true,
				),
				200
			);
		}

		public function rest_test_token( \WP_REST_Request $request ) {
			$token = $request->get_param( 'token' );

			$imageshop = new ISML_REST_Controller( $token );

			if ( $imageshop->test_valid_token() ) {
				update_option( 'imageshop_auth_token', $token );

				return new \WP_REST_Response(
					array(
						'valid'   => true,
						'message' => 'The token is valid',
					),
					200
				);
			}

			return new \WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'The token is not valid, or no user found',
				),
				400
			);
		}

		public function rest_get_interfaces() {
			$imageshop = ISML_REST_Controller::get_instance();

			return new \WP_REST_Response(
				array(
					'interfaces' => $imageshop->get_interfaces(),
				),
				200
			);
		}

		public function rest_set_interface( \WP_REST_Request $request ) {
			$interface = $request->get_param( 'interface' );

			update_option( 'imageshop_upload_interface', $interface );
		}

		public function onboarding_styles() {
			if ( ! $this->user_can_setup_plugin() ) {
				return;
			}

			$asset = require_once ISML_ABSPATH . '/build/onboarding.asset.php';

			wp_enqueue_style( 'imageshop-onboarding', plugins_url( 'build/onboarding.css', ISML_PLUGIN_BASE_NAME ), array(), $asset['version'] );
			wp_enqueue_script(
				'imageshop-onboarding',
				plugins_url( 'build/onboarding.js', ISML_PLUGIN_BASE_NAME ),
				$asset['dependencies'],
				$asset['version'],
				true
			);
		}

		public function onboarding_notice() {
			if ( ! $this->user_can_setup_plugin() ) {
				return;
			}
			?>

			<div id="imageshop-onboarding"></div>

			<?php
		}
	}
}