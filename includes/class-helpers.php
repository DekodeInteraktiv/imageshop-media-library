<?php
declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Helper class.
 */
class Helpers {
	private static $instance;

	public function __construct() {
		add_action( 'wp_ajax_isml_test_connection', array( $this, 'test_connection' ) );

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
	 *
	 */
	public function test_connection() {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$api_key = $_POST['isml_api_key'] ?? '';
		}
		try {
			$isml_rest_controller = new REST_Controller( $api_key );
			$can_upload           = $isml_rest_controller->can_upload();
			if ( $can_upload ) {
				$this->show_message( __( 'Connection is successfully established. Save the settings.', 'imageshop' ) );
			} else {
				$this->show_message( __( 'Connection Error.', 'imageshop' ), true );
			}

			exit();
		} catch ( \Exception $e ) {
			$this->show_message(
				__(
					'Connection is not established.',
					'imageshop'
				) . ' : ' . $e->getMessage() . ( 0 === $e->getCode() ? '' : ' - ' . $e->getCode() ),
				true
			);
			exit();
		}
	}

	/**
	 * @param       $message
	 * @param false $errormsg
	 */
	public function show_message( $message, $errormsg = false ) {
		if ( $errormsg ) {
			echo '<div id="message" class="error">';
		} else {
			echo '<div id="message" class="updated fade">';
		}

		echo "<p><strong>$message</strong></p></div>";
	}

	/**
	 * Curl execution for getting the image from the server.
	 * We use curl and not file_get_contents because we need to set the headers and use authentication.
	 *
	 * @param $url
	 *
	 * @return bool|string
	 */
	public function collect_file( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, false );
		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return ( $result );
	}
}
