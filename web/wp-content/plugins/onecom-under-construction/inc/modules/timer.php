<?php
ob_start();
?>
<div class="timer">
	<div>
		<span class="count-number" id="counter-day"></span>
		<div class="smalltext"><?php _e( 'Days', 'onecom-uc' ); ?></div>
	</div>
	<div>
		<span class="count-number" id="counter-hour"></span>
		<div class="smalltext"><?php _e( 'Hours', 'onecom-uc' ); ?></div>
	</div>
	<div>
		<span class="count-number" id="counter-minute"></span>
		<div class="smalltext"><?php _e( 'Minutes', 'onecom-uc' ); ?></div>
	</div>
	<div>
		<span class="count-number" id="counter-second"></span>
		<div class="smalltext"><?php _e( 'Seconds', 'onecom-uc' ); ?></div>
	</div>
	<p id="time-up"></p>
</div>
<?php

$html = ob_get_clean();
