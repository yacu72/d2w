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

		$drupal_node_types = $migratePost->d2w_migrate_drupal_node_types_list();

		$migrateFields = new d2w_Migrate_Post_fields;

		$migrateTax = new d2w_Migrate_taxonomy;

		$migrateComments = new d2w_Migrate_Comments;

	?>

	<div class="wrap">
		<h2>D2W <?php _e(' Options', $this->plugin_name); ?></h2>

		<p>Before migration, create columns for old drupal id's</p>
		<code>
			ALTER TABLE wp_posts ADD old_ID bigint(20) unsigned NOT NULL default '0';<br>
			ALTER TABLE wp_users ADD old_uid bigint(20) unsigned NOT NULL default '0';<br>
			ALTER TABLE wp_comments ADD old_comment_ID bigint(20) unsigned NOT NULL default '0';<br>
		</code>

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
										<input data-action="migrate-users" class="button button-migrate" type="submit" value="Migrate Users">	
									<?php } else { ?>
										<?php _e('User Migration completed', 'd2w'); ?>
									<?php } ?>

								</div>
							</div>
							<!-- TAXONOMY -->
							<div class="postbox">
				        <button type="button" class="handlediv button-link" aria-expanded="true">
				            <span class="screen-reader-text"><?php _e('Toggle panel'); ?></span>
				            <span class="toggle-indicator" aria-hidden="true"></span>
				        </button>
								<h3 class="hndle ui-sortable-handle">
									<span><?php _e( 'Migrate Taxonomy Terms', 'd2w' ); ?></span></h3>

								<div class="inside">

									<?php echo $migrateTax->d2w_drupal_node_to_tax_rel(); ?>

									<!--<?php if ( !get_site_option('d2w_taxonomy_migrated') ) { ?>
										<input data-action="migrate-tax" class="button button-migrate" type="submit" value="Migrate Taxonomy">	
									<?php } else { ?>
										<?php _e('Taxonomy Migration completed', 'd2w'); ?>
									<?php } ?>-->

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

										<h3>Map fields:</h3>
										<?php echo $migratePost->d2w_migrate_node_fields( $type ); ?>		
										<hr>
										
										<?php if ( !get_option( 'd2w_'. $type .'_migrated' )  ) { ?>

											<input data-action="migrate-content" data-drupal-type="<?php echo $type; ?>" class="button button-migrate" type="submit" value="Migrate <?php echo $name; ?>" >	

										<?php } else { ?>

											<?php echo get_option( 'd2w_'. $type .'_migrated' ) .' '. $type; ?> Migrated. <i class="dashicons dashicons-yes"></i>

										<?php } ?>
										<hr>
										<?php 
										/**
							       * MIGRATE POST TERMS 
							       */
										?>
										<h3>Migrate Post Terms</h3>

										<?php if( !get_option('d2w_'. $type .'_post_terms_migrated') ) { ?>

											<input data-action="migrate-post-terms" data-drupal-type="<?php echo $type; ?>" class="buttton button-migrate" type="submit" value="Migrate Terms" > 

										<?php } else { ?>

											<?php echo get_option('d2w_'. $type .'_post_terms_migrated'); ?> Post Terms Migrated.<i class="dashicons dashicons-yes"></i> 

										<?php } ?>										
										<hr>
										<?php
											/**
								       * MIGRATE POST COMMENTS
								       */
										?>
										<h3>Migrate Post Comments - <?php echo $migrateComments->d2w_comments_counter( $type ); ?></h3>
										<?php if( !get_option('d2w_'. $type .'_comments_migrated') ) { ?>

											<input data-action="migrate-post-comments" data-drupal-type="<?php echo $type; ?>" class="buttton button-migrate" type="submit" value="Migrate Comments" > 

										<?php } else { ?>

											<?php echo get_option('d2w_'. $type .'_comments_migrated'); ?> Comments Migrated.<i class="dashicons dashicons-yes"></i> 

										<?php } ?>										

									</div>
								</div>	

							<?php } ?>						

						</form>

					</div>
				</div>
			</div>
		</div>

	</div>
