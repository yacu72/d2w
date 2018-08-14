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

		    if(isset($attachments) && is_array($attachments)){
		        foreach($attachments as $attachment){
		            // grab source of full size images (so no 300x150 nonsense in path)
		            $image = wp_get_attachment_image_src($attachment->ID, 'full');
		            // determine if in the $media image we created, the string of the URL exists
		            if(strpos($media, $image[0]) !== false){
		                // if so, we found our image. set it as thumbnail
		                set_post_thumbnail($wp_post_id, $attachment->ID);
		                return $attachment->ID;
		                // only want one image
		                break;
		            }
		            return $attachment->ID;
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

		print_r( $image_name );

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

}