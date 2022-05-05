<?php
/**
 * Plugin Name: Imageshop Media Library
 * Plugin URI:
 * Description: Use the Imageshop media library as your companys one source for all media.
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

// Validate that the plugin is compatible when being activated.
register_activation_hook( __FILE__, function() {
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			isml_incompatibile(
				sprintf(
					// translators: %s is the PHP version.
					__(
						'The Imageshop Media Library plugin requires PHP version 5.6 or higher. This site uses PHP version %s, which has caused the plugin to be automatically deactivated.',
						'imagesop'
					),
					PHP_VERSION
				)
			);
		}
	}
} );

// Clean up the database when the plugin is deactivated.
register_deactivation_hook( __FILE__, function() {
	global $wpdb;

	$attachments = $wpdb->get_results( "
		SELECT
	       p.ID
		FROM
			{$wpdb->posts} AS p
	    LEFT JOIN
		    {$wpdb->postmeta} AS pm
		        ON (p.ID = pm.post_id)
		WHERE
			p.post_type = 'attachment'
		AND
		(
			pm.meta_key = '_imageshop_document_id'
			AND
			(
			    pm.meta_value IS NOT NULL
			        OR
			    pm.meta_value != ''
		    )
		)
		AND
	    	NOT EXISTS (
	    	    SELECT
					1
				FROM
				     {$wpdb->postmeta} as spm
	    	    WHERE
					spm.post_id = p.ID
				AND
			    	spm.meta_key = '_wp_attached_file'
			    AND
				(
				    spm.meta_value IS NOT NULL
				        AND
				    spm.meta_value != ''
				)
			)
	    " );

	if ( empty( $attachments ) ) {
		return;
	}

	$removable = array();

	foreach ( $attachments as $attachment ) {
		$removable[] = $attachment->ID;
	}

	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN (" . implode( ',', $removable ) . ")" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN (" . implode( ',', $removable ) . ")" );
} );

$isml = ISML::get_instance();
$isml->start();
