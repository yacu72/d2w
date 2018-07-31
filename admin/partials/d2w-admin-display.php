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
		$migrateUsers = new d2w_Migrate_Users;
		$migratePost = new d2w_Migrate_Post_Types;
		//$types_options = $migratePost->d2w_migrate_post_types_options();

		$drupal_node_types = $migratePost->d2w_migrate_drupal_node_types_list();
		
	?>

	<div class="wrap">
		<h2>D2W <?php _e(' Options', $this->plugin_name); ?></h2>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">

						<form class="migrate-form" method="post">

							<div class="postbox">
				        <button type="button" class="handlediv button-link" aria-expanded="true">
				            <span class="screen-reader-text"><?php _e('Toggle panel'); ?></span>
				            <span class="toggle-indicator" aria-hidden="true"></span>
				        </button>
								<h3 class="hndle ui-sortable-handle">
									<span><?php _e( 'Migrate Users', 'd2w' ); ?></span></h3>

								<div class="inside">

									<label><?php _e('Total Users to migrate: ','w2d'); ?></label><span><?php echo $migrateUsers->d2w_migrate_users_counter(); ?></span><br>  

									<?php if ( !get_site_option('d2w_users_migrated') ) { ?>
										<input name="migrate-users-button" class="button button-migrate" type="submit" value="Migrate Users">	
									<?php } else { ?>
										<?php _e('User Migration completed', 'd2w'); ?>
									<?php } ?>

								</div>
							</div>

							<?php foreach ($drupal_node_types as $type => $name) { ?>

								<div class="postbox closed">
					        <button type="button" class="handlediv button-link" aria-expanded="true">
					            <span class="screen-reader-text"><?php _e('Toggle panel'); ?></span>
					            <span class="toggle-indicator" aria-hidden="true"></span>
					        </button>
									<h3 class="hndle ui-sortable-handle">

										<span><?php echo sprintf( __( 'Migrate Drupal post type: %s', 'd2w' ), $name); ?></span></h3>

									<div class="inside">

										<label><?php _e('Select WP Post Type of destination', 'd2w'); ?></label>
										<select class="select-post-type" data-post-type="<?php echo $type; ?>" name="migrate-<?php echo $name; ?>">
											<?php echo $migratePost->d2w_migrate_post_types_options( $type ) ?>
										</select>

										<h3>Map fields</h3>
										<?php echo $migratePost->d2w_migrate_node_fields( $type ); ?>		
										
										<!--<dt>Node type Fields</dt>
										<dd>
											<select class="select-field-par">
												<option value="0">Select field...</option>
												<option value="full_wuestion">full_question</option>
											</select>
										</dd>-->

										<input data-action="migrate-content" data-drupal-type="<?php echo $type; ?>" class="button button-migrate" type="submit" value="Migrate <?php echo $name; ?>" >	
									</div>
								</div>	

							<?php } ?>						

						</form>

					</div>
				</div>
			</div>
		</div>

	</div>
