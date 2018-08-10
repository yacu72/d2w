<?php
/**
 * Migrate Images from Drupal to Wordpress
 *
 * @since      1.0.0
 */
class d2w_Migrate_Images {

	/**
	 * Creates Attachment post  type with images related to posts.
	 *
	 * @param (string) $drupal_node_type
	 * @param (int) $wp_post_id;
	 * @param (string) $image
	 */
	function d2w_migrate_image( $drupal_node_type = NULL , $image = NULL, $wp_post_id = NULL ) {

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
		                set_post_thumbnail($post_id, $attachment->ID);
		                return $attachment->ID;
		                // only want one image
		                break;
		            }
		            return $attachment->ID;
		        }
		    }
		}

	}


}