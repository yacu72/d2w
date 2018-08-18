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

		$migrateImages = new d2w_Migrate_Images;

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

							<?php
							/**
							 *
							 * MIGRATE SETTINGS
							 *
							 */
							?>
							<div class="postbox" >
				        <button type="button" class="handlediv button-link" aria-expanded="true">
			            <span class="screen-reader-text"><?php _e('Toggle panel'); ?></span>
			            <span class="toggle-indicator" aria-hidden="true"></span>
				        </button>
								<h3 class="hndle ui-sortable-handle">
									<span><?php _e( 'Migrate Settings', 'd2w' ); ?></span>
								</h3>	
								
								<div class="inside" >

									<?php include ('d2w-admin-settings-display.php'); ?>

									<!--<input value="" class="settings-input" type="text" data-name="images-url" placeholder="Images URL" >
									<description><?php _e( 'Load images from live site.', 'd2w' ); ?></description>
									<hr>

									<input disabled value="" class="settings-input" type="text" data-name="images-folder-path" placeholder="Images Folder Path" >
									<description><?php _e( 'Load images from a folder.', 'd2w' ); ?></description>								
									<hr>

									<input data-action="migrate-settings" class="button button-migrate-settings" type="submit" value="Save Settings">-->

								</div>						

							</div>

							<?php
							/**
							 *
							 * MIGRATE USERS
							 *
							 */
							?>
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

							<?php
							/**
							 *
							 * MIGRATE TAXONOMY
							 *
							 */
							?>
							<div class="postbox">
				        <button type="button" class="handlediv button-link" aria-expanded="true">
				            <span class="screen-reader-text"><?php _e('Toggle panel'); ?></span>
				            <span class="toggle-indicator" aria-hidden="true"></span>
				        </button>
								<h3 class="hndle ui-sortable-handle">
									<span><?php _e( 'Migrate Taxonomy Terms', 'd2w' ); ?></span></h3>

								<div class="inside">

									<?php echo $migrateTax->d2w_drupal_node_to_tax_rel(); ?>

								</div>

							</div>

							<?php
							 /**
							  *
							  * MIGRATE POST TYPES
							  *
							  */
							 ?>

							<?php foreach ($drupal_node_types as $type => $name) { ?>

								<div class="postbox closed">
					        <button type="button" class="handlediv button-link" aria-expanded="true">
					            <span class="screen-reader-text"><?php _e('Toggle panel'); ?></span>
					            <span class="toggle-indicator" aria-hidden="true"></span>
					        </button>
									<h3 class="hndle ui-sortable-handle">

										<span><?php echo sprintf( __( 'Migrate Drupal post type: %s', 'd2w' ), $name); ?></span>
										<?php echo get_option("d2w_". $type ."_migrated") ? get_option("d2w_". $type ."_migrated") : ''; ?>
									</h3>

									<div class="inside">

										<label><?php _e('Select WP Post Type of destination', 'd2w'); ?></label>
										<select class="select-post-type" data-post-type="<?php echo $type; ?>" name="migrate-<?php echo $name; ?>">
											<?php echo $migratePost->d2w_migrate_post_types_options( $type ) ?>
										</select>

										<hr>
										<label>Make Hierarchycal:</label>
										<input type="checkbox" data-drupal-type="<?php echo $type; ?>" name="hierarchycal-box" value="1" <?php checked( 'true', get_option('d2w_'. $type .'_hierarchycal') ); ?> >
										<hr>

										<h3>Map fields:</h3>
										<hr>
										<?php echo $migratePost->d2w_migrate_node_fields( $type ); ?>		

										
										<?php if ( !get_option( 'd2w_'. $type .'_migrated' )  ) { ?>

											<input data-action="migrate-content" data-drupal-type="<?php echo $type; ?>" class="button button-migrate" type="submit" value="Migrate <?php echo $name; ?>" >	

										<?php } else { ?>

											<?php echo get_option( 'd2w_'. $type .'_migrated' ) .' '. $type; ?> Migrated. <i class="dashicons dashicons-yes"></i>

										<?php } ?>
										<hr>

										<?php
										/**
										 * MIGRATE FILEFIELDS
										 */
										?>
										<h3>Migrate Images from Filefields</h3>
										<?php echo  $migrateImages->d2w_migrate_fielfields_options( $type ); ?>

										<?php 
											$default_options = get_option( 'd2w_drupal_filefields' ); 
											$filefield = isset($default_options[ $type ]) ? $default_options[ $type ] : array();

											$files = array();

											foreach ($filefield as $name ) {

												$files = $migrateImages->d2w_files_by_filefield( $type, $name);

											}

											echo '<hr>';
											if ( isset($files['wrapper']) ){
												echo $files['wrapper'];
											}
											if ( isset($files['field_name']) ){
												echo '<div data-field="'. $files['field_name'] .'" class="filefields-wrapper" >';
											}
											if ( isset($files['html']) ){
												echo $files['html'];
												echo '</div>';
											}
											

										?>

										<?php 
										/**
										 *
							       * MIGRATE POST TERMS 
							       *
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
											 *
								       * MIGRATE POST COMMENTS
								       *
								       */
										?>
										<h3>Migrate Post Comments - <?php echo $migrateComments->d2w_comments_counter( $type ); ?></h3>
										<?php if( !get_option('d2w_'. $type .'_comments_migrated') ) { ?>

											<input data-action="migrate-post-comments" data-drupal-type="<?php echo $type; ?>" class="buttton button-migrate" type="submit" value="Migrate Comments" > 

										<?php } else { ?>

											<?php echo get_option('d2w_'. $type .'_comments_migrated'); ?> Comments Migrated.<i class="dashicons dashicons-yes"></i> 

										<?php } ?>
										<hr>
										<?php	
											/**
											 *
								       * MIGRATE POST IMAGES
								       *
								       */
										?>
										<?php $images_data = $migrateImages->d2w_migrate_images_info( $type ); ?>
										<h3>Migrate Post Images - <?php echo $images_data['count']; ?></h3>
										
										<?php if( !get_option('d2w_'. $type .'_images_migrated') ) { ?>

											<h4>Total size to migrate: <?php echo $images_data['total_size']; ?> kb</h4>											

											<label>Batch Limit for Migration:</label>

											<?php $size_limit = ( get_option( 'd2w_size_limit_'. $type)/1000000 ); ?>

											<select data-drupal-type="<?php echo $type; ?>" name="data-size-limit" >
												<option <?php if ( $size_limit == 100 ) { echo 'selected=selected'; } ?> value="100">100MB</option>
												<option <?php if ( $size_limit == 50 ) { echo 'selected=selected'; } ?> value="50">50MB</option>
												<option <?php if ( $size_limit == 20 ) { echo 'selected=selected'; } ?> value="20">20MB</option>
												<option <?php if ( $size_limit == 10 ) { echo 'selected=selected'; } ?> value="10">10MB</option>
												<option <?php if ( $size_limit == 5 ) { echo 'selected=selected'; } ?> value="5">5MB</option>
												<option <?php if ( $size_limit == 2 ) { echo 'selected=selected'; } ?> value="2" >2MB</option>
											</select>
											<hr>

											<div class="d2w-batch-list-<?php echo $type; ?>" >

												<?php echo $migrateImages->d2w_batch_migration_display( '', $type ); ?>

											</div>

											<!--<?php 
												foreach( $images_data as $key => $data) {
												
														if ( $key != 'count' && $key != 'total_size') {
															echo count($data) .' Images to migrate.';
															echo "<input data-action='migrate-image-group' data-images-group='". $key ."' type='submit' value='Migrate Group ". $key ."' >";
															echo '<hr>';
														}

												}
											?>-->

											<input data-action="migrate-post-images" data-drupal-type="<?php echo $type; ?>" class="buttton button-migrate" type="submit" value="Migrate All Images" > 

										<?php } else { ?>

											<?php echo get_option('d2w_'. $type .'_images_migrated'); ?> Images Migrated.<i class="dashicons dashicons-yes"></i> 

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
