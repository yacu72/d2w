<?php
/**
 * Migrate Taxonomy from Drupal to Wordpress
 *
 * @since      1.0.0
 */
class d2w_Migrate_taxonomy {

	/**
	 * Migrate taxonomy terms to specific category.
	 *
	 * @param string $drupal_node_type, Drupal node type, migrate all terms related to this node type.
	 * @param string $taxonomy, WP destination category, by default WP brings two categories: category and post_tag. Other categories can be created manually or using a plugin(example Pods) 
	 * @param array $range, Optional parameter. This parameter allow us to filter the sql for specific values.(TODO: add range to this query)
	 *
	 * @return object $terms, sql response.
	 */
	public function msa_migrate_tax( $drupal_node_type, $taxonomy, $range = NULL) {

		global $wpdb;

		// Check if migartion was triggered.
		$migration_flag = 'd2w_'. $drupal_node_type;
		if ( get_option( $migration_flag ) ) {
			exit;
		}

		// Migrate taxonomy terms to WP DB 
		$sql = "SELECT DISTINCT (td.name), n.nid, v.name, td.*
			FROM vocabulary v
			INNER JOIN term_data td ON v.vid = td.vid
			INNER JOIN  term_node tn ON td.tid = tn.tid
			INNER JOIN node n  ON tn.vid = (SELECT MAX(nr1.vid ) FROM node_revisions nr1 WHERE nr1.nid = n.nid)
			WHERE n.type = %s GROUP BY td.name";

		$terms = $wpdb->get_results($wpdb->prepare($sql, $drupal_node_type));

		foreach ($terms as $key => $term) {
			$new_term = wp_insert_term(
				$term->name,
				$taxonomy,
				array(
					'description' => $term->description,
				)
			);
		}

		// Set the migration flag to true.
		update_option( $migration_flag, true );

		return $terms;
	}

	/**
	 * Migrate relationship between terms and posts.
	 *
	 * @param string $wp_post_type, WP post type, establish relations of terms with this post type.
	 * @param string $taxonomy, WP destination category, by default WP brings two categories: category and post_tag. Other categories can be created manually or using a plugin(example Pods) 
	 * @param (array) Optional parameter. This parameter allow us to filter the sql for specific values. In this case, the filter can limit the query to specific values of the old node's nid. Helpful in preparation phase for the migration.
	 *	 
	 */
	public function msa_migrate_tax_to_posts( $wp_post_type, $taxonomy, $range = NULL) {

		global $wpdb;

		$query_vars = array($wp_post_type, 'publish');

		$sql_and = '';

		if ($range) {
			if (is_array($range)) {
				switch ($range[0]) {
					case '=':
						$sql_and = ($range) ? ' AND wp.old_ID = %d' : '';
						$query_vars[] = $range[1];
						break;

					case '!=':
						$sql_and = ($range) ? ' AND wp.old_ID != %d' : '';
						$query_vars[] = $range[1];
						break;					

					case '<':
						$sql_and = ($range) ? ' AND wp.old_ID < %d' : '';
						$query_vars[] = $range[1];
						break;

					case '>':
						$sql_and = ($range) ? ' AND wp.old_ID > %d' : '';
						$query_vars[] = $range[1];
						break;

					case '<>':
						$sql_and = ($range) ? ' AND wp.old_ID > %d AND wp.old_ID < %d' : '';
						$query_vars[] = $range[1];
						$query_vars[] = $range[2];
						break;

				}
			}		
		}

			$sql_tax = "SELECT wp.old_ID, wp.ID, tn.tid, td.name
			FROM  wp_posts wp
			INNER JOIN term_node tn ON tn.vid = (SELECT MAX(nr.vid) FROM node_revisions nr WHERE nr.nid = wp.old_ID)
			INNER JOIN   term_data td ON tn.tid = td.tid
			WHERE wp.post_type = %s AND wp.post_status = '%s' ". $sql_and ;

			$res = $wpdb->get_results($wpdb->prepare($sql_tax, $query_vars));

			foreach ($res as $key => $data) {
				$term_id = term_exists( $data->name, $taxonomy);

				// for hierarchical terms use temms id's, non-hirrarchical use term name's
				$out[$data->ID][] = ($taxonomy == 'post_tag')  ? $data->name : $term_id['term_id']; 
			}

			foreach ($out as $post_id => $post_terms) {
				$term = wp_set_post_terms( $post_id, $post_terms, $taxonomy );
			}

			return $term;		
	}

	/**
	 * Drupal node Type => wp taxonomy
	 *
	 * @return Form that triggers the migration of node/taxonomy relation.
	 */
	public function d2w_drupal_node_to_tax_rel() {
		global $wpdb;

		$sql ="SELECT type, name 
		FROM node_type WHERE 1";

		$res = $wpdb->get_results($sql);

		foreach( $res as $key => $data) {
			$type = $data->type;
			$drupal_node_types[$type] = $data->name;
		}

		// HTML From for migration.
		$out = '<form>';

		foreach($drupal_node_types as $type => $name) {

			$out .= '<label> '. $name .' </label>';
			$out .= '<select data-post-type="'. $type .'" data-action="select-tax-rel">';
			$out .= $this->d2w_tax_options( $type );
			$out .= '</select>';
			$out .= ( !get_option( 'd2w_'. $type ) ) ? '<input data-post-type="'. $type .'" class="button button-migrate-tax" type="submit" data-action="migrate-tax-terms" value="Migrate Terms" >' : '<i class="dashicons dashicons-yes"></i>';
			$out .= '<hr>';

		}

		$out .= '</form>';

		return $out;

	}

	public function d2w_tax_options( $drupal_node_type ) {

		// Load default values for options
		$node_tax_rel = get_option('d2w-node-tax-rel');

		$current_type = $node_tax_rel[$drupal_node_type];

		$taxs = get_taxonomies( array('public' => true ), 'names' );

		// Taxonomy options
		$options =  '<option value="0" >Select taxonomy...</option>';
		foreach($taxs as $tax) {
			if ( $current_type == $tax ){
				$options .= '<option selected="selected" value='. $tax .'>'. $tax .'</option>';
			} else {
				$options .= '<option value='. $tax .'>'. $tax .'</option>';
			}
		}

		return $options;

	}

}