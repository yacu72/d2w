<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://mauro.com
 * @since      1.0.0
 *
 * @package    D2w
 * @subpackage D2w/admin/partials
 */
?>

	<?php	
		// If this file is called directly, abort.
		if ( ! defined( 'WPINC' ) ) die;
	?>
	
	<?php
	/**
	 * This form hold's all the calls related to migration functions.
	 */
	?>

	<div class="wrap">
		<h2>D2W <?php _e(' Options', $this->plugin_name); ?></h2>

		<form method="" name="d2w_settings" action="options.php">
			<input class="button button-migrate-pages" type="submit" value="Migrate pages">
		</form>
	</div>
