<?php
/**
 *
 */

declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Class ISML_Library
 */
class Library {
	private static $instance;

	public function __construct() {
		add_filter( 'get_user_option_media_library_mode', array( $this, 'force_grid_view' ) );
		add_action( 'admin_init', array( $this, 'override_list_view_mode_url' ) );
		add_action( 'admin_head', array( $this, 'hide_list_view_button' ) );
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

	public function hide_list_view_button() {
		?>
		<style>
			.wp-admin.upload-php .view-switch .view-list,
			.wp-admin.upload-php .select-mode-toggle-button{
				display: none!important;
			}
		</style>
		<?php
	}

	public function override_list_view_mode_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		if ( stristr( $_SERVER['REQUEST_URI'], 'upload.php?mode=' ) ) {
			$_GET['mode'] = 'grid';
		}
	}

	public function force_grid_view() {
		return 'grid';
	}

}
