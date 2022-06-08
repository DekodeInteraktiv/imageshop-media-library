<?php
/**
 * The library class.
 */

declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Class Library
 */
class Library {
	private static $instance;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( Imageshop::get_instance()->onboarding_completed() ) {
			\add_filter( 'get_user_option_media_library_mode', array( $this, 'force_grid_view' ) );
			\add_action( 'admin_init', array( $this, 'override_list_view_mode_url' ) );
			\add_action( 'admin_head', array( $this, 'hide_list_view_button' ) );

			\add_action( 'wp_enqueue_media', array( $this, 'add_custom_media_modal_filters' ) );
		}
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
	 * Register the scripts and styles needed for the Imageshop media library controls.
	 *
	 * @return void
	 */
	public function add_custom_media_modal_filters() {
		$imageshop = REST_Controller::get_instance();

		\wp_enqueue_script(
			'imageshop-media-library-filters',
			\plugins_url( '/assets/scripts/media-library-modal.js', IMAGESHOP_PLUGIN_BASE_NAME ),
			array(
				'media-editor',
				'media-views',
				'wp-api-fetch',
			)
		);

		\wp_localize_script(
			'imageshop-media-library-filters',
			'ImageshopMediaLibrary',
			array(
				'sources'           => array(
					array(
						'label' => __( 'Imageshop Media Library', 'imageshop' ),
						'value' => 'imageshop',
					),
					array(
						'label' => __( 'WordPress\'s Media Library', 'imageshop' ),
						'value' => 'wordpress',
					),
				),
				'interfaces'        => $imageshop->get_interfaces(),
				'default_interface' => (int) \get_option( 'imageshop_upload_interface' ),
				'categories'        => $imageshop->get_categories(),
			)
		);
		// Overrides code styling to accommodate for a third dropdown filter
		\add_action(
			'admin_footer',
			function() {
				?>
				<style>
					.media-modal-content .media-frame select.attachment-filters {
						max-width: -webkit-calc(33% - 12px);
						max-width: calc(33% - 12px);
					}
				</style>
				<?php
			}
		);
	}

	/**
	 * Custom wp-admin styles to disable the media list view.
	 *
	 * @return void
	 */
	public function hide_list_view_button() {
		?>
		<style>
			.wp-admin #imageshop-posts-per-page,
			.wp-admin.upload-php #media-attachment-filters,
			.wp-admin.upload-php #media-attachment-date-filters,
			.wp-admin.post-php #media-attachment-date-filters,
			.wp-admin.upload-php .view-switch .view-list,
			.wp-admin.upload-php .select-mode-toggle-button{
				display: none!important;
			}

			body.block-editor-page .media-frame select.attachment-filters:last-of-type {
				max-width: inherit;
			}
		</style>
		<?php
	}

	/**
	 * Modify direct attempts at using the media library in list view.
	 *
	 * @return void
	 */
	public function override_list_view_mode_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		if ( \stristr( $_SERVER['REQUEST_URI'], 'upload.php?mode=' ) ) {
			$_GET['mode'] = 'grid';
		}
	}

	/**
	 * Always set the media list in grid view.
	 *
	 * @return string
	 */
	public function force_grid_view() {
		return 'grid';
	}

}
