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

	private $documents = array();

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( Imageshop::get_instance()->onboarding_completed() ) {
			\add_filter( 'wp_get_attachment_image_src', array( $this, 'attachment_image_src' ), 10, 3 );
			\add_filter( 'wp_get_attachment_url', array( $this, 'attachment_url' ), 10, 2 );
			\add_action( 'add_attachment', array( $this, 'export_to_imageshop' ), 10, 1 );
			\add_filter( 'wp_generate_attachment_metadata', array( $this, 'filter_wp_generate_attachment_metadata' ), 20, 2 );
			\add_filter( 'media_send_to_editor', array( $this, 'media_send_to_editor' ), 10, 2 );
			\add_filter( 'wp_get_attachment_image_attributes', array( $this, 'validate_post_thumbnail_srcset' ), 10, 3 );
			\add_filter( 'wp_content_img_tag', array( $this, 'validate_post_content_image_srcset' ), 10, 3 );
		}
	}

	/**
	 * Validate that post-content images being output have been given the appropriate srcset data when possible.
	 *
	 * @param string $filtered_image The HTML markup for the `img` tag.
	 * @param string $context        The context where the filter was triggered from.
	 * @param int    $attachment_id  Attachment post ID.
	 *
	 * @return string The HTML markup with `srcset` and `sizes` attributes added if applicable.
	 */
	public function validate_post_content_image_srcset( $filtered_image, $context, $attachment_id ) {
		$dimensions = array();

		// Extract the image size slug.
		preg_match( '/class=".+?size-(\S*)/si', $filtered_image, $size_slug );
		$size_slug = ( isset( $size_slug[1] ) ? $size_slug[1] : 'full' );

		// Generate a fallback max width for images with no size defined.
		preg_match( '/src=".+?-([0-9]+?)x([0-9]+?)\/?"/si', $filtered_image, $src_dimensions );
		if ( 3 === count( $src_dimensions ) ) {
			$dimensions = array(
				'width'  => $src_dimensions[1],
				'height' => $src_dimensions[2],
			);
		}

		$srcset_meta = $this->generate_attachment_srcset( $attachment_id, $size_slug );

		if ( empty( $srcset_meta ) ) {
			return $filtered_image;
		}

		if ( ! empty( $dimensions ) && $srcset_meta['widest'] > $dimensions['width'] ) {
			$srcset_meta['widest'] = $dimensions['width'];
		}

		if ( ! stristr( $filtered_image, 'srcset=' ) ) {
			$filtered_image = str_replace(
				'src=',
				sprintf(
					'srcset="%s" src=',
					implode( ', ', $srcset_meta['entries'] )
				),
				$filtered_image
			);
		}

		if ( ! stristr( $filtered_image, 'sizes=' ) ) {
			$filtered_image = str_replace(
				'src=',
				sprintf(
					'sizes="%s" src=',
					sprintf(
						'(max-width: %1$dpx) 100vw, %1$dpx',
						$srcset_meta['widest']
					)
				),
				$filtered_image
			);
		}

		return $filtered_image;
	}

	/**
	 * Validate that featured images being output have been given the appropriate srcset data when possible.
	 *
	 * @param array    $attr       An array of all attributes for this attachment item.
	 * @param \WP_Post $attachment The attachment post object.
	 * @param string   $size       The chosen size for the attachment.
	 *
	 * @return array
	 */
	public function validate_post_thumbnail_srcset( $attr, $attachment, $size ) {
		if ( ! isset( $attr['srcset'] ) ) {
			$srcset_meta = $this->generate_attachment_srcset( $attachment->ID, $size );

			if ( ! empty( $srcset_meta ) ) {
				$attr['srcset'] = implode( ', ', $srcset_meta['entries'] );

				if ( ! isset( $attr['sizes'] ) || empty( $attr['sizes'] ) ) {
					$attr['sizes'] = sprintf(
						'(max-width: %1$dpx) 100vw, %1$dpx',
						$srcset_meta['widest']
					);
				}
			}
		}

		return $attr;
	}

	/**
	 * Generate the `srcset` and related attribute data for an `img` tag.
	 *
	 * @param int    $attachment_id Attachment post ID.
	 * @param string $size          The size slug for the size requested.
	 *
	 * @return array
	 */
	private function generate_attachment_srcset( $attachment_id, $size ) {
		$srcset_meta = array(
			'entries' => array(),
			'widest'  => 0,
		);

		$size_array = array();

		$media_details = \get_post_meta( $attachment_id, '_imageshop_media_sizes', true );
		$document_id   = \get_post_meta( $attachment_id, '_imageshop_document_id', true );

		// If this isn't an Imageshop item, break out early.
		if ( empty( $document_id ) ) {
			return array();
		}

		if ( isset( $media_details['sizes'][ $size ] ) ) {
			$size_array = array(
				$media_details['sizes'][ $size ]['width'],
				$media_details['sizes'][ $size ]['height'],
			);
		} elseif ( isset( $media_details['sizes']['original'] ) ) {
			// The `original` size is, if it exists, a fallback size so that images can still load.
			$size_array = array(
				$media_details['sizes']['original']['width'],
				$media_details['sizes']['original']['height'],
			);
		}

		$max_srcset_image_width = apply_filters( 'max_srcset_image_width', 2048, $size_array );
		$srcset_meta['widest']  = 0;

		// Bail early if there's no further data to iterate over.
		if ( ! isset( $media_details['sizes'] ) || empty( $media_details['sizes'] ) ) {
			return $srcset_meta;
		}

		foreach ( $media_details['sizes'] as $size => $data ) {
			if ( $data['width'] > $max_srcset_image_width ) {
				continue;
			}

			if ( empty( $data['source_url'] ) ) {
				$new_source         = $this->get_permalink_for_size( $document_id, $data['file'], $data['width'], $data['height'], false );
				$data['source_url'] = $new_source['source_url'];
			}

			// If the source URL is still not found, then Imageshop was unable to create the file, and we should skip it.
			if ( empty( $data['source_url'] ) ) {
				continue;
			}

			$srcset_meta['entries'][] = sprintf(
				'%s %dw',
				$data['source_url'],
				$data['width']
			);

			if ( $data['width'] > $srcset_meta['widest'] ) {
				$srcset_meta['widest'] = $data['width'];
			}
		}

		return $srcset_meta;
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
	 * Create a unique permalink token for this attachment
	 *
	 * @param int $attachment_id The WordPress attachment ID for the image.
	 *
	 * @return string
	 */
	public function get_attachment_permalink_token_base( $attachment_id ) {
		$token = \get_post_meta( $attachment_id, '_imageshop_permalink_token', true );

		if ( empty( $token ) ) {
			$domain = \get_site_url();

			// Strip off domain prefixes.
			$domain = str_ireplace( array( 'http://', 'https://', 'www.' ), '', $domain );

			// Remove TLD, we only care about the domain name.
			$domain = explode( '.', $domain, 2 );
			$domain = $domain[0];

			// In the case of internal links (or testing), strip port numbers as well.
			$domain = explode( ':', $domain, 2 );
			$domain = $domain[0];

			$token = sprintf(
				'%s-%d-%s',
				$domain,
				$attachment_id,
				md5(
					sprintf(
						'%s-%s',
						gmdate( \get_the_date( 'Y-m-d H:i:s', $attachment_id ) ),
						\get_the_title( $attachment_id )
					)
				)
			);

			\update_post_meta( $attachment_id, '_imageshop_permalink_token', $token );
		}

		return $token;
	}

	/**
	 * Export an attachment to Imageshop.
	 *
	 * @param int  $post_id The attachment ID to export to Imageshop.
	 * @param bool $force   Allow the forceful re-export of an attachment.
	 *
	 * @return false|mixed
	 */
	public function export_to_imageshop( $post_id, $force = false ) {
		if ( true === \wp_attachment_is_image( $post_id )
			&& ( ! \boolval( \get_post_meta( $post_id, '_imageshop_document_id', true ) ) || true === $force ) ) {
			$rest_controller = REST_Controller::get_instance();
			try {
				$file = \get_attached_file( $post_id );
				if ( \is_readable( $file ) ) {
					// create file in storage
					$meta = \get_post_meta( $post_id, '_wp_attached_file', true );
					$ret  = $rest_controller->create_document(
						\base64_encode( \file_get_contents( $file ) ),
						$meta
					);
					\update_post_meta( $post_id, '_imageshop_document_id', $ret->docId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$ret->docId` is defined by the SaaS API.

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

		$size_data        = \wp_get_additional_image_sizes();
		$registered_sizes = \get_intermediate_image_sizes();

		foreach ( $registered_sizes as $size ) {
			// If the size data is empty, this is likely a core size, so look them up via the database.
			if ( ! isset( $size_data[ $size ] ) ) {
				$size_data[ $size ] = array(
					'width'  => (int) \get_option( $size . '_size_w' ),
					'height' => (int) \get_option( $size . '_size_h' ),
					'crop'   => (bool) \get_option( $size . '_crop' ),
				);
			}

			$image_sizes[ $size ] = array(
				'width'  => $size_data[ $size ]['width'],
				'height' => $size_data[ $size ]['height'],
				'crop'   => $size_data[ $size ]['crop'],
			);
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
		$media_details = \get_post_meta( $attachment_id, '_imageshop_media_sizes', true );
		$document_id   = \get_post_meta( $attachment_id, '_imageshop_document_id', true );

		// Don't override if there is no Imageshop ID association.
		if ( ! $document_id ) {
			return $image;
		}

		if ( empty( $media_details ) && $document_id ) {
			$att           = Attachment::get_instance();
			$media_details = $att->generate_imageshop_metadata( \get_post( $attachment_id ) );
		}

		if ( 'full' === $size ) {
			$size = 'original';
		}
		if ( \is_array( $size ) ) {

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
						$same_ratio = \wp_image_matches_ratio( $data['width'], $data['height'], $media_details['width'], $media_details['height'] );
					} else {
						$same_ratio = \wp_image_matches_ratio( $data['width'], $data['height'], $size[0], $size[1] );
					}

					if ( $same_ratio ) {
						$candidates[ $data['width'] * $data['height'] ] = $data;
					}
				}
			}

			if ( ! empty( $candidates ) ) {
				// Sort the array by size if we have more than one candidate.
				if ( 1 < \count( $candidates ) ) {
					\ksort( $candidates );
				}

				$data = \array_shift( $candidates );
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

		// If, for whatever reason, the original image is missing, get a permalink for the original as a fallback.
		if ( empty( $data['source_url'] ) ) {
			$new_source         = $this->get_permalink_for_size( $document_id, $media_details['sizes']['original']['file'], $media_details['sizes']['original']['width'], $media_details['sizes']['original']['height'], false );
			$data['source_url'] = $new_source['source_url'];
		}

		return \array_merge(
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
		if ( false === \wp_attachment_is_image( $attachment_id ) ) {
			return $metadata;
		}

		$paths      = array();
		$upload_dir = \wp_upload_dir();

		// collect original file path
		if ( isset( $metadata['file'] ) ) {
			$path          = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file'];
			$paths['full'] = $path;

			// set basepath for other sizes
			$file_info = \pathinfo( $path );
			$basepath  = isset( $file_info['extension'] )
				? \str_replace( $file_info['filename'] . '.' . $file_info['extension'], '', $path )
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
				\unlink( $filepath );
			}
		}

		return $metadata;
	}

	public function get_original_dimensions( $interface, $original_image ) {
		if ( ! is_array( $interface ) ) {
			$interface = (array) $interface;
		}
		$rest = REST_Controller::get_instance();

		$url = $rest->get_document_link( $interface[0]->InterfaceName, $original_image->SubDocumentPath ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$interface[0]->InterfaceName` and `$original_image->SubDocumentPath` are provided by the SaaS API.

		if ( ! $url ) {
			return array(
				'width'  => 0,
				'height' => 0,
			);
		}

		// Make a HEAD request to validate the resource first.
		$response     = \wp_remote_head( $url );
		$content_type = \wp_remote_retrieve_header( $response, 'content-type' );

		// Validate file types before remotely fetching dimensions.
		if ( empty( $content_type ) || ! stristr( $content_type, 'image' ) ) {
			return array(
				'width'  => 0,
				'height' => 0,
			);
		}

		$sizes = \getimagesize( $url );

		return array(
			'width'  => $sizes[0],
			'height' => $sizes[1],
		);
	}

	/**
	 * Generate WordPress-equivalent metadata for a pseudo-attachment post.
	 *
	 * @param \WP_Post $post The attachment post object.
	 *
	 * @return array|array[]
	 */
	public function generate_imageshop_metadata( $post ) {
		$media_details = array(
			'sizes' => array(),
		);

		$image_sizes = Attachment::get_wp_image_sizes();

		$media = $this->get_document( $post->_imageshop_document_id );

		// If no valid media object is returned for any reason, bail early.
		if ( empty( $media ) || ! isset( $media->SubDocumentList ) ) { // / phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->SubDocumentList` are provided by the SaaS API.
			return $media_details;
		}

		$original_image = null;
		foreach ( $media->SubDocumentList as $document ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->SubDocumentList` is defined by the SaaS API.
			// For some reason, `IsOriginal` may sometimes be `0`, even on an original image.
			if ( 'Original' === $document->VersionName && null === $original_image ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$document->VersionName` is provided by the SaaS API.
				$original_image = $document;
			}

			// An actually declared original should always take priority.
			if ( 1 === $document->IsOriginal ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$document->VersionName` is defined by the SaaS API.
				$original_image = $document;
				break;
			}
		}

		if ( $original_image && ( 0 === $original_image->Width || 0 === $original_image->Height ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are provided by the SaaS API.
			$dimensions = $this->get_original_dimensions( $media->InterfaceList, $original_image ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->InterfaceList` is provided by the SaaS API.

			$original_image->Width  = $dimensions['width']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is provided by the SaaS API.
			$original_image->Height = $dimensions['height']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Height` is provided by the SaaS API.
		}

		foreach ( $image_sizes as $slug => $size ) {
			$image_width  = $size['width'];
			$image_height = $size['height'];

			// If no original image to calculate crops of exist, skip this size.
			if ( empty( $original_image ) && ( 0 === $image_width || 0 === $image_height ) ) {
				continue;
			}

			$size = $this->get_permalink_for_size( $media->DocumentID, $post->post_title, $image_width, $image_height, $size['crop'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentId` is provided by the SaaS API.

			// If criteria do not allow for this size, skip it.
			if ( empty( $size ) ) {
				continue;
			}

			$media_details['sizes'][ $slug ] = $size;
		}

		if ( ! isset( $media_details['size']['original'] ) ) {
			$url = $this->preloaded_url(
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

		if ( ! isset( $media_details['sizes']['full'] ) ) {
			$media_details['sizes']['full'] = $media_details['sizes']['original'];
		}

		\update_post_meta( $post->ID, '_imageshop_media_sizes', $media_details );
		return $media_details;
	}

	public function get_permalink_for_size_slug( $document_id, $filename, $size ) {
		$image_sizes = Attachment::get_wp_image_sizes();

		if ( ! isset( $image_sizes[ $size ] ) ) {
			return null;
		}
		return $this->get_permalink_for_size( $document_id, $filename, $image_sizes[ $size ]['width'], $image_sizes[ $size ]['height'], $image_sizes[ $size ]['crop'] );
	}

	public function get_image_dimensions( $size ) {
		$image_sizes = Attachment::get_wp_image_sizes();

		if ( ! isset( $image_sizes[ $size ] ) ) {
			return array(
				'width'       => 0,
				'height'      => 0,
				'orientation' => 'landscape',
			);
		}

		return array(
			'width'       => $image_sizes[ $size ]['width'],
			'height'      => $image_sizes[ $size ]['height'],
			'orientation' => ( $image_sizes[ $size ]['height'] > $image_sizes[ $size ]['width'] ? 'portrait' : 'landscape' ),
		);
	}

	public function save_local_permalink_for_size( $document_id, $size_key, $filename, $url, $width, $height, $crop = false ) {
		$attachment = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_imageshop_document_id',
				'meta_value'  => $document_id,
				'numberposts' => 1,
			)
		);

		if ( ! $attachment ) {
			return null;
		}

		if ( is_array( $attachment ) ) {
			$attachment = $attachment[0];
		}

		$image_sizes = get_post_meta( $attachment->ID, '_imageshop_permalinks', true );

		if ( empty( $image_sizes ) ) {
			$image_sizes = array();
		}
		if ( ! is_array( $image_sizes ) ) {
			$image_sizes = (array) $image_sizes;
		}

		$image_sizes[ $size_key ] = array(
			'height'     => $height,
			'width'      => $width,
			'source_url' => $url,
			'file'       => $filename,
		);

		update_post_meta( $attachment->ID, '_imageshop_permalinks', $image_sizes );
	}

	public function get_local_permalink_for_size( $document_id, $filename, $width, $height, $crop = false ) {
		$attachment = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_imageshop_document_id',
				'meta_value'  => $document_id,
				'numberposts' => 1,
			)
		);

		if ( ! $attachment ) {
			return null;
		}

		if ( is_array( $attachment ) ) {
			$attachment = $attachment[0];
		}

		$size_key = $this->get_permalink_size_key( $filename, $width, $height, $crop );

		$image_sizes = get_post_meta( $attachment->ID, '_imageshop_permalinks', true );

		if ( ! isset( $image_sizes[ $size_key ] ) ) {
			return null;
		}

		return $image_sizes[ $size_key ];
	}

	public function get_permalink_size_key( $filename, $width, $height, $crop ) {
		return sprintf(
			'%s-%s',
			$filename,
			sprintf(
				'%s-%s-%s',
				$width,
				$height,
				$crop ? '1' : '0'
			)
		);
	}

	public function get_document( $document_id ) {
		if ( ! isset( $this->documents[ $document_id ] ) ) {
			$imageshop = REST_Controller::get_instance();

			$this->documents[ $document_id ] = $imageshop->get_document( $document_id );
		}

		return $this->documents[ $document_id ];
	}

	public function append_document( $document_id, $document_details ) {
		$this->documents[ $document_id ] = $document_details;
	}

	public function get_permalink_for_size( $document_id, $filename, $width, $height, $crop = false ) {
		// Check for a local copy of the permalink first.
		$local_sizes = $this->get_local_permalink_for_size( $document_id, $filename, $width, $height, $crop );
		if ( null !== $local_sizes ) {
			return $local_sizes;
		}

		$size_key = $this->get_permalink_size_key( $filename, $width, $height, $crop );

		$media = $this->get_document( $document_id );

		$original_image = array();
		foreach ( $media->SubDocumentList as $document ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->SubDocumentList` is defined by the SaaS API.
			if ( 'Original' === $document->VersionName ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$document->VersionName` is defined by the SaaS API.
				$original_image = $document;
				break;
			}
		}

		// If no original image to calculate crops of exist, skip this size.
		if ( empty( $original_image ) && ( 0 === $width || 0 === $height ) ) {
			return null;
		}

		if ( $original_image && ( 0 === $original_image->Width || 0 === $original_image->Height ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are provided by the SaaS API.
			$dimensions = $this->get_original_dimensions( $media->InterfaceList, $original_image ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->InterfaceList` is provided by the SaaS API.

			$original_image->Width  = $dimensions['width']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is provided by the SaaS API.
			$original_image->Height = $dimensions['height']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Height` is provided by the SaaS API.
		}

		// No sizes should ever exceed the original image sizes, make it so.
		if ( $width > $original_image->Width ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
			$width = $original_image->Width; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
		}
		if ( $height > $original_image->Height ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Height` is defined by the SaaS API.
			$height = $original_image->Height; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Height` is defined by the SaaS API.
		}

		/*
		 * There us no obvious reason why this check should  ever be needed, yet here we are.
		 *
		 * For whatever reason, there are scenarios where an image size is reporting both with, and without
		 * any sensible sizes, leading to division by zero errors in both directions.
		 *
		 * This check will catch such a scenario, and return a `null` value, as if an original is missing,
		 * this is done so as not to break any behavior elsewhere, but this is bad mojo all around.
		 */
		if ( 0 === $width && 0 === $height ) {
			return null;
		}

		if ( 0 === $width || 0 === $height ) {
			if ( 0 === $width ) {
				$width = (int) \floor( ( $height / $original_image->Height ) * $original_image->Width ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are defined by the SaaS API.
			}
			if ( 0 === $height ) {
				$height = (int) \floor( ( $width / $original_image->Width ) * $original_image->Height ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are defined by the SaaS API.
			}
		} elseif ( $original_image->Width > $width || $original_image->Height > $height ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are defined by the SaaS API.
			// Calculate the aspect ratios for use in getting the appropriate dimension height/width wise for this image.
			$original_ratio = ( $original_image->Width / $original_image->Height ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` and `$original_image->Height` are defined by the SaaS API.
			$image_ratio    = ( $width / $height );

			if ( $image_ratio > $original_ratio ) {
				$width = round( $height * $original_ratio );
			} else {
				$height = round( $width / $original_ratio );
			}
		}

		if ( $crop ) {
			if ( $width > $original_image->Width ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
				$width = $original_image->Width; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$original_image->Width` is defined by the SaaS API.
			}
		}

		$url = $this->preloaded_url(
			$media,
			$width,
			$height
		);

		$this->save_local_permalink_for_size( $media->DocumentID, $size_key, $filename, $url, $width, $height, $crop ); // / phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `media->DocumentID` are provided by the SaaS API.

		return array(
			'height'     => $height,
			'width'      => $width,
			'source_url' => $url,
			'file'       => $filename,
		);
	}

	public function preloaded_url( $media, $width, $height ) {
		$imageshop = REST_Controller::get_instance();
		$media_id  = ( is_object( $media ) ? $media->DocumentID : $media ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is defined by the SaaS API.

		$attachment = \get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => '_imageshop_document_id',
				'meta_value'     => $media_id,
				'posts_per_page' => 1,
			)
		);

		if ( is_array( $attachment ) ) {
			$attachment = $attachment[0];
		}

		return trim(
			sprintf(
				'%s/%s',
				\untrailingslashit( $imageshop->create_permalinks_url( $media_id, $width, $height, $this->get_attachment_permalink_token_base( $attachment->ID ) ) ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$media->DocumentID` is defined by the SaaS API.
				urlencode( $this->get_attachment_filename( $attachment->ID ) )
			)
		);
	}

	private function get_attachment_filename( $attach_id ) {
		$filename = \get_post_meta( $attach_id, '_wp_attached_file', true );
		$filename = basename( $filename );

		return $filename;
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
		$media_details = \get_post_meta( $id, '_imageshop_media_sizes', true );
		$document_id   = \get_post_meta( $id, '_imageshop_document_id', true );

		if ( empty( $media_details ) && $document_id ) {
			$att           = Attachment::get_instance();
			$media_details = $att->generate_imageshop_metadata( \get_post( $id ) );

		}
		if ( isset( $media_details['sizes']['original'] ) ) {
			$data = $media_details['sizes']['original'];
			$html = '<img src="' . $data['source_url'] . '" alt="" width="' . $data['width'] . '" height="' . $data['height'] . '" class="alignnone size-medium wp-image-3512" />';
		}

		return $html;

	}

	/**
	 * Filter the attachment URL.
	 *
	 * The URL to an attachment may be called directly at various points in the process, so filter it as well.
	 *
	 * @param string $url     The URL to the full sized image.
	 * @param int    $post_id The ID for the attachment post.
	 * @return string
	 */
	public function attachment_url( $url, $post_id ) {
		$media_meta  = \get_post_meta( $post_id, '_imageshop_media_sizes', true );
		$document_id = \get_post_meta( $post_id, '_imageshop_document_id', true );

		// Don't override if there is no Imageshop ID assocaition.
		if ( ! $document_id ) {
			return $url;
		}

		if ( ! isset( $media_meta['sizes']['original'] ) ) {
			return $url;
		}

		return $media_meta['sizes']['original']['source_url'];
	}
}
