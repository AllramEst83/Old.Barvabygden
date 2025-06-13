<?php
/**
 * Class Onecom_Logger
 */
if ( ! class_exists( 'Onecom_Logger' ) ) {
	class Onecom_Logger {

		const TYPE_ERROR = 'ERROR';

		const TYPE_CRITICAL = 'CRITICAL';

		const TYPE_FATAL = 'FATAL';

		const TYPE_WARNING = 'WARNING';

		const TYPE_INFO = 'STATE';

		const TYPE_DEBUG = 'DEBUG';

		const TYPE_STATUS = 'STATUS';

		private $middleware;
		private $middleware_ver      = 'v1.0';
		private $middleware_endpoint = 'log';


		/**
		 * Log directory (full path)
		 * @var string
		 */
		private $log_dir;

		/**
		 * Log file extension
		 * @var string
		 */
		private $log_extension = 'log';

		/**
		 * Messages to log
		 * @var array
		 */
		private $messages = array();

		/**
		 * Forced filename for the log
		 * @var null|string
		 */
		private $file_name = null;

		/**
		 * Logger constructor.
		 * @param null|string $log_dir
		 * @param null|string $log_extension
		 * @throws \Exception
		 */
		public function __construct() {
			if ( isset( $_SERVER['ONECOM_WP_ADDONS_API'] ) && '' !== $_SERVER['ONECOM_WP_ADDONS_API'] ) {
				$onecom_wp_addons_api = $_SERVER['ONECOM_WP_ADDONS_API'];
			} elseif ( defined( 'ONECOM_WP_ADDONS_API' ) && ONECOM_WP_ADDONS_API !== '' && ONECOM_WP_ADDONS_API !== false ) {
				$onecom_wp_addons_api = ONECOM_WP_ADDONS_API;
			} else {
				$onecom_wp_addons_api = 'http://wpapi.one.com/';
			}
			$onecom_wp_addons_api = rtrim( $onecom_wp_addons_api, '/' );
			$this->middleware     = $onecom_wp_addons_api . '/api/' . $this->middleware_ver . '/' . $this->middleware_endpoint;
			$this->log_dir        = wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . 'onecom_logs' . DIRECTORY_SEPARATOR . 'onecom_vcache' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
			$this->init();
		}

		public function init( $log_dir = null, $log_extension = null ) {
			// Set log directory
			if ( ! empty( $log_dir ) && is_dir( $log_dir ) ) {
				$this->log_dir = rtrim( $log_dir, '/\\' ) . DIRECTORY_SEPARATOR;
			}

			// Set log extension
			if ( ! empty( $log_extension ) ) {
				$this->log_extension = $log_extension;
			}

			// If cache directory doesn't exists, create it
			if ( ! is_dir( $this->log_dir ) && ! @mkdir( $this->log_dir, 0775, true ) ) {
				throw new \Exception( 'Failed to create log directory!' );
			}
		}

		/**
		 * @param string $message
		 * @param string $type
		 */
		public function log( $message, $type = self::TYPE_ERROR ) {
			$this->add( $message, $type );
			$this->commit();
		}

		/**
		 * @param string $message
		 * @param string $type
		 */
		public function add( $message, $type = self::TYPE_ERROR ) {
			$this->messages[] = array(
				'type'    => $type,
				'date'    => gmdate( 'Y/m/d H:i:s' ),
				'message' => $message,
			);
		}

		/**
		 * @return null|string
		 */
		public function get_file_name() {
			if ( '' === $this->file_name ) {
				return 'error';
			}
			return $this->file_name;
		}

		/**
		 * @param string $file_name
		 */
		public function set_file_name( $file_name ) {
			$this->file_name = $file_name;
		}

		/**
		 * @return bool
		 */
		public function commit() {
			if ( empty( $this->messages ) ) {
				return true;
			}

			$message_string = '';
			foreach ( $this->messages as $message ) {
				$message_string .= "[{$message["type"]}]-[{$message["date"]}] {$message["message"]}" . PHP_EOL;
			}

			$this->messages = array();

			if ( 1 > strlen( $message_string ) ) {
				return true;
			}
			$result = file_put_contents( $this->get_log_file(), $message_string, FILE_APPEND | LOCK_EX );

			// Check if the file_put_contents operation was successful
			if ( false === $result ) {
				// Handle the error, such as logging it or notifying the user
				error_log( 'Failed to write to log file: ' . $this->get_log_file() );

			}

			// Return the result of file_put_contents
			return $result;
		}

		/**
		 * @param null|string $file
		 * @return string
		 */
		public function read( $file = null ) {
			return @file_get_contents( $this->get_log_file( $file ) );
		}

		/**
		 * @param null|string $file_name
		 * @return string
		 */
		public function get_log_file( $file_name = null ) {
			//$this->init();
			// Default
			if ( null === $file_name ) {
				$file_name = ( null !== $this->file_name ) ? $this->file_name : gmdate( 'Y_m_d' );
			}

			return $this->log_dir . $file_name . '.' . $this->log_extension;
		}


		/**
		 * Generic log to WP API
		 * @param string entry_prefix // unique prefix to indetify the plugin or theme
		 * @param string action_type //
		 * @param string message // message for log
		 * @param string version // version of plugin or theme
		 * @param boolean error // is log having error or not
		 * @return bool
		 **/
		public function wp_api_sendlog( $action_type, $entry_prefix = 'general_', $message = '', $version = null, $error = 'false' ) {
			if ( '' === $action_type || null === $action_type ) {
				return;
			}
			$error   = (string) $error;
			$log_url = $this->middleware;

			$entry_prefix = rtrim( $entry_prefix, '_' ) . '_';

			$params = array(
				'action_type' => $entry_prefix . filter_var( $action_type, FILTER_SANITIZE_STRING ),
				'message'     => $message,
				'error'       => $error,
			);

			if ( null !== $version ) {
				$params['version']  = $version;
				$params['message'] .= ' | ' . 'Version:' . $version;
			}

			$client_ip     = $this->onecom_get_client_ip_env();
			$client_domain = ( isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) && ! empty( $_SERVER['ONECOM_DOMAIN_NAME'] ) ) ? $_SERVER['ONECOM_DOMAIN_NAME'] : 'localhost';

			global $wp_version;

			$log_entry = json_encode( $params );

			$save_log = wp_safe_remote_post(
				$log_url,
				array(
					'method'     => 'POST',
					'timeout'    => 3,
					'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
					'compress'   => false,
					'decompress' => true,
					'sslverify'  => true,
					'stream'     => false,
					'body'       => $log_entry,
					'headers'    => array(
						'X-ONECOM-CLIENT-IP'     => $client_ip,
						'X-ONECOM-CLIENT-DOMAIN' => $client_domain,
					),
				)
			);

			if ( ! is_wp_error( $save_log ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Function to get the client ip address
		 **/
		public function onecom_get_client_ip_env() {
			if ( getenv( 'HTTP_CLIENT_IP' ) ) {
				$ipaddress = getenv( 'HTTP_CLIENT_IP' );
			} elseif ( getenv( 'REMOTE_ADDR' ) ) {
				$ipaddress = getenv( 'REMOTE_ADDR' );
			} else {
				$ipaddress = '0.0.0.0';
			}
			return $ipaddress;
		}
	}
}
