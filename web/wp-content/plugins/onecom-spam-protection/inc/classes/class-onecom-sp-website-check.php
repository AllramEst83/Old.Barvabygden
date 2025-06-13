<?php


class OnecomSpWebsiteCheck extends OnecomSp {

	public $sp_options;

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'oc_check_exploit_url' ) );
		$this->sp_options = oc_get_sp_options( 'onecom_sp_protect_options' );
	}

	public function execute( $user_ip, $logs = array(), $sp_options = array(), &$oc_post = array() ) {

		$spam_detected = ' ';

		if ( isset( $this->sp_options['checks']['oc_sp_quickres'] ) && $this->sp_options['checks']['oc_sp_quickres'] != 'false' ) {

			$this->oc_sp_limit_login_attempts();
		}

		$sp_checks = OnecomSp::sp_protect_options();

		foreach ( $sp_checks as $sp_check ) {
			if ( $this->sp_options['checks'][ $sp_check ] == 'true' ) {

				$spam_detected = $this->sp_load_check_modules( $sp_check, $user_ip, $this->sp_options, $oc_post );

				if ( $spam_detected !== false ) {
					break;
				}
			}
		}
		if ( $spam_detected === false ) {
			return false;
		}

		if ( $spam_detected !== ' ' ) {

			oc_log_spam( $user_ip, $oc_post, $spam_detected );

			return $spam_detected;
		} else {
			return false;
		}
	}

	public function oc_check_exploit_url() {
		if ( $this->sp_options['checks']['oc_sp_proburl'] != 'true' || ! is_404() ) {
			return false;

		}

		$spam_detected = $this->sp_load_check_modules( 'oc_sp_proburl', '', $this->sp_options );

		if ( $spam_detected === false ) {
			return false;
		}

		oc_log_spam( $this->get_user_ip(), array(), $spam_detected );

		wp_die(
			"<p style='text-align: center'>" . __( '404 exploit probing detected!', OC_SP_TEXTDOMAIN ) . '</p>',
			__( 'Spam detected!', OC_SP_TEXTDOMAIN ),
			array( 'response' => 403 )
		);
		exit();
	}

	public function oc_sp_limit_login_attempts() {

		if ( ! isset( $_POST['log'] ) ) {
			return false;

		}

		$oc_post       = array( 'author' => $_POST['log'] );
		$spam_detected = $this->sp_load_check_modules( 'oc_sp_quickres', $this->get_user_ip(), $this->sp_options, $oc_post );

		if ( $spam_detected === false ) {
			return false;
		}

		oc_log_spam( $this->get_user_ip(), $oc_post, $spam_detected );
	}

	public function oc_sp_diagnostics( $user_ip, $oc_post ) {

		$options = array(
			'oc_sp_accept'   => array( 'description' => 'HTTP_ACCEPT header' ),
			'oc_sp_referrer' => array( 'description' => 'Invalid HTTP_REFERER' ),
			'oc_sp_long'     => array( 'description' => 'Disable lengthy emails and author names' ),
			'oc_sp_short'    => array( 'description' => 'Disable too short emails and author names' ),
			'oc_sp_bbcode'   => array( 'description' => 'Mark comments having BBCodes as spam' ),
			'oc_sp_urlshort' => array( 'description' => 'Block URL shortening services' ),
			'oc_sp_exploit'  => array( 'description' => 'Analyse comments for exploits' ),
		);

		foreach ( $options as $key => $value ) {

			$options[ $key ]['status'] = $this->sp_options['checks'][ $key ];

			$options[ $key ]['result'] = $this->sp_load_check_modules( $key, $user_ip, $this->sp_options, $oc_post );

		}

		return $options;
	}

	public function sp_load_check_modules( $file_name, $user_ip, &$sp_options = array(), &$oc_post = array() ) {

		if ( empty( $file_name ) ) {

			return false;
		}

		$module_file = ONECOM_PLUGIN_PATH . 'inc' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $file_name . '.php';
		if ( ! file_exists( $module_file ) ) {
			return false;
		}
		require_once $module_file;

		$class_name = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $file_name ) ) );

		$oc_object = new $class_name();
		$response  = $oc_object->execute( $sp_options, $oc_post );

		unset( $oc_object );
		return $response;
	}
}
