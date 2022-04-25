<?php
/**
 *
 */

declare(strict_types=1);

namespace Dekode\WordPress\Imageshop_Media_Library_V2;

if (!class_exists('ISML_Library')) {

	/**
	 * Class ISML_Library
	 */
	class ISML_Library {
		private static $instance;

		public function __construct() {
			add_filter('get_user_option_media_library_mode', [$this, 'force_grid_view']);
			add_action('admin_init', [$this, 'override_list_view_mode_url']);
			add_action('admin_head', [$this, 'hide_list_view_button']);
		}

		/**
		 *
		 * @return self
		 */
		public static function get_instance() {
			if (!self::$instance) {
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
			if (!isset($_SERVER['REQUEST_URI'])) {
				return;
			}

			if (stristr($_SERVER['REQUEST_URI'], 'upload.php?mode=')) {
				$_GET['mode'] = 'grid';
			}
		}

		public function force_grid_view() {
			return 'grid';
		}

	}
}

