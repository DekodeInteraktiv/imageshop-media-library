<?php
namespace Dekode\WordPress\Imageshop_Media_Library_V2;

/**
 * Plugin Name: Imageshop Media Library V2
 * Plugin URI:
 * Description: This WordPress plugin syncs your media library with Imageshop.
 * Version: 0.0.1
 * Author: Dekode
 * Author URI: https://dekode.no
 * License: MIT
 * Text Domain: isml
 * Domain Path: /languages
 */
defined( 'ABSPATH' ) || exit;

define( 'ISML_ABSPATH', __DIR__ );
define( 'ISML_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'ISML_PLUGIN_BASE_NAME', __FILE__ );

require ISML_ABSPATH . '/vendor/autoload.php';

// Load plugin classes.
foreach ( glob( __DIR__ . '/includes/*.php' ) as $file ) {
	require $file;
}


load_plugin_textdomain( 'isml', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

function isml_incompatibile( $msg ) {
	require_once ABSPATH . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
	deactivate_plugins( __FILE__ );
	wp_die( $msg );
}

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	if ( version_compare( PHP_VERSION, '5.3.3', '<' ) ) {
		isml_incompatibile(
			__(
				'Plugin Imageshop Media Library requires PHP 5.3.3 or higher. The plugin has now disabled itself.',
				'isml'
			)
		);
	} elseif ( ! function_exists( 'curl_version' )
			   || ! ( $curl = curl_version() ) || empty( $curl['version'] ) || empty( $curl['features'] )
			   || version_compare( $curl['version'], '7.16.2', '<' )
	) {
		isml_incompatibile(
			__( 'Plugin Imageshop Media Library requires cURL 7.16.2+. The plugin has now disabled itself.', 'isml' )
		);
	} elseif ( ! ( $curl['features'] & CURL_VERSION_SSL ) ) {
		isml_incompatibile(
			__(
				'Plugin Imageshop Media Library requires that cURL is compiled with OpenSSL. The plugin has now disabled itself.',
				'isml'
			)
		);
	}
}

$isml = ISML::get_instance();
$isml->start();
