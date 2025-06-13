<?php

/**
 * This class decorator is intended for removing the actions added by OneComCentralizedMenu class
 */
if ( ! class_exists( 'OneComCentralizedMenuDecorator' ) ) {
	class OneComCentralizedMenuDecorator {
		protected OneComCentralizedMenu $central_menu;

		public function __construct( OneComCentralizedMenu $menu ) {

			$this->central_menu = $menu;
			remove_action( 'admin_menu', array( $this->central_menu, 'onecom_register_menu' ), - 1 );
			add_action( 'admin_menu', array( $this, 'onecom_remove_menu' ), 12 );
			remove_action( 'network_admin_menu', array( $this, 'onecom_register_menu' ), - 1 );
			remove_action( 'admin_head', array( $this, 'add_onecom_branding_css' ), 1 );
		}


		public function onecom_remove_menu() {

			remove_menu_page( 'onecom-wp' );
		}
	}
}
