<?php
namespace WpUlikePro\Includes\Elementor;


/**
 * WpUlike Elementor Elements
 *
 * Custom Elementor extension.
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Main WpUlike Elementor Elements Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class Elements {

    /**
     * Plugin Version
     *
     * @since 1.0.0
     *
     * @var string The plugin version.
     */
    const VERSION = '1.0.0';

    /**
     * Minimum Elementor Version
     *
     * @since 1.0.0
     *
     * @var string Minimum Elementor version required to run the plugin.
     */
    const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

    /**
     * Minimum PHP Version
     *
     * @since 1.0.0
     *
     * @var string Minimum PHP version required to run the plugin.
     */
    const MINIMUM_PHP_VERSION = '5.4.0';

    /**
     * Default elementor dir path
     *
     * @since 1.0.0
     *
     * @var string The defualt path to elementor dir on this plugin.
     */
    private $dir_path = '';

    /**
     * Instance
     *
     * @since 1.0.0
     *
     * @access private
     * @static
     *
     * @var Elements The single instance of the class.
    */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @access public
     * @static
     *
     * @return Elements An instance of the class.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
          self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin
     *
     * Load the plugin only after Elementor (and other plugins) are loaded.
     *
     * @since 1.0.0
     *
     * @access public
    */
    public function init() {

        // Check if Elementor installed and activated
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        // Check for required Elementor version
        if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
            return;
        }

        // Check for required PHP version
        if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
            return;
        }

        // Define elementor dir path
        $this->dir_path = WP_ULIKE_PRO_INC_DIR . '/elementor';

        // Include core files
        $this->includes();

        // Add required hooks
        $this->hooks();
    }

    /**
     * Include Files
     *
     * Load required core files.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function includes() {
        $this->load_modules();
    }

    /**
     * Add hooks
     *
     * Add required hooks for extending the Elementor.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function hooks() {

        // Register controls, widgets, and categories
        add_action( 'elementor/elements/categories_registered' , array( $this, 'register_categories' ) );
        add_action( 'elementor/widgets/widgets_registered'     , array( $this, 'register_widgets'    ) );
        // add_action( 'elementor/controls/controls_registered'   , array( $this, 'register_controls'   ) );

        // Register Admin Scripts
        add_action( 'elementor/editor/before_enqueue_scripts'  , array( $this, 'editor_scripts' ) );
    }

    /**
     * Register widgets
     *
     * Register all wp-ulike widgets which are in widgets list.
     *
     * @access public
     */
    public function register_widgets( $widgets_manager ) {

        $widgets = array(

            '10' => array(
                'file'  => $this->dir_path . '/widgets/posts-button.php',
                'class' => 'Elements\PostsButton'
            ),
            '20' => array(
                'file'  => $this->dir_path . '/widgets/counter.php',
                'class' => 'Elements\Counter'
            ),
            '30' => array(
                'file'  => $this->dir_path . '/widgets/most-liked-posts.php',
                'class' => 'Elements\MostLikedPosts'
            ),
            '40' => array(
                'file'  => $this->dir_path . '/widgets/most-liked-comments.php',
                'class' => 'Elements\MostLikedComments'
            ),
            '50' => array(
                'file'  => $this->dir_path . '/widgets/most-liked-activities.php',
                'class' => 'Elements\MostLikedActivities'
            ),
            '60' => array(
                'file'  => $this->dir_path . '/widgets/most-liked-topics.php',
                'class' => 'Elements\MostLikedTopics'
            ),
            '70' => array(
                'file'  => $this->dir_path . '/widgets/best-likers.php',
                'class' => 'Elements\BestLikers'
            )
        );

        // sort the widgets by priority number
        ksort( $widgets );

        // making the list of widgets filterable
        $widgets = apply_filters( 'wp_ulike_pro/elementor/widgets_list', $widgets, $widgets_manager );

        foreach ( $widgets as $widget ) {
            if( ! empty( $widget['file'] ) && ! empty( $widget['class'] ) ){
                include_once( $widget['file'] );
                if( class_exists( $widget['class'] ) ){
                    $class_name = $widget['class'];
                } elseif( class_exists( __NAMESPACE__ . '\\' . $widget['class'] ) ){
                    $class_name = __NAMESPACE__ . '\\' . $widget['class'];
                } else {
                    trigger_error( sprintf( esc_html__('Element class "%s" not found.', WP_ULIKE_PRO_DOMAIN ), $class_name ) );
                    continue;
                }
                $widgets_manager->register_widget_type( new $class_name() );
            }
        }
    }

    /**
     * Load Modules
     *
     * Load all wp-ulike elementor modules.
     *
     * @since 1.0.0
     *
     * @access public
     */
    private function load_modules() {

        $modules = array(
            array(
                'file'  => $this->dir_path . '/modules/common.php',
                'class' => 'Modules\Common'
            ),
            array(
                'file'  => $this->dir_path . '/modules/query-control/module.php',
                'class' => 'Modules\QueryControl\Module'
            ),
        );

        foreach ( $modules as $module ) {
            if( ! empty( $module['file'] ) && ! empty( $module['class'] ) ){
                include_once( $module['file'] );

                if( isset( $module['instance'] ) ) {
                    continue;
                }

                if( class_exists( __NAMESPACE__ . '\\' . $module['class'] ) ){
                    $class_name = __NAMESPACE__ . '\\' . $module['class'];
                } else {
                    trigger_error( sprintf( esc_html__('Module class "%s" not found.', WP_ULIKE_PRO_DOMAIN ), $class_name ) );
                    continue;
                }
                new $class_name();
            }
        }
    }


    /**
     * Register controls
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function register_controls( $controls_manager ) {

        $controls = array(
            // 'wp-ulike-visual-select' => array(
            //     'file'  => $this->dir_path . '/controls/visual-select.php',
            //     'class' => 'Controls\Control_Visual_Select',
            //     'type'  => 'single'
            // )
        );

        foreach ( $controls as $control_type => $control_info ) {
            if( ! empty( $control_info['file'] ) && ! empty( $control_info['class'] ) ){
                include_once( $control_info['file'] );

                if( class_exists( $control_info['class'] ) ){
                    $class_name = $control_info['class'];
                } elseif( class_exists( __NAMESPACE__ . '\\' . $control_info['class'] ) ){
                    $class_name = __NAMESPACE__ . '\\' . $control_info['class'];
                }

                if( $control_info['type'] === 'group' ){
                    $controls_manager->add_group_control( $control_type, new $class_name() );
                } else {
                    $controls_manager->register_control( $control_type, new $class_name() );
                }

            }
        }
    }

    /**
     * Register categories
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function register_categories( $categories_manager ) {

        $categories_manager->add_category(
            WP_ULIKE_PRO_DOMAIN,
            array(
                'title' => WP_ULIKE_PRO_NAME,
                'icon' => 'eicon-font',
            )
        );

    }

    /**
     * Enqueue scripts.
     *
     * Enqueue all the backend scripts.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function editor_scripts() {
        // Elementor Custom Scripts
        wp_enqueue_script( 'wp-ulike-elementor-editor', WP_ULIKE_PRO_ADMIN_URL . '/assets/js/elementor/editor.js', array('jquery'), WP_ULIKE_PRO_VERSION );
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required Elementor version.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function admin_notice_minimum_elementor_version() {

        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

        $message = sprintf(
          esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', WP_ULIKE_PRO_DOMAIN ),
          '<strong>' . WP_ULIKE_PRO_NAME . '</strong>',
          '<strong>' . esc_html__( 'Elementor', WP_ULIKE_PRO_DOMAIN ) . '</strong>',
           self::MINIMUM_ELEMENTOR_VERSION
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required PHP version.
     *
     * @since 1.0.0
     *
     * @access public
     */
    public function admin_notice_minimum_php_version() {

        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

        $message = sprintf(
          /* translators: 1: Plugin name 2: PHP 3: Required PHP version */
          esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', WP_ULIKE_PRO_DOMAIN ),
          '<strong>' . WP_ULIKE_PRO_NAME . '</strong>',
          '<strong>' . esc_html__( 'PHP', WP_ULIKE_PRO_DOMAIN ) . '</strong>',
           self::MINIMUM_PHP_VERSION
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

}

Elements::instance();
