<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://mauro.com
 * @since      1.0.0
 *
 * @package    D2w
 * @subpackage D2w/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    D2w
 * @subpackage D2w/includes
 * @author     Mauro <mauro@mojahmedia.net>
 */
class D2w {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      D2w_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'd2w';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - D2w_Loader. Orchestrates the hooks of the plugin.
	 * - D2w_i18n. Defines internationalization functionality.
	 * - D2w_Admin. Defines all hooks for the admin area.
	 * - D2w_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-d2w-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-d2w-public.php';

		/**
		 * Load custom clases with migrate functions
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-migrate-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-migrate_Users.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-post-types.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-migrate-post-fields.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-migrate-taxonomy.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-migrate-comments.php';	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-d2w-migrate-images.php';


		$this->loader = new D2w_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the D2w_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new D2w_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new D2w_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

  	// Add menu item
  	$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

  	// Save meta post
  	$this->loader->add_action( 'save_post', $plugin_admin, 'd2w_save_meta_options' );

    /**
     * Ajax call for migrate pages post type
     */
    $this->loader->add_action( 'wp_ajax_d2w_migrate_page_action', $plugin_admin, 'd2w_migrate_page_handler' );
    $this->loader->add_action( 'wp_ajax_nopriv_d2w_migrate_page_action', $plugin_admin, 'd2w_migrate_page_handler' ); 

    /**
     * Ajax call for creation for field pairing
     */   	
    $this->loader->add_action( 'wp_ajax_d2w_field_relationship_action', $plugin_admin, 'd2w_field_relationship_save' );
    $this->loader->add_action( 'wp_ajax_nopriv_d2w_field_relationship_action', $plugin_admin, 'd2w_field_relationship_save' );

    /**
     * Ajax call for creation for field pairing
     */   	
    $this->loader->add_action( 'wp_ajax_d2w_node_type_relationship_action', $plugin_admin, 'd2w_node_type_relationship_save' );
    $this->loader->add_action( 'wp_ajax_nopriv_d2w_node_type_relationship_action', $plugin_admin, 'd2w_node_type_relationship_save' );

    /**
     * Ajax call for taxonomy migration
     */   	
    $this->loader->add_action( 'wp_ajax_d2w_migrate_tax_action', $plugin_admin, 'd2w_migrate_tax' );
    $this->loader->add_action( 'wp_ajax_nopriv_d2w_migrate_tax_action', $plugin_admin, 'd2w_migrate_tax' );    

    /**
     * Ajax call for migration settings
     */   	
    $this->loader->add_action( 'wp_ajax_d2w_migrate_settings_action', $plugin_admin, 'd2w_migrate_settings_handler' );
    $this->loader->add_action( 'wp_ajax_nopriv_d2w_migrate_settings_action', $plugin_admin, 'd2w_migrate_settings_handler' ); 

    /**
     * Ajax call for migration of Images
     */   	
    $this->loader->add_action( 'wp_ajax_d2w_migrate_images_action', $plugin_admin, 'd2w_migrate_images_handler' );
    $this->loader->add_action( 'wp_ajax_nopriv_d2w_migrate_images_action', $plugin_admin, 'd2w_migrate_images_handler' ); 

    /**
     * Ajax call for Hierarchycal post type
     */   	
    $this->loader->add_action( 'wp_ajax_d2w_hierarchycal_post_action', $plugin_admin, 'd2w_hierarchycal_post_handler' );
    $this->loader->add_action( 'wp_ajax_nopriv_d2w_hierarchycal_post_action', $plugin_admin, 'd2w_hierarchycal_post_handler' );     

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new D2w_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    D2w_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
