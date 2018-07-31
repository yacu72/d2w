<?php
/**
 * Functions related to the migration of Users from Drupal to Wordpress.
 *
 * @since      1.0.0
 */
class d2w_Migrate_Users {

	/**
	 * Helper function: Count the number of users in drupal table
	 *
	 * @return $num_of_rows: the total number o users to migrate
	 */
	public function d2w_migrate_users_counter() {
		global $wpdb;

		$user_migrated_flag = '';

		$count_query = "SELECT DISTINCT COUNT(uid) FROM users";

		$num_of_rows = $wpdb->get_var($count_query);

		return $num_of_rows;
	}

	/**
	 * Migrate old user from drupal to wprdpress.
	 *
	 * @return $wp_user_id: the new ID of the migrated user.
	 */
	public function d2w_migrate_users_action( ) {
		global $wpdb;

		$sql = "SELECT * FROM users WHERE 1";

		$res = $wpdb->get_results($sql);

		foreach($res as $key => $user) {
			$user_id = username_exists( $user->name ); 
			if (!$user_id) {
			
				//$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$wp_user_id = wp_create_user( $user->name, $user->pass, $user->mail );
				
				// Migrate old uid values to new DB
				$wpdb->update($wpdb->users, array('old_uid' => $user->uid), array('ID' => $wp_user_id));
			
			} else {
				
				echo('user exists'); 
			
			}
		
		}
		
		if (is_numeric( $wp_user_id )) {
			update_option( 'd2w_users_migrated', true );
		}

		return $wp_user_id;
	}

}