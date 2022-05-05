<?php
/**
 *
 */

declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Class ISML_Search
 */
class Search {

	private $imageshop;
	private static $instance;

	public function __construct() {
		$onboarding_completed = get_option( 'imageshop_onboarding_completed', true );

		if ( $onboarding_completed ) {
			$this->imageshop = REST_Controller::get_instance();

			add_action( 'wp_ajax_query-attachments', array( $this, 'search_media' ), 0 );
			add_filter( 'rest_prepare_attachment', array( $this, 'rest_image_override' ), 10, 2 );
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

	/**
	 *
	 * @param $response
	 * @param $post
	 *
	 * @return mixed
	 */
	public function rest_image_override( $response, $post ) {
		if ( 'attachment' !== $post->post_type ) {
			return $response;
		}
		if ( ! $post->_imageshop_document_id ) {
			return $response;
		}

		$media_details = $post->_imageshop_media_sizes;

		if ( empty( $media_details ) ) {
			$att           = Attachment::get_instance();
			$media_details = $att->generate_imageshop_metadata( $post );
		}

		$response->data['media_details'] = $media_details;

		return $response;
	}

	/**
	 *
	 */
	public function search_media() {
		$media = array();

		$search_attributes = array();

		if ( isset( $_POST['query']['s'] ) ) {
			$search_attributes['Querystring'] = $_POST['query']['s'];
		}
		if ( isset( $_POST['query']['order'] ) ) {
			$search_attributes['SortDirection'] = $_POST['query']['order'];
		}
		if ( isset( $_POST['query']['paged'] ) ) {
			// Subtract one, as Imageshop starts with page 0.
			$search_attributes['Page'] = ( $_POST['query']['paged'] - 1 );
		}
		if ( isset( $_POST['query']['posts_per_page'] ) ) {
			$search_attributes['Pagesize'] = $_POST['query']['posts_per_page'];
			//              $search_attributes['Pagesize'] = 80;
		}

		$search_results = $this->imageshop->search( $search_attributes );

		header( 'X-WP-Total: ' . (int) $search_results->NumberOfDocuments ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$search_Results->NumberOfDocuments` is provided by the SaaS API.
		header( 'X-WP-TotalPages: ' . (int) $search_attributes['Pagesize'] );

		foreach ( $search_results->DocumentList as $result ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$search_results->DocumentList` is provided by the SaaS API.
			$media[] = $this->imageshop_pseudo_post( $result );
		}

		wp_send_json_success( $media );

		wp_die();
	}

	/**
	 * Creates a pseudo-object mirroring what is needed from WP_Post.
	 *
	 * The media searches are returning complete WP_Post objects, so we need to provide the expected data
	 * via our own means to ensure that media searches show up as expected, but with data from the
	 * Imageshop source library instead.
	 *
	 * @param object $media
	 *
	 * @return object
	 */
	private function imageshop_pseudo_post( $media ) {
		$wp_post = get_posts(
			array(
				'posts_per_page' => 1,
				'meta_key'       => '_imageshop_document_id',
				'meta_value'     => $media->DocumentID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is provided by the SaaS API.
				'post_type'      => 'attachment',
			)
		);

		if ( ! $wp_post ) {
			$a          = wp_check_filetype( $media->FileName )['type']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->FileName` is provided by the SaaS API.
			$wp_post_id = wp_insert_post(
				array(
					'post_type'      => 'attachment',
					'post_title'     => $media->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Name` is provided by the SaaS API.
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', strtotime( $media->Created ) ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Created` is provided by the SaaS API.
					'post_mime_type' => $a,
					'meta_input'     => array(
						'_imageshop_document_id' => $media->DocumentID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is provided by the SaaS API.
					),
				)
			);
		} else {
			if ( is_array( $wp_post ) ) {
				$wp_post_id = $wp_post[0]->ID;
			} else {
				$wp_post_id = $wp_post->ID;
			}
		}

		return (object) array(
			'filename'  => $media->FileName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->FileName` is provided by the SaaS API.
			'id'        => $wp_post_id,
			'meta'      => false,
			'date'      => $media->Created, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Created` is provided by the SaaS API.
			'name'      => $media->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Name` is provided by the SaaS API.
			'sizes'     => array(
				'medium' => array(
					'url' => $media->DetailThumbUrl, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DetailThumbUrl` is provided by the SaaS API.
				),
			),
			'status'    => 'inherit',
			'title'     => $media->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Name` is provided by the SaaS API.
			'url'       => $media->ListThumbUrl, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->ListThumbUrl` is provided by the SaaS API.
			'menuOrder' => 0,
		);
	}
}
