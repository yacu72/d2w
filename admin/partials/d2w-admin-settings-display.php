<?php

/**
 * Provide input fields for save settings values.
 *
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
	 * Use this area to call functions.
	 */

	$migrateSettings = new d2w_Migrate_Settings;
	$default = $migrateSettings->d2w_migrate_settings_values();

	?>

	<input value="<?php echo $default['images-url']; ?>" class="settings-input" type="text" data-name="images-url" placeholder="Images URL" >
	<description><?php _e( 'Load images from live site.', 'd2w' ); ?></description>
	<hr>

	<input disabled value="<?php echo $default['images-folder-path']; ?>" class="settings-input" type="text" data-name="images-folder-path" placeholder="Images Folder Path" >
	<description><?php _e( 'Load images from a folder.', 'd2w' ); ?></description>								
	<hr>

	<input data-action="migrate-settings" class="button button-migrate-settings" type="submit" value="Save Settings">