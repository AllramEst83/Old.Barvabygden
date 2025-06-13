<?php
defined( 'ABSPATH' ) or die( 'Cheating Huh!' ); // Security

require_once __DIR__ . '/vendor/autoload.php';


use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;


class OCLAUTH {


	const TOKENKEY = 'onecom-auth';

	const DEFAULT_USERID = 1;

	const WP_PATHKEY = 'wp_path';

	public static $instance = null;

	private $key;

	public $wpPathVal;
	public $tokenVal;
	public $tokenStatus;
	public $token_message;
	public $errors;

	public $errorTemplate = array(
		'error'   => true,
		'data'    => null,
		'message' => 'Some error occurred.',
	);

	public $additional_info = array();

	public $request_host;
	public $request_uri;
	public $prefix;
	public $site_url;
	public $home_url;
	public $onecom_domain;
	public $onecom_subdomain = '';

	private $is_own_dir;
	private $is_req_host_different;
	private $is_rest_request;
	private $is_wpadmin = false;

	/**
	 * Returns the *Singleton* instance of this class.
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new OCLAUTH();
		}
		return self::$instance;
	}

	/*
	* Private constructor to prevent initiation with outer code.
	*/
	public function __construct() {

		//        error_log("------------------------");
		//        error_log("Entered inside OCL");
		//        error_log("------------------------");

		global $wpdb;
		//set table prefix
		$this->prefix = $wpdb->prefix;

		$this->preliminaryChecks();

		// check and correct request URL
		// covers support for subdirectory installations
		$this->validateRequestURL();

		/* Push stats - 1-click-login init after login button click */
		/*(class_exists('OCPushStats')?\OCPushStats::push_stats_performance_cache("click_login",'login','login','control-panel', $this->additional_info):'');*/

		$this->generateKey();
		if ( $this->tokenExist() ) {
			$this->tokenVal  = $_GET[ self::TOKENKEY ];
			$this->wpPathVal = isset( $_GET[ self::WP_PATHKEY ] ) ? urldecode( $_GET[ self::WP_PATHKEY ] ) : '';
			$this->validateToken();
		}
	}

	public function __destruct() {
	}

	/**
	 * Strip off http:// and www from URL
	 */
	public function cleanURL( $url = '' ): string {
		return rtrim( str_replace( 'www.', '', str_replace( 'https://', '', str_replace( 'http://', '', $url ) ) ), '/' );
	}

	/**
	 * Preliminary checks before processing the auth request
	 */
	public function preliminaryChecks() {

		// original request vars
		$this->request_host = $_SERVER['HTTP_HOST'];
		$this->request_uri  = $_SERVER['REQUEST_URI'];

		// get WP's site_url & home_url
		$this->site_url = get_site_option( 'siteurl' );
		$this->home_url = is_multisite() ? get_home_url() : get_site_option( 'home' );

		// is wp-admin url
		$this->is_wpadmin = ( false !== strpos( $this->request_uri, '/wp-admin' ) );

		// is rest request
		$this->is_rest_request = isset( $_REQUEST['rest_route'] );

		/* is WP having its own directory */
		if ( $this->cleanURL( $this->site_url ) !== $this->cleanURL( $this->home_url ) ) {
			$this->is_own_dir = true;
		}

		// parse hosts for matching in next step
		$site_url_host = parse_url( $this->site_url )['host'];
		$home_url_host = parse_url( $this->home_url )['host'];

		// if WP site URL doesn't match with requested URL
		if ( $home_url_host != $this->cleanURL( $this->request_host ) ) {
			$this->is_req_host_different = true;
		}

		// onecom domain and subdomain/subdirectory
		$this->onecom_domain    = $this->get_sitedomain();
		$this->onecom_subdomain = $this->get_sitesubdomain();
	}

	/*
	 * Check and correct request URL
	 * Covers support for subdirectory installations detected by CMS Scanner
	 * */
	public function validateRequestURL() {

		if ( $this->is_wpadmin ) {
			//            error_log("Reached inside is_wpadmin==true");
			return true;
		}

		// parse hosts for matching in next step
		$site_url_host = parse_url( $this->site_url )['host'];
		$home_url_host = parse_url( $this->home_url )['host'];

		// in general we'll rely on WP's site_url
		$wp_url_host = $site_url_host;

		// but check if this installation is having its own directory
		// in such a case site_url and home_url are different
		/*if($site_url_host !== $home_url_host){
			// we'll prefer using home_url instead of site_url
			// because this installation is having its own directory.
			$wp_url_host = $home_url_host;
			$wp_url = $home_url;
		}*/

		// get request URL sent by CP
		$request_host = $_SERVER['HTTP_HOST'];

		// if the current is a rest_api call
		if ( $this->is_rest_request ) {
			// then use home_url to get rest_api response
			$correct_url = rtrim( $this->home_url, '/' ) . $this->request_uri;
			//            error_log("1st case --- rest api call");
		}
		// if the WP is having its own directory
		elseif ( $this->is_own_dir ) {

			// if the current call is only for wp-admin login
			// then first redirect to wp-admin URL and then attempt login
			//            error_log("2nd case --- WP having its own dir call");
			$correct_url = rtrim( $this->site_url, '/' ) . '/wp-admin/' . $this->request_uri;
			$correct_url = str_replace( 'wp-admin//', 'wp-admin/', $correct_url );
			$correct_url = str_replace( 'wp-admin/wp-admin/', 'wp-admin/', $correct_url );
			//            error_log('#### REDIRECTING TO ==> '.$correct_url);
			wp_redirect( $correct_url );
			exit;
		}

		// if WP site URL doesn't match with requested URL
		// possibly a case when subdirectory installation was treated as subdomain
		// then redirect to site url first and then attempt request (i.e., login + rest_api)
		elseif ( $wp_url_host != $request_host ) {
			// convert the request URL into WP site URL and append the request URI
			//            error_log("3rd case --- Request URL doesnt match with siteurl");
			$correct_url = rtrim( $this->site_url, '/' ) . $this->request_uri;

			//            error_log('#### REDIRECTING TO ==> '.$correct_url);
			wp_redirect( $correct_url );
			exit;
			// redirect to functional site URL

		} else {
			//            error_log("4th case --- None of above");
		}
	}

	/**
	 * Update status and prepare response
	 * @return array
	 */
	public function responseHandler( $is_success = true, $data = array() ): array {

		$this->tokenStatus = $is_success;

		$this->token_message = isset( $data['message'] ) ? $data['message'] : $this->errorTemplate['message'];

		$response            = $this->errorTemplate;
		$response['error']   = (bool) ! ( $this->tokenStatus );
		$response['message'] = $this->token_message;

		// Push stats - Authentication status (valid/invalid) push
		array_push( $this->additional_info, $this->token_message );
		//        error_log("ResponseHandler Status --> " . json_encode($this->additional_info));
		( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'lookup', 'login', 'authentication', 'control-panel', $this->additional_info ) : '' );

		return $response;
	}


	/**
	 * Function to check token key exist
	 * Entry point
	 */
	public function tokenExist() {
		$getParam = $_GET;
		if ( isset( $getParam[ self::TOKENKEY ] ) && ! empty( $getParam[ self::TOKENKEY ] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * function to validate token
	 */
	public function validateToken() {
		if (
			defined( 'REST_REQUEST' ) ||
			( isset( $_GET['rest_route'] ) && ! empty( $_GET['rest_route'] ) )
		) {
			//handle token
			return $this->checkToken( $this->tokenVal );
		}

		//handle token
		$this->checkToken( $this->tokenVal );

		if ( ! $this->tokenStatus ) {
			return null;
		}
		$user = get_userdata( self::DEFAULT_USERID );

		// condition improved as per case WPIN-2551
		if ( ! $user || ! isset( $user->roles ) || ! in_array( 'administrator', $user->roles ) ) {
			$get_user_id = $this->getAdminIds();
			if ( $get_user_id > 0 ) {
				$this->oclAuthCheck( $get_user_id );
			}
		} else {
			//call oclauthCheck on default userid 1
			$this->oclAuthCheck( self::DEFAULT_USERID );
		}
	}

	/**
	 * Update status and prepare response
	 * @return array
	 */
	public function checkTokenData( $decoded_token ) {

		// if required data exists
		if ( ! ( property_exists( $decoded_token, 'domain' ) && property_exists( $decoded_token, 'subdomain' ) ) ) {
			return $this->responseHandler(
				false,
				array(
					'message' => 'Incorrect Installation URL provided in token.',
				)
			);
		}

		// if required data intended for current installation
		if ( true === $this->checkValidDomain( $decoded_token->domain, $decoded_token->subdomain ) ) {
			return $this->responseHandler(
				true,
				array(
					'message' => 'Valid Status',
				)
			);
		}

		// anything bad
		return $this->responseHandler(
			false,
			array(
				'message' => 'Incorrect Installation URL provided or unknown error occured.',
			)
		);
	}

	/**
	 * Check token is valid or not
	 */
	public function checkToken( $get_token_val ) {

		// seat belt
		if ( empty( $get_token_val ) ) {
			return $this->responseHandler(
				false,
				array(
					'message' => 'Token missing.',
				)
			);
		}

		try {
			//Ref:https://github.com/firebase/php-jwt/
			$decoded_token = JWT::decode( $get_token_val, new Key( $this->key, 'RS256' ) );

			// check if token decoded
			if ( empty( $decoded_token ) || ! is_object( $decoded_token ) ) {
				return $this->responseHandler(
					false,
					array(
						'message' => 'Invalid token. Failed to decode token.',
					)
				);
			}

			return $this->checkTokenData( $decoded_token );
		} catch ( Exception $e ) {
			return $this->responseHandler(
				false,
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Function to chech valid domain
	 */
	public function checkValidDomain( $token_domain, $token_subdomain ): bool {

		$actualSubdomain = trim( $this->onecom_subdomain );

		if ( $this->onecom_subdomain === 'www' ) {
			$actualSubdomain = str_replace( 'www', '', $actualSubdomain );
		}

		if ( $token_subdomain === 'www' ) {
			$token_subdomain = str_replace( 'www', '', $token_subdomain );
		}
		if ( $this->onecom_domain === $token_domain && ( $this->is_own_dir || $actualSubdomain === $token_subdomain ) ) {
			return true;
		}

		//        error_log("Token domain --> {$token_domain}");
		//        error_log("Token sub-domain --> {$token_subdomain}");
		//        error_log("Request domain --> {$this->onecom_domain}");
		//        error_log("Request sub-domain --> {$actualSubdomain}");

		return false;
	}

	/**
	 * Function to get domain
	 */
	public function get_sitedomain() {
		if ( isset( $_SERVER['SERVER_NAME'] ) && $_SERVER['SERVER_NAME'] === 'localhost' ) {
			return 'localhost';
		} elseif ( isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) && ! empty( $_SERVER['ONECOM_DOMAIN_NAME'] ) ) {
			return $_SERVER['ONECOM_DOMAIN_NAME'];
		} else {
			return 'localhost';
		}
	}

	/**
	 * Function to get sub domain
	 */
	public function get_sitesubdomain() {
		if ( $this->get_sitedomain() === 'localhost' ) {
			return null;
		}

		// parse WP site URL
		// but if it is a WP with its own directory, then prefer using home_url of WP as trusted destination.
		$wp_siteurl = ( $this->is_own_dir/* && !$this->is_rest_request*/ ) ? parse_url( $this->home_url ) : parse_url( $this->site_url );

		//        error_log("Going to use SiteURL  ---> ".json_encode($wp_siteurl));

		// get subdirectory from site URL
		if ( ! empty( $wp_siteurl['path'] ) ) {
			// return the subdirectory to caller function
			// so that it can be matched against the subdomain sent by CP in token
			return str_replace( '/', '', $wp_siteurl['path'] );
		}

		$subdomain = substr( $_SERVER['SERVER_NAME'], 0, -( strlen( $_SERVER['ONECOM_DOMAIN_NAME'] ) ) );

		if ( $subdomain && $subdomain !== '' ) {
			return rtrim( $subdomain, '.' );
		} else {
			return 'www';
		}
	}


	/**
	 * Function to set error message on login page
	 */
	public function ocl_login_error_callback( $message ) {

		if ( ! isset( $_GET['redirect_to'] ) && empty( $_GET['redirect_to'] ) ) {
			return $message;
		}

		$redirectto = $_GET['redirect_to'];
		$query      = parse_url( $redirectto, PHP_URL_QUERY );
		$queries    = array();

		parse_str( $query, $queries );

		if ( isset( $queries[ self::TOKENKEY ] ) && ! empty( $queries[ self::TOKENKEY ] ) ) {
			$tokenPassVal = $queries[ self::TOKENKEY ];
			$this->checkToken( $tokenPassVal );

			if ( ! $this->tokenStatus ) {
				return '<div id="login_error">	' . __( $this->token_message, 'ocl' ) . '<br>
                </div>';
			}
		}
		return $message;
	}

	/**
	 * function for oclauthCheck
	 */
	public function oclAuthCheck( $user_id ) {

		$user      = get_user_by( 'ID', $user_id );
		$loginuser = $user->data->user_login;

		wp_set_current_user( $user_id, $loginuser );
		wp_set_auth_cookie( $user_id );
		do_action( 'wp_login', $loginuser, $user );
		// Go to admin area
		$redirect_page = is_multisite() ? get_admin_url() : admin_url();
		if ( is_user_logged_in() ) {

			if ( ! empty( $this->wpPathVal ) ) {
				$redirect_page = $redirect_page . $this->wpPathVal;
				// handle "/" for frontpage url
				if ( '/' === $this->wpPathVal ) {
					// parse WP site URL
					$wp_siteurl = parse_url( get_site_option( 'siteurl' ) );
					// get subdirectory from site URL
					if ( ! empty( $wp_siteurl['path'] ) ) {
						//if subdirectory exit then simply return site url
						$redirect_page = get_site_option( 'siteurl' );
					} else {
						$redirect_page = $_SERVER['HTTP_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
					}
				}
			}
			//            error_log("Redirect to ---> {$redirect_page}");
			nocache_headers();
			// Push stats - redirect to dashboard
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'redirect', 'login', 'wordpress', 'control-panel', $this->additional_info ) : '' );
			wp_redirect( $redirect_page );
			exit;
		}
	}


	/**
	 * Function to get admin id
	 */
	public function getAdminIds() {

		global $wpdb;
		$getAdminQuery = 'SELECT u.ID, u.user_login FROM ' . $this->prefix . 'users u, ' . $this->prefix . 'usermeta m WHERE u.ID = m.user_id AND m.meta_key LIKE "' . $this->prefix . 'capabilities" AND m.meta_value LIKE "%administrator%" order by ID ASC';

		//Get all admin users ids from DB
		$wpAuthUser = $wpdb->get_results( $getAdminQuery );

		//return first found user id
		if ( ! empty( $wpAuthUser ) ) {
			return $wpAuthUser[0]->ID;
		}
		return 0;
	}

	/**
	 * Public key generator
	 */
	private function generateKey() {
		$this->key = <<<EOD
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA3A3D9KpeYiAZlJsSG6yc
        K0rk5X07gH+VPWyc3y5BLnpFXnjFcWPBCo00TG1PjPb7dTi2li1rO6l3wLCSz0D7
        Eo9Jrd6UoqwzEuxTAfZYLf242O50NonhDVnuETu+C+MwNiPXXJyv6lDWcatInCVR
        Gcb1XK/Vo06Fb7WZ29YUsFrY0niBsUQLe61uci9HqQe1vskvVc2sHSI5gCz8A/eV
        qMpfwrwLUBeudyqlESfd9jGgFq9m7NMKlVlt49878iTgGGeJSqWsvQPq88e81yEl
        waKJBHY7JO2dMZi7ygbuEK0Ek1dVs0aZHIs1ganu9UqPPmOfftxmfVdI54IFcOCX
        fwIDAQAB
        -----END PUBLIC KEY-----
        EOD;
	}
}
