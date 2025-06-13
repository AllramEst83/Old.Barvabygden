<?php

/**
 * Defines admin settings functions
 *
 * @since      0.1.0
 * @package    Under_Construction
 * @subpackage OCUC_Admin_Settings_API
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Admin_Settings_API {

	public $premium_inline_msg;

	// Constructor
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		// Store premium tag once to avoid multiple calls to features endpoint
		//Commented below code during cu event track
        //$this->premium_inline_msg = apply_filters( 'onecom_premium_inline_badge', '', __( 'This is a Managed WordPress feature.', 'onecom-uc' ), 'mwp' );
	}

	// initialize settings array
	protected $settings_sections = array();
	protected $settings_fields   = array();



	/**
	 * Enqueue scripts and styles
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery' );
	}

	// Set settings sections
	public function set_sections( $sections ) {
		$this->settings_sections = $sections;

		return $this;
	}

	// Set settings fields
	public function set_fields( $fields ) {
		$this->settings_fields = $fields;
		return $this;
	}

	// Function to check if given premium feature (default: stg) available or not
	public function oc_premium( $call_for = null ) {
		$features = oc_set_premi_flag();
		if ( null === $call_for ) {
			if ( isset( $features['data'] ) && ( ! empty( $features['data'] ) ) && ( in_array( 'MWP_ADDON', $features['data'], true ) ) ) {
				return true;
			} else {
				return false;
			}
		} elseif ( 'all_plugins' === $call_for ) {
			if ( ( isset( $features['data'] ) && ( empty( $features['data'] ) ) )
				|| ( in_array( 'ONE_CLICK_INSTALL', $features['data'], true ) )
				|| ( in_array( 'MWP_ADDON', $features['data'], true ) ) ) {
				return true;
			}
			return false;
		}
	}

	/**
	 * Loop through all settings and fields
	 */
	public function settings_init() {

		// add settings sections
		foreach ( $this->settings_sections as $section ) {

			if ( isset( $section['callback'] ) ) {
				$callback = array( $this, $section['callback'] );
			} else {
				$callback = null;
			}

			add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
		}

		// add settings fields
		foreach ( $this->settings_fields as $section => $fields ) {
			// add fields to section
			foreach ( $fields as $option ) {

				$name     = $option['name'];
				$type     = isset( $option['type'] ) ? $option['type'] : 'text';
				$label    = isset( $option['label'] ) ? $option['label'] : '';//label come from here
				$callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

				//override label to show premium tag
				$override_label = array(
					'label' => $label,
					'id'    => $name,
                    'event_track_class'            => isset( $option['event_track_class'] ) ? $option['event_track_class'] : '',
                    'modal_event_class'            => isset( $option['modal_event_class'] ) ? $option['modal_event_class'] : '',

				);
				$label          = $this->get_premium_tag_after_label( $override_label );

				$args = array(
					'id'                => $name,
					'class'             => isset( $option['class'] ) ? $option['class'] : $name,
					'label_for'         => ONECOM_UC_OPTION_FIELD,
					'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
					'name'              => $label,
					'section'           => ONECOM_UC_OPTION_FIELD,
					'size'              => isset( $option['size'] ) ? $option['size'] : null,
					'options'           => isset( $option['options'] ) ? $option['options'] : '',
					'std'               => isset( $option['default'] ) ? $option['default'] : '',
					'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
					'type'              => $type,
					'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
					'min'               => isset( $option['min'] ) ? $option['min'] : '',
					'max'               => isset( $option['max'] ) ? $option['max'] : '',
					'step'              => isset( $option['step'] ) ? $option['step'] : '',
					'srcset'            => isset( $option['srcset'] ) ? $option['srcset'] : '',
					'event_track_class'            => isset( $option['event_track_class'] ) ? $option['event_track_class'] : '',
					'modal_event_class'            => isset( $option['modal_event_class'] ) ? $option['modal_event_class'] : '',
				);

				// Register section and its fields
				add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
			}
		}

		// creates our settings in the options table
		foreach ( $this->settings_sections as $section ) {
			register_setting( ONECOM_UC_OPTION_FIELD, ONECOM_UC_OPTION_FIELD, array( $this, 'sanitize_options' ) );
		}

		return $this;
	}

	// Sanitize callback for Settings API
	public function sanitize_options( $options ) {

		if ( ! $options ) {
			return $options;
		}

		/**
		 * Server side premium settings validation for non-premium package
		 * * Reset, Retain or Update settings based on package
		 */

		$old_settings = get_option( ONECOM_UC_OPTION_FIELD );

		if ( $this->oc_premium() === false ) {
			/**
			 * If key exists, and design is selected design is not non-premium
			 * * Or If key does not exist (because premium selected but disabled)
			 * * * retain old design (even if it is premium)
			 */
			$non_prem_designs = array( 'theme-1', 'theme-2', 'theme-3' );
			if ( ( array_key_exists( 'uc_theme', $options ) &&
					! in_array( $options['uc_theme'], $non_prem_designs, true ) ) ||
				! array_key_exists( 'uc_theme', $options )
			) {
				$options['uc_theme'] = $old_settings['uc_theme'];
			}

			/**
			 * If favicon exists and changed (but not allowed)
			 * [Case: Cu modified using html source]
			 * * * revert to old favicon
			 */
			if ( ( array_key_exists( 'uc_favicon', $options ) &&
					trim( $options['uc_favicon'] ) !== '' ) &&
				$options['uc_favicon'] !== $old_settings['uc_favicon']
			) {
				$options['uc_favicon'] = $old_settings['uc_favicon'];
			}

			/**
			 * If any other Whitelisted user roles updated which cu donot have access
			 * [Downgraded Customer Case: He can only disable roles, which were enabled earier]
			 * Logic: Updated (child) user array should be subset of existing (parent) value
			 * * * revert to old values
			 */
			// Administrator is always allowed, so make it part of parent array
			if ( ! is_array( $old_settings['uc_whitelisted_roles'] ) ) {
				settype( $old_settings['uc_whitelisted_roles'], 'array' );
			}
			$old_settings['uc_whitelisted_roles']['administrator'] = 'administrator';

			if (
				array_key_exists( 'uc_whitelisted_roles', $options ) &&
				is_array( $options['uc_whitelisted_roles'] ) &&
				array_intersect( $options['uc_whitelisted_roles'], $old_settings['uc_whitelisted_roles'] ) !== $options['uc_whitelisted_roles']
			) {
				$options['uc_whitelisted_roles'] = $old_settings['uc_whitelisted_roles'];
			}

			/**
			 * If key exists, and selected setting is not non-premium
			 * Or If key does not exist (because premium selected but disabled)
			 * * * retain old settings (even if it is premium)
			 */
			if ( ( array_key_exists( 'uc_timer_action', $options ) &&
				'no-action' !== $options['uc_timer_action'] ) ||
				! array_key_exists( 'uc_timer_action', $options )
			) {
				$options['uc_timer_action'] = $old_settings['uc_timer_action'];
			}

			if ( ( array_key_exists( 'uc_seo_title', $options ) &&
					trim( $options['uc_seo_title'] ) !== '' ) ||
				! array_key_exists( 'uc_seo_title', $options )
			) {
				$options['uc_seo_title'] = $old_settings['uc_seo_title'];
			}

			if ( ( array_key_exists( 'uc_seo_description', $options ) &&
					trim( $options['uc_seo_description'] ) !== '' ) ||
				! array_key_exists( 'uc_seo_description', $options )
			) {
				$options['uc_seo_description'] = $old_settings['uc_seo_description'];
			}

			if ( ( array_key_exists( 'uc_footer_scripts', $options ) &&
					trim( $options['uc_footer_scripts'] ) !== '' ) ||
				! array_key_exists( 'uc_footer_scripts', $options )
			) {
				$options['uc_footer_scripts'] = $old_settings['uc_footer_scripts'];
			}
		}

		foreach ( $options as $option_slug => $option_value ) {
			$sanitize_callback = $this->get_sanitize_callback( $option_slug );

			// If callback is set, call it
			if ( $sanitize_callback ) {
				$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
				continue;
			}
		}

		// Admin Notice after settings saved
		$message = __( 'Settings saved.', 'onecom-uc' ) .
			'<br/>' . __( 'Remember to delete cache in case you are using any caching plugin.', 'onecom-uc' );

		add_settings_error( 'onecom_under_construction', 'onecom_under_construction', $message, 'success' );

		return $options;
	}

	// Get sanitization callback for given option slug
	public function get_sanitize_callback( $slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}

		// Iterate over registered fields and see if we can find proper callback
		foreach ( $this->settings_fields as $options ) {
			foreach ( $options as $option ) {
				if ( $option['name'] !== $slug ) {
					continue;
				}

				// Return the callback name
				return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
			}
		}

		return false;
	}

	// Section HTML, displayed before the first option
	public function callback_section( $section ) {
		// echo '<h3 class="postbox postbox-header">' . $section['title'] . '</h3>';
	}

	// Get the value of a settings field
	public function get_option( $option, $section = ONECOM_UC_OPTION_FIELD, $default_value = '' ) {
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default_value;
	}

	// Get field description for display
	public function get_field_description( $args ) {

		if (
			! empty( $args['desc'] ) &&
			( 'uc_seo_title' === $args['id']
				|| 'uc_seo_description' === $args['id']
				|| 'uc_whitelisted_roles' === $args['id']
				|| 'uc_timer_action' === $args['id']
				|| 'uc_favicon' === $args['id']
				|| 'uc_footer_scripts' === $args['id']
				|| 'uc_exclude_pages' === $args['id'] )
		) {
			$desc = sprintf( '<p class="description"><span>%s</span></p>', $args['desc'] );
		} elseif ( ! empty( $args['desc'] ) ) {
			$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
		} else {
			$desc = '';
		}

		return $desc;
	}

	public function get_premium_tag_after_label( $args ) {

		$label = $args['label'];
		if ( $this->oc_premium() ) {
			return $label;
		}

		if (
			! empty( $args['label'] ) &&
			( 'uc_seo_title' === $args['id']
				|| 'uc_seo_description' === $args['id']
				|| 'uc_whitelisted_roles' === $args['id']
				|| 'uc_timer_action' === $args['id']
				|| 'uc_favicon' === $args['id']
				|| 'uc_footer_scripts' === $args['id']
				|| 'uc_exclude_pages' === $args['id'] )
		) {
			$label = $args['label'] . '<p class="tag_badge nmWP-badge ">' . apply_filters( 'onecom_premium_inline_badge', '', __( 'This is a Managed WordPress feature.', 'onecom-uc' ), 'mwp', $args['event_track_class'] ) . '</p>';

		}

		return $label;
	}
	/**
	 *  Displays a submit button via settings field with custom class
	 *  Useful if displaying submit button at multiple places
	 */
	public function callback_submit() {
		// Regular save button
		submit_button( __( 'Save', 'onecom-uc' ), 'uc-submit-button oc-uc-btn oc-regular-submit ocwp_ocmm_settings_saved_event' );

		// Bottom fixed button for mobile
		echo '<div class="oc_sticky_footer">' .
			get_submit_button( __( 'Save', 'onecom-uc' ), 'uc-submit-button oc-uc-btn ocwp_ocmm_settings_saved_event', 'submit', false ) .
		'</div>';

		// Alternate floating save button for desktop
		submit_button( __( 'Save', 'onecom-uc' ), 'uc-submit-button oc-uc-btn oc-uc-float-btn ocwp_ocmm_settings_saved_event' );
	}

	/**
	 * Displays a text field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_text( $args ) {
		$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

		if ( true !== $this->oc_premium() && 'uc_seo_title' === $args['id'] ) {
			$html = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s disabled="disabled" />', $type, $size, $args['section'], $args['id'], $value, $placeholder );
		} else {
			$html = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
		}
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a datetime field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_datetime( $args ) {

		$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = 'text';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

		$html  = sprintf( '<input type="%1$s" class="%2$s-datetime picker-datetime regular-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a url field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_url( $args ) {
		$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type        = 'url';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
		$html        = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
		$html       .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a checkbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_checkbox( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $class = 'checkbox '. $args["event_track_class"];
		$html = '<fieldset>';

		$html .= sprintf( '<label class="oc_switch_label" for="wpuf-%1$s[%2$s]">', $args['section'], $args['id'] );
		$html .= '<span class="oc_uc_switch">';
		$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="checkbox" class="'.$class.'" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ) );
		$html .= '<span class="oc_uc_slider"></span></span>';
		$html .= sprintf( '<span class="description">%s</span></label>', $args['desc'] );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a multicheckbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_multicheck( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

		$html  = '<fieldset>';
		$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );
		foreach ( $args['options'] as $key => $label ) {

            $eventClass = $args['event_track_class'];
            $modalClass = $args['modal_event_class'];

			$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';
			$html   .= sprintf( '<label class="oc_switch_label" for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$html   .= '<span class="oc_uc_switch">';

			if ( $this->oc_premium() === true && 'uc_whitelisted_roles' === $args['id'] ) {
				$html .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
			} else {
                $eventClass = ($key === 'administrator') ? '' : $eventClass;
                $modalClass = ($key === 'administrator') ? '' : $modalClass;
				$html .= sprintf( '<input type="checkbox" class="checkbox onecom_multicheckbox %1$s" data-upsell-btn-event="%2$s" id="wpuf-%3$s[%4$s][%5$s]" name="%3$s[%4$s][%5$s]" value="%5$s" %6$s readonly="readonly" />', $eventClass, $modalClass, $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
			}

			$html .= '<span class="oc_uc_slider"></span></span>';
			$html .= sprintf( '<span>%s</span></label><br/>', $label );
		}

		$html .= $this->get_field_description( $args );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a radio button for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_radio( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$html  = '<fieldset>';

		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
			$html .= sprintf( '%1$s</label><br/>', $label );
		}

		$html .= $this->get_field_description( $args );
		$html .= '</fieldset>';

		echo $html;
	}

	/**
	 * Displays a radio button for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_radio_image( $args ) {
		$value         = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$premium_badge = '<span class="badge_bg" style="position: absolute; top: 0; right: 0; padding: 4px 10px; background-color: #fff; color: #76B82A; font-size: 16px; line-height: 24px; z-index: 100; font-weight: 600;">' . __( 'Premium', 'validator' ) . '</span>';

		$html = '<div class="ocp-radio-image" >';

		foreach ( $args['options'] as $key => $label ) {
			$image        = ONECOM_UC_DIR_URL . 'assets/images/' . $args['options'][ $key ];
			$image_srcset = ONECOM_UC_DIR_URL . 'assets/images/' . $args['srcset'][ $key ];
			$html        .= sprintf( '<div><label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$count        = substr( $key, strpos( $key, '-' ) + 1 );
			// separate design access for premium and not premium cu
			if ( $this->oc_premium() === true && $count > 3 ) {
				$html .= sprintf(
					'<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />
					<img src="%5$s" title="" srcset="%5$s 300w, %6$s 194w" sizes="(max-width: 767px) 300px, 194px" />' . $premium_badge,
					$args['section'],
					$args['id'],
					$key,
					checked( $value, $key, false ),
					$image,
					$image_srcset
				);
			} elseif ( $this->oc_premium() !== true && $count > 3 ) {
                $eventClass = $args['event_track_class'];
                $modalClass = $args['modal_event_class'];
				$html .= sprintf(
					'<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s disabled="disabled" />
					<img src="%5$s" title="" srcset="%5$s 300w, %6$s 194w" sizes="(max-width: 767px) 300px, 194px" class="mm-radio '.$eventClass.'" data-upsell-btn-event="'.$modalClass.'"  />' . $premium_badge,
					$args['section'],
					$args['id'],
					$key,
					checked( $value, $key, false ),
					$image,
					$image_srcset
				);
			} else {
				$html .= sprintf(
					'<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />
					<img src="%5$s" title="" srcset="%5$s 300w, %6$s 194w" sizes="(max-width: 767px) 300px, 194px" />',
					$args['section'],
					$args['id'],
					$key,
					checked( $value, $key, false ),
					$image,
					$image_srcset
				);
			}

			$html .= '</label></div>';
		}

		$html .= '</div>';
		$html .= $this->get_field_description( $args );
		echo $html;
	}

	/**
	 * Displays a selectbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_select( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = sprintf( '<select class="%1$s regular-text" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

		foreach ( $args['options'] as $key => $label ) {
			if ( $this->oc_premium() === true ) {
				$html .= sprintf( '<option value="%s"%s >%s</option>', $key, selected( $value, $key, false ), $label );
			} else {
				$html .= sprintf( '<option value="%s"%s disabled="disabled">%s</option>', $key, selected( $value, $key, false ), $label );
			}
		}

		$html .= sprintf( '</select>' );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Custom callback to display multiselect dropdown for each public posts/cpt
	 * Selected posts/pages will be whitelisted from MM mode
	 */
	public function callback_exclude_multiselect( $args ) {

		// Get all public post types
		$post_types = get_post_types(
			array(
				'show_ui' => true,
				'public'  => true,
			),
			'objects'
		);

		$html = '';
		$html = '<fieldset>';

		// List all published post types except defaults
		foreach ( $post_types as $post_slug => $type ) {

			if ( ( 'attachment' === $post_slug ) || ( 'revision' === $post_slug ) || ( 'nav_menu_item' === $post_slug )
			) {
				continue;
			}

			$cpt_args = array(
				'posts_per_page' => 250,
				'post_type'      => $post_slug,
				'post_status'    => 'publish',
			);

			$posts_array = get_posts( $cpt_args );

			if ( ! empty( $posts_array ) ) {
				/**
				 * Idea behind Unique field id to store CPT inside 'onecom_under_construction_info' option:
				 * We will use settings array id (actually name), and then subarray with cpt name
				 * example for page: onecom_under_construction_info['exclude_pages']['post']
				 * Here we have: $args['section'], $args['id'], [$post_slug]
				 */
				$size = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

				// prepare html with select dropdown for each post types
				$html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $post_slug );
				/* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText */
				$html .= __( $type->labels->name, 'onecom-uc' );
				$html .= sprintf( '</label><br /><select class="%1$s oc-select2-multi regular-text" name="%2$s[%3$s][%4$s][]" id="%2$s[%3$s][%4$s][]" multiple="multiple">', $size, $args['section'], $args['id'], $post_slug );

				$current_value = null;
				$exclude_ids   = $this->get_option( $args['id'], $args['section'] );

				// if current post/page found in stored options data, pass in selected();
				if ( ! empty( $exclude_ids )
					&& key_exists( $post_slug, $exclude_ids )
					&& in_array( 'all-' . $post_slug, $exclude_ids[ $post_slug ], true ) ) {
					$current_value = 'all-' . $post_slug;
				}

				if ( $this->oc_premium() === true ) {
					$html .= sprintf( '<option value="%s"%s >%s</option>', 'all-' . $post_slug, selected( $current_value, 'all-' . $post_slug, false ), __( 'All', 'onecom-uc' ) . ' ' . $type->labels->name );
				} else {
					$html .= sprintf( '<option value="%s"%s disabled="disabled">%s</option>', 'all-' . $post_slug, selected( $current_value, 'all-' . $post_slug, false ), __( 'All', 'onecom-uc' ) . ' ' . $type->labels->name );
				}

				foreach ( $posts_array as $post_values ) {

					// if current post/page found in stored options data, pass in selected();
					if ( ! empty( $exclude_ids )
						&& key_exists( $post_slug, $exclude_ids )
						&& in_array( (string) $post_values->ID, $exclude_ids[ $post_slug ], true ) ) {
						$current_value = $post_values->ID;
					}

					if ( $this->oc_premium() === true ) {
						$html .= sprintf( '<option value="%s"%s >%s</option>', $post_values->ID, selected( $current_value, $post_values->ID, false ), esc_html( $post_values->post_title ) );
					} else {
						$html .= sprintf( '<option value="%s"%s disabled="disabled">%s</option>', $post_values->ID, selected( $current_value, $post_values->ID, false ), esc_html( $post_values->post_title ) );
					}
				}

				$html .= sprintf( '</select><br /><br />' );
			}
		}

		$html .= $this->get_field_description( $args );

		$html .= '</fieldset>';
		echo $html;
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_textarea( $args ) {

		$value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

		if ( true !== $this->oc_premium() && ( 'uc_seo_description' === $args['id'] || 'uc_footer_scripts' === $args['id'] ) ) {
			$html = sprintf( '<textarea onkeyup="onload(this)" rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s disabled="disabled">%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
		} else {
			$html = sprintf( '<textarea onkeyup="onload(this)" rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
		}
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays the html for a settings field
	 *
	 * @param array   $args settings field args
	 * @return string
	 */
	public function callback_html( $args ) {
		echo $this->get_field_description( $args );
	}

	/**
	 * Displays a rich text textarea for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_wysiwyg( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '516px';

		echo '<div style="max-width: ' . $size . ';">';

		// @later-todo - Following css is not getting applied to description editor
		$editor_style = '<style type="text/css">
           .onecom_under_construction_info-uc_description p{
			font-family: sans-serif;
			font-size: 15px;}
           </style>';

		$editor_settings = array(
			'teeny'         => true,
			'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
			'textarea_rows' => 10,
			'editor_css'    => $editor_style,
		);

		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
			$editor_settings = array_merge( $editor_settings, $args['options'] );
		}

		wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

		echo '</div>';

		echo $this->get_field_description( $args );
	}

	/**
	 * Displays a color picker field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_color( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" data-alpha-enabled="true"/>', $size, $args['section'], $args['id'], $value, $args['std'] );
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Displays a file upload field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function callback_file( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$id    = $args['section'] . '[' . $args['id'] . ']';
		$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

		if ( ( true === $this->oc_premium() ) || 'uc_favicon' !== $args['id'] ) {
			$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
		} else {
			$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" readonly="readonly" />', $size, $args['section'], $args['id'], $value );
			$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" disabled="disabled" />';
		}

		// Show image thumb if URL found, else create empty img box to use later
		if ( isset( $value ) && ! empty( $value ) ) {
			$html .= sprintf( '<br /><div class="img-box"><img class="image-thumb" src="%1$s" /><button class="img-delete" title="Remove image" type="button">x</button></div>', $value );
		} else {
			$html .= sprintf( '<br /><div class="img-box"></div>', $value );
		}
		$html .= $this->get_field_description( $args );

		echo $html;
	}

	/**
	 * Scripts for file upload, color picker etc
	 */
	public function script() {
		?>
		<script>
			jQuery(document).ready(function($) {
				/* Initiate Color Picker and
				 * Trigger form change after color change/clear
				 * If any new change found, enable else disable save button
				 */
				//$('.wp-color-picker-field').attr('data-alpha-enabled',true);//set to enable the opacity bar in color picker
				$('.wp-color-picker-field').wpColorPicker({
					change: function(event, ui){
						var theColor = ui.color.toString();
						// First time color change not detected, so set value mannually
						$(this).closest('span').find(".wp-color-picker").val(theColor);
						$('#uc-form').trigger("change");
					},
					clear: function (event) {
						$('#uc-form').trigger("change");
					}
				});

				$('.wpsa-browse').on('click', function(event) {
					event.preventDefault();

					var self = $(this);

					// Create the media frame.
					var file_frame = wp.media.frames.file_frame = wp.media({
						title: self.data('uploader_title'),
						button: {
							text: self.data('uploader_button_text'),
						},
						multiple: false
					});

					file_frame.on('select', function() {
						attachment = file_frame.state().get('selection').first().toJSON();
						self.prev('.wpsa-url').val(attachment.url).change();
					});

					// Finally, open the modal
					file_frame.open();
				});
			});
		</script>
		<?php
		$this->_style_fix();
	}

	public function _style_fix() {
		global $wp_version;

		if ( version_compare( $wp_version, '3.8', '<=' ) ) {
			?>
			<style type="text/css">
				/** WordPress 3.8 Fix **/
				.form-table th {
					padding: 20px 10px;
				}

				.onecom_under_construction_info-uc_description,
				.onecom_under_construction_info-uc_description p {
					font-family: sans-serif;
					font-size: 15px;
				}

				#wpbody-content .metabox-holder {
					padding-top: 5px;
				}
			</style>
			<?php
		}
	}
}
