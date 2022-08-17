<?php
/**
 * WP_Cron handler for the Create Permalink event.
 */

namespace Imageshop\Cron;

use Imageshop\WordPress\Attachment;
use Imageshop\WordPress\REST_Controller;

/**
 * Create_Permalink class.
 */
class Create_Permalink {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		\add_action( 'imageshop_cron_create_permalink', array( $this, 'create_permalink' ), 10, 3 );
	}

	/**
	 * Create a permalink image size within Imageshop as a scheduled event.
	 *
	 * @param int $attachment_id The WordPress attachment ID for the image that needs a size created.
	 * @param int $width
	 * @param int $height
	 *
	 * @return void
	 */
	public function create_permalink( $attachment_id, $width, $height ) {
		$imageshop = REST_Controller::get_instance();
		$attachment = Attachment::get_instance();

		$document_id = \get_post_meta( $attachment_id, '_imageshop_document_id', true );

		$imageshop->get_permalink(
			$document_id,
			$width,
			$height,
			$attachment->get_attachment_permalink_token_base( $attachment_id )
		);
	}

}

new Create_Permalink();
