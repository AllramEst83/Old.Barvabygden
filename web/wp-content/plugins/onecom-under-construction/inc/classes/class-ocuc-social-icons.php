<?php

/**
 * Generate SVG Social Icons
 *
 * @since      0.1.0
 * @package    Under_Construction
 * @subpackage OCUC_Social_Icons
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Social_Icons {


	/**
	 * Create social icons html based on url available
	 */
	public function uc_get_social_icons( $uc_option_data ) {
		$uc_option = $uc_option_data;
		ob_start();

		if ( isset( $uc_option['uc_facebook_url'] ) && ! empty( $uc_option['uc_facebook_url'] ) ) {
			$fb_url = $uc_option['uc_facebook_url'];
			printf(
				'
                        <li class="%s">
                            <a href="%s" target="_blank">%s</a>
                        </li>',
				'facebook',
				filter_var( $fb_url, FILTER_SANITIZE_URL ),
				$this->uc_svg_social_icons( 'facebook' )
			);
		}

		if ( isset( $uc_option['uc_twitter_url'] ) && ! empty( $uc_option['uc_twitter_url'] ) ) {
			$twitter_url = $uc_option['uc_twitter_url'];
			printf(
				'
                        <li class="%s">
                            <a href="%s" target="_blank">%s</a>
                        </li>',
				'twitter',
				filter_var( $twitter_url, FILTER_SANITIZE_URL ),
				$this->uc_svg_social_icons( 'twitter' )
			);
		}

		if ( isset( $uc_option['uc_instagram_url'] ) && ! empty( $uc_option['uc_instagram_url'] ) ) {
			$instagram_url = $uc_option['uc_instagram_url'];
			printf(
				'
                        <li class="%s">
                            <a href="%s" target="_blank">%s</a>
                        </li>',
				'instagram',
				filter_var( $instagram_url, FILTER_SANITIZE_URL ),
				$this->uc_svg_social_icons( 'instagram' )
			);
		}

		if ( isset( $uc_option['uc_linkedin_url'] ) && ! empty( $uc_option['uc_linkedin_url'] ) ) {
			$linkedin_url = $uc_option['uc_linkedin_url'];
			printf(
				'
                        <li class="%s">
                            <a href="%s" target="_blank">%s</a>
                        </li>',
				'linkedin',
				filter_var( $linkedin_url, FILTER_SANITIZE_URL ),
				$this->uc_svg_social_icons( 'linkedin' )
			);
		}

		if ( isset( $uc_option['uc_youtube_url'] ) && ! empty( $uc_option['uc_youtube_url'] ) ) {
			$youtube_url = $uc_option['uc_youtube_url'];
			printf(
				'
                        <li class="%s">
                            <a href="%s" target="_blank">%s</a>
                        </li>',
				'youtube',
				filter_var( $youtube_url, FILTER_SANITIZE_URL ),
				$this->uc_svg_social_icons( 'youtube' )
			);
		}

		$social_html = ob_get_clean();
		return $social_html;
	}

	public function uc_svg_social_icons( $id ) {
		if ( ! ( isset( $id ) && strlen( $id ) ) ) {
			return;
		}
		// @later-todo - youtube ending li issue in test coverage
		$svg_tag_start = '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">';

		$svg_tag_end = '</svg>';

		switch ( $id ) {
			case 'facebook':
				return $svg_tag_start . '
				<path d="M15 0C6.71571 0 0 6.71571 0 15C0 23.2843 6.71571 30 15 30C15.1073 30 15.2142 29.9982 15.321 29.996V20.1065H11.9989V15.954H15.321V13.4625C15.321 10.0416 17.2519 7.64891 20.3041 7.64891C21.7658 7.64891 22.7956 7.64891 23.6261 7.64891V11.8015H21.9651C20.3057 11.8015 19.4736 12.632 19.4736 14.293V15.954H23.6261L22.7956 20.1065H19.4736V29.3215C25.5728 27.4182 30 21.7262 30 15C30 6.71571 23.2843 0 15 0Z" fill="url(#paint0_linear)"/><defs><linearGradient id="paint0_linear" x1="24.8706" y1="27.4658" x2="3.8995" y2="0.980839" gradientUnits="userSpaceOnUse"><stop stop-color="#3B5998"/><stop offset="1" stop-color="#336699"/></linearGradient></defs>' . $svg_tag_end;

			case 'instagram':
				return $svg_tag_start . '<path d="M14.5158 17.9593C16.1696 17.9948 17.5075 16.6732 17.5424 15.0657C17.5784 13.409 16.2414 12.0395 14.5837 12.0405C12.9634 12.0395 11.6462 13.3434 11.6236 14.956C11.6002 16.6128 12.926 17.9253 14.5158 17.9593Z" fill="url(#paint0_linear)"/>
				<path d="M19.0925 15.5426C19.0153 16.1952 18.8047 16.8023 18.4623 17.3636C18.1199 17.9248 17.6751 18.3906 17.1316 18.7608C15.7219 19.7213 13.8655 19.8056 12.3692 18.9657C11.6131 18.542 11.0194 17.9506 10.6047 17.1878C9.98746 16.0516 9.88885 14.8583 10.2508 13.6201C9.78731 13.6201 9.3267 13.6201 8.85983 13.6201V13.6593C8.85983 15.8212 8.85936 17.9832 8.85983 20.1451C8.85983 20.4558 9.12895 20.7235 9.44014 20.7235C12.8686 20.724 16.2965 20.724 19.725 20.7235C20.0391 20.7235 20.3067 20.4568 20.3067 20.1432C20.3072 17.9841 20.3067 15.8241 20.3067 13.6656V13.6206H18.9124C19.1092 14.2498 19.1696 14.89 19.0925 15.5426Z" fill="url(#paint1_linear)"/>
				<path d="M17.7786 11.0535C17.8615 11.2967 18.0846 11.4461 18.3575 11.4476C18.5792 11.4476 18.8014 11.4471 19.0231 11.4471V11.4443C19.2634 11.4443 19.5043 11.4491 19.7452 11.4433C20.0545 11.4356 20.3063 11.1704 20.3063 10.8606C20.3063 10.3995 20.3063 9.93792 20.3063 9.47679C20.3063 9.14591 20.0444 8.88306 19.7145 8.88253C19.2544 8.88253 18.7942 8.88206 18.334 8.88253C18.0046 8.883 17.7422 9.14685 17.7422 9.47773C17.7417 9.93499 17.7402 10.3923 17.7436 10.8496C17.7441 10.918 17.7571 10.9889 17.7786 11.0535Z" fill="url(#paint2_linear)"/>
				<path d="M14.5841 0.415894C6.52951 0.415894 0 6.94541 0 15C0 23.0546 6.52951 29.5841 14.5841 29.5841C22.6387 29.5841 29.1682 23.0546 29.1682 15C29.1682 6.94541 22.6387 0.415894 14.5841 0.415894ZM21.8884 20.8457C21.8779 20.9108 21.8683 20.9759 21.8563 21.0415C21.7395 21.6415 21.2521 22.1389 20.6555 22.2659C20.5803 22.2821 20.5042 22.2932 20.428 22.3056H8.74019C8.70908 22.3013 8.6775 22.295 8.64679 22.2912C8.00373 22.2084 7.46361 21.7176 7.32041 21.0846C7.30365 21.0108 7.2931 20.9357 7.27968 20.8619V9.14109C7.28402 9.11378 7.28976 9.08648 7.29357 9.05923C7.38597 8.40322 7.8648 7.88226 8.5122 7.73478C8.58164 7.71896 8.653 7.70941 8.72337 7.69646H20.4447C20.472 7.70126 20.4983 7.70748 20.5261 7.71082C21.1888 7.80181 21.7289 8.30982 21.8572 8.96437C21.8692 9.02806 21.8783 9.09269 21.8883 9.15638V20.8457H21.8884Z" fill="url(#paint3_linear)"/>
				<defs>
				<linearGradient id="paint0_linear" x1="2.1088" y1="2.52615" x2="26.1489" y2="26.5663" gradientUnits="userSpaceOnUse">
				<stop stop-color="#517FA6"/>
				<stop offset="0.4075" stop-color="#4F7BA4"/>
				<stop offset="0.7829" stop-color="#48709E"/>
				<stop offset="1" stop-color="#426699"/>
				</linearGradient>
				<linearGradient id="paint1_linear" x1="1.10964" y1="3.52531" x2="25.1498" y2="27.5654" gradientUnits="userSpaceOnUse">
				<stop stop-color="#517FA6"/>
				<stop offset="0.4075" stop-color="#4F7BA4"/>
				<stop offset="0.7829" stop-color="#48709E"/>
				<stop offset="1" stop-color="#426699"/>
				</linearGradient>
				<linearGradient id="paint2_linear" x1="6.74745" y1="-2.11243" x2="30.7876" y2="21.9276" gradientUnits="userSpaceOnUse">
				<stop stop-color="#517FA6"/>
				<stop offset="0.4075" stop-color="#4F7BA4"/>
				<stop offset="0.7829" stop-color="#48709E"/>
				<stop offset="1" stop-color="#426699"/>
				</linearGradient>
				<linearGradient id="paint3_linear" x1="2.10956" y1="2.52545" x2="26.1497" y2="26.5656" gradientUnits="userSpaceOnUse">
				<stop stop-color="#517FA6"/>
				<stop offset="0.4075" stop-color="#4F7BA4"/>
				<stop offset="0.7829" stop-color="#48709E"/>
				<stop offset="1" stop-color="#426699"/>
				</linearGradient>
				</defs>' . $svg_tag_end;

			case 'linkedin':
				return $svg_tag_start . '<path d="M16.0753 12.4899V12.4591C16.0694 12.4691 16.0608 12.4802 16.0549 12.4899H16.0753Z" fill="url(#paint0_linear)"/>
				<path d="M15.1682 0C6.88393 0 0.168213 6.71571 0.168213 15C0.168213 23.2843 6.88393 30 15.1682 30C23.4525 30 30.1682 23.2843 30.1682 15C30.1682 6.71571 23.4525 0 15.1682 0ZM11.0374 20.8337H7.7938V11.0762H11.0374V20.8337ZM9.41619 9.74456H9.39431C8.30605 9.74456 7.60114 8.99456 7.60114 8.05763C7.60114 7.10026 8.32745 6.37209 9.4371 6.37209C10.5472 6.37209 11.2298 7.10026 11.2512 8.05763C11.2512 8.99456 10.5473 9.74456 9.41619 9.74456ZM22.7353 20.8339H19.4917V15.6134C19.4917 14.3021 19.0221 13.4069 17.8482 13.4069C16.9518 13.4069 16.4185 14.01 16.1835 14.5933C16.0979 14.8021 16.076 15.0921 16.076 15.3843V20.8337H12.832C12.832 20.8337 12.8753 11.9918 12.832 11.0762H16.0759V12.4591C16.5067 11.7953 17.2768 10.8468 18.9998 10.8468C21.135 10.8468 22.7352 12.2412 22.7353 15.2383V20.8339H22.7353Z" fill="url(#paint1_linear)"/>
				<defs>
				<linearGradient id="paint0_linear" x1="0.719965" y1="-4.69878" x2="34.057" y2="32.604" gradientUnits="userSpaceOnUse">
				<stop stop-color="#517FA6"/>
				<stop offset="1" stop-color="#426699"/>
				</linearGradient>
				<linearGradient id="paint1_linear" x1="-1.47202" y1="-3.62121" x2="32.8154" y2="34.7481" gradientUnits="userSpaceOnUse">
				<stop stop-color="#517FA6"/>
				<stop offset="1" stop-color="#426699"/>
				</linearGradient>
				</defs>' . $svg_tag_end;

			case 'twitter':
				return $svg_tag_start . '<path d="M15.1682 0C6.88393 0 0.168213 6.71571 0.168213 15C0.168213 23.2843 6.88393 30 15.1682 30C23.4525 30 30.1682 23.2843 30.1682 15C30.1682 6.71571 23.4525 0 15.1682 0ZM22.4705 11.2544C22.4776 11.4162 22.4809 11.5791 22.4809 11.7424C22.4809 16.721 18.6918 22.4617 11.7616 22.4617C9.63429 22.4617 7.65411 21.8384 5.98635 20.769C6.28122 20.804 6.58128 20.8219 6.88507 20.8219C8.65047 20.8219 10.2746 20.2195 11.564 19.2089C9.91566 19.1787 8.52445 18.0891 8.04475 16.5924C8.27472 16.6368 8.51071 16.6603 8.75376 16.6603C9.09745 16.6603 9.43035 16.6141 9.74644 16.5283C8.02275 16.1817 6.72405 14.6593 6.72405 12.8342C6.72405 12.8182 6.72405 12.8021 6.72441 12.7865C7.2325 13.0686 7.81363 13.2383 8.43092 13.258C7.41998 12.5826 6.75496 11.4293 6.75496 10.1224C6.75496 9.43124 6.94058 8.78412 7.26492 8.22794C9.12318 10.5074 11.8995 12.0071 15.0307 12.1644C14.9663 11.8881 14.9331 11.6007 14.9331 11.3059C14.9331 9.22514 16.6198 7.53833 18.7003 7.53833C19.7839 7.53833 20.7631 7.9961 21.4501 8.72819C22.3086 8.55897 23.1145 8.24547 23.8425 7.81344C23.5611 8.6939 22.9643 9.43196 22.186 9.89793C22.9483 9.80735 23.6748 9.60492 24.3499 9.30487C23.8452 10.0605 23.2064 10.7243 22.4705 11.2544Z" fill="url(#paint0_linear)"/>
				<defs>
				<linearGradient id="paint0_linear" x1="0.880753" y1="-0.912311" x2="23.9308" y2="24.7592" gradientUnits="userSpaceOnUse">
				<stop stop-color="#11A4DC"/>
				<stop offset="0.5051" stop-color="#0F94C9"/>
				<stop offset="1" stop-color="#0C82B4"/>
				</linearGradient>
				</defs>' . $svg_tag_end;

			case 'youtube':
				return $svg_tag_start . '<path d="M12.875 18.4441L18.615 15L12.875 11.556V18.4441Z" fill="url(#paint0_linear)"/>
				<path d="M15.1682 0C6.88393 0 0.168213 6.71571 0.168213 15C0.168213 23.2843 6.88393 30 15.1682 30C23.4525 30 30.1682 23.2843 30.1682 15C30.1682 6.71571 23.4525 0 15.1682 0ZM24.3495 15.7405C24.3495 17.3259 24.1658 18.9107 24.1658 18.9107C24.1658 18.9107 23.9861 20.2607 23.4362 20.8543C22.7376 21.6338 21.9553 21.6384 21.5965 21.6843C19.0267 21.8812 15.1682 21.8881 15.1682 21.8881C15.1682 21.8881 10.3936 21.8416 8.92473 21.6912C8.51602 21.6096 7.59933 21.6332 6.9002 20.8537C6.34974 20.2601 6.17064 18.9101 6.17064 18.9101C6.17064 18.9101 5.98695 17.3258 5.98695 15.7399V14.2543C5.98695 12.6695 6.17064 11.0847 6.17064 11.0847C6.17064 11.0847 6.35028 9.73401 6.9002 9.13938C7.59933 8.35986 8.38114 8.35528 8.7399 8.3111C11.3092 8.11193 15.1636 8.11193 15.1636 8.11193H15.1722C15.1722 8.11193 19.0267 8.11193 21.5965 8.3111C21.9553 8.35528 22.7376 8.35992 23.4362 9.13938C23.9867 9.73407 24.1658 11.0847 24.1658 11.0847C24.1658 11.0847 24.3495 12.6695 24.3495 14.2549V15.7405Z" fill="url(#paint1_linear)"/>
				<defs>
				<linearGradient id="paint0_linear" x1="1.53929" y1="2.51632" x2="26.4428" y2="27.4198" gradientUnits="userSpaceOnUse">
				<stop stop-color="#DD272D"/>
				<stop offset="0.5153" stop-color="#CA2429"/>
				<stop offset="1" stop-color="#B22025"/>
				</linearGradient>
				<linearGradient id="paint1_linear" x1="2.11195" y1="1.94373" x2="27.0155" y2="26.8472" gradientUnits="userSpaceOnUse">
				<stop stop-color="#DD272D"/>
				<stop offset="0.5153" stop-color="#CA2429"/>
				<stop offset="1" stop-color="#B22025"/>
				</linearGradient>
				</defs>' . $svg_tag_end;

			default:
				return;
		}
	}
}
