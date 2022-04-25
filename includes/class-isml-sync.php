<?php
declare(strict_types=1);

namespace Dekode\WordPress\Imageshop_Media_Library_V2;

use ActionScheduler_Store;

if (!class_exists('ISML_Sync')) {
	/**
	 *
	 */
	class ISML_Sync {
		private static $instance;
		private $helpers;

		const HOOK_ISML_IMPORT_WP_TO_IMAGESHOP = 'isml_import_wp_to_imageshop';
		const HOOK_ISML_IMPORT_IMAGESHOP_TO_WP = 'isml_import_imageshop_to_wp';

		/**
		 *
		 */
		public function __construct() {
			require ISML_ABSPATH . "/vendor/woocommerce/action-scheduler/action-scheduler.php";
			$this->helpers = ISML_Helpers::get_instance();
			add_action('plugin_loaded', [$this, 'register_init_actions']);
			add_action('wp_ajax_isml_import_wp_to_imageshop', [$this, 'import_wp_to_imageshop']);
			add_action('wp_ajax_isml_import_imageshop_to_wp', [$this, 'import_imageshop_to_wp']);
			add_action('admin_notices', [$this, 'check_import_progress']);
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

		/**
		 * Register actions for action scheduler.
		 */
		public function register_init_actions() {
			add_action(self::HOOK_ISML_IMPORT_WP_TO_IMAGESHOP, [$this, 'do_import_batch_to_imageshop']);
			add_action(self::HOOK_ISML_IMPORT_IMAGESHOP_TO_WP, [$this, 'do_import_batch_to_wp']);
		}

		/**
		 * Import all media from WP to Imageshop in scheduled batches.
		 *
		 * @return void
		 */
		public function import_wp_to_imageshop() {
			if ($this->get_pending_scheduled_import_events(self::HOOK_ISML_IMPORT_WP_TO_IMAGESHOP)) {
				$this->helpers->show_message('Previous import did not finished yet. Try later.', true);
				die();
			}

			// get id of all posts that are attachements and don't have the meta _imageshop_document_id using raw SQL
			global $wpdb;
			$ret = $wpdb->get_results(
				  "
				SELECT p.ID, p.post_title
				FROM {$wpdb->prefix}posts p
					LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_imageshop_document_id'
				WHERE post_type = 'attachment'
				  AND pm.post_id IS NULL
				  AND p.post_mime_type LIKE 'image/%' ;"
				, ARRAY_A
			);

			if (count($ret) > 0) {
				$batchs = array_chunk($ret, 20);
				foreach ($batchs as $posts) {
					as_enqueue_async_action(self::HOOK_ISML_IMPORT_WP_TO_IMAGESHOP, [$posts]);
				}

				$this->helpers->show_message(
					'Import scheduled. Check the <a href="' . admin_url(
						'tools.php?page=action-scheduler&s=isml_import_wp_to_imageshop'
					) . '">scheduler page</a> for status.'
				);
				die();
			}
			$this->helpers->show_message('Nothing to import.');
			die();
		}

		public function get_pending_scheduled_import_events($hook): bool {
			$args = [
				'hook'   => $hook,
				'status' => ActionScheduler_Store::STATUS_PENDING,
			];
			$pending_scheduled_actions = as_get_scheduled_actions($args);

			return (bool)count($pending_scheduled_actions) > 0;
		}

		/**
		 * Import all media from Imageshop to WP in scheduled batches.
		 *
		 * @return void
		 */
		public function import_imageshop_to_wp() {
			if ($this->get_pending_scheduled_import_events(self::HOOK_ISML_IMPORT_IMAGESHOP_TO_WP)) {
				$this->helpers->show_message('Previous import did not finished yet. Try later.', true);
				die();
			}

			$rest = ISML_REST_Controller::get_instance();
			// Pagesize set to 0 to get all documents.
			$attr = ['Pagesize' => 0, 'SortDirection' => 'ASC'];
			$ret = $rest->search($attr);

			if (count($ret->DocumentList) <= 0) {
				$this->helpers->show_message('Nothing to import.');
				die();
			}

			$response = $this->prepare_response($ret->DocumentList);
			$batches = array_chunk($response, 5);
			foreach ($batches as $documents) {
				as_enqueue_async_action(self::HOOK_ISML_IMPORT_IMAGESHOP_TO_WP, [$documents]);
			}

			$this->helpers->show_message(
				'Import scheduled. Check the <a href="' . admin_url(
					'tools.php?page=action-scheduler&s=isml_import_imageshop_to_wp'
				) . '">scheduler page</a> for status.'
			);
			die();
		}

		/**
		 * @param $documents
		 */
		public function do_import_batch_to_wp($documents) {
			foreach ($documents as $document) {
				$rest = ISML_REST_Controller::get_instance();
				$ret = $rest->download($document['DocumentID']);
				$this->execute_import_to_wp($ret->Url, $document);
			}
		}

		/**
		 * @param $document_id
		 *
		 * @return string|null
		 */
		public function get_post_id_by_document_id($document_id) {
			global $wpdb;
			$ret = $wpdb->get_var(
				"
				SELECT pm.post_id
				FROM {$wpdb->prefix}postmeta pm
				WHERE pm.meta_value = '{$document_id}'
				    AND pm.meta_key = '_imageshop_document_id';"
			);

			return $ret;
		}


		/**
		 * @param $url
		 * @param $document
		 */
		public function execute_import_to_wp($url, $document) {
			$file = $this->helpers->collect_file($url);
			$filename = $document['FileName'];

			$upload_file = wp_upload_bits($filename, null, $file);
			if (!$upload_file['error']) {
				$wp_filetype = wp_check_filetype($filename, null);
				$attachment = [
					'post_mime_type' => $wp_filetype['type'],
					'post_parent'    => 0,
					'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
					'post_content'   => '',
					'post_status'    => 'inherit',
					'meta_input'     => [
						'_imageshop_document_id' => $document['DocumentID'],
					],
				];
				if ($ret = $this->get_post_id_by_document_id($document['DocumentID'])) {
					$attachment = array_merge(['ID' => $ret], $attachment);
				}

				$attachment_id = wp_insert_attachment($attachment, $upload_file['file']);
				if (!is_wp_error($attachment_id)) {
					require_once(ABSPATH . "wp-admin" . '/includes/image.php');
					$attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
					wp_update_attachment_metadata($attachment_id, $attachment_data);
				}
			}
		}

		/**
		 * Callback that is used by action scheduler in processing a batch.
		 *
		 * @param $posts
		 */
		public function do_import_batch_to_imageshop($posts) {
			$isml = ISML_Attachment::get_instance();
			foreach ($posts as $post) {
				$isml->import_to_imageshop((int)$post['ID']);
			}

		}

		/**
		 * Filter the doc_list of uncecessary parameters because action scheduler has a limited number of charachters
		 * that can receive as parameters.
		 *
		 * @param $doc_list
		 *
		 * @return array
		 */
		private function prepare_response($doc_list) {
			$ret = [];
			$allowed_values = [
				'DocumentID',
				'FileName',
			];
			foreach ($doc_list as $key => $document) {
				foreach ($allowed_values as $value) {
					$ret[$key][$value] = $document->$value;
				}
			}

			return $ret;
		}

		/**
		 * @param $hook
		 *
		 * @return string
		 */
		public static function get_events_hook_preaty_text($hook): string {
			$data = [
				self::HOOK_ISML_IMPORT_WP_TO_IMAGESHOP => 'WP to imageshop',
				self::HOOK_ISML_IMPORT_IMAGESHOP_TO_WP => 'imageshop to WP',
			];


			return $data[$hook];
		}

		/**
		 *
		 */
		public function check_import_progress() {
			echo '<div class="notice notice-warning inline">
				<h2>Imageshop import status</h2>';
			foreach ([self::HOOK_ISML_IMPORT_WP_TO_IMAGESHOP, self::HOOK_ISML_IMPORT_IMAGESHOP_TO_WP] as $hook) {
				$ret = $this->get_pending_scheduled_import_events($hook);
				if ($ret) {
					echo "<p>Import <strong>" . self::get_events_hook_preaty_text($hook) . "</strong> still in prigress. Please check later</p>";
				}
			}
			echo '</div>';
		}
	}
}

