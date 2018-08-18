<?php
/**
 * Functions related to the migration of Post fields.
 *
 * @since      1.0.0
 */
class d2w_Migrate_Post_fields extends  d2w_Migrate_Post_Types {

	/**
	 * Migrate Drupal content type fields.
	 */
	public function d2w_migrate_drupal_fields( $drupal_node_type, $wp_post_id ) {

		if ( !is_numeric( $wp_post_id )) {
			exit;
		}

		global $wpdb;

		// Load saved node types relations
		$fields_par = get_option('d2w-fields-par');

		$post_type_fields = $fields_par[$drupal_node_type];

		$field_types = get_option( 'd2w_field_types' );

		$query_vars = array ($drupal_node_type, 1);

		$drupal_node_type_row = 'content_type_'. $drupal_node_type;

		$inner_join = "INNER JOIN $drupal_node_type_row  dnt ON dnt.vid = n.vid ";

		$and = is_numeric( $wp_post_id ) ? ' AND n.nid = %d ' : '';

		if ( is_numeric( $wp_post_id ) ){
			$query_vars[] = $wp_post_id;
		}

		$sql = "SELECT dnt.*, p.ID
		FROM node n 
		". $inner_join ."
		INNER JOIN wp_posts p ON p.old_ID = dnt.nid
		WHERE n.vid = (SELECT MAX(nr.vid) FROM node_revisions nr WHERE nr.nid = n.nid) AND n.type = '%s' AND n.status = %d". $and ; 

		print $sql;

		$res = $wpdb->get_results($wpdb->prepare($sql, $query_vars));

		//print_r($post_type_fields);

		print_r($res);

		foreach ( $res as $key => $data ) {

			$data_array = (array) $data;// convert from object to array

			foreach ( $post_type_fields as $drupal_field_name => $wp_field_name ) {

				if ( $data_array[$drupal_field_name] != '' ) {
					$out[] = array( 
						'ID' => $wp_post_id, 
						'field_name' => $wp_field_name, 
						'field_value' => sanitize_text_field( $data_array[$drupal_field_name]),
						'type' => $field_types[$drupal_field_name],
				  );
				}

				$field_value = $drupal_field_name .'_value'; 

				if ( isset($data_array[$field_value]) && $data_array[$field_value] != '' ) {

					$out[] = array( 
						'ID' => $wp_post_id, 
						'field_name' => $wp_field_name, 
						'field_value' => sanitize_text_field( $data_array[$drupal_field_name] ), 
						'type' => $field_types[$drupal_field_name],
					);

				}

			}

		}

		return $out;
	}

}