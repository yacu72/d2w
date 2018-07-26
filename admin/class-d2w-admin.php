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
    	add_submenu_page( 'plugins.php', 'Plugin settings page title', 'Admin area menu slug', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page')
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

}
