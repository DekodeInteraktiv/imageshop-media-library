<?php
/**
 * Search class.
 */

declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Class Search
 */
class Search {

	private $imageshop;
	private $attachment;
	private static $instance;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( Imageshop::get_instance()->onboarding_completed() ) {
			$this->imageshop  = REST_Controller::get_instance();
			$this->attachment = Attachment::get_instance();

			\add_action( 'wp_ajax_query-attachments', array( $this, 'search_media' ), 0 );
			\add_filter( 'rest_prepare_attachment', array( $this, 'rest_image_override' ), 10, 2 );
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
	 * Modify the attachment post object response.
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @param \WP_Post          $post     The original attachment post.
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
	 * Filter out unneccesary Imageshop data when doing a direct WordPress library search.
	 *
	 * @param \WP_Query $query The query being performed.
	 * @return void
	 */
	public function skip_imageshop_items( $query ) {
		$meta = $query->get( 'meta_query' );

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$meta[] = array(
			'key'     => '_wp_attached_file',
			'compare' => 'EXISTS',
		);

		$query->set( 'meta_query', $meta );
	}

	/**
	 * Override WordPress normal media search with the Imageshop search behavior.
	 */
	public function search_media() {
		$media = array();

		// If Imageshop isn't the search origin, return early and let something else handle the process.
		if ( isset( $_POST['query']['imageshop_origin'] ) && 'imageshop' !== $_POST['query']['imageshop_origin'] ) {
			\add_action( 'pre_get_posts', array( $this, 'skip_imageshop_items' ) );
			return;
		}

		// Querying for posts specifically attached ot a page does nothing, so do not process them.
		if ( isset( $_POST['query']['post__in'] ) ) {
			\wp_send_json_success( array() );
			\wp_die();
		}

		$search_attributes = array(
			'Pagesize' => 10,
		);

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
			// The default value is too heavy at this time, so discard and rely on the next request.
			if ( 80 === (int) $_POST['query']['posts_per_page'] ) {
				\wp_send_json_success( array() );
				\wp_die();
			}
			$search_attributes['Pagesize'] = $_POST['query']['posts_per_page'];
		}
		if ( isset( $_POST['query']['imageshop_interface'] ) && ! empty( $_POST['query']['imageshop_interface'] ) ) {
			$search_attributes['InterfaceIds'] = array( \absint( $_POST['query']['imageshop_interface'] ) );
		}
		if ( isset( $_POST['query']['imageshop_category'] ) && ! empty( $_POST['query']['imageshop_category'] ) ) {
			$search_attributes['CategoryIds'] = array( \absint( $_POST['query']['imageshop_category'] ) );
		}

		$search_results = $this->imageshop->search( $search_attributes );

		\header( 'X-WP-Total: ' . (int) $search_results->NumberOfDocuments ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$search_Results->NumberOfDocuments` is provided by the SaaS API.
		\header( 'X-WP-TotalPages: ' . (int) ceil( ( $search_results->NumberOfDocuments / $search_attributes['Pagesize'] ) ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$search_results->NumberOfDocuments` and `$search_attributes['Pagesize']` are provided by the SaaS API.

		foreach ( $search_results->DocumentList as $result ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$search_results->DocumentList` is provided by the SaaS API.
			$media[] = $this->imageshop_pseudo_post( $result, $search_attributes['InterfaceIds'][0] );
		}

		\wp_send_json_success( $media );

		\wp_die();
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
	private function imageshop_pseudo_post( $media, $interface = null ) {
		if ( null === $interface ) {
			$interface = \get_option( 'imageshop_upload_interface' );
		}

		$wp_post = \get_posts(
			array(
				'posts_per_page' => 1,
				'meta_key'       => '_imageshop_document_id',
				'meta_value'     => $media->DocumentID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is provided by the SaaS API.
				'post_type'      => 'attachment',
			)
		);

		if ( ! $wp_post ) {
			$a          = \wp_check_filetype( $media->FileName )['type']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->FileName` is provided by the SaaS API.
			$wp_post_id = \wp_insert_post(
				array(
					'post_type'      => 'attachment',
					'post_title'     => $media->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Name` is provided by the SaaS API.
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_date_gmt'  => \gmdate( 'Y-m-d H:i:s', \strtotime( $media->Created ) ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Created` is provided by the SaaS API.
					'post_mime_type' => $a,
					'meta_input'     => array(
						'_imageshop_document_id' => $media->DocumentID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is provided by the SaaS API.
					),
				)
			);
		} else {
			if ( \is_array( $wp_post ) ) {
				$wp_post_id = $wp_post[0]->ID;
			} else {
				$wp_post_id = $wp_post->ID;
			}
		}

		$caption = $media->Description; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Description` is provided by the SaaS API.

		if ( ! empty( $media->Credits ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Credits` is provided by the SaaS API.
			if ( ! empty( $caption ) ) {
				$caption = \sprintf(
					'%s (%s)',
					$caption,
					$media->Credits // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Credits` is provided by the SaaS API.
				);
			} else {
				$caption = $media->Credits; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Credits` is provided by the SaaS API.
			}
		}

		$original_media = null;

		foreach ( $media->SubDocumentList as $sub ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->SubDocumentList` is provided by the SaaS API.
			// For some reason, `IsOriginal` may sometimes be `0`, even on an original image.
			if ( 'Original' === $sub->VersionName && null === $original_media ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$sub->VersionName` is provided by the SaaS API.
				$original_media = $sub;
			}

			// An actually declared original should always take priority.
			if ( 1 === $sub->IsOriginal ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$sub->IsOriginal` is provided by the SaaS API.
				$original_media = $sub;
				break;
			}
		}

		if ( null === $original_media ) {
			$original_media = (object) array(
				'Width'         => 0,
				'Height'        => 0,
				'FileSize'      => 0,
				'FileExtension' => 'jpg',
			);
		} elseif ( $original_media && ( 0 === $original_media->Width || 0 === $original_media->Height ) && count( $media->InterfaceList ) >= 1 ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Width`, `$original_media->Height`, and `$media->InterfaceList` are provided by the SaaS API.
			$dimensions = $this->attachment->get_original_dimensions( $interface, $original_media );

			$original_media->Width  = $dimensions['width']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Width` is provided by the SaaS API.
			$original_media->Height = $dimensions['height']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Height` is provided by the SaaS API.
		}

		$full_size_url = $this->attachment->get_permalink_for_size( $media->DocumentID, $media->FileName, $original_media->Width, $original_media->Height, false ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID`, `$media->FileName`, `$original_media->Width`, and `$oreiginal_media->Height` are provided by the SaaS API.
		$medium_url    = $this->attachment->get_permalink_for_size_slug( $media->DocumentID, $media->FileName, 'medium' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` and `$media->FileName` are provided by the SaaS API.
		$thumb_url     = $this->attachment->get_permalink_for_size_slug( $media->DocumentID, $media->FileName, 'thumbnail' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` and `$media->FileName` are provided by the SaaS API.

		$medium_dimension = $this->attachment->get_image_dimensions( 'medium' );
		$thumb_dimension  = $this->attachment->get_image_dimensions( 'thumbnail' );

		return (object) array(
			'filename'              => $media->FileName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->FileName` is provided by the SaaS API.
			'id'                    => $wp_post_id,
			'meta'                  => false,
			'date'                  => strtotime( $media->Created ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Created` is provided by the SaaS API.
			'dateFormatted'         => $media->Created, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Created` is provided by the SaaS API.
			'name'                  => $media->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Name` is provided by the SaaS API.
			'sizes'                 => array(
				'full'      => array(
					'url'         => $full_size_url['source_url'],
					'width'       => $original_media->Width, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Width` is provided by the SaaS API.
					'height'      => $original_media->Height, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Height` is provided by the SaaS API.
					'orientation' => ( $original_media->Height > $original_media->Width ? 'portrait' : 'landscape' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Height` and `$original_media->Width` are provided by the SaaS API.
				),
				'medium'    => array(
					'url'         => $medium_url['source_url'],
					'width'       => $medium_dimension['width'],
					'height'      => $medium_dimension['height'],
					'orientation' => $medium_dimension['orientation'],
				),
				'thumbnail' => array(
					'url'         => $thumb_url['source_url'],
					'width'       => $thumb_dimension['width'],
					'height'      => $thumb_dimension['height'],
					'orientation' => $thumb_dimension['orientation'],
				),
			),
			'status'                => 'inherit',
			'title'                 => $media->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Name` is provided by the SaaS API.
			'url'                   => ( $full_size_url ? $full_size_url['source_url'] : $media->ListThumbUrl ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->ListThumbUrl` is provided by the SaaS API.
			'menuOrder'             => 0,
			'alt'                   => $media->Name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Name` is provided by the SaaS API.
			'description'           => $media->Description, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->Description` is provided by the SaaS API.
			'caption'               => $caption,
			'height'                => $original_media->Height, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Height` is provided by the SaaS API.
			'width'                 => $original_media->Width, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Width` is provided by the SaaS API.
			'filesizeInBytes'       => $original_media->FileSize, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->FileSize` is provided by the SaaS API.
			'filesizeHumanReadable' => \size_format( $original_media->FileSize ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->FileSize` is provided by the SaaS API.
			'orientation'           => ( $original_media->Height > $original_media->Width ? 'portrait' : 'landscape' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_media->Height` and `$original_media->Width` are provided by the SaaS API.
			'type'                  => ( $media->IsImage ? 'image' : ( $media->IsVideo ? 'video' : 'document' ) ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->IsImage` and `$media->IsVideo` are provided by the SaaS API.
		);
	}
}
