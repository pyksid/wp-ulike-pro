<?php
/**
 * Post Type Metaboxes
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_Meta_Box {

    protected $option_domain = 'wp_ulike_pro_meta_box';

    /**
     * __construct
     */
    function __construct() {
        add_action( 'ulf_loaded', array( $this, 'register_meta_panel' ) );
    }

    public function register_meta_panel(){

        // Set a unique slug-like ID
        $post_types   = wp_ulike_get_option( 'enable_meta_box', array( 'post', 'page' ) );
        // If post type not selected return
        if( empty( $post_types ) ){
            return;
        }

        $is_serialize = wp_ulike_get_option( 'enable_serialize', false );

        // Option prefix name
        $prefix = wp_ulike_is_true( $is_serialize ) ? '' : 'wp_ulike_pro_';

        // Create a metabox
        ULF::createMetabox( $this->option_domain, array(
            'title'     => WP_ULIKE_PRO_NAME . ' ' . esc_html__('Metabox Tools', WP_ULIKE_PRO_DOMAIN),
            'post_type' => $post_types,
            'data_type' => wp_ulike_is_true( $is_serialize ) ? 'serialize' : 'unserialize',
            'theme'     => 'light wp-ulike-post-metabox-panel'
        ) );

        if( ! WP_Ulike_Pro_API::has_permission() ){
            // General section
            ULF::createSection( $this->option_domain, array(
                'fields' => array(
                    array(
                        'type'    => 'notice',
                        'style'   => 'danger',
                        'content' => sprintf( '<p>%s</p><a class="button" href="%s">%s</a>', esc_html__( 'Features of the Pro version are only available once you have registered your license. If you don\'t yet have a license key, get WP ULike Pro now.' , WP_ULIKE_PRO_DOMAIN ), self_admin_url( 'admin.php?page=wp-ulike-pro-license' ), esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ) ),
                    )
                )
            ) );
            return;
        }

        // General section
        ULF::createSection( $this->option_domain, array(
            'title'  => esc_html__('General', WP_ULIKE_PRO_DOMAIN),
            'fields' => array(
                array(
                    'title' => esc_html__('Display Button', WP_ULIKE_PRO_DOMAIN),
                    'id'   => $prefix . 'auto_display',
                    'desc' => esc_html__('Enable auto display if not activated in settings panel.', WP_ULIKE_PRO_DOMAIN),
                    'type' => 'switcher',
                ),
                array(
                    'title'      => esc_html__('Button Template', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'template',
                    'type'       => 'image_select',
                    'title'      => esc_html__( 'Select a Template',WP_ULIKE_PRO_DOMAIN),
                    'desc'       => sprintf( '%s <a target="_blank" href="%s" title="Click">%s</a>', esc_html__( 'Display online preview',WP_ULIKE_PRO_DOMAIN),  WP_ULIKE_PLUGIN_URI . 'templates/?utm_source=metabox-section&utm_campaign=plugin-uri&utm_medium=wp-dash',esc_html__( 'Here',WP_ULIKE_PRO_DOMAIN) ),
                    'options'    => $this->get_templates_option_array(),
                    'class'      => 'wp-ulike-visual-select',
                    'dependency' => array( $prefix . 'auto_display', '==', 'true' ),
                ),
                array(
                    'title'             => esc_html__('Button Position', WP_ULIKE_PRO_DOMAIN),
                    'id'               => $prefix . 'display_position',
                    'type'             => 'radio',
                    'options'          => array(
                        'top'        => esc_html__('Top of Content', WP_ULIKE_PRO_DOMAIN),
                        'bottom'     => esc_html__('Bottom of Content', WP_ULIKE_PRO_DOMAIN),
                        'top_bottom' => esc_html__('Top and Bottom', WP_ULIKE_PRO_DOMAIN)
                    ),
                    'dependency'  => array( $prefix . 'auto_display', '==', 'true' ),
                ),
                array(
                    'title'       => esc_html__('Likes Counter Quantity', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'likes_counter_quantity',
                    'type'       => 'number',
                    'default'    => 0,
                    'unit'       => esc_html__('Likes', WP_ULIKE_PRO_DOMAIN),
                ),
                array(
                    'title'      => esc_html__('Dislikes Counter Quantity', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'dislikes_counter_quantity',
                    'type'       => 'number',
                    'default'    => 0,
                    'unit'       => esc_html__('Dislikes', WP_ULIKE_PRO_DOMAIN)
                )
            )
        ) );

        // Schema generator section
        ULF::createSection( $this->option_domain, array(
            'title'  => esc_html__('Schema Generator', WP_ULIKE_PRO_DOMAIN),
            'fields' => array(
                array(
                    'title' => esc_html__('Enable Schema', WP_ULIKE_PRO_DOMAIN),
                    'id'    => $prefix . 'enable_schema',
                    'desc'  => sprintf( '%s <a href="https://schema.org/docs/gs.html" target="_blank">%s</a>',  esc_html__('Schema provides a collection of shared vocabularies webmasters can use to mark up their pages in ways that can be understood by the major search engines: Google, Microsoft, Yandex and Yahoo!', WP_ULIKE_PRO_DOMAIN),  esc_html__('More Information', WP_ULIKE_PRO_DOMAIN) ),
                    'type'  => 'switcher',
                ),
                array(
                    'title'   => esc_html__('Schema Type', WP_ULIKE_PRO_DOMAIN),
                    'desc'    => esc_html__('The schemas are a set of types, each associated with a set of properties. The types are arranged in a hierarchy.', WP_ULIKE_PRO_DOMAIN),
                    'id'      => $prefix . 'schema_type',
                    'chosen'  => true,
                    'settings'=> array(
                        'width' => '50%'
                    ),
                    'type'    => 'select',
                    'options' => array(
                        'Book'                => esc_html__('Book', WP_ULIKE_PRO_DOMAIN),
                        'Course'              => esc_html__('Course', WP_ULIKE_PRO_DOMAIN),
                        'HowTo'               => esc_html__('How-to', WP_ULIKE_PRO_DOMAIN),
                        'Event'               => esc_html__('Event', WP_ULIKE_PRO_DOMAIN),
                        'LocalBusiness'       => esc_html__('Local Business', WP_ULIKE_PRO_DOMAIN),
                        'Movie'               => esc_html__('Movie', WP_ULIKE_PRO_DOMAIN),
                        'Product'             => esc_html__('Product', WP_ULIKE_PRO_DOMAIN),
                        // 'Recipe'              => esc_html__('Recipe', WP_ULIKE_PRO_DOMAIN),
                        'SoftwareApplication' => esc_html__('Software App', WP_ULIKE_PRO_DOMAIN),
                        'CreativeWorkSeason'  => esc_html__('CreativeWorkSeason', WP_ULIKE_PRO_DOMAIN),
                        'CreativeWorkSeries'  => esc_html__('CreativeWorkSeries', WP_ULIKE_PRO_DOMAIN),
                        'Episode'             => esc_html__('Episode', WP_ULIKE_PRO_DOMAIN),
                        'Game'                => esc_html__('Game', WP_ULIKE_PRO_DOMAIN),
                        'MediaObject'         => esc_html__('MediaObject', WP_ULIKE_PRO_DOMAIN),
                        'MusicPlaylist'       => esc_html__('MusicPlaylist', WP_ULIKE_PRO_DOMAIN),
                        // 'MusicRecording'      => esc_html__('MusicRecording', WP_ULIKE_PRO_DOMAIN),
                        'Organization'        => esc_html__('Organization', WP_ULIKE_PRO_DOMAIN)
                    ),
                    'placeholder' => esc_html__('Select an option', WP_ULIKE_PRO_DOMAIN),
                    'dependency'  => array( $prefix . 'enable_schema', '==', 'true' ),
                ),
                array(
                    'title'      => esc_html__('Title', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The name of the item. (Put it empty if you want to use post title)', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'title',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'enable_schema', '==', 'true' ),
                ),
                array(
                    'title'      => esc_html__('Description', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'description',
                    'type'       => 'textarea',
                    'dependency' => array( $prefix . 'enable_schema', '==', 'true' ),
                ),
                array(
                    'title'      => esc_html__('Name/Brand/Actor', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'name',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Course,Product,CreativeWorkSeason,Episode,Organization,SoftwareApplication,HowTo|true' )
                ),
                array(
                    'title'       => esc_html__('Author/Performer/Director', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'author',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Book,Event,Movie,CreativeWorkSeason,Episode|true' )
                ),
                array(
                    'title'    => esc_html__('Days of Week', WP_ULIKE_PRO_DOMAIN),
                    'id'       => $prefix . 'day_of_week',
                    'type'     => 'select',
                    'chosen'   => true,
                    'multiple' => true,
                    'options'  => array(
                        'Sunday'    => 'Sunday',
                        'Monday'    => 'Monday',
                        'Tuesday'   => 'Tuesday',
                        'Wednesday' => 'Wednesday',
                        'Thursday'  => 'Thursday',
                        'Friday'    => 'Friday',
                        'Saturday'  => 'Saturday'
                    ),
                    'placeholder' => esc_html__('Select an option', WP_ULIKE_PRO_DOMAIN),
                    'dependency'  => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness|true' )
                ),
                array(
                    'title'       =>  esc_html__('Opens', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The time the business location opens.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'opens',
                    'type'       => 'date',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness|true' )
                ),

                array(
                    'title'       =>  esc_html__('Closes', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The start date of a seasonal business closure.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'closes',
                    'type'       => 'date',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness|true' )
                ),

                array(
                    'title'       => esc_html__('Location Name', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'location',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Event|true' )
                ),

                array(
                    'title'       => esc_html__('Street Address', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'street_address',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Event,Organization|true' )
                ),
                array(
                    'title'       => esc_html__('Street Address', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'street_address',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Event,Organization|true' )
                ),
                array(
                    'title'       => esc_html__('Address Locality', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'address_locality',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Event,Organization|true' )
                ),
                array(
                    'title'       => esc_html__('Address Region', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'address_region',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Event,Organization|true' )
                ),
                array(
                    'title'       => esc_html__('Postal Code', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'postal_code',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Event,Organization|true' )
                ),
                array(
                    'title'       => esc_html__('Address Country', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'address_country',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Event,Organization|true' )
                ),
                array(
                    'title'       => esc_html__('Telephone', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'telephone',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Organization|true' )
                ),
                array(
                    'title'       => esc_html__('Price Range', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'price_range',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness|true' )
                ),
                array(
                    'title'       => esc_html__('Start Date', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'start_date',
                    'type'       => 'date',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Event,CreativeWorkSeason,CreativeWorkSeries|true' )
                ),
                array(
                    'title'       => esc_html__('End Date', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'end_date',
                    'type'       => 'date',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Event,CreativeWorkSeason,CreativeWorkSeries|true' )
                ),
                array(
                    'title'      => esc_html__('Created/Published Date', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'created_date',
                    'type'       => 'date',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Movie,Episode|true' )
                ),
                array(
                    'title'      => esc_html__('Price', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'price',
                    'type'       => 'number',
                    'unit'       => '$',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Event,Product,SoftwareApplication,Game,HowTo|true' )
                ),
                array(
                    'title'       => esc_html__('Price Curreny', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'price_currency',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Event,Product,SoftwareApplication,Game,HowTo|true' )
                ),
                array(
                    'title'             => esc_html__('Availability', WP_ULIKE_PRO_DOMAIN),
                    'id'               => $prefix . 'availability',
                    'type'             => 'select',
                    'options'          => array(
                        'InStock'  => esc_html__('InStock', WP_ULIKE_PRO_DOMAIN),
                        'PreOrder' => esc_html__('PreOrder', WP_ULIKE_PRO_DOMAIN),
                        'SoldOut'  => esc_html__('SoldOut', WP_ULIKE_PRO_DOMAIN)
                    ),
                    'placeholder' => esc_html__('Select an option', WP_ULIKE_PRO_DOMAIN),
                    'dependency'  => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Event,Product,SoftwareApplication|true' )
                ),
                array(
                    'title'       => esc_html__('Valid Date', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'valid_date',
                    'type'       => 'date',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Event,Product|true' )
                ),
                array(
                    'title'       => esc_html__('Url', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'url',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Book,Event,Product,Organization,MediaObject,SoftwareApplication|true' )
                ),
                array(
                    'title'       => esc_html__('SKU', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('Merchant-specific identifier for product.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'sku',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Product|true' )
                ),
                array(
                    'title'       => esc_html__('MPN', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('Include all applicable global identifiers; these are described at schema.org/Product|true', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'mpn',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Product|true' )
                ),
                array(
                    'title'    => esc_html__('Application Category', WP_ULIKE_PRO_DOMAIN),
                    'id'       => $prefix . 'application_category',
                    'type'     => 'select',
                    'chosen'   => true,
                    'settings'=> array(
                        'width' => '50%'
                    ),
                    'options'  => array(
                        'GameApplication'               => esc_html__('GameApplication', WP_ULIKE_PRO_DOMAIN),
                        'SocialNetworkingApplication'   => esc_html__('SocialNetworkingApplication', WP_ULIKE_PRO_DOMAIN),
                        'TravelApplication'             => esc_html__('TravelApplication', WP_ULIKE_PRO_DOMAIN),
                        'ShoppingApplication'           => esc_html__('ShoppingApplication', WP_ULIKE_PRO_DOMAIN),
                        'SportsApplication'             => esc_html__('SportsApplication', WP_ULIKE_PRO_DOMAIN),
                        'LifestyleApplication'          => esc_html__('LifestyleApplication', WP_ULIKE_PRO_DOMAIN),
                        'BusinessApplication'           => esc_html__('BusinessApplication', WP_ULIKE_PRO_DOMAIN),
                        'DesignApplication'             => esc_html__('DesignApplication', WP_ULIKE_PRO_DOMAIN),
                        'DeveloperApplication'          => esc_html__('DeveloperApplication', WP_ULIKE_PRO_DOMAIN),
                        'DriverApplication'             => esc_html__('DriverApplication', WP_ULIKE_PRO_DOMAIN),
                        'EducationalApplication'        => esc_html__('EducationalApplication', WP_ULIKE_PRO_DOMAIN),
                        'HealthApplication'             => esc_html__('HealthApplication', WP_ULIKE_PRO_DOMAIN),
                        'FinanceApplication'            => esc_html__('FinanceApplication', WP_ULIKE_PRO_DOMAIN),
                        'SecurityApplication'           => esc_html__('SecurityApplication', WP_ULIKE_PRO_DOMAIN),
                        'BrowserApplication'            => esc_html__('BrowserApplication', WP_ULIKE_PRO_DOMAIN),
                        'CommunicationApplication'      => esc_html__('CommunicationApplication', WP_ULIKE_PRO_DOMAIN),
                        'DesktopEnhancementApplication' => esc_html__('DesktopEnhancementApplication', WP_ULIKE_PRO_DOMAIN),
                        'EntertainmentApplication'      => esc_html__('EntertainmentApplication', WP_ULIKE_PRO_DOMAIN),
                        'MultimediaApplication'         => esc_html__('MultimediaApplication', WP_ULIKE_PRO_DOMAIN),
                        'HomeApplication'               => esc_html__('HomeApplication', WP_ULIKE_PRO_DOMAIN),
                        'ReferenceApplication'          => esc_html__('ReferenceApplication', WP_ULIKE_PRO_DOMAIN),
                        'UtilitiesApplication'          => esc_html__('UtilitiesApplication', WP_ULIKE_PRO_DOMAIN),
                        'MedicalApplication'            => esc_html__('MedicalApplication', WP_ULIKE_PRO_DOMAIN),
                        'OtherApplication'              => esc_html__('OtherApplication', WP_ULIKE_PRO_DOMAIN)
                    ),
                    'placeholder' => esc_html__('Select an option', WP_ULIKE_PRO_DOMAIN),
                    'dependency'  => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'SoftwareApplication|true' )
                ),
                array(
                    'title'      => esc_html__('Operating System', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'operating_system',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'SoftwareApplication|true' )
                ),
                array(
                    'title'      => esc_html__('Software Version', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'software_version',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'SoftwareApplication|true' )
                ),
                array(
                    'title'      => esc_html__('Is Accessible For Free?', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('A flag to signal that the item, event, or place is accessible for free.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . ' is_accessible_for_free ',
                    'type'       => 'switcher',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'SoftwareApplication|true' )
                ),
                array(
                    'title'      => esc_html__('ISSN', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The International Standard Serial Number (ISSN) that identifies this serial publication. You can repeat this property to identify different formats of, or the linking ISSN (ISSN-L) for, this serial publication.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'issn',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'CreativeWorkSeries|true' )
                ),
                array(
                    'title'       => esc_html__('Duration', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The duration of the item (movie, audio recording, event, etc.) in ISO 8601 date format.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'duration',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'MediaObject,HowTo|true' )
                ),
                array(
                    'title'      => esc_html__('Encoding Format', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('In cases where a CreativeWork has several media type representations, encoding can be used to indicate each MediaObject alongside particular encodingFormat information. e.g. audio/mpeg', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'encoding_format',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'MediaObject|true' )
                ),
                array(
                    'title'      => esc_html__('Tracks Number', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The number of tracks in this album or playlist.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'num_tracks',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'MusicPlaylist|true' )
                ),
                array(
                    'title'    => esc_html__('Image/Logo', WP_ULIKE_PRO_DOMAIN),
                    'id'      => $prefix . 'image',
                    'type'    => 'upload',
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'Organization|true' )
                ),

                array(
                    'id'     => $prefix . 'tracks',
                    'type'   => 'repeater',
                    'title'  => esc_html__('Traks', WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                      array(
                        'id'    => 'by_artist',
                        'type'  => 'text',
                        'title' => esc_html__('Artist Name', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'duration',
                        'type'  => 'text',
                        'title' => esc_html__('Duration', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'in_album',
                        'type'  => 'text',
                        'title' => esc_html__('Album Name', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => esc_html__('Name', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'url',
                        'type'  => 'text',
                        'title' => esc_html__('Url', WP_ULIKE_PRO_DOMAIN)
                      )
                    ),
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'MusicPlaylist|true' )
                ),

                array(
                    'id'     => $prefix . 'supply',
                    'type'   => 'repeater',
                    'title'  => esc_html__('Supply', WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                      array(
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => esc_html__('Name', WP_ULIKE_PRO_DOMAIN)
                      )
                    ),
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'HowTo|true' )
                ),
                array(
                    'id'     => $prefix . 'tool',
                    'type'   => 'repeater',
                    'title'  => esc_html__('Tool', WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                      array(
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => esc_html__('Name', WP_ULIKE_PRO_DOMAIN)
                      )
                    ),
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'HowTo|true' )
                ),
                array(
                    'id'     => $prefix . 'step',
                    'type'   => 'repeater',
                    'title'  => esc_html__('Steps', WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                      array(
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => esc_html__('Name', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'title' => esc_html__('Url', WP_ULIKE_PRO_DOMAIN),
                        'id'    => 'url',
                        'type'  => 'text'
                      ),
                      array(
                        'title' => esc_html__('Image', WP_ULIKE_PRO_DOMAIN),
                        'id'    => 'image',
                        'type'  => 'upload'
                      ),
                      array(
                        'id'     => 'list',
                        'type'   => 'repeater',
                        'title'  => esc_html__('Step List', WP_ULIKE_PRO_DOMAIN),
                        'fields' => array(
                          array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => esc_html__('Text', WP_ULIKE_PRO_DOMAIN)
                          )
                        )
                      ),
                    ),
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'HowTo|true' )
                ),

                array(
                    'title'      => esc_html__('Image(s) List', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'image_list',
                    'type'       => 'gallery',
                    'add_title'  => esc_html__('Add Image(s)', WP_ULIKE_PRO_DOMAIN),
                    'dependency' => array( $prefix . 'schema_type|' . $prefix . 'enable_schema', 'any|==', 'LocalBusiness,Event,Movie,Product,Episode,SoftwareApplication,HowTo|true' )
                ),
                array(
                    'title'      => esc_html__('Disable Star Ratings', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'disable_star_ratings',
                    'desc'       => esc_html__('Star rating data is automatically added to the schema markup by calculating the number of likes and dislikes. You can disable it if you wish.', WP_ULIKE_PRO_DOMAIN),
                    'type'       => 'switcher',
                    'dependency' => array( $prefix . 'enable_schema', '==', 'true' ),
                ),
                array(
                    'title'      => esc_html__('Enable Time Factor Calculation', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'enable_time_factor_rating',
                    'desc'       => esc_html__('By default, the star rating value is calculated by counting the number of likes and dislikes. If you have disabled the dislike button, by activating this option, the star rating value will be calculated by considering the time factor on the number of likes.', WP_ULIKE_PRO_DOMAIN),
                    'type'       => 'switcher',
                    'dependency' => array( $prefix . 'enable_schema|' . $prefix . 'disable_star_ratings', '==|!=', 'true|true' ),
                ),
                array(
                    'title'      => esc_html__('Enable Custom Star Rating', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'enable_custom_rating',
                    'desc'       => esc_html__('You can manually rate the post (Instead of our dynamic algorithms) by activating this option.', WP_ULIKE_PRO_DOMAIN),
                    'type'       => 'switcher',
                    'dependency' => array( $prefix . 'enable_schema|' . $prefix . 'disable_star_ratings', '==|!=', 'true|true' ),
                ),
                array(
                    'title'      => esc_html__('Rating Value', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => sprintf( '%s<br>* %s<br>* %s', esc_html__('The rating for the content.', WP_ULIKE_PRO_DOMAIN), esc_html__('Use values from 0123456789 (Unicode \'DIGIT ZERO\' (U+0030) to \'DIGIT NINE\' (U+0039)) rather than superficially similiar Unicode symbols.', WP_ULIKE_PRO_DOMAIN), esc_html__('Use \'.\' (Unicode \'FULL STOP\' (U+002E)) rather than \',\' to indicate a decimal point. Avoid using these symbols as a readability separator.', WP_ULIKE_PRO_DOMAIN) ),
                    'id'         => $prefix . 'rating_value',
                    'type'       => 'text',
                    'dependency' => array( $prefix . 'enable_custom_rating|' . $prefix . 'enable_schema', '==|==', 'true|true' ),
                ),
                array(
                    'title'       => esc_html__('Rating Count', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The count of total number of ratings.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'rating_count',
                    'type'       => 'number',
                    'dependency' => array( $prefix . 'enable_custom_rating|' . $prefix . 'enable_schema', '==|==', 'true|true' ),
                ),
                array(
                    'title'       => esc_html__('Review Count', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The count of total number of reviews.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'review_count',
                    'type'       => 'number',
                    'dependency' => array( $prefix . 'enable_custom_rating|' . $prefix . 'enable_schema', '==|==', 'true|true' ),
                ),
                array(
                    'title'       => esc_html__('Worst Rating', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The lowest value allowed in this rating system. If worstRating is omitted, 1 is assumed.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'worst_rating',
                    'type'       => 'number',
                    'dependency' => array( $prefix . 'enable_custom_rating|' . $prefix . 'enable_schema', '==|==', 'true|true' ),
                ),
                array(
                    'title'       => esc_html__('Best Rating', WP_ULIKE_PRO_DOMAIN),
                    'desc'       => esc_html__('The highest value allowed in this rating system. If bestRating is omitted, 5 is assumed.', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'best_rating',
                    'type'       => 'number',
                    'dependency' => array( $prefix . 'enable_custom_rating|' . $prefix . 'enable_schema', '==|==', 'true|true' ),
                ),

                array(
                    'title'      => esc_html__('Enable Custom Reviews', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'enable_custom_reviews',
                    'desc'       => esc_html__('A review of an item - for example, of a restaurant, movie, or store.', WP_ULIKE_PRO_DOMAIN),
                    'type'       => 'switcher',
                    'dependency' => array( $prefix . 'enable_schema', '==', 'true' ),
                ),
                array(
                    'id'     => $prefix . 'reviews',
                    'type'   => 'repeater',
                    'title'  => esc_html__('Reviews', WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                      array(
                        'id'    => 'author',
                        'type'  => 'text',
                        'title' => esc_html__('Author Name', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'published_date',
                        'type'  => 'date',
                        'title' => esc_html__('Date of first publication', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'name',
                        'type'  => 'text',
                        'title' => esc_html__('Name', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'review_body',
                        'type'  => 'textarea',
                        'title' => esc_html__('Review Body', WP_ULIKE_PRO_DOMAIN),
                      ),
                      array(
                        'id'    => 'rating_value',
                        'type'  => 'slider',
                        'min'   => 1,
                        'max'   => 5,
                        'title' => esc_html__('Review Rating', WP_ULIKE_PRO_DOMAIN),
                        'desc'  => esc_html__('The rating for the content.', WP_ULIKE_PRO_DOMAIN)
                      ),
                    ),
                    'dependency' => array( $prefix . 'enable_custom_reviews|' . $prefix . 'enable_schema', '==|==', 'true|true' )
                ),

                // FAQ
                array(
                    'title'      => esc_html__('Enable Frequently Asked Question (FAQ)', WP_ULIKE_PRO_DOMAIN),
                    'id'         => $prefix . 'enable_faq',
                    'type'       => 'switcher'
                ),
                array(
                    'id'     => $prefix . 'faq',
                    'type'   => 'repeater',
                    'title'  => esc_html__('FAQ', WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                      array(
                        'id'    => 'question',
                        'type'  => 'text',
                        'title' => esc_html__('Question', WP_ULIKE_PRO_DOMAIN)
                      ),
                      array(
                        'id'    => 'answer',
                        'type'   => 'wp_editor',
                        'height' => '100px',
                        'title' => esc_html__('Answer', WP_ULIKE_PRO_DOMAIN)
                      )
                    ),
                    'dependency' => array( $prefix . 'enable_faq', '==', 'true' )
                )
            )
        ) );

        if( ! empty( $_GET['post'] ) ){
            // Delete data section
            ULF::createSection( $this->option_domain, array(
                'title'  => esc_html__('Delete Data', WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'     => esc_html__( 'Delete All Logs', WP_ULIKE_PRO_DOMAIN),
                            'label'     => esc_html__( 'Delete Records', WP_ULIKE_PRO_DOMAIN),
                            'desc'      => esc_html__( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'      => json_encode( array( 'method' => 'logs', 'id' => $_GET['post'] ) ),
                            'inline_js' => true,
                            'action'    => 'post_metabox_truncate'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'     => esc_html__( 'Delete Meta Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'     => esc_html__( 'Delete Values', WP_ULIKE_PRO_DOMAIN),
                            'desc'      => sprintf( '<span>%s</span><br><strong>* %s</strong>', esc_html__( 'You Are About To Delete All Meta Counter Data. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN), esc_html__( 'After completing this operation, go to the "Optimization" section in the settings panel and delete all meta user status.', WP_ULIKE_PRO_DOMAIN) ),
                            'type'      => json_encode( array( 'method' => 'meta', 'id' => $_GET['post'] ) ),
                            'inline_js' => true,
                            'action'    => 'post_metabox_truncate'
                        )
                    )
                )
            ) );
        }

    }

        /**
         * Get templates option array
         *
         * @return array
         */
        public function get_templates_option_array(){
            $options = wp_ulike_generate_templates_list();
            $output  = array();

            if( !empty( $options ) ){
                foreach ($options as $key => $args) {
                    $output[$key] = $args['symbol'];
                }
            }

            return $output;
        }

}
