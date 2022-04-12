<?php
/**
 *
 */

declare(strict_types=1);

namespace Dekode\WordPress\Imageshop_Media_Library_V2;

if (!class_exists('ISML_Attachment')) {

	/**
	 * Class ISML_Attachment
	 */
	class ISML_Attachment {
		private static $instance;
		private bool $storage_file_only;

		public function __construct() {
			$this->storage_file_only = boolval(get_option('isml_storage_file_only'));
			add_filter('wp_get_attachment_image_src', [$this, 'attachment_image_src'], 10, 3);
			add_action('add_attachment', [$this, 'import_to_imageshop'], 10, 1);
			add_filter('wp_generate_attachment_metadata', [$this, 'filter_wp_generate_attachment_metadata'], 20, 2);
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

		public function import_to_imageshop($postID) {
			if (wp_attachment_is_image($postID) == true
				&& !boolval(get_post_meta($postID, '_imageshop_document_id', true))) {
				$isml_rest_controller = ISML_REST_Controller::get_instance();
				try {
					$file = get_attached_file($postID);
					if (is_readable($file)) {
						// create file in storage
						$meta = get_post_meta($postID, '_wp_attached_file', true);
						$ret = $isml_rest_controller->create_document(
							base64_encode(file_get_contents($file)),
							$meta
						);
						update_post_meta($postID, '_imageshop_document_id', $ret->docId);

						return $postID;
					}

					return $postID;
				} catch (Exception $e) {
					return false;
				}
			}

			return $postID;
		}


		public static function get_wp_image_sizes() {
			$image_sizes = [];

			$size_data = wp_get_additional_image_sizes();
			$registered_sizes = get_intermediate_image_sizes();

			foreach ($registered_sizes as $size) {
				// If the size data is empty, this is likely a core size, so look them up via the database.
				if (!isset($size_data[$size])) {
					$size_data[$size] = [
						'width'  => (int)get_option($size . "_size_w"),
						'height' => (int)get_option($size . "_size_h"),
						'crop'   => (bool)get_option($size . "_crop"),
					];
				}

				$image_sizes[$size] = [
					'width'  => $size_data[$size]['width'],
					'height' => $size_data[$size]['height'],
					'crop'   => $size_data[$size]['crop'],
				];
			}
			if (isset($image_sizes['post-thumbnail'])) {
				unset($image_sizes['post-thumbnail']);
			}
			return $image_sizes;
		}

		/**
		 * @param $image
		 * @param $attachment_id
		 * @param $size
		 *
		 * @return array|false
		 */
		public function attachment_image_src($image, $attachment_id, $size) {
			$media_details = get_post_meta($attachment_id, '_imageshop_media_sizes', true);
			$document_id = get_post_meta($attachment_id, '_imageshop_document_id', true);

			if (empty($media_details) && $document_id) {
				$att = ISML_Attachment::get_instance();
				$media_details = $att->generate_imageshop_metadata(get_post($attachment_id));
			}

			if ($size == 'full') {
				$size = 'original';
			}
			if (is_array($size)) {

				$candidates = [];


				if (!isset($media_details['file']) && isset($media_details['sizes']['original'])) {
					$media_details['height'] = $media_details['sizes']['original']['height'];
					$media_details['width'] = $media_details['sizes']['original']['width'];
				}

				foreach ($media_details['sizes'] as $_size => $data) {
					// If there's an exact match to an existing image size, short circuit.
					if ((int)$data['width'] === (int)$size[0] && (int)$data['height'] === (int)$size[1]) {
						$candidates[$data['width'] * $data['height']] = $data;
						break;
					}

					// If it's not an exact match, consider larger sizes with the same aspect ratio.
					if ($data['width'] >= $size[0] && $data['height'] >= $size[1]) {
						// If '0' is passed to either size, we test ratios against the original file.
						if (0 === $size[0] || 0 === $size[1]) {
							$same_ratio = wp_image_matches_ratio($data['width'], $data['height'], $media_details['width'], $media_details['height']);
						} else {
							$same_ratio = wp_image_matches_ratio($data['width'], $data['height'], $size[0], $size[1]);
						}

						if ($same_ratio) {
							$candidates[$data['width'] * $data['height']] = $data;
						}
					}
				}

				if (!empty($candidates)) {
					// Sort the array by size if we have more than one candidate.
					if (1 < count($candidates)) {
						ksort($candidates);
					}

					$data = array_shift($candidates);
					/*
					* When the size requested is smaller than the thumbnail dimensions, we
					* fall back to the thumbnail size to maintain backward compatibility with
					* pre 4.6 versions of WordPress.
					*/
				} elseif (!empty($media_details['sizes']['thumbnail']) && $media_details['sizes']['thumbnail']['width'] >= $size[0] && $media_details['sizes']['thumbnail']['width'] >= $size[1]) {
					$data = $media_details['sizes']['thumbnail'];
				} else {
					$data = $media_details['sizes']['original'];

					//					return false;
				}

			} elseif (!empty($media_details['sizes'][$size])) {
				$data = $media_details['sizes'][$size];
			} elseif (isset($media_details['sizes']['original'])){
				$data = $media_details['sizes']['original'];

			}
			// If we still don't have a match at this point, return false.
			if (empty($data)) {
				return false;
			}


			return array_merge(
				[
					0 => $data['source_url'],
					1 => $data['width'],
					2 => $data['height'],
					3 => ('original' === $size ? false : true),
				],
				$data
			);
		}

		/**
		 * @param $metadata
		 * @param $attachment_id
		 *
		 * @return mixed
		 */
		public function filter_wp_generate_attachment_metadata($metadata, $attachment_id) {
			if (wp_attachment_is_image($attachment_id) == false) {
				return $metadata;
			}

			$paths = [];
			$upload_dir = wp_upload_dir();

			// collect original file path
			if (isset($metadata['file'])) {
				$path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file'];
				$paths['full'] = $path;

				// set basepath for other sizes
				$file_info = pathinfo($path);
				$basepath = isset($file_info['extension'])
					? str_replace($file_info['filename'] . "." . $file_info['extension'], "", $path)
					: $path;
			}

			// collect size files path
			if (isset($metadata['sizes'])) {
				foreach ($metadata['sizes'] as $key => $size) {
					if (isset($size['file'])) {
						$paths[$key] = $basepath . $size['file'];
					}
				}
			}

			// process paths
			foreach ($paths as $key => $filepath) {
				// remove fisical file.
				if (
					$this->storage_file_only == 1
					&& (
						!empty($metadata['sizes'][$key]['imageshop_permalink'])
						|| ($key == 'full') && !empty($metadata['imageshop_permalink'])
					)
				) {
					unlink($filepath);
				}
			}

			return $metadata;
		}


		public function generate_imageshop_metadata($post) {
			$imageshop = ISML_REST_Controller::get_instance();
			$media_details = [
				'sizes' => [],
			];

			$image_sizes = ISML_Attachment::get_wp_image_sizes();

			$media = $imageshop->get_document($post->_imageshop_document_id);

			$original_image = [];
			foreach ($media->SubDocumentList as $document) {
				if ('Original' === $document->VersionName) {
					$original_image = $document;
					break;
				}
			}

			foreach ($image_sizes as $slug => $size) {
				$image_width = $size['width'];
				$image_height = $size['height'];

				// If no original image to calculate crops of exist, skip this size.
				if (empty($original_image) && (0 === $image_width || 0 === $image_height)) {
					continue;
				}

				if (0 === $image_width) {
					$image_width = (int)floor(($image_height / $original_image->Height) * $original_image->Width);
				}
				if (0 === $image_height) {
					$image_height = (int)floor(($image_width / $original_image->Width) * $original_image->Height);
				}

				if ($size['crop']) {
					if ($image_width > $original_image->Width) {
						$image_width = $original_image->Width;
					}
				}

				$url = $imageshop->get_permalink(
					$media->DocumentID,
					$image_width,
					$image_height
				);
				$media_details['sizes'][$slug] = [
					'height'     => $image_height,
					'width'      => $image_width,
					'source_url' => $url,
					'file'       => $post->post_title,
				];

				if (!isset($media_details['size']['original'])) {
					$url = $imageshop->get_permalink(
						$media->DocumentID,
						$original_image->Width,
						$original_image->Height
					);
					$media_details['sizes']['original'] = [
						'height'     => $original_image->Height,
						'width'      => $original_image->Width,
						'file'       => $post->post_title,
						'source_url' => $url,
					];
				}

			}
			update_post_meta($post->ID, '_imageshop_media_sizes', $media_details);
			return $media_details;
		}
	}
}
