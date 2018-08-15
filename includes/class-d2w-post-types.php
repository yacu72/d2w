<?php
/**
 * Functions related to the migration of Users from Drupal to Wordpress .
 *
 * @since      1.0.0
 */
class d2w_Migrate_Post_Types {

	/**
	 * Migrate content from Drupal to Wordpress
	 *
	 * @param string $drupal_node_type, original node tpe to migrate
	 * @param string $wp_post_type,  destination wordpress post type( this post type are created by activated plugins or using Pods Plugin )
	 * @param int $node_nid, drupal node type ID
	 * @param int $limit, number of items to migrate
	 *
	 * @return int $post_id, last ID of wordpress post created. TODO(how to interact with ajax call to show migration on live. )	 
	 */
	public function d2w_migrate_content( $drupal_node_type, $wp_post_type = NULL, $node_nid = NULL , $limit = NULL) {

		global $wpdb;

		$i = 0;

		// Load saved node types relations
		$node_type_par = get_option('d2w-node-types-par');
		$wp_post_type = $node_type_par[$drupal_node_type];		

		$query_vars = array($drupal_node_type, 1);

		$sql_and = '';
		if ($node_nid) {
			$sql_and = ($node_nid) ? ' AND n.nid = %d' : '';
			$query_vars[] = $node_nid;	
		}
		
		$sql_limit = '';

		if ( $limit) {
			$sql_limit = ($limit) ? ' LIMIT %d' : '';
			$query_vars[] = $limit;
		}

		$sql = " SELECT * 
		FROM node_revisions nr
		INNER JOIN node n ON nr.vid = ( SELECT nr1.vid FROM node_revisions nr1 WHERE nr1.nid = n.nid  ORDER BY nr1.vid DESC LIMIT 0,1  )
		INNER JOIN wp_users wpu ON wpu.old_uid = n.uid
		WHERE n.type = %s AND n.status = %d". $sql_and . $sql_limit;

		$res = $wpdb->get_results($wpdb->prepare($sql, $query_vars));

		$permalink_uris = get_option('permalink-manager-uris');

		foreach($res as $key => $node) {

			$post_author = $node->ID;
			$post_date = $post_date_gmt = date('Y-m-d h:i:s', $node->created);
			$post_content = $node->body;
			$post_title = $node->title;
			$post_status = ($node->status == 1) ? 'publish' : 'draft';
			$comment_status = ($node->comment == 2) ? 'open' : 'closed';
			$ping_status = 'open';

			$node_dst = $wpdb->get_results($wpdb->prepare("SELECT dst FROM url_alias url WHERE url.src = '%s'", 'node/'. $node->nid));
			$post_name = str_replace('-htm', '.htm', $node_dst[0]->dst);
			$post_modified = $post_modified_gmt = $post_date_gmt = date('Y-m-d h:i:s', $node->changed);
			$comment_count = $wpdb->get_results($wpdb->prepare("SELECT COUNT(cid) counter FROM comments WHERE nid = %d", $node->nid));

			//Initialize the post ID to  -1
			$post_id = -1;

			//check the page title doesn't exists
			if (null == get_page_by_title( $node->type )) {

				// Insert post in DB
				$post_id = wp_insert_post (
					array (
						'comment_status' => $comment_status,
						'ping_status' => 'closed',
						'post_author' => $post_author,
						//'post_name' => $post_name,
						'post_title' => $post_title,
						'post_content' => $post_content,
						'post_excerpt' => $node->teaser,
						'post_date' => $post_date,
						'post_modified' => $post_modified,
						'post_type' => $wp_post_type,
						'post_status' => $post_status,
						'comment_count' => $comment_count
					)
				);

				// Set WP alias
				$permalink_uris[$post_id] = $node_dst[0]->dst; 

				// Arrays for migrated posts counter
				$post_ids[] = $post_id;

				// save original id in new register for future reference
				$wpdb->update($wpdb->posts, array('old_ID' => $node->nid), array('ID' => $post_id));

				if ( is_numeric( $post_id )) {
					$i++;
				}

				// Save fields values
				$migrateFields = new d2w_Migrate_Post_fields;
				$meta_array = $migrateFields->d2w_migrate_drupal_fields( $drupal_node_type, $post_id );
				foreach( $meta_array as $key => $metadata ) {
					$field = $metadata[1];
					add_post_meta( $post_id, "$field", $metadata[2] );
				}


				// Set the migration flag to true.
				update_option( 'd2w_'. $drupal_node_type .'_migrated', $i );


			} else {
				$post_id = -2;
			}
		}

		// Save Permalinks alias
		update_option('permalink-manager-uris', $permalink_uris);

		// Migrate hierarchycal data for books
		$hierarchycal = false;
		$hierarchycal = get_option('d2w_'. $drupal_node_type .'_hierarchycal');

		if ( $hierarchycal && ($drupal_node_type == 'book') ) {

			// query that fix parent pages
			$sql = "SELECT wpp.ID new_nid, wpp1.ID new_parent
		FROM wp_posts wpp
		INNER JOIN book b ON wpp.old_ID = b.nid
		INNER JOIN wp_posts wpp1 ON wpp1.old_ID = b.bid 
		WHERE b.nid != b.bid";

			$res = $wpdb->get_results($sql);

			foreach($res  as $key => $data) {
				$wpdb->update($wpdb->posts, array('post_parent' => $data->new_parent), array('ID' => $data->new_nid));
			}
 
		} 

		return $post_id;

	}


	/**
	 * Generates the options for WP post types
	 *
	 * @return Options HTML for use inside a select input type.
	 */
	public function d2w_migrate_post_types_options( $wp_post_type = false ) {

		$types = get_post_types( );

		$options = "<option value='0'>Select post type...</option>";

		// Load saved node types relations
		$drupal_node_type_par = get_option('d2w-node-types-par');
		$type_selected = $drupal_node_type_par[$wp_post_type];

		foreach ($types as $key => $type ){

			if ($type == $type_selected ) {
				$options .= "<option selected='selected' value='". $key ."'>". $type ."</option>";
			} else {

				$options .= "<option value='". $key ."'>". $type ."</option>";
			}

		}

		return $options;
	}

	/**
	 * Helper function: Generates a lis of node types from Drupal DB.
	 */
	public function d2w_migrate_drupal_node_types_list() {
		global $wpdb;

		$sql = "SELECT type, name FROM node_type";

		$types = $wpdb->get_results($sql);

		foreach ($types as $key => $value) {
			$options[$value->type] = $value->name; 
		}

		return $options;
	}

	/**
	 * Creates list of fields related to drupal node type.
	 *
	 * @param $drupal_node_type: drupal node type name
	 */
	public function d2w_migrate_node_fields( $drupal_node_type ) {
		global $wpdb;

		$out = '';

		$content_type = 'content_type_'. $drupal_node_type;

		$sql = "SELECT REPLACE(COLUMN_NAME,'_value', '') field_name
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME ='%s'
AND COLUMN_NAME NOT IN ('vid', 'nid')";

		$node_fields = $wpdb->get_results( $wpdb->prepare( $sql, $wpdb->dbname, $content_type ) );

		foreach ($node_fields as $key => $field ) {
			$fields[$field->field_name] = $field->field_name;

			$out .= '<dt class="drupal-field">'. $field->field_name .'</dt><dd>Select wp field par <select class="field-option" data-post-type="fields-'. $drupal_node_type .'">'. $this->d2w_beta_pods_fields_options( $drupal_node_type, $field->field_name) .'</select></dd><hr>';
		}

		return '<dl>'. $out .'</dl>';
	}

	/**
	 * Helper function: Search between Pods created fields
	 *
	 * @param string $wp_post_type drupal node type
	 * @param string $drupal_field name of the drupal field to relate with wp field
	 *
	 * @return options list for select field, with created field in pods plugin
	 */
	public function d2w_pods_fields_options( $drupal_node_type = false, $drupal_field = false ) {
		global $wpdb;

		$option_html = '<option value="0">Select field...</option>';

		// Load saved node types relations
		$node_type_par = get_option('d2w-node-types-par');
		$index = isset( $node_type_par[$drupal_node_type] ) ? $node_type_par[$drupal_node_type] : '';
		$wp_post_type = $index;

		$wp_post_type_fields = $wp_post_type ? 'pods_field_'. $wp_post_type : 'pods_field_';

		// Load default field values from DB
		$field_par = get_option('d2w-fields-par');

		$sql = "SELECT *
		FROM wp_options 
		WHERE option_name LIKE '%%%s%%' ";

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $wp_post_type_fields ));

		foreach( $res as $key => $data ) {
			$option_data = unserialize( $data->option_value );
			
			$index = isset( $field_par[$drupal_node_type][$drupal_field] ) ? $field_par[$drupal_node_type][$drupal_field] : '';
			$option_index = isset($option_data['name']) ? $option_data['name'] : '';// fix notice warnings messages 

			if ($option_index == $index ) {

				$option_html .= '<option selected="selected" value="'. $option_index .'">'. $option_index .'</option>';

			} else {

				$option_html .= '<option value="'. $option_index .'">'. $option_index .'</option>';
			}			
		}

		return $option_html;
	}

	/**
	 * Beta test for types fields code
	 */
	public function d2w_beta_pods_fields_options( $drupal_node_type = false, $drupal_field = false ) {
		global $wpdb;

		$option_html = '<option value="0">Select fields...</option>';

		// Load saved node types relations
		$node_type_par = get_option('d2w-node-types-par');
		$index = isset( $node_type_par[$drupal_node_type] ) ? $node_type_par[$drupal_node_type] : '';
		$wp_post_type = $index;

		$wp_post_type_fields = $wp_post_type ? 'pod_'. $wp_post_type : 'pods_field_';

		// Load default field values from DB
		$field_par = get_option('d2w-fields-par');

		$sql = "SELECT *
		FROM wp_options 
		WHERE option_name LIKE '%%%s%%' ";

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $wp_post_type_fields ));

		foreach( $res as $key => $data ) {
			$option_data = unserialize( $data->option_value );
			
			$index = isset( $field_par[$drupal_node_type][$drupal_field] ) ? $field_par[$drupal_node_type][$drupal_field] : '';
			$option_index = isset($option_data['name']) ? $option_data['name'] : '';// fix notice warnings messages 

			foreach ( $option_data['fields'] as $field_type => $field_data ) {

				if ($field_type == $index ) {

					$option_html .= '<option selected="selected" value="'. $field_type .'">'. $field_type .'</option>';

				} else {

					$option_html .= '<option value="'. $field_type.'">'. $field_type .'</option>';
				}	
			}		
		}

		return $option_html;
	}

}