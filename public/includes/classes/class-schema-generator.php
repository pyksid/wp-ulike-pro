<?php
/**
 * Schema Genrator Class.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

/**
 *  Class to generate schema structures
 */
class WP_Ulike_Pro_Schema_Generator{

    protected $schema;
    protected $item_ID;

    /**
     * __construct
     */
    function __construct( $item_ID ) {
        $this->item_ID = $item_ID;
    }

    public function generateAutoSchema( $type ){
        $this->schema = WP_Ulike_Pro_JsonLd::create( $type );
        $this->setProperties( $type );
        $this->printScript();
    }

    public function generateCustomFAQSchema(){
        $item_faq = $this->getMetaValue( 'faq' );
        if ( empty( $item_faq ) || ! is_array( $item_faq ) ) {
            return;
        }

        $faq_stack = array();
        foreach ( $item_faq as $faq ) {
            if ( empty( $faq ) || empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
                continue;
            }
            $faq_stack[] = WP_Ulike_Pro_JsonLd::nested(
                'Question',
                array(
                    'name'           => $faq['question'],
                    'acceptedAnswer' => WP_Ulike_Pro_JsonLd::nested(
                        'Answer',
                        array( 'text' => $faq['answer'] )
                    ),
                )
            );
        }

        if ( empty( $faq_stack ) ) {
            return;
        }

        $faq_page = WP_Ulike_Pro_JsonLd::create( 'FAQPage' );
        $faq_page->mainEntity( $faq_stack );
        echo $faq_page->toScript();
    }

    private function setProperties( $type ){
        // General Name Generator
        $item_name = $this->getMetaValue( 'title' );
        if ( null === $item_name || '' === $item_name || false === $item_name ) {
            $item_name = wp_strip_all_tags( get_the_title( $this->item_ID ) );
        }
        $this->schema->name( $item_name );

        // General Item Description
        if ( ! empty( $item_description = $this->getMetaValue( 'description' ) ) ) {
            $this->schema->description( $item_description );
        }

        // General AggregateRating Generator
        if ( ! wp_ulike_pro_is_metabox_true( 'disable_star_ratings' ) ) {
            if ( wp_ulike_pro_is_metabox_true( 'enable_custom_rating' ) ) {
                $aggregate_rating = WP_Ulike_Pro_JsonLd::buildAggregateRating(
                    array(
                        'worstRating'  => $this->getMetaValue( 'worst_rating' ),
                        'bestRating'   => $this->getMetaValue( 'best_rating' ),
                        'ratingValue'  => $this->getMetaValue( 'rating_value' ),
                        'ratingCount'  => $this->getMetaValue( 'rating_count' ),
                    )
                );
                if ( $aggregate_rating ) {
                    $this->schema->aggregateRating( $aggregate_rating );
                }
            } else {
                $aggregate_rating = $this->GetAggregateRating();
                if ( $aggregate_rating ) {
                    $this->schema->aggregateRating( $aggregate_rating );
                }
            }
        }

        // General Reviews
        if ( ! empty( $item_reviews = $this->getMetaValue( 'reviews' ) ) && wp_ulike_pro_is_metabox_true( 'enable_custom_reviews' ) ) {
            $reviews_stack = array();
            foreach ( $item_reviews as $review ) {
                if ( empty( $review ) ) {
                    continue;
                }
                $rating_scale = $this->getRatingScale();
                $review_entity = WP_Ulike_Pro_JsonLd::buildReview(
                    array(
                        'author'         => $review['author'],
                        'published_date' => wp_ulike_pro_parse_schema_date( $review['published_date'] ),
                        'name'           => $review['name'],
                        'review_body'    => $review['review_body'],
                        'rating_value'   => $review['rating_value'],
                        'worst_rating'   => $rating_scale['worst'],
                        'best_rating'    => $rating_scale['best'],
                    )
                );
                if ( $review_entity ) {
                    $reviews_stack[] = $review_entity;
                }
            }
            if ( ! empty( $reviews_stack ) ) {
                $this->schema->review( $reviews_stack );
            }
        }

        switch ( $type ) {
            case 'Book':
                $author = WP_Ulike_Pro_JsonLd::person( $this->getMetaValue( 'author' ) );
                if ( $author ) {
                    $this->schema->author( $author );
                }
                $this->schema->url( $this->schemaUrl() );
                break;

            case 'Course':
                $provider = WP_Ulike_Pro_JsonLd::organization( $this->getMetaValue( 'name' ) );
                if ( $provider ) {
                    $this->schema->provider( $provider );
                }
                break;

            case 'Event':
                $this->schema->startDate( $this->schemaDate( 'start_date' ) );
                $this->schema->endDate( $this->schemaDate( 'end_date' ) );

                $location = WP_Ulike_Pro_JsonLd::nested(
                    'Place',
                    array(
                        'name'    => $this->getMetaValue( 'location' ),
                        'address' => WP_Ulike_Pro_JsonLd::buildPostalAddress(
                            array(
                                'street_address'     => $this->getMetaValue( 'street_address' ),
                                'address_locality'   => $this->getMetaValue( 'address_locality' ),
                                'address_region'     => $this->getMetaValue( 'address_region' ),
                                'postal_code'        => $this->getMetaValue( 'postal_code' ),
                                'address_country'    => $this->getMetaValue( 'address_country' ),
                            )
                        ),
                    )
                );
                if ( $location && $location->hasProperties() ) {
                    $this->schema->location( $location );
                }

                $offer = WP_Ulike_Pro_JsonLd::buildOffer(
                    array(
                        'price'          => $this->getMetaValue( 'price' ),
                        'valid_from'     => $this->schemaDate( 'valid_date' ),
                        'url'            => $this->schemaUrl(),
                        'availability'   => $this->getMetaValue( 'availability' ),
                        'price_currency' => $this->getMetaValue( 'price_currency' ),
                    )
                );
                if ( $offer ) {
                    $this->schema->offers( $offer );
                }

                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );

                $performer = WP_Ulike_Pro_JsonLd::nested(
                    'PerformingGroup',
                    array( 'name' => $this->getMetaValue( 'author' ) )
                );
                if ( $performer && $performer->hasProperties() ) {
                    $this->schema->performer( $performer );
                }
                break;

            case 'Product':
                $this->schema->sku( $this->getMetaValue( 'sku' ) );
                $this->schema->mpn( $this->getMetaValue( 'mpn' ) );

                $brand = WP_Ulike_Pro_JsonLd::brand( $this->getMetaValue( 'author' ) );
                if ( $brand ) {
                    $this->schema->brand( $brand );
                }

                $product_url = $this->schemaUrl();
                if ( $product_url ) {
                    $this->schema->url( $product_url );
                }

                $offer = WP_Ulike_Pro_JsonLd::buildOffer(
                    array(
                        'price'              => $this->getMetaValue( 'price' ),
                        'price_valid_until'  => $this->schemaDate( 'valid_date' ),
                        'url'                => $product_url,
                        'availability'       => $this->getMetaValue( 'availability' ),
                        'price_currency'     => $this->getMetaValue( 'price_currency' ),
                    )
                );
                if ( $offer ) {
                    $this->schema->offers( $offer );
                }

                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                break;

            case 'SoftwareApplication':
                $this->schema->operatingSystem( $this->getMetaValue( 'operating_system' ) );

                $application_category = WP_Ulike_Pro_JsonLd::normalizeApplicationCategory(
                    $this->getMetaValue( 'application_category' )
                );
                if ( '' !== $application_category ) {
                    $this->schema->applicationCategory( $application_category );
                }

                $this->schema->softwareVersion( $this->getMetaValue( 'software_version' ) );
                if ( wp_ulike_pro_is_metabox_true( 'is_accessible_for_free' ) ) {
                    $this->schema->isAccessibleForFree( true );
                }
                $this->schema->url( $this->schemaUrl() );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );

                $offer = WP_Ulike_Pro_JsonLd::buildOffer(
                    array(
                        'price'          => $this->getMetaValue( 'price' ),
                        'price_currency' => $this->getMetaValue( 'price_currency' ),
                        'url'            => $this->schemaUrl(),
                    )
                );
                if ( $offer ) {
                    $this->schema->offers( $offer );
                }
                break;

            case 'CreativeWorkSeason':
                $actor = WP_Ulike_Pro_JsonLd::person( $this->getMetaValue( 'name' ) );
                if ( $actor ) {
                    $this->schema->actor( $actor );
                }

                $director = WP_Ulike_Pro_JsonLd::person( $this->getMetaValue( 'author' ) );
                if ( $director ) {
                    $this->schema->director( $director );
                }

                $this->schema->startDate( $this->schemaDate( 'start_date' ) );
                $this->schema->endDate( $this->schemaDate( 'end_date' ) );
                break;

            case 'CreativeWorkSeries':
                $this->schema->issn( $this->getMetaValue( 'issn' ) );
                $this->schema->startDate( $this->schemaDate( 'start_date' ) );
                $this->schema->endDate( $this->schemaDate( 'end_date' ) );
                break;

            case 'Episode':
                $director = WP_Ulike_Pro_JsonLd::person( $this->getMetaValue( 'author' ) );
                if ( $director ) {
                    $this->schema->director( $director );
                }

                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->dateCreated( $this->schemaDate( 'created_date' ) );
                break;

            case 'Movie':
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->dateCreated( $this->schemaDate( 'created_date' ) );

                $director = WP_Ulike_Pro_JsonLd::person( $this->getMetaValue( 'author' ) );
                if ( $director ) {
                    $this->schema->director( $director );
                }
                break;

            case 'Game':
                $offer = WP_Ulike_Pro_JsonLd::buildOffer(
                    array(
                        'price'          => $this->getMetaValue( 'price' ),
                        'price_currency' => $this->getMetaValue( 'price_currency' ),
                        'url'            => $this->schemaUrl(),
                    )
                );
                if ( $offer ) {
                    $this->schema->offers( $offer );
                }
                break;

            case 'MediaObject':
                $this->schema->url( $this->schemaUrl() );
                $this->schema->duration( $this->getMetaValue( 'duration' ) );
                $this->schema->encodingFormat( $this->getMetaValue( 'encoding_format' ) );
                break;

            case 'MusicPlaylist':
                $this->schema->numTracks( $this->getMetaValue( 'num_tracks' ) );

                $get_traks = $this->getMetaValue( 'tracks' );
                if ( ! empty( $get_traks ) ) {
                    $music_stack = array();
                    foreach ( $get_traks as $track ) {
                        if ( empty( $track ) ) {
                            continue;
                        }

                        $track_properties = array(
                            'name' => $track['name'] ?? '',
                            'url'  => $track['url'] ?? '',
                        );

                        $by_artist = WP_Ulike_Pro_JsonLd::person( $track['by_artist'] ?? '' );
                        if ( $by_artist ) {
                            $track_properties['byArtist'] = $by_artist;
                        }

                        $duration = trim( (string) ( $track['duration'] ?? '' ) );
                        if ( '' !== $duration ) {
                            $track_properties['duration'] = $duration;
                        }

                        $in_album = trim( (string) ( $track['in_album'] ?? '' ) );
                        if ( '' !== $in_album ) {
                            $track_properties['inAlbum'] = WP_Ulike_Pro_JsonLd::nested(
                                'MusicAlbum',
                                array( 'name' => $in_album )
                            );
                        }

                        $recording = WP_Ulike_Pro_JsonLd::nested( 'MusicRecording', $track_properties );
                        if ( $recording->hasProperties() ) {
                            $music_stack[] = $recording;
                        }
                    }
                    if ( ! empty( $music_stack ) ) {
                        $this->schema->track( $music_stack );
                    }
                }

                break;

            case 'Organization':
                $this->schema->url( $this->schemaUrl() );
                $this->schema->telephone( $this->getMetaValue( 'telephone' ) );

                $address = WP_Ulike_Pro_JsonLd::buildPostalAddress(
                    array(
                        'street_address'     => $this->getMetaValue( 'street_address' ),
                        'address_locality'   => $this->getMetaValue( 'address_locality' ),
                        'address_region'     => $this->getMetaValue( 'address_region' ),
                        'postal_code'        => $this->getMetaValue( 'postal_code' ),
                        'address_country'    => $this->getMetaValue( 'address_country' ),
                    )
                );
                if ( $address ) {
                    $this->schema->address( $address );
                }

                $this->schema->logo( $this->getMetaValue( 'image' ) );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                break;

            case 'LocalBusiness':
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->telephone( $this->getMetaValue( 'telephone' ) );
                $this->schema->priceRange( $this->getMetaValue( 'price_range' ) );

                $address = WP_Ulike_Pro_JsonLd::buildPostalAddress(
                    array(
                        'street_address'     => $this->getMetaValue( 'street_address' ),
                        'address_locality'   => $this->getMetaValue( 'address_locality' ),
                        'address_region'     => $this->getMetaValue( 'address_region' ),
                        'postal_code'        => $this->getMetaValue( 'postal_code' ),
                        'address_country'    => $this->getMetaValue( 'address_country' ),
                    )
                );
                if ( $address ) {
                    $this->schema->address( $address );
                }

                $day_of_week = $this->getMetaValue( 'day_of_week' );
                $opens       = $this->getMetaValue( 'opens' );
                $closes      = $this->getMetaValue( 'closes' );
                if ( ! empty( $day_of_week ) && is_array( $day_of_week ) ) {
                    $hours_stack = array();
                    foreach ( $day_of_week as $day ) {
                        if ( empty( $day ) ) {
                            continue;
                        }
                        $spec = WP_Ulike_Pro_JsonLd::buildOpeningHoursSpecification( $day, $opens, $closes );
                        if ( $spec ) {
                            $hours_stack[] = $spec;
                        }
                    }
                    if ( ! empty( $hours_stack ) ) {
                        $this->schema->openingHoursSpecification( $hours_stack );
                    }
                }
                break;

            case 'HowTo':
                $this->schema->totalTime( $this->getMetaValue( 'duration' ) );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );

                $estimated_cost = WP_Ulike_Pro_JsonLd::nested(
                    'MonetaryAmount',
                    array(
                        'currency' => $this->getMetaValue( 'price_currency' ),
                        'value'    => $this->getMetaValue( 'price' ),
                    )
                );
                if ( $estimated_cost->hasProperties() ) {
                    $this->schema->estimatedCost( $estimated_cost );
                }

                $get_supplies = $this->getMetaValue( 'supply' );
                if ( ! empty( $get_supplies ) ) {
                    $supply_stack = array();
                    foreach ( $get_supplies as $supply ) {
                        if ( empty( $supply ) ) {
                            continue;
                        }
                        $supply_entity = WP_Ulike_Pro_JsonLd::nested(
                            'HowToSupply',
                            array( 'name' => $supply['name'] ?? '' )
                        );
                        if ( $supply_entity->hasProperties() ) {
                            $supply_stack[] = $supply_entity;
                        }
                    }
                    if ( ! empty( $supply_stack ) ) {
                        $this->schema->supply( $supply_stack );
                    }
                }

                $get_tools = $this->getMetaValue( 'tool' );
                if ( ! empty( $get_tools ) ) {
                    $tool_stack = array();
                    foreach ( $get_tools as $tool ) {
                        if ( empty( $tool ) ) {
                            continue;
                        }
                        $tool_entity = WP_Ulike_Pro_JsonLd::nested(
                            'HowToTool',
                            array( 'name' => $tool['name'] ?? '' )
                        );
                        if ( $tool_entity->hasProperties() ) {
                            $tool_stack[] = $tool_entity;
                        }
                    }
                    if ( ! empty( $tool_stack ) ) {
                        $this->schema->tool( $tool_stack );
                    }
                }

                $get_steps = $this->getMetaValue( 'step' );
                if ( ! empty( $get_steps ) ) {
                    $step_stack = array();
                    foreach ( $get_steps as $step ) {
                        if ( empty( $step ) ) {
                            continue;
                        }
                        $step_list_stack = array();
                        $step_list = isset( $step['list'] ) && is_array( $step['list'] ) ? $step['list'] : array();
                        foreach ( $step_list as $step_value ) {
                            if ( empty( $step_value ) ) {
                                continue;
                            }
                            $direction = WP_Ulike_Pro_JsonLd::nested(
                                'HowToDirection',
                                array( 'text' => $step_value['name'] ?? '' )
                            );
                            if ( $direction->hasProperties() ) {
                                $step_list_stack[] = $direction;
                            }
                        }

                        $step_entity = WP_Ulike_Pro_JsonLd::nested(
                            'HowToStep',
                            array(
                                'name'            => $step['name'] ?? '',
                                'url'             => $step['url'] ?? '',
                                'image'           => $step['image'] ?? '',
                                'itemListElement' => $step_list_stack,
                            )
                        );
                        if ( $step_entity->hasProperties() ) {
                            $step_stack[] = $step_entity;
                        }
                    }
                    if ( ! empty( $step_stack ) ) {
                        $this->schema->step( $step_stack );
                    }
                }

                break;
        }

        do_action( 'wp_ulike_pro_generate_schema_properties', $this->schema );
    }


    private function getMetaValue( $meta_name ) {
        return wp_ulike_pro_get_metabox_value_raw( $meta_name, $this->item_ID );
    }

    private function schemaDate( $meta_name ) {
        return wp_ulike_pro_parse_schema_date( $this->getMetaValue( $meta_name ) );
    }

    private function GetAggregateRating(){
        $rating = $this->getRatingInfo();
        if( empty( $rating['count'] ) ){
            return null;
        }
        return WP_Ulike_Pro_JsonLd::buildAggregateRating( $rating );
    }


    private function getRatingInfo(){
        $preview = wp_ulike_pro_get_schema_rating_preview( $this->item_ID );

        return array(
            'count' => (int) ( $preview['count'] ?? 0 ),
            'value' => $preview['value'] ?? 0,
            'worst' => (float) ( $preview['worst'] ?? 1 ),
            'best'  => (float) ( $preview['best'] ?? 5 ),
        );
    }

    /**
     * Rating scale bounds used for aggregate and review ratings.
     *
     * @return array{worst: float, best: float}
     */
    private function getRatingScale() {
        $preview = wp_ulike_pro_get_schema_rating_preview( $this->item_ID );

        return array(
            'worst' => (float) ( $preview['worst'] ?? 1 ),
            'best'  => (float) ( $preview['best'] ?? 5 ),
        );
    }

    /**
     * Schema URL from metabox, falling back to the current post permalink.
     *
     * @return string
     */
    private function schemaUrl() {
        $url = trim( (string) $this->getMetaValue( 'url' ) );
        if ( '' === $url ) {
            $url = get_permalink( $this->item_ID );
        }

        return $url ? esc_url_raw( $url ) : '';
    }

    private function printScript(){
        echo $this->schema->toScript();
    }

}

