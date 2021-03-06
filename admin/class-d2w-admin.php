<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://mauro.com
 * @since      1.0.0
 *
 * @package    D2w
 * @subpackage D2w/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    D2w
 * @subpackage D2w/admin
 * @author     Mauro <mauro@mojahmedia.net>
 */
class D2w_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in D2w_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The D2w_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/d2w-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in D2w_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The D2w_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/d2w-admin.js', array( 'jquery' ), $this->version, false );

    /**
     *  In backend there is global ajaxurl variable defined by WordPress itself.
     *
     * This variable is not created by WP in frontend. It means that if you want to use AJAX calls in frontend, then you have to define such variable by yourself.
     * Good way to do this is to use wp_localize_script.
     *
     * @link http://wordpress.stackexchange.com/a/190299/90212
     */
    wp_localize_script( $this->plugin_name, 'wp_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );			

	}

	/**
 	* Register the administration menu for this plugin into the WordPress Dashboard menu.
 	*
 	* @since    1.0.0
 	*/
	public function add_plugin_admin_menu() {
    	/**
    	 * Add a settings page for this plugin to the Settings menu.
    	 *
    	 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
    	 *
    	 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
    	 *
    	 * add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
    	 *
    	 * @link https://codex.wordpress.org/Function_Reference/add_options_page
    	 */
    	add_submenu_page( 'plugins.php', 'Plugin settings page title', 'Admin area D2W', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page')
    	);
	}

	/**
 	* Render the settings page for this plugin.
 	*
 	* @since    1.0.0
 	*/
	public function display_plugin_setup_page() {
    	include_once( 'partials/' . $this->plugin_name . '-admin-display.php' );
	}

	/**
	 * Ajax procress for migration settings
	 */
	public function d2w_migrate_settings_handler() {

		$d2wSettings = $_POST['d2wSettings'];

		update_option('d2w_migrate_settings', $d2wSettings);


		$send_to_ajax = array(
			'd2wSettings' => $d2wSettings,
			'test' => $save,
		);

		echo json_encode($send_to_ajax);

		exit;		
	}

	/**
	 * Ajax process for page migration
	 */
	public function d2w_migrate_page_handler() {
		
		$action = $_POST['action_type'];
		$drupal_type = $_POST['drupal_type'];
		$wp_post_type = '';
		$counter = '';

		if ( $action == 'migrate-users' ) {

			$usersMigrate = new d2w_Migrate_Users;
			$counter = $usersMigrate->d2w_migrate_users_action();

			$out = 'button clicek was user migrate';
			$wp_post_type = 'user';

		}

		if ( $action == 'migrate-content' ) {

			$migratePost = new d2w_Migrate_Post_Types;
			$counter = $migratePost->d2w_migrate_content( $drupal_type );
			
			$out = 'migrate content of the type: '. $drupal_type;			

		}

		if ( $action == 'migrate-post-terms') {

			$migrateTaxonomy = new d2w_Migrate_taxonomy;
			$counter = $migrateTaxonomy->msa_migrate_tax_to_posts( $drupal_type, '', 475 );

			$out = 'migration of post terms';

		}

		if ( $action == 'migrate-post-comments') {

			$migrateComments = new d2w_Migrate_Comments;
			$counter = $migrateComments->d2w_migrate_post_comments( $drupal_type );

			$out = 'Migrating Comments';
		}

		if ( $action == 'migrate-post-images') {
			$migrateImages = new d2w_Migrate_Images;
			$images = $migrateImages->d2w_migrate_images_loader( $drupal_type );
			$counter = $images['post']['counter'];

			$out = "migration of images";
		}

		$send_to_ajax = array(
			'action' => $action,
			'wp_type' => $wp_post_type,
			'msg' => $out,
			'response' => $counter,
			'drupal_node_type' => $_POST['drupal_type'],
		);

		echo json_encode($send_to_ajax);

		exit;
	}

	/**
	 * Ajax process for field pairing
	 */
	public function d2w_field_relationship_save() {

		$post_type = $_POST['post_type'];
		$drupal_field = $_POST['drupal_field'];
		$wp_field = $_POST['pod_field'];

		$field_par = get_option('d2w-fields-par');

		$field_par[$post_type][$drupal_field] = $wp_field;

		$option_saved = update_option( "d2w-fields-par", $field_par );		

		$send_to_ajax = array(
			'data' => $option_saved,
			'fields' => $post_type .'|'. $drupal_field .'|'. $wp_field,
		);

		echo json_encode($send_to_ajax);

		exit;		

	}

	/**
	 * Ajax process for node types pairing
	 */
	public function d2w_node_type_relationship_save() {

		// exit if no wp post type is selected
		if ( !$_POST['wp_post_type']) {
			exit;
		}

		$drupal_post_type = $_POST['drupal_post_type'];
		$wp_post_type = $_POST['wp_post_type'];

		$node_type_par = get_option('d2w-node-types-par');

		$node_type_par[$drupal_post_type] = $wp_post_type;

		$option_saved = update_option( "d2w-node-types-par", $node_type_par );		

		$send_to_ajax = array(
			'data' => $option_saved,
			'fields' => $drupal_post_type .'|'. $wp_post_type,
		);

		echo json_encode($send_to_ajax);

		exit;		

	}

	public function d2w_save_meta_options() {
		global $post;
	}

	/**
	 * Handles Ajax Code for taxonomy migration
	 */
	public function d2w_migrate_tax() {

		$action = $_POST['action_type'];

		if ( $action == 'migrate-tax-rel' ){

			$node_tax_rel = get_option('d2w-node-tax-rel');

			$node_tax_rel[$_POST['drupal_type']] = $_POST['wp_tax'];

			$option_saved = update_option( "d2w-node-tax-rel", $node_tax_rel );

		}

		if ( $action == 'migrate-tax-terms') {
			$migrateTaxonomy = new d2w_Migrate_taxonomy;
			$terms = $migrateTaxonomy->msa_migrate_tax( $_POST['drupal_type'], $_POST['wp_tax'] ); 
		}

		$send_to_ajax = array(
			'action' => $action,
			'drupal_node_type' => $_POST['drupal_type'],
		);

		echo json_encode($send_to_ajax);

		exit;
	}

	/**
	 * Handles Ajax Image Migration
	 */
	public function d2w_migrate_images_handler() {

		$drupal_node_type = $_POST['drupal_type'];
		$size_limit = $_POST['size_limit'] * 1000000; /* covert MB to kb*/
		

		$migrateImages = new d2w_Migrate_Images;

		// Saves batches size for images to import.
		$size_updated = update_option( 'd2w_size_limit_'. $drupal_node_type, $size_limit );

		$images_info = $migrateImages->d2w_migrate_images_info( $drupal_node_type, '', $size_limit );

		

		// Renders HTML list for batch migration.
		$html = $migrateImages->d2w_batch_migration_display( '', $drupal_node_type);

		$send_to_ajax = array(
			'drupal_type' => $drupal_node_type,
			'size_updated' => $size_updated,
			'total_size' => $images_info['total_size'],
			'html' => $html,
		);		

		if ( $_POST['data_action'] == 'migrate-image-group' ){
			$group_id = $_POST['group_id']; // Batch group ID to migrate.

			// Load path to image to download.
			$migrate_settings = get_option( 'd2w_migrate_settings' );
			foreach ($migrate_settings as $key => $data ) {
				if ( $data[0] == 'images-url' ) {
					$image_path = $data[1];
				}
			}			

			$images = get_option('d2w_'. $drupal_node_type .'_images_import');

			foreach( $images[$group_id] as $index => $image ){

				// TODO. do this replace more generic, maybe adding something in settings section.
				$path = str_replace( 'sites/dev.medstudentadvisors.do5.mflw.us/files', 'sites/medstudentadvisors.com/files', $image['path'] );


				$migrateImages->d2w_migrate_image( '' , $image_path .'/'. $path, $image['ID'] );

				$migrateImages->d2w_image_content_filter( $image['ID'] );

				$paths .= $path .', ';

				$parents .= $image['ID'] .', ';

			}			

			$send_to_ajax = array(
				'drupal_type' => $drupal_node_type,
				'group_id' => $group_id,
				'paths' => $paths,
				'parents' => $parents,
			);

		}



		echo json_encode( $send_to_ajax );

		exit;
	}

	/**
	 * Handles AJAX hierarchycal post type call 
	 */
	public function d2w_hierarchycal_post_handler() {
		
		$hierarchycal = $_POST['hierarchical'];
    $drupal_node_type = $_POST['drupal_type'];
    $status = '';

    // Set the migration flag to true.
		$status = update_option( 'd2w_'. $drupal_node_type .'_hierarchycal', $hierarchycal );

		$send_to_ajax = array(
			'status' => $status,
		);

    echo json_encode( $send_to_ajax );

    exit;
	}

	/**
	 * Ajax Response: Saves field type
	 */
	public function d2w_field_type_handler(){

		$drupal_node_type = $_POST['drupal_type'];
		$drupal_field_name = $_POST['drupal_field'];
		$field_type = $_POST['field_type'];

		$field_types = get_option('d2w_field_types');

		$field_types[$drupal_field_name] = $field_type;

		$field_type_saved = update_option( 'd2w_field_types', $field_types );

		$send_to_ajax = array(
			'saved' => $field_type_saved,
		);

		echo json_encode( $send_to_ajax );
		
		exit;
	}


	public function d2w_migrate_filefield_handler() {

		$migrateImages = new d2w_Migrate_Images;

		// Collects Data from ajax
		$form_data = $_POST['formData'];

		// Migrate Files
		if ( $form_data['button'] == 'migrate-files' ){

	 		$formData = $_POST['formData'];

			// Load path to image to download.
			$migrate_settings = get_option( 'd2w_migrate_settings' );
			foreach ($migrate_settings as $key => $data ) {
				if ( $data[0] == 'images-url' ) {
					$image_path = $data[1];
				}
			}			

			$images = get_option('d2w_filefields_data');

			foreach( $images[$formData['drupal_node_type']][$formData['group_id']] as $ID => $image ){

				// TODO. do this replace more generic, maybe adding something in settings section.
				$path = str_replace( 'sites/dev.medstudentadvisors.do5.mflw.us/files', 'sites/medstudentadvisors.com/files', $image );


				$counter = $migrateImages->d2w_migrate_image( '' , $image_path .'/'. $path, $ID );

				$migrateImages->d2w_image_content_filter( $ID );

				$paths .= $path .', ';

				$parents .= $ID .', ';
			}

			$send_to_ajax = array(
  			'counter' => $counter,
				'drupal_type' => $formData['drupal_node_type'],
				'group' => $formData['group_id'],
			);				 		

		}

		// Update filefield 
		if ( $form_data['button'] == 'set_filename' ){

			$filefields = get_option('d2w_drupal_filefields');

			$filefields[$form_data['drupal_node_type']][$form_data['drupal_field']] = $form_data['drupal_field'];

			if ( $form_data['drupal_field'] == '0') {
				unset($filefields[$form_data['drupal_node_type']]);
			}

			$status = update_option( 'd2w_drupal_filefields', $filefields );

			if ( $status ){
				foreach( $filefields  as $node_type => $field_name ){
					$counter = count(  $field_name );
				}
			}

			$send_to_ajax = array(
				'status' => $status,
				'data' => $counter,
				'field' => $form_data['drupal_field'],
			);
		}

		// Update filesixe limit
		if ( $form_data['button'] == 'set_filesize_limit' ){

			$filefield_size_limits = get_option('d2w_filefield_size_limits');

			$filefield_size_limits[$form_data['drupal_node_type']][$form_data['drupal_field']] = $form_data['filesize_limit'];

			$status = update_option( 'd2w_filefield_size_limits', $filefield_size_limits );

			

			$files = $migrateImages->d2w_files_by_filefield( $form_data['drupal_node_type'], $form_data['drupal_field'] );

			$send_to_ajax = array(
				'status' => $status,
				'drupal_type' => $form_data['drupal_node_type'],
				'drupal_field' => $form_data['drupal_field'],
				'html' => $files['html'],
			);

		}

		echo json_encode( $send_to_ajax );
		
		exit;		
	}


}
