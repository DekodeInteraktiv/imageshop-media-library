<?php
/**
 *
 */

declare(strict_types=1);

namespace Dekode\WordPress\Imageshop_Media_Library_V2;

if ( ! class_exists( 'ISML_Search' ) ) {
	/**
	 * Class ISML_Search
	 */
	class ISML_Search {

		private $imageshop;
		private static $instance;

		public function __construct() {
			$onboarding_completed = get_option( 'imageshop_onboarding_completed', true );

			if ( $onboarding_completed ) {
				$this->imageshop = ISML_REST_Controller::get_instance();

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
				$att           = ISML_Attachment::get_instance();
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

			header( 'X-WP-Total: ' . (int) $search_results->NumberOfDocuments );
			header( 'X-WP-TotalPages: ' . (int) $search_attributes['Pagesize'] );

			foreach ( $search_results->DocumentList as $result ) {
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
					'meta_value'     => $media->DocumentID,
					'post_type'      => 'attachment',
				)
			);

			if ( ! $wp_post ) {
				$a          = wp_check_filetype( $media->FileName )['type'];
				$wp_post_id = wp_insert_post(
					array(
						'post_type'      => 'attachment',
						'post_title'     => $media->Name,
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_date'      => date( 'Y-m-d H:i:s', strtotime( $media->Created ) ),
						'post_mime_type' => $a,
						'meta_input'     => array(
							'_imageshop_document_id' => $media->DocumentID,
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
				'filename'  => $media->FileName,
				'id'        => $wp_post_id,
				'meta'      => false,
				'date'      => $media->Created,
				'name'      => $media->Name,
				'sizes'     => array(
					'medium' => array(
						'url' => $media->DetailThumbUrl,
					),
				),
				'status'    => 'inherit',
				'title'     => $media->Name,
				'url'       => $media->ListThumbUrl,
				'menuOrder' => 0,
			);
		}
	}
}
