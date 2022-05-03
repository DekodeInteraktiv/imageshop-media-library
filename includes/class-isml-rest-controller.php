<?php

namespace Dekode\WordPress\Imageshop_Media_Library_V2;

if ( ! class_exists( 'ISML_REST_Controller' ) ) {

	/**
	 * Class ISML_REST_Controller
	 */
	class ISML_REST_Controller {
		private const ISML_API_BASE_URL        = 'https://api.imageshop.no';
		private const ISML_API_CAN_UPLOAD      = '/Login/CanUpload';
		private const ISML_API_WHOAMI          = '/Login/WhoAmI';
		private const ISML_API_CREATE_DOCUMENT = '/Document/CreateDocument';
		private const ISML_API_GET_DOCUMENT    = '/Document/GetDocumentById';
		private const ISML_API_DOWNLOAD        = '/Download';
		private const ISML_API_GET_PERMALINK   = '/Permalink/GetPermalink';
		private const ISML_API_GET_INTERFACE   = '/Interface/GetInterfaces';
		private const ISML_API_GET_SEARCH      = '/Search2';

		/**
		 * @var ISML_REST_Controller
		 */
		private static $instance;

		/**
		 * @var string
		 */
		private string $api_token;

		/**
		 * @var string
		 */
		private string $language = 'en';

		public $interfaces;


		/**
		 * @param string $api_token
		 */
		public function __construct( $token = null ) {
			if ( null !== $token ) {
				$this->api_token = $token;
			} else {
				$this->api_token = \get_option( 'isml_api_key' );
			}
		}

		/**
		 * @return array
		 */
		public function get_headers(): array {
			return array(
				'Accept'       => 'application/json',
				'token'        => $this->api_token,
				'Content-Type' => 'application/json',
			);
		}

		/**
		 * @param string $url
		 * @param array  $args
		 *
		 * @return array|mixed
		 */
		public function execute_request( string $url, array $args ) {
			try {
				$response      = wp_remote_request( $url, $args );
				$response_code = wp_remote_retrieve_response_code( $response );

				if ( ! in_array( $response_code, array( 200, 201 ), true ) ) {
					return array(
						'code'    => wp_remote_retrieve_response_code( $response ),
						'message' => wp_remote_retrieve_response_message( $response ),
					);
				}

				return json_decode( wp_remote_retrieve_body( $response ) );
			} catch ( Exception $e ) {
				return array(
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
				);
			}
		}

		/**
		 * @return mixed|void
		 */
		public function can_upload() {
			$args = array(
				'method'  => 'GET',
				'headers' => $this->get_headers(),
			);

			return $this->execute_request( self::ISML_API_BASE_URL . self::ISML_API_CAN_UPLOAD, $args );
		}

		/**
		 * @param $b64_file_content
		 * @param $name
		 *
		 * @return array|mixed
		 */
		public function create_document( $b64_file_content, $name ) {
			$pyload = array(
				'bFile'         => $b64_file_content,
				'fileName'      => str_replace( '/', '_', $name ),
				'interfaceName' => get_option( 'imageshop_upload_interface' ),
				'doc'           => array(
					'Active' => true,
				),
			);

			$args = array(
				'method'  => 'POST',
				'headers' => $this->get_headers(),
				'body'    => json_encode( $pyload ),
			);

			return $this->execute_request( self::ISML_API_BASE_URL . self::ISML_API_CREATE_DOCUMENT, $args );
		}

		/**
		 * @param $document_id
		 * @param $width
		 * @param $height
		 *
		 * @return mixed
		 */
		public function get_permalink( $document_id, $width, $height ) {
			$pyload = array(
				'language'      => $this->language,
				'documentid'    => $document_id,
				'cropmode'      => 'ZOOM',
				'width'         => $width,
				'height'        => $height,
				'x1'            => 0,
				'y1'            => 0,
				'x2'            => 100,
				'y2'            => 100,
				'previewwidth'  => 100,
				'previewheight' => 100,
			);

			$args = array(
				'method'  => 'POST',
				'headers' => $this->get_headers(),
				'body'    => json_encode( $pyload ),
			);
			$ret  = $this->execute_request( self::ISML_API_BASE_URL . self::ISML_API_GET_PERMALINK, $args );
			return $ret->permalinktoken;
		}

		/**
		 * @return array|mixed
		 */
		public function get_interfaces() {
			if ( empty( $this->interfaces ) ) {
				$interfaces = \get_transient( 'imageshop_interfaces' );

				if ( false === $interfaces ) {
					$args    = array(
						'method'  => 'GET',
						'headers' => $this->get_headers(),
					);
					$request = $this->execute_request( self::ISML_API_BASE_URL . self::ISML_API_GET_INTERFACE, $args );

					if ( \is_wp_error( $request ) ) {
						return $this->interfaces;
					}

					$interfaces = $request;

					\set_transient( 'imageshop_interfaces', $interfaces, HOUR_IN_SECONDS );
				}

				$this->interfaces = $interfaces;
			}

			return $this->interfaces;
		}

		/**
		 * @param array $attributes
		 *
		 * @return array|mixed
		 */
		public function search( array $attributes ) {
			$interface_ids  = array();
			$interface_list = $this->get_interfaces();

			foreach ( $interface_list as $interface ) {
				$interface_ids[] = $interface->Id; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- `$interface->Id` is defined by the third party SaaS API.
			}

			// set default search attributes
			$attributes = \array_merge(
				array(
					'InterfaceIds'  => $interface_ids,
					'Language'      => 'no',
					'Querystring'   => '',
					'Page'          => 0,
					'Pagesize'      => 80,
					'DocumentType'  => 'IMAGE',
					'SortBy'        => 'DEFAULT',
					'SortDirection' => 'DESC',
				),
				$attributes
			);

			$args    = array(
				'method'  => 'POST',
				'headers' => $this->get_headers(),
				'body'    => json_encode( $attributes ),
			);
			$results = $this->execute_request( self::ISML_API_BASE_URL . self::ISML_API_GET_SEARCH, $args );

			if ( \is_wp_error( $results ) ) {
				return array();
			}

			return $results;
		}

		/**
		 * @param $id
		 *
		 * @return array|mixed|object
		 */
		public function get_document( $id ) {
			$url  = \add_query_arg(
				array(
					'language'   => 'no',
					'DocumentID' => $id,
				),
				self::ISML_API_BASE_URL . self::ISML_API_GET_DOCUMENT
			);
			$args = array(
				'method'  => 'GET',
				'headers' => $this->get_headers(),
			);

			$ret = $this->execute_request( $url, $args );

			if ( \is_wp_error( $ret ) ) {
				return (object) array();
			}

			return $ret;
		}

		/**
		 * @return bool
		 */
		public function test_valid_token() {

			$args = array(
				'method'  => 'GET',
				'headers' => $this->get_headers(),
			);

			$ret = $this->execute_request( self::ISML_API_BASE_URL . self::ISML_API_WHOAMI, $args );

			return ( ! \is_wp_error( $ret ) && ! isset( $ret['code'] ) );
		}

		/**
		 * @param $api_token
		 *
		 * @return ISML_REST_Controller
		 */
		public static function get_instance(): ISML_REST_Controller {
			if ( ! self::$instance ) {
				self::$instance = new ISML_REST_Controller();
			}

			return self::$instance;
		}

		/**
		 * @param int $document_id The ID of the document to be downloaded.
		 *
		 * @return array|mixed
		 */
		public function download( $document_id ) {
			$payload = array(
				'DocumentId'           => $document_id,
				'Quality'              => 'OriginalFile',
				'DownloadAsAttachment' => false,
			);
			$args    = array(
				'method'  => 'POST',
				'headers' => $this->get_headers(),
			);

			return $this->execute_request( self::ISML_API_BASE_URL . self::ISML_API_DOWNLOAD, $args );
		}
	}
}
