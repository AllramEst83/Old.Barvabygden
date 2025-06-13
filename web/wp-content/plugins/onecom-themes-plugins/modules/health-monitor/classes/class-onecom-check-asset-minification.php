<?php
declare( strict_types=1 );

/**
 * Class Onecom_Check_Asset_Minification
 * Deals with features related to frontend asset
 */
class OnecomCheckAssetMinification extends OnecomHealthMonitor {

	/**
	 * check_minification, check if css and js files are minified or not
	 * @return array
	 */
	public function check_minification(): array {
		$html    = $this->get_page_content();
		$assets  = $this->get_assets( $html );
		$bloated = $this->bloated_assets( $assets );
		if ( $bloated && ( count( $bloated ) > 0 ) ) {
			$result         = $this->format_result( $this->flag_open, __( 'Some of your static assets are not minified.', 'onecom-wp' ), __( 'You could use a minified version of following files to further optimize your site.', 'onecom-wp' ) );
			$result['list'] = $bloated;
		} else {
			$result = $this->format_result( $this->flag_resolved, __( 'All your static assets are minified.', 'onecom-wp' ) );
		}

		return $result;
	}

	/**
	 * Get page html of homepage
	 * @return string
	 */
	public function get_page_content(): string {
		$url      = home_url();
		$response = wp_remote_get( $url );

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Get a list of CSS and JS urls
	 *
	 * @param string $html
	 *
	 * @return array
	 */
	public function get_assets( string $html ): array {

		$pattern = '~(?<=href=\'|")[^\'|"]+\.(css|js)~';
		preg_match_all( $pattern, $html, $matches );

		return $matches;
	}

	/**
	 * Check if all the css and js files are minified
	 *
	 * @param array $assets
	 *
	 * @return array
	 */
	public function bloated_assets( array $assets ): array {
		$bloated_resources = array();
		if ( ! $assets ) {
			return array();
		}
		foreach ( $assets[0] as $asset ) {
			$pattern = '/{\s+((.*)\s+(.*))*\s+}/';
			$res     = wp_remote_get( $asset );
			$content = wp_remote_retrieve_body( $res );
			preg_match_all( $pattern, $content, $matches );
			if ( count( $matches[0] ) > 0 ) {
				$bloated_resources[] = $asset;
			}
		}

		return $bloated_resources;
	}
}
