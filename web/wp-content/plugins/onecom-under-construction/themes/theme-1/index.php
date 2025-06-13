<?php
// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Render View object
$html           = new OCUC_Render_Views();
$uc_option_data = $html->get_uc_option();
$newsletter     = new OCUC_Newsletter();
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>

	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="description" content="<?php echo $html->uc_meta_description(); ?>" />
	<title> <?php echo $html->uc_meta_title(); ?> </title>
	<?php echo $html->uc_favicon(); ?>

	<!-- Include CSS -->
	<link href="<?php echo ONECOM_UC_DIR_URL; ?>assets/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo ONECOM_UC_DIR_URL; ?>themes/theme-1/style.css?ver=<?php echo ONECOM_UC_VERSION; ?>" rel="stylesheet">

	<!-- Include JS -->
	<script src="<?php echo ONECOM_UC_DIR_URL; ?>assets/js/jquery.min.js"></script>
	<script src="<?php echo ONECOM_UC_DIR_URL; ?>assets/js/script.js?ver=<?php echo ONECOM_UC_VERSION; ?>"></script>

	<!-- WordPress meta tag generator -->
	<?php echo get_the_generator( 'html' ); ?>

	<!-- Design Customization -->
	<style>
		<?php
		if ( strlen( $html->uc_bg_color() ) || strlen( $html->uc_bg_image() ) ) {
			?>
		body {
			background: <?php echo $html->uc_bg_color(); ?> url('<?php echo $html->uc_bg_image(); ?>') no-repeat top center fixed;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			-o-background-size: cover;
			background-size: cover;
		}

			<?php
		}
		?>

		/** Firefox - Mobile only CSS fix for placeholder padding */
		@-moz-document url-prefix() {
			.ocuc-page .oc-captcha-wrap .oc-captcha-val {
				margin: 1px;
				border: 1px solid #ccc;
				height: 24px;
			}

			.ocuc-page .newsletter .form-control.oc-newsletter-input {
				padding: 0.8rem 1.2rem;
			}
		}

		<?php
		if ( strlen( $uc_option_data['uc_primary_color'] ) ) {
			?>
		.ocuc-page .newsletter .content .btn {
			background-color: <?php echo $uc_option_data['uc_primary_color']; ?>;
		}

		.ocuc-page .ocuc-site-title-logo h1 {
			color: <?php echo $uc_option_data['uc_primary_color']; ?>;
		}

			<?php
		}
		// include custom css
		echo $html->uc_custom_css();
		?>
	</style>

	<!-- Include ajax, timer js, and analytics js -->
	<script>
		var oc_ajax = {
			ajaxurl: '<?php echo admin_url( 'admin-ajax.php' ); ?>'
		}
	</script>
	<?php
	echo $html->uc_enqueue_timer_js();
	echo $html->uc_scripts();
	?>
</head>

<body>
	<div class="wrapper ocuc-page">
		<div class="container">
			<main class="ocuc-content-box">
				<div class="row">
					<div class="col-md-12">

						<!-- Display logo or site title -->
						<div class="ocuc-site-title-logo">
							<?php echo $html->uc_logo_title(); ?>
						</div>

						<!-- Display the headline -->
						<?php if ( ! empty( $uc_option['uc_headline'] ) ) { ?>
							<h2 class="ocuc-headline">
								<?php echo $html->uc_headline(); ?>
							</h2>
						<?php } ?>

						<?php
						/**
						 * Show countdown timer
						 * * if timer is on
						 * * AND a valid future date
						 * * OR past date with no action
						 */
						if ( isset( $uc_option['uc_timer'] ) &&
							strtotime( $uc_option['uc_timer'] ) !== false &&
							'on' === $uc_option['uc_timer_switch'] &&
							( strtotime( $uc_option['uc_timer'] ) >= strtotime( current_time( 'Y-m-d H:i:s' ) ) ||
								( strtotime( $uc_option['uc_timer'] ) < strtotime( current_time( 'Y-m-d H:i:s' ) ) &&
								'no-action' === $uc_option['uc_timer_action'] ) )
						) {
							?>
							<div class="ocuc-timer">
								<?php echo $html->uc_timer(); ?>
							</div>
							<?php
						}
						?>

						<?php
						$newsletter->subscriber_form();
						?>

						<div class="ocuc-description">
							<?php
							echo $html->uc_description();
							?>
						</div>

						<?php
						if ( ! empty( $uc_option['uc_copyright'] ) ) {
							?>
							<div class="ocuc-copyright">
								<?php
								echo $html->uc_copyright();
								?>
							</div>
						<?php } ?>
					</div>
				</div>
			</main>

			<?php
			// include social icons
			if ( ! empty( $html->uc_social_icons() ) ) {
				?>
				<div class="row">
					<div class="col-sm-12 col-md-12">
						<div class="uc-social-container">
							<div class="ocuc-social-icons">
								<ul>
									<?php
									echo $html->uc_social_icons();
									?>
								</ul>
							</div>
						</div>

					</div>
				</div>
			<?php } ?>

		</div>
	</div>
	<?php
	echo $html->uc_footer_scripts();
	// allow footer scripts in customizer to make autofocus favicon work
	if ( is_customize_preview() ) {
		wp_footer();
	}
	?>
</body>
</html>