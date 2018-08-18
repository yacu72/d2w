<?php
/**
 * Migrate Images from Drupal to Wordpress
 *
 * @since      1.0.0
 */
class d2w_Migrate_Images {

	/**
	 * Display HTML for Batc migration.
	 */
	public function d2w_batch_migration_display( $images = NULL, $drupal_node_type = NULL ) {

		$out = '';

		$site_url = get_home_url();

		if ( $drupal_node_type ) {
			$images = get_option('d2w_'. $drupal_node_type .'_images_import');
		}

		foreach( $images as $key => $data) {

			if ( $key != 'count' && $key != 'total_size') {
				$out .= count($data) .' Images to migrate.';
				$out .= "<input data-drupal_type=". $drupal_node_type ." data-action='migrate-image-group' data-images-group='". $key ."' type='submit' value='Migrate Group ". $key ."' >";
				$out .= '<img class="waiting" src="'. $site_url .'/wp/wp-admin/images/wpspin_light.gif" >';
				$out .= '<hr>';
			}

		}

		return $out;
	}


	/**
	 * Render Data preview to migration
	 */
	public function d2w_migrate_images_info( $drupal_node_type = NULL, $wp_post_type = NULL, $size_limit = NULL ) {

		global $wpdb;

		$size_limit = get_option( 'd2w_size_limit_'. $drupal_node_type);

		$images = array();

		// Load node types relation
		if ( $drupal_node_type ) {
			$type_rel = get_option( 'd2w-node-types-par' );
			$wp_post_type = $type_rel[ $drupal_node_type ];
		}

		$sql = "SELECT p.ID, p.old_ID, f.*
		FROM files f 
		INNER JOIN content_field_mbase_images cfmi ON ( f.fid = cfmi.field_mbase_images_fid AND cfmi.vid = (SELECT MAX(n.vid) FROM node n WHERE n.nid =cfmi.nid) )
		INNER JOIN wp_posts p ON cfmi.nid = p.old_ID
		WHERE p.post_type = '%s' ORDER BY p.ID DESC";		

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $wp_post_type ) );

		$size_limit = $size_limit? $size_limit : 2000000;
		$group = 1;
		$counter = 0;
		$size_counter = 0;
		$total_size = 0;

		foreach ( $res as $key => $value ) {

			$total_size += $value->filesize;

			$size_counter = $value->filesize + $size_counter;

			if ( $size_counter > $size_limit ){
				$size_counter = 0;
				$group++;
			}

			// TODO. do this replace more generic, maybe adding something in settings section.
			$path = str_replace( 'sites/dev.medstudentadvisors.do5.mflw.us/files', 'sites/medstudentadvisors.com/files', $value->filepath );


			$images[$group][] = array(
				'ID' => $value->ID,
				'filename' => $value->filename,
				'size' => $value->filesize,
				'path' => $path,
			);

			$counter++;

		} 

		update_option('d2w_'. $drupal_node_type .'_images_import', $images);

		$images['total_size'] = $total_size;

		$images['count'] = $counter;

		return $images;
	}

	/**
	 * Load Images files from Drupal DB.
	 *
	 */
	public function d2w_migrate_images_loader( $drupal_node_type = NULL, $wp_post_type = NULL, $wp_post_id = NULL ) {

		global $wpdb;

		$images = array();
		$i = 0;
		$ssql_and = '';
		$total_size = 0;

		// Load node types relation
		if ( $drupal_node_type ) {
			$type_rel = get_option( 'd2w-node-types-par' );
			$wp_post_type = $type_rel[ $drupal_node_type ];
		}

		// Load path to image to download.
		$migrate_settings = get_option( 'd2w_migrate_settings' );
		foreach ($migrate_settings as $key => $data ) {
			if ( $data[0] == 'images-url' ) {
				$image_path = $data[1];
			}
		}

		// Limit search to specific post.
		$sql_and = ( is_numeric( $wp_post_id ) ) ? ' AND p.ID = '. $wp_post_id .' ' : '';

		$sql = "SELECT p.ID, p.old_ID, f.*
		FROM files f 
		INNER JOIN content_field_mbase_images cfmi ON ( f.fid = cfmi.field_mbase_images_fid AND cfmi.vid = (SELECT MAX(n.vid) FROM node n WHERE n.nid =cfmi.nid) )
		INNER JOIN wp_posts p ON cfmi.nid = p.old_ID $sql_and
		WHERE p.post_type = '%s' ORDER BY p.ID DESC";

		

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $wp_post_type ) );



		foreach ( $res as $key => $value ) {

			$path = str_replace( 'sites/dev.medstudentadvisors.do5.mflw.us/files', 'sites/medstudentadvisors.com/files', $value->filepath );

			$images[$value->ID][] = array(
				'filename' => $value->filename,
				'path' => $path,
				'size' => $value->filesize,
				'filemime' => $value->filemime,
			);

			$this->d2w_migrate_image( '' , $image_path .'/'. $path, $value->ID );

			$this->d2w_image_content_filter( $value->ID );

			$total_size += $value->filesize;

			$i++;
		}

		$type_images[$wp_post_type] = array (
			'counter' => $i,
			'images' => $images,
			'total' => $total_size,
		);



		return $type_images;

	}

	/**
	 * Creates Attachment post  type with images related to posts. 
	 *
	 * @param (string) $drupal_node_type
	 * @param (int) $wp_post_id;
	 * @param (string) $image: image full path
	 */
	public function d2w_migrate_image( $drupal_node_type = NULL , $image = NULL, $wp_post_id = NULL ) {

		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// magic sideload image returns an HTML image, not an ID
		$media = media_sideload_image($image, $wp_post_id);

		// therefore we must find it so we can set it as featured ID
		if(!empty($media) && !is_wp_error($media)){ 
		    $args = array(
		        'post_type' => 'attachment',
		        'posts_per_page' => -1,
		        'post_status' => 'any',
		        'post_parent' => $wp_post_id
		    );

		    // reference new image to set as featured
		    $attachments = get_posts($args);
		    $i = 0;
		    if(isset($attachments) && is_array($attachments)){
		        foreach($attachments as $attachment){
		            // grab source of full size images (so no 300x150 nonsense in path)
		            $image = wp_get_attachment_image_src($attachment->ID, 'full');
		            // determine if in the $media image we created, the string of the URL exists
		            if(strpos($media, $image[0]) !== false){
		                // if so, we found our image. set it as thumbnail
		                $status = set_post_thumbnail($wp_post_id, $attachment->ID);
		                if ($status) {
		                	$i++; // counter of sucessfull migrations
		                }
		                return $i;
		                // only want one image
		                break;
		            }
		            return $i;
		        }
		    }
		}

	}

	/**
	 * Helper function: Filters Imge HTML from Post content string.
	 *
	 * Queries the post by post ID, getting all related images to the post.
	 * Replaces old Drupal img tags with new tags usign WP images paths.
	 * Update Post content with filtered string.
	 *
	 * @param (int) $wp_post_id: Post ID of the content to filter.   
	 *
	 * @return (int) $result: Updated Post ID 
	 */
	public function d2w_image_content_filter( $wp_post_id ) {

		if ( !is_numeric( $wp_post_id )) {
			exit;
		}

		global $wpdb;
		global $wp_query;
		$result = '';

		$sql = "SELECT p.post_type, p.guid, p.post_content
						FROM  wp_posts p 
						WHERE  p.ID = %d  OR p.post_parent = %d";

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $wp_post_id, $wp_post_id ));

		$post_images = array();

		foreach( $res as $key => $post ) {

			if ( $post->post_type == 'attachment') {
				$post_images[] = $post->guid;
			}

			if ( $post->post_content != '') {
				$content = $post->post_content;
			}
		} 

		// Replace Colorbox tags.
		if ( preg_match_all ('/<a class="colorbox*(.*?)+<\/a>/', $content, $matches) ) {

			$filter_post_content = $this->d2w_replace_pic( $matches, $content, $post_images );



			// Update post content with filtered one
			$edited_post = array(
				'ID' => esc_sql( $wp_post_id ),
				'post_content' => wp_kses_post( $filter_post_content ),
			);

	    $result = wp_update_post( $edited_post, true);

	    if (is_wp_error($result)){
	      wp_die( 'Post not saved' );
	    }

	    $wp_query = new WP_Query($wp_query->query);  //resets the global query so updated post data is available.	

		}

		return $result;
	}

	/**
	 * Helper function: Replace old img tags, with WP migrated images.
	 *
	 * Removes img tags linked to old drupal paths and replace them with new WP paths.
	 *
	 * @param (array) $matches: Listing with all ocurrences of colorbox images tags
	 * @param (string) $content: Current content string with old linked colorbox tags
	 * @param (array) $post_types: Listing of all images related to current post.
	 *
	 * @return (string) $output: Fileterd content with images replaacement completed.
	 */
	public function d2w_replace_pic( $matches, $content, $post_images ) {

		$output = $content;

		foreach ( $post_images as $key => $image ) {

			$image_path_array = explode('/', $image);
			$image_name[$image] = end( $image_path_array );

		}

		foreach ( $matches[0] as $key => $img ){

			preg_match('/(?<=src=")(?s).+?(?=")/',$img, $match);

			$image_match_path = explode( '/', $match[0]);

			foreach ($image_name as $image_path => $image_full_name ) {

				if ( in_array( $image_full_name,$image_match_path ) ) {

					$output = str_replace( $img, "<img src=$image_path >", $output );

				}

			}
			
		}

		return $output;

	}

	/**
	 * Helper function 
	 */
	public function d2w_image_path_by_fid( $fid, $wp_post_id ) {

		global $wpdb;

		// Load path to image to download.
		$migrate_settings = get_option( 'd2w_migrate_settings' );
		foreach ($migrate_settings as $key => $data ) {
			if ( $data[0] == 'images-url' ) {
				$image_path = $data[1];
			}
		}		

		$sql = "SELECT f.*
		FROM files f
		WHERE f.fid = %d";

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $fid ) );

		foreach ( $res as $key => $value ) {

			$path = str_replace( 'sites/dev.medstudentadvisors.do5.mflw.us/files', 'sites/medstudentadvisors.com/files', $value->filepath );

			$images[$value->ID][] = array(
				'filename' => $value->filename,
				'path' => $path,
				'size' => $value->filesize,
				'filemime' => $value->filemime,
			);

			$this->d2w_migrate_image( '' , $image_path .'/'. $path, $wp_post_id );

			$total_size += $value->filesize;

			$i++;
		}		

	}

	/**
	 * Migrate Filefields
	 *
	 *
	 *
	 */
	public function d2w_migrate_fielfields_options( $drupal_node_type ) {

		global $wpdb;
		$options = '<option value="0">Select filefield...</option>';
		$out = '';

		$default_options = get_option( 'd2w_drupal_filefields' );

		$sql = "SELECT cnf.field_name
		FROM content_node_field cnf
		WHERE cnf.type = '%s'";

		$res = $wpdb->get_results( $wpdb->prepare( $sql, 'filefield') );

		foreach ( $res as $key => $field ){

			if ( isset( $default_options[$drupal_node_type][$field->field_name] ) && $default_options[$drupal_node_type][$field->field_name] == $field->field_name ) {

				$options .= '<option selected="selected" value="'. $field->field_name .'">'. $field->field_name .'</option>';

			} else {

				$options .= '<option value="'. $field->field_name .'">'. $field->field_name .'</option>';

			}

		}

		$out = '<select data-drupal-type="'. $drupal_node_type .'" data-action="select-filefield">';
		$out .= $options;
		$out .= '</select>';
		$out .=  '<div class="filefield-wrapper" >';
		$out .= '</div>';


		return $out;

	}

	/**
	 * Search files by filefield 
	 *
	 * Group files to migrate by a size limit to prebent TIME_MAX issues.
	 *
	 * @param (string) $drupal_node_type: The name of the Drual content type related to the fiels migration
	 * @param (string) $drupal_filefield: The name of the filefield
	 * @param (int) $wp_post_id: The ID of the post
	 * 
	 * @return (array) Post fiels grouped by files sixe limit, and text string with details of the gouped files. 
	 */
	public function d2w_files_by_filefield( $drupal_node_type, $drupal_filefield, $wp_post_id = NULL ) {

		global $wpdb;

		$content_type_row = 'content_type_'. $drupal_node_type;
		$drupal_field_fid = 'ctmr.'. $drupal_filefield .'_fid';
		$total_size = $all_files = 0;
		$i = 1;
		$size_limit = 300000; // TODO: load by variable
		$bigest = 0;
		$site_url = get_home_url();

		$filefield_size_limits = get_option('d2w_filefield_size_limits');

		$size_limit = isset( $filefield_size_limits[$drupal_node_type][$drupal_filefield]) ? $filefield_size_limits[$drupal_node_type][$drupal_filefield] : 500000 ;

		$size_options  = ( $size_limit == 500000 ) ? '<option selected="selected" value="500000">500kb</option>' : '<option value="500000">500kb</option>';
		$size_options .= ( $size_limit == 1000000 ) ? '<option selected="selected"  value="1000000">1MB</option>' : '<option value="1000000">1MB</option>';
		$size_options .= '<option value="2000000">2MB</option>';
		$size_options .= '<option value="5000000">5MB</option>';

		$select_size = '<select data-drupal-field="'. $drupal_filefield .'" data-drupal-type="'. $drupal_node_type.'" data-action="filesize-limit" >'. $size_options .'</select>';


		$and = ($wp_post_id) ? ' AND wp.ID = %d' : '';
		$args = ($wp_post_id) ? array('publish', $wp_post_id ) : array( 'publish' ); 

		$sql = "SELECT wp.ID, $drupal_field_fid, f.filesize, f.filepath
FROM files f
INNER JOIN $content_type_row ctmr ON f.fid =  $drupal_field_fid
INNER JOIN wp_posts wp ON wp.old_ID = ctmr.nid 
WHERE wp.post_status = '%s' $and ";

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $args ));

		foreach ( $res as $key => $field ){

			$bigest = ($field->filesize > $bigest) ? $field->filesize : $bigest;

			$total_size = $total_size + $field->filesize;
			$all_files = $all_files + $field->filesize;

			if( $total_size > $size_limit ){
				$i++;
				$total_size = 0;
			}

			$groups[$i][$field->ID] = $field->filepath;


		}

		$filefield_data = get_option('d2w_filefields_data');

		$filefield_data[$drupal_node_type] = $groups;
		
		update_option('d2w_filefields_data', $filefield_data );

		// TODO: move this to template
		$html = '';

		$wrapper = $all_files .'kb divided in <span class="groups-counter">'. count ($groups) .'</span> groups of '. $select_size .'. (Max size:'. $bigest .'kb)';

		$wrapper .= '<hr>';

		foreach ($groups as $group => $files ){
			$html .= count( $files ) .' files <input data-action="migrate-filefield" type="submit" data-drupal-field="'. $drupal_filefield .'" data-drupal-type="'. $drupal_node_type .'" data-group="'. $group .'" value="Migrate Files" >';
			$html .= '<img class="waiting" src="'. $site_url .'/wp/wp-admin/images/wpspin_light.gif" >';
			$html .= '<hr>';
		}

		$out['wrapper'] = $wrapper;

		$out['html'] = $html;

		$out['field_name'] = $drupal_filefield;

		return $out;

	}


	

}