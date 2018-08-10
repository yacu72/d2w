<?php
/**
 * Functions related to Migrate Settings .
 *
 * @since      1.0.0
 */
class d2w_Migrate_Settings {

	public function d2w_migrate_settings_values() {

		$settings = get_option( 'd2w_migrate_settings' );

		foreach ( $settings as $key => $data ) {
		  $input = $data[0];
		  $value = $data[1];
		  $default_values[$input] = $value; 
		}

		return $default_values;

	}
}