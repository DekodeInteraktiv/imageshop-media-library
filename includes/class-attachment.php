<?php
/**
 *
 */

declare(strict_types=1);

namespace Imageshop\WordPress;

/**
 * Class Attachment
 */
class Attachment {
	private static $instance;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'wp_get_attachment_image_src', array( $this, 'attachment_image_src' ), 10, 3 );
		add_action( 'add_attachment', array( $this, 'export_to_imageshop' ), 10, 1 );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'filter_wp_generate_attachment_metadata' ), 20, 2 );
		add_filter( 'media_send_to_editor', array( $this, 'media_send_to_editor' ), 10, 2 );
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
	 * Export an attachment to Imageshop.
	 *
	 * @param int $post_id The attachment ID to export to Imageshop.
	 *
	 * @return false|mixed
	 */
	public function export_to_imageshop( $post_id ) {
		if ( true === wp_attachment_is_image( $post_id )
			&& ! boolval( get_post_meta( $post_id, '_imageshop_document_id', true ) ) ) {
			$rest_controller = REST_Controller::get_instance();
			try {
				$file = get_attached_file( $post_id );
				if ( is_readable( $file ) ) {
					// create file in storage
					$meta = get_post_meta( $post_id, '_wp_attached_file', true );
					$ret  = $rest_controller->create_document(
						base64_encode( file_get_contents( $file ) ),
						$meta
					);
					update_post_meta( $post_id, '_imageshop_document_id', $ret->docId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$ret->docId` is defined by the SaaS API.

					return $post_id;
				}

				return $post_id;
			} catch ( \Exception $e ) {
				return false;
			}
		}

		return $post_id;
	}

	/**
	 * Helper function to return image sizes registered in WordPress.
	 *
	 * @return array
	 */
	public static function get_wp_image_sizes() {
		$image_sizes = array();

		$size_data        = wp_get_additional_image_sizes();
		$registered_sizes = get_intermediate_image_sizes();

		foreach ( $registered_sizes as $size ) {
			// If the size data is empty, this is likely a core size, so look them up via the database.
			if ( ! isset( $size_data[ $size ] ) ) {
				$size_data[ $size ] = array(
					'width'  => (int) get_option( $size . '_size_w' ),
					'height' => (int) get_option( $size . '_size_h' ),
					'crop'   => (bool) get_option( $size . '_crop' ),
				);
			}

			$image_sizes[ $size ] = array(
				'width'  => $size_data[ $size ]['width'],
				'height' => $size_data[ $size ]['height'],
				'crop'   => $size_data[ $size ]['crop'],
			);
		}
		if ( isset( $image_sizes['post-thumbnail'] ) ) {
			unset( $image_sizes['post-thumbnail'] );
		}
		return $image_sizes;
	}

	/**
	 * Filter the image source attributes to replace with Imageshop resources.
	 *
	 * @param array|false  $image         Array of image data, or boolean false if no image is available.
	 * @param int          $attachment_id Image attachment ID.
	 * @param string|int[] $size          Requested image size. Can be any registered image size name, or
	 *                                    an array of width and height values in pixels (in that order).
	 * @return array|false
	 */
	public function attachment_image_src( $image, $attachment_id, $size ) {
		$media_details = get_post_meta( $attachment_id, '_imageshop_media_sizes', true );
		$document_id   = get_post_meta( $attachment_id, '_imageshop_document_id', true );

		if ( empty( $media_details ) && $document_id ) {
			$att           = Attachment::get_instance();
			$media_details = $att->generate_imageshop_metadata( get_post( $attachment_id ) );
		}

		if ( 'full' === $size ) {
			$size = 'original';
		}
		if ( is_array( $size ) ) {

			$candidates = array();

			if ( ! isset( $media_details['file'] ) && isset( $media_details['sizes']['original'] ) ) {
				$media_details['height'] = $media_details['sizes']['original']['height'];
				$media_details['width']  = $media_details['sizes']['original']['width'];
			}

			foreach ( $media_details['sizes'] as $_size => $data ) {
				// If there's an exact match to an existing image size, short circuit.
				if ( (int) $data['width'] === (int) $size[0] && (int) $data['height'] === (int) $size[1] ) {
					$candidates[ $data['width'] * $data['height'] ] = $data;
					break;
				}

				// If it's not an exact match, consider larger sizes with the same aspect ratio.
				if ( $data['width'] >= $size[0] && $data['height'] >= $size[1] ) {
					// If '0' is passed to either size, we test ratios against the original file.
					if ( 0 === $size[0] || 0 === $size[1] ) {
						$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $media_details['width'], $media_details['height'] );
					} else {
						$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $size[0], $size[1] );
					}

					if ( $same_ratio ) {
						$candidates[ $data['width'] * $data['height'] ] = $data;
					}
				}
			}

			if ( ! empty( $candidates ) ) {
				// Sort the array by size if we have more than one candidate.
				if ( 1 < count( $candidates ) ) {
					ksort( $candidates );
				}

				$data = array_shift( $candidates );
				/*
				* When the size requested is smaller than the thumbnail dimensions, we
				* fall back to the thumbnail size to maintain backward compatibility with
				* pre 4.6 versions of WordPress.
				*/
			} elseif ( ! empty( $media_details['sizes']['thumbnail'] ) && $media_details['sizes']['thumbnail']['width'] >= $size[0] && $media_details['sizes']['thumbnail']['width'] >= $size[1] ) {
				$data = $media_details['sizes']['thumbnail'];
			} else {
				$data = $media_details['sizes']['original'];

				//					return false;
			}
		} elseif ( ! empty( $media_details['sizes'][ $size ] ) ) {
			$data = $media_details['sizes'][ $size ];
		} elseif ( isset( $media_details['sizes']['original'] ) ) {
			$data = $media_details['sizes']['original'];

		}
		// If we still don't have a match at this point, return false.
		if ( empty( $data ) ) {
			return false;
		}

		return array_merge(
			array(
				0 => $data['source_url'],
				1 => $data['width'],
				2 => $data['height'],
				3 => ( 'original' === $size ? false : true ),
			),
			$data
		);
	}

	/**
	 * Filter the metadata for an attachment after upload.
	 *
	 * @param array $metadata      An array of attachment meta data.
	 * @param int   $attachment_id Current attachment ID.
	 *
	 * @return mixed
	 */
	public function filter_wp_generate_attachment_metadata( $metadata, $attachment_id ) {
		if ( false === wp_attachment_is_image( $attachment_id ) ) {
			return $metadata;
		}

		$paths      = array();
		$upload_dir = wp_upload_dir();

		// collect original file path
		if ( isset( $metadata['file'] ) ) {
			$path          = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file'];
			$paths['full'] = $path;

			// set basepath for other sizes
			$file_info = pathinfo( $path );
			$basepath  = isset( $file_info['extension'] )
				? str_replace( $file_info['filename'] . '.' . $file_info['extension'], '', $path )
				: $path;
		}

		// collect size files path
		if ( isset( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $key => $size ) {
				if ( isset( $size['file'] ) ) {
					$paths[ $key ] = $basepath . $size['file'];
				}
			}
		}

		// process paths.
		foreach ( $paths as $key => $filepath ) {
			// remove physical file.
			if (
				! empty( $metadata['sizes'][ $key ]['imageshop_permalink'] )
				|| ( 'full' === $key ) && ! empty( $metadata['imageshop_permalink'] )
			) {
				unlink( $filepath );
			}
		}

		return $metadata;
	}

	/**
	 * Generate WordPress-equivalent metadata for a pseudo-attachment post.
	 *
	 * @param \WP_Post $post The attachment post object.
	 *
	 * @return array|array[]
	 */
	public function generate_imageshop_metadata( $post ) {
		$imageshop     = REST_Controller::get_instance();
		$media_details = array(
			'sizes' => array(),
		);

		$image_sizes = Attachment::get_wp_image_sizes();

		$media = $imageshop->get_document( $post->_imageshop_document_id );

		$original_image = array();
		foreach ( $media->SubDocumentList as $document ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->SubDocumentList` is defined by the SaaS API.
			if ( 'Original' === $document->VersionName ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$document->VersionName` is defined by the SaaS API.
				$original_image = $document;
				break;
			}
		}

		foreach ( $image_sizes as $slug => $size ) {
			$image_width  = $size['width'];
			$image_height = $size['height'];

			// If no original image to calculate crops of exist, skip this size.
			if ( empty( $original_image ) && ( 0 === $image_width || 0 === $image_height ) ) {
				continue;
			}

			if ( 0 === $image_width ) {
				$image_width = (int) floor( ( $image_height / $original_image->Height ) * $original_image->Width ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are defined by the SaaS API.
			}
			if ( 0 === $image_height ) {
				$image_height = (int) floor( ( $image_width / $original_image->Width ) * $original_image->Height ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are defined by the SaaS API.
			}

			if ( $size['crop'] ) {
				if ( $image_width > $original_image->Width ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
					$image_width = $original_image->Width; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
				}
			}

			$url = $imageshop->get_permalink(
				$media->DocumentID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is defined by the SaaS API.
				$image_width,
				$image_height
			);

			$media_details['sizes'][ $slug ] = array(
				'height'     => $image_height,
				'width'      => $image_width,
				'source_url' => $url,
				'file'       => $post->post_title,
			);

			if ( ! isset( $media_details['size']['original'] ) ) {
				$url = $imageshop->get_permalink(
					$media->DocumentID, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is defined by the SaaS API.
					$original_image->Width, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
					$original_image->Height // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Height` is defined by the SaaS API.
				);

				$media_details['sizes']['original'] = array(
					'height'     => $original_image->Height, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Height` is defined by the SaaS API.
					'width'      => $original_image->Width, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
					'file'       => $post->post_title,
					'source_url' => $url,
				);
			}
		}
		update_post_meta( $post->ID, '_imageshop_media_sizes', $media_details );
		return $media_details;
	}

	/**
	 * Filter the media HTML markup sent ot the editor.
	 *
	 * @param string $html HTML markup for a media item sent to the editor.
	 * @param int    $id   The first key from the $_POST['send'] data.
	 *
	 * @return string
	 */
	public function media_send_to_editor( $html, $id ) {
		$media_details = get_post_meta( $id, '_imageshop_media_sizes', true );
		$document_id   = get_post_meta( $id, '_imageshop_document_id', true );

		if ( empty( $media_details ) && $document_id ) {
			$att           = Attachment::get_instance();
			$media_details = $att->generate_imageshop_metadata( get_post( $id ) );

		}
		if ( isset( $media_details['sizes']['original'] ) ) {
			$data = $media_details['sizes']['original'];
			$html = '<img src="' . $data['source_url'] . '" alt="" width="' . $data['width'] . '" height="' . $data['height'] . '" class="alignnone size-medium wp-image-3512" />';
		}

		return $html;

	}
}
