<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Onecom_Themes_Loader {
	public function __construct() {
		// Hook to enqueue the JavaScript function in the admin footer
		add_action( 'admin_footer' , [ $this , 'schedule_theme_fetch' ] );

		// Handle the AJAX request for fetching themes
		add_action( 'wp_ajax_oc_prefetch_themes' , [ $this , 'fetch_themes' ] );
	}

	public function schedule_theme_fetch(): void {
		// Ensure this only runs in the admin area
		if ( ! is_admin() ) {
			return;
		}

		// Only trigger if the transient is not set
		if ( ! get_site_transient( 'onecom_themes' ) ) {
			?>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: "action=oc_prefetch_themes"
                    })
                        .then(response => response.json()) // Parse response as JSON
                        .then(data => {
                            if (data && data.success) {
                                console.log("Success:", data.data.message);
                            } else {
                                console.error(data);
                            }
                        })
                        .catch(error => console.error("Error in fetching themes:", error));
                });
            </script>
			<?php
		}
	}

	public function fetch_themes(): void {
		// Prevent multiple executions by setting a transient lock
		if ( get_transient( 'oc_theme_fetch_lock' ) ) {
			wp_send_json_success( [ 'message' => 'Lock active, skipping execution.' ] );

			return;
		}

		// Set a temporary lock to prevent duplicate requests
		set_transient( 'oc_theme_fetch_lock' , true , 5 * MINUTE_IN_SECONDS );

		// Fetch themes and process them
		$theme_data      = onecom_fetch_themes( 1 , true );
		$oci_theme_fetch = $theme_data->collection;
		merge_classic_wp_themes( $oci_theme_fetch );

		// Log execution for debugging
		error_log( 'oc_prefetch_themes executed' );

		// Remove the lock after execution
		delete_transient( 'oc_theme_fetch_lock' );

		// Send JSON response indicating success
		wp_send_json_success( [ 'message' => 'Theme fetch completed.' ] );
	}
}
