<?php
/**
 * WP ULIKE PRO BASE CLASS
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

if ( ! class_exists( 'WP_Ulike_Pro' ) ) :

  class WP_Ulike_Pro {

    /**
     * Unique identifier for your plugin.
     *
     * The variable name is used as the text domain when internationalizing strings of text.
     *
     * @var      string
     */
    protected $plugin_slug = WP_ULIKE_PRO_DOMAIN;

    /**
     * Instance of this class.
     *
     * @var      object
     */
    protected static $instance = null;


    /**
     * Instance of Admin class.
     *
     * @var      object
     */
    public $admin = null;

    /**
     * Initialize the plugin
     *
     * @since     1.0.0
     */
    private function __construct() {
      // Include packages
      $this->includes();

      // Loaded action
      do_action( 'wp_ulike_pro_loaded' );
    }


    /**
     *
     * @return [type] [description]
    */
    private function includes() {

      // Auto-load classes on demand
      if ( function_exists( "__autoload" ) ) {
        spl_autoload_register( "__autoload" );
      }
      spl_autoload_register( array( $this, 'autoload' ) );

      // Load plugin text domain
      $this->load_plugin_textdomain();
      // maybe upgrade database
      $this->maybe_upgrade_database();

      // load packages
      include_once( WP_ULIKE_PRO_DIR . '/vendor/autoload.php' );
      // load common functionalities
      include_once( WP_ULIKE_PRO_DIR . '/includes/index.php' );


      // Dashboard and Administrative Functionality
      if ( self::is_admin_backend() ) {
        include_once( WP_ULIKE_PRO_DIR . '/admin/class-admin.php' );

        // Load AJAX specifics codes on demand
        if ( self::is_ajax() ){
          // register public events (ajax controllers)
          new WP_Ulike_Pro_Register_Public_Events;
          // Register admin events
          include( WP_ULIKE_PRO_DIR . '/admin/includes/admin-ajax.php' );
        }
      }

      // Load Frontend Functionality
      if( self::is_frontend() ){
        include ( 'includes/index.php' );
      }

    }

    /**
     * maybe upgrade database fields
     *
     * @return void
     */
    private function maybe_upgrade_database(){
      $current_version = get_option( 'wp_ulike_pro_database_version', '1.0.0' );

      if( ! class_exists('WP_Ulike_Pro_Activator') ){
        require_once WP_ULIKE_PRO_DIR . 'public/class-activator.php';
      }

      // Check database upgrade if needed
      if ( version_compare( $current_version, '1.0.1', '<' ) ) {
        WP_Ulike_Pro_Activator::activate();
      }
    }

    /**
     * Is edit mode
     *
     * @return bool
     */
    public static function is_edit_mode(){
      // Check elementor front-end
      $actions = [
          'elementor',

          // Templates
          'elementor_get_templates',
          'elementor_save_template',
          'elementor_get_template',
          'elementor_delete_template',
          'elementor_export_template',
          'elementor_import_template',
      ];
      if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $actions ) ) {
        return true;
      }

      return false;
    }

    /**
     * Is preview mode
     *
     * @return bool
     */
    public static function is_preview_mode(){

      if( isset( $_GET['preview'] ) && $_GET['preview'] ){
        return true;
      }

      // Whether the site is being previewed in the Elementor.
      if ( defined('ELEMENTOR_VERSION') && class_exists('Elementor\Plugin') ) {
        if ( \Elementor\Plugin::$instance->preview->is_preview_mode() || \Elementor\Plugin::$instance->editor->is_edit_mode() || isset( $_GET['elementor-preview'] ) ) {
          return true;
        }
      }

      // Whether the site is being previewed in the Bricks builder.
      if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
        return true;
      }

      // Whether the site is being previewed in the Customizer.
      if( is_customize_preview() ){
        return true;
      }

      return apply_filters( 'wp_ulike_pro_is_preview_mode', false );
    }

    /**
     * Is ajax
     *
     * @return bool
     */
    public static function is_ajax() {
      return ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || defined( 'DOING_AJAX' );
    }

    /**
     * Is admin
     *
     * @return bool
     */
    public static function is_admin_backend() {
      return is_admin();
    }

    /**
     * Is cron
     *
     * @return bool
     */
    public static function is_cron() {
      return ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || defined( 'DOING_CRON' );
    }

    /**
     * Is rest
     *
     * @return bool
     */
    public static function is_rest() {
      return defined( 'REST_REQUEST' );
    }

    /**
     * Is frontend
     *
     * @return bool
     */
    public static function is_frontend() {
      return ( ! self::is_admin_backend() || ! self::is_ajax() ) && ! self::is_cron() && ! self::is_rest();
    }

    /**
     * Auto-load classes on demand to reduce memory consumption
     *
     * @param mixed $class
     * @return void
     */
    public function autoload( $class ) {
      $path  = null;
      $class = strtolower( $class );
      $file = 'class-' . str_replace( '_', '-', str_replace( 'wp_ulike_pro_', '',  $class ) ) . '.php';

      // the possible pathes containing classes
      $possible_pathes = array(
          WP_ULIKE_PRO_DIR . '/includes/classes/',
          WP_ULIKE_PRO_DIR . '/public/includes/classes/',
          WP_ULIKE_PRO_DIR . '/admin/includes/classes/'
      );

      foreach ( $possible_pathes as $path ) {
          if( is_readable( $path . $file ) ){
              include_once( $path . $file );
              return;
          }

      }
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    3.1
     */
    public function load_plugin_textdomain() {
			// Set filter for language directory
			$lang_dir = WP_ULIKE_PRO_DIR . 'languages/';
			$lang_dir = apply_filters( 'wp_ulike_pro_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), WP_ULIKE_PRO_DOMAIN );
			$mofile = sprintf( '%1$s-%2$s.mo', WP_ULIKE_PRO_DOMAIN, $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/plugins/' . WP_ULIKE_PRO_DOMAIN . '/' . $mofile;

			if( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/plugins/wp-ulike/ folder
				load_textdomain( WP_ULIKE_PRO_DOMAIN, $mofile_global );
			} elseif( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/wp-ulike/languages/ folder
				load_textdomain( WP_ULIKE_PRO_DOMAIN, $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( WP_ULIKE_PRO_DOMAIN, false, $lang_dir );
			}
    }

    /**
    * Return an instance of this class.
    *
    * @return    object    A single instance of this class.
    */
    public static function get_instance() {
      // If the single instance hasn't been set, set it now.
      if ( null == self::$instance ) {
        self::$instance = new self;
      }

      return self::$instance;
    }

  }

endif;

WP_Ulike_Pro::get_instance();