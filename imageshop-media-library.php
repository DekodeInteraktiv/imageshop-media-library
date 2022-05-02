<?php
/**
 * Plugin Name: Imageshop Media Library V2
 * Plugin URI:
 * Description: This WordPress plugin syncs your media library with Imageshop.
 * Version: 0.0.1
 * Author: Dekode
 * Author URI: https://dekode.no
 * License: MIT
 * Text Domain: imageshop
 * Domain Path: /languages
 * Requires PHP: 5.6
 * Requires at least: 5.0
 */

namespace Dekode\WordPress\Imageshop_Media_Library_V2;

defined( 'ABSPATH' ) || exit;

define( 'ISML_ABSPATH', __DIR__ );
define( 'ISML_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'ISML_PLUGIN_BASE_NAME', __FILE__ );

require_once ISML_ABSPATH . '/vendor/autoload.php';

require_once __DIR__ . '/includes/class-isml.php';
require_once __DIR__ . '/includes/class-isml-attachment.php';
require_once __DIR__ . '/includes/class-isml-helpers.php';
require_once __DIR__ . '/includes/class-isml-library.php';
require_once __DIR__ . '/includes/class-isml-onboarding.php';
require_once __DIR__ . '/includes/class-isml-rest-controller.php';
require_once __DIR__ . '/includes/class-isml-search.php';
require_once __DIR__ . '/includes/class-isml-sync.php';

function isml_incompatibile( $msg ) {
	require_once ABSPATH . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
	deactivate_plugins( __FILE__ );
	wp_die( $msg );
}

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
		isml_incompatibile(
			sprintf(
				__(
					'The Imageshop Media Library plugin requires PHP version 5.6 or higher. This site uses PHP version %s, which has caused the plugin to be automatically deactivated.',
					'imagesop'
				),
				PHP_VERSION
			)
		);
	}
}

$isml = ISML::get_instance();
$isml->start();
