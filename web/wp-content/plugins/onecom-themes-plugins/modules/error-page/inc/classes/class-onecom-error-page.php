<?php

class Onecom_Error_Page {
	private $error_class_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'fatal-error-handler.php';
	private $local_class_path = ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'error-page' . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'fatal-error-handler.php';

	public function __construct() {
		if ( ! defined( 'OC_TEXTDOMAIN' ) ) {
			define( 'OC_TEXTDOMAIN', 'onecom-wp' );
		}
		add_action( 'admin_menu', array( $this, 'menu_pages' ), 1 );
		add_action( 'network_admin_menu', array( $this, 'menu_pages' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_onecom-error-pages', array( $this, 'configure_feature' ) );
	}

	public function menu_pages() {
		add_submenu_page(
			OC_TEXTDOMAIN,
			__( 'Advanced Error Page', OC_TEXTDOMAIN ),
			'<span id="onecom_errorpage">' . __( 'Advanced Error Page', 'onecom-wp' ) . '</span>',
			'manage_options',
			'onecom-wp-error-page',
			array( $this, 'error_page_callback' ),
			4
		);
	}

	public function enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix !== '_page_onecom-wp-error-page' ) {
			return;
		}
		$extenstion = '';
		wp_enqueue_script( 'onecom-error-page', ONECOM_WP_URL . '/modules/error-page/assets/js/error-page' . $extenstion . '.js', array( 'jquery' ), null, true );
		wp_enqueue_script( 'onecom-errorpage-script', ONECOM_WP_URL . 'assets/js/block-scripts/oc-advance-error-page.js', array( 'wp-element' ), null, true );
		wp_enqueue_style( 'onecom-error-page-css', ONECOM_WP_URL . '/modules/error-page/assets/css/error-page' . $extenstion . '.css', null );
        wp_localize_script('onecom-errorpage-script', 'ErrorPage',
        array(
                'status'=> file_exists( $this->error_class_path ) && $this->is_onecom_plugin() ,
                'labelStatus'=> __('Status', 'onecom-wp'),
                'labelSaving'=> __('Saving', 'onecom-wp'),
                'labelSave'=> __('Save', 'onecom-wp'),
                'labelActive'=> __('Active', 'onecom-wp'),
                'labelInactive'=> __('Inactive', 'onecom-wp'),
                'noticeHeading'=> __("Couldn’t save your settings.", 'onecom-wp'),
                'noticeDescription'=> __("We detected a custom error on your site. You need to deactivate it before you can use the Advanced Error Page.", 'onecom-wp'),
                'imageURL' => ONECOM_WP_URL
        ));
		//create object for localize into script
		$LocalizeObj        = array(
			'isPremium' => (int) $this->isPremium(),
		);
		$localizeHandleName = 'onecom-error-page';
		wp_localize_script( $localizeHandleName, 'LocalizeObj', $LocalizeObj );
	}

	public function error_page_callback() {
		$checked = ( file_exists( $this->error_class_path ) && $this->is_onecom_plugin() ) ? 'checked' : ''
		?>
		<div class="gv-activated  oc-error-wrap">
            <div id="oc-error-toast" class="gv-toast-container"></div>
            <div class="gv-p-fluid">
            <h3><?php _e( 'Advanced Error Page', 'onecom-wp' ); ?> </h3>
            <p class="gv-mt-sm gv-mb-fluid gv-text-sm">
				<?php _e('The advanced error page shows admin users if the error is related to a plugin or theme and what might be possible causes.', 'onecom-wp') ?>
            </p>
                <div id="oc-notice-placeholder"></div>
            <div id="oc-errorpage-root" class="gv-content-container gv-p-lg  gv-stack-space-md"></div>
            </div>
        </div>
		<?php
	}

	public function configure_feature() {
		$action = strip_tags( $_POST['type'] );
		//check if there is an existing file, owned by one.com. If no, bail out
		if ( ! $this->is_onecom_plugin() ) {
			wp_send_json_error(
				array(
					'status'  => 'failed',
					'message' => __( 'File already exist', 'onecom-wp' ),
				)
			);

			return;
		}
		if ( $action === 'enable' ) {
			$response = $this->enable_feature();
		} else {
			$response = $this->disable_feature();
		}
		wp_send_json_success( $response );
	}

	public function enable_feature() {

		if ( file_exists( $this->error_class_path ) && ( ! $this->is_onecom_plugin() ) ) {
			return array(
				'status'  => 'failed',
				'message' => __( 'An error handler is already present!', 'onecom-wp' ),
			);
		}

		if ( copy( $this->local_class_path, $this->error_class_path ) ) {
			$response = array(
				'status'  => 'success',
				'message' => __( 'Your settings were saved.', 'onecom-wp' ),
			);
		} else {
			$response = array(
				'status'  => 'failed',
				'message' => __( 'Couldn’t save your settings.', 'onecom-wp' ),
			);
		}

		return $response;
	}

	public function disable_feature() {
		if ( ! file_exists( $this->error_class_path ) ) {
			return array(
				'status'  => 'success',
				'message' => __( 'Your settings were saved.', 'onecom-wp' ),
			);
		}
		if ( unlink( $this->error_class_path ) ) {
			$response = array(
				'status'  => 'success',
				'message' => __( 'Your settings were saved.', 'onecom-wp' ),
			);
		} else {
			$response = array(
				'status'  => 'failed',
				'message' => __( 'Couldn’t save your settings.', 'onecom-wp' ),
			);
		}

		wp_send_json_success( $response );
	}

	public function is_onecom_plugin() {
		if ( ! file_exists( $this->error_class_path ) ) {
			return true;
		}
		$data = get_plugin_data( $this->error_class_path );
		if ( isset( $data['AuthorName'] ) && ( $data['AuthorName'] === 'one.com' ) ) {
			return true;
		}

		return false;
	}
	public function isPremium() {
		$features = oc_set_premi_flag();
		if ( ( isset( $features['data'] ) && ( empty( $features['data'] ) ) ) || ( in_array( 'MWP_ADDON', $features['data'] ) || in_array( 'ONE_CLICK_INSTALL', $features['data'] ) )
		) {
			return true;
		}
		return false;
	}
}
