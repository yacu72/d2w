<?php
/**
 * Migrate cOMMENTS from Drupal to Wordpress
 *
 * @since      1.0.0
 */
class d2w_Migrate_Comments {

	/**
	 * Migrate Post Comments
	 *
	 * @param (string) $drupal_node_type: node type name in Drupal DB
	 * @param (string) $wp_post_type: post type name in Wordpress DB
	 * @param (int) $node_nid: Drupal node ID, usually used for debug and test.
	 *
	 * @return (int) number of comments migrated.
	 */
	public function d2w_migrate_post_comments( $drupal_node_type = NULL, $wp_post_type = NULL, $node_nid = NULL ) {

		global $wpdb;

		$sql_and = ''; // removes comments with external links, most of them are spam.

		if ( $drupal_node_type ) {
			$node_par = get_option('d2w-node-types-par');
			$wp_post_type = $node_par[ $drupal_node_type ];
		}

		$sql_and = " AND c.homepage = '' ";

		if ( is_numeric( $node_nid )) {
			$sql_and .= " AND c.nid = $node_nid ";
		}

		$sql_comments = "SELECT *
			FROM comments c
			INNER JOIN wp_posts wpp ON c.nid = wpp.old_ID
			WHERE wpp.post_type = %s $sql_and";

		$res_comments = $wpdb->get_results($wpdb->prepare($sql_comments, $wp_post_type));

		foreach ( $res_comments as $key => $comment) {

			// find user id
			$sql_user = "SELECT * FROM wp_users wpu WHERE wpu.user_login = %s";
			$res_user = $wpdb->get_results($wpdb->prepare($sql_user, $comment->name));


			$data = array(
				'comment_post_ID' => $comment->ID,
				'comment_author' => $comment->name,
				'comment_author_IP' => $comment->hostname,
				'comment_date' =>  date('Y-m-d h:i:s', $comment->timestamp),
				'comment_content' => $comment->comment,
				'comment_approved' => 1,
				//'user_id' = ($res_user[0]->ID) ? $res_user[0]->ID : 0,
				'comment_author_url' => $comment->homepage,
				'comment_author_email' => $comment->mail,
			);

			// Insert the comment into the database
			$comment_id[] = wp_insert_comment($data);

			// Save original comment id in wp_comments table
			$wpdb->update($wpdb->comments, array('old_comment_ID' => $comment->cid), array('comment_ID' => $comment_id));

			// handles comment parents code
			if ( $comment->pid != 0) {
				// search old parent ID in new table 
				$new_parent = $wpdb->get_results($wpdb->prepare("SELECT comment_ID FROM wp_comments wpc WHERE wpc.old_comment_ID = %d", $comment->pid));

				// insert new parent ID in wp_comments table
				$wpdb->update($wpdb->comments, array('comment_parent' => $new_parent[0]->comment_ID), array('comment_ID' => $comment_id));
			}

		}

		update_option('d2w_'. $drupal_post_type .'_comments_migrated', count( $comment_id ) );

		return count( $comment_id );
	}


}