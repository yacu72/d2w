<?php
/**
 * Functions related to the migration of Users from Drupal to Wordpress.
 *
 * @since      1.0.0
 */
class d2w_Migrate_Post_Types {

	/**
	 * Generates the options for WP post types
	 *
	 * @return Options HTML for use inside a select input type.
	 */
	public function d2w_migrate_post_types_options( $post_type = false ) {

		$types = get_post_types( );

		$options = "<option value='0'>Select post type...</option>";

		// Load saved node types relations
		$node_type_par = get_option('d2w-node-types-par');
		$type_selected = $node_type_par[$post_type];

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
	 * @param $node_type: drupal node type name
	 */
	public function d2w_migrate_node_fields( $node_type ) {
		global $wpdb;

		$content_type = 'content_type_'. $node_type;

		$sql = "SELECT REPLACE(COLUMN_NAME,'_value', '') field_name
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'med3_local_development' AND TABLE_NAME ='%s'
AND COLUMN_NAME NOT IN ('vid', 'nid')";

		$node_fields = $wpdb->get_results( $wpdb->prepare( $sql, $content_type ) );

		foreach ($node_fields as $key => $field ) {
			$fields[$field->field_name] = $field->field_name;

			$out .= '<dt class="drupal-field">'. $field->field_name .'</dt><dd>Select wp field par <select class="field-option" data-post-type="fields-'. $node_type .'">'. $this->d2w_pods_fields_options( $node_type, $field->field_name) .'</select></dd><hr>';
		}

		return '<dl>'. $out .'</dl>';
	}

	/**
	 * Helper function: Search between Pods created fields
	 *
	 * @return options list for select field, with created field in pods plugin
	 */
	public function d2w_pods_fields_options( $post_type = false, $drupal_field = false ) {
		global $wpdb;

		$option_html = '<option value="0">Select field...</option>';

		// Load saved node types relations
		$node_type_par = get_option('d2w-node-types-par');
		$wp_post_type = $node_type_par[$post_type];

		$post_type_fields = $post_type ? 'pods_field_'. $wp_post_type : 'pods_field_';

		// Load default field values from DB
		$field_par = get_option('d2w-fields-par');

		$sql = "SELECT *
		FROM wp_options 
		WHERE option_name LIKE '%%%s%%' ";

		$res = $wpdb->get_results( $wpdb->prepare( $sql, $post_type_fields ));

		foreach( $res as $key => $data ) {
			$option_data = unserialize( $data->option_value );
			

			if ($option_data['name'] == $field_par[$post_type][$drupal_field] ) {
				//$options .= "<option selected='selected' value='". $key ."'>". $type ."</option>";
				$option_html .= '<option selected="selected" value="'. $option_data['name'] .'">'. $option_data['name'] .'</option>';
			} else {

				$option_html .= '<option value="'. $option_data['name'] .'">'. $option_data['name'] .'</option>';
			}			
		}

		return $option_html;
	}

}