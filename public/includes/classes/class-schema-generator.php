<?php
/**
 * Schema Genrator Class.
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

use Spatie\SchemaOrg\Schema;

/**
 *  Class to generate schema structures
 */
class WP_Ulike_Pro_Schema_Generator{

    protected $schema;
    protected $item_ID;
    protected $wpdb;

    /**
     * __construct
     */
    function __construct( $item_ID ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->item_ID = $item_ID;
    }

    public function generateAutoSchema( $type ){
        $this->schema = Schema::$type();
        $this->setProperties( $type );
        $this->printScript();
    }

    public function generateCustomFAQSchema(){
        $fAQPage = Schema::fAQPage();
        if( '' !== ( $item_faq = wp_ulike_pro_get_metabox_value('faq') ) ){
            $faq_stack = array();
            foreach ($item_faq as $key => $faq) {
                if( empty( $faq ) ){
                    continue;
                }
                $faq_stack[] = Schema::Question()
                ->name( $faq['question'] )
                ->acceptedAnswer( Schema::Answer()->text( $faq['answer'] ) );
            }
            $fAQPage->mainEntity( $faq_stack );
            echo $fAQPage->toScript();
        }
    }

    private function setProperties( $type ){
        // General Name Generator
        $item_name = wp_ulike_pro_get_metabox_value('title');
        $item_name = empty( $item_name ) ? the_title_attribute( 'echo=0' ) : $item_name;
        $this->schema->name( $item_name );

        // General Item Description
        if( '' !== ( $item_description = wp_ulike_pro_get_metabox_value('description') ) ){
            $this->schema->description( $item_description );
        }

        // General AggregateRating Generator
        if( ! wp_ulike_pro_get_metabox_value('disable_star_ratings') ){
            if( wp_ulike_is_true( wp_ulike_pro_get_metabox_value('enable_custom_rating') ) ){
                $this->schema->AggregateRating(
                    Schema::AggregateRating()
                    ->worstRating( wp_ulike_pro_get_metabox_value('worst_rating') )
                    ->bestRating( wp_ulike_pro_get_metabox_value('best_rating') )
                    ->ratingValue( wp_ulike_pro_get_metabox_value('rating_value') )
                    ->ratingCount( wp_ulike_pro_get_metabox_value('rating_count') )
                );
            } else {
                $this->schema->AggregateRating( $this->GetAggregateRating() );
            }
        }

        // General Reviews
        if( '' !== ( $item_reviews = wp_ulike_pro_get_metabox_value('reviews') ) && wp_ulike_is_true( wp_ulike_pro_get_metabox_value('enable_custom_reviews') ) ){
            $reviews_stack = array();
            foreach ($item_reviews as $key => $review) {
                if( empty( $review ) ){
                    continue;
                }
                $reviews_stack[] = Schema::Review()
                ->author( $review['author'] )
                ->datePublished( $review['published_date'] )
                ->name( $review['name'] )
                ->reviewBody( $review['review_body'] )
                ->reviewRating( Schema::Rating()->ratingValue( $review['rating_value'] ) );
            }
            $this->schema->review( $reviews_stack );
        }

        switch ( $type ) {
            case 'Book':
                $this->schema->author(
                    Schema::person()
                    ->name( wp_ulike_pro_get_metabox_value('author') )
                );
                $this->schema->url( wp_ulike_pro_get_metabox_value('url') );
                break;

            case 'Course':
                $this->schema->provider(
                    Schema::organization()
                    ->name( wp_ulike_pro_get_metabox_value('name') )
                );
                break;

            case 'Event':
                $this->schema->startDate( wp_ulike_pro_get_metabox_value('start_date') );
                $this->schema->endDate( wp_ulike_pro_get_metabox_value('end_date') );
                $this->schema->location(
                    Schema::place()
                    ->name( wp_ulike_pro_get_metabox_value('location') )
                    ->address(
                        Schema::PostalAddress()
                        ->streetAddress( wp_ulike_pro_get_metabox_value('street_address') )
                        ->addressLocality( wp_ulike_pro_get_metabox_value('address_locality') )
                        ->addressRegion( wp_ulike_pro_get_metabox_value('address_region') )
                        ->postalCode( wp_ulike_pro_get_metabox_value('postal_code') )
                        ->addressCountry( wp_ulike_pro_get_metabox_value('address_country') )
                    )
                );
                $this->schema->offers(
                    Schema::Offer()
                    ->price( wp_ulike_pro_get_metabox_value('price') )
                    ->validFrom( wp_ulike_pro_get_metabox_value('valid_date') )
                    ->url( wp_ulike_pro_get_metabox_value('url') )
                    ->availability( wp_ulike_pro_get_metabox_value('availability') )
                    ->priceCurrency( wp_ulike_pro_get_metabox_value('price_currency') )
                );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->performer(
                    Schema::PerformingGroup()
                    ->name( wp_ulike_pro_get_metabox_value('author') )
                );
                break;

            case 'Product':
                $this->schema->sku( wp_ulike_pro_get_metabox_value('sku') );
                $this->schema->mpn( wp_ulike_pro_get_metabox_value('mpn') );
                $this->schema->brand(
                    Schema::thing()
                    ->name( wp_ulike_pro_get_metabox_value('author') )
                );
                $this->schema->offers(
                    Schema::Offer()
                    ->price( wp_ulike_pro_get_metabox_value('price') )
                    ->priceValidUntil( wp_ulike_pro_get_metabox_value('valid_date') )
                    ->url( wp_ulike_pro_get_metabox_value('url') )
                    ->availability( wp_ulike_pro_get_metabox_value('availability') )
                    ->priceCurrency( wp_ulike_pro_get_metabox_value('price_currency') )
                );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                break;

            case 'SoftwareApplication':
                $this->schema->operatingSystem( wp_ulike_pro_get_metabox_value('operating_system') );
                $this->schema->applicationCategory( wp_ulike_pro_get_metabox_value('application_category') );
                $this->schema->offers(
                    Schema::Offer()
                    ->price( wp_ulike_pro_get_metabox_value('price') )
                    ->priceCurrency( wp_ulike_pro_get_metabox_value('price_currency') )
                );
                break;

            case 'CreativeWorkSeason':
                $this->schema->actor( wp_ulike_pro_get_metabox_value('name') );
                $this->schema->director( wp_ulike_pro_get_metabox_value('author') );
                $this->schema->startDate( wp_ulike_pro_get_metabox_value('start_date') );
                $this->schema->endDate( wp_ulike_pro_get_metabox_value('end_date') );
                break;

            case 'CreativeWorkSeries':
                $this->schema->issn( wp_ulike_pro_get_metabox_value('issn') );
                $this->schema->startDate( wp_ulike_pro_get_metabox_value('start_date') );
                $this->schema->endDate( wp_ulike_pro_get_metabox_value('end_date') );
                break;

            case 'Episode':
                $this->schema->director( wp_ulike_pro_get_metabox_value('author') );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->dateCreated( wp_ulike_pro_get_metabox_value('created_date') );
                break;

            case 'Movie':
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->dateCreated( wp_ulike_pro_get_metabox_value('created_date') );
                $this->schema->director(
                    Schema::person()
                    ->name( wp_ulike_pro_get_metabox_value('author') )
                );
                break;

            case 'Game':
                $this->schema->offers(
                    Schema::Offer()
                    ->price( wp_ulike_pro_get_metabox_value('price') )
                    ->priceCurrency( wp_ulike_pro_get_metabox_value('price_currency') )
                );
                break;

            case 'MediaObject':
                $this->schema->url( wp_ulike_pro_get_metabox_value('url') );
                $this->schema->duration( wp_ulike_pro_get_metabox_value('duration') );
                $this->schema->encodingFormat( wp_ulike_pro_get_metabox_value('encoding_format') );
                break;

            case 'MusicPlaylist':
                $this->schema->numTracks( wp_ulike_pro_get_metabox_value('num_tracks') );

                $get_traks = wp_ulike_pro_get_metabox_value('tracks');
                if( ! empty( $get_traks ) ){
                    $music_stack = array();
                    foreach ($get_traks as $key => $track) {
                        if( empty( $track ) ){
                            continue;
                        }
                        $music_stack[] = Schema::MusicRecording()
                        ->byArtist( $track['by_artist'] )
                        ->duration( $track['duration'] )
                        ->inAlbum( $track['in_album'] )
                        ->url( $track['url'] )
                        ->name( $track['name'] );
                    }
                    $this->schema->track( $music_stack );
                }

                break;

            case 'Organization':
                $this->schema->url( wp_ulike_pro_get_metabox_value('url') );
                $this->schema->telephone( wp_ulike_pro_get_metabox_value('telephone') );
                $this->schema->url( wp_ulike_pro_get_metabox_value('url') );
                $this->schema->address(
                    Schema::PostalAddress()
                    ->streetAddress( wp_ulike_pro_get_metabox_value('street_address') )
                    ->addressLocality( wp_ulike_pro_get_metabox_value('address_locality') )
                    ->addressRegion( wp_ulike_pro_get_metabox_value('address_region') )
                    ->postalCode( wp_ulike_pro_get_metabox_value('postal_code') )
                    ->addressCountry( wp_ulike_pro_get_metabox_value('address_country') )
                );
                $this->schema->logo( wp_ulike_pro_get_metabox_value('image') );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                break;

            case 'LocalBusiness':
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->telephone( wp_ulike_pro_get_metabox_value('telephone') );
                $this->schema->priceRange( wp_ulike_pro_get_metabox_value('price_range') );
                $this->schema->address(
                    Schema::PostalAddress()
                    ->streetAddress( wp_ulike_pro_get_metabox_value('street_address') )
                    ->addressLocality( wp_ulike_pro_get_metabox_value('address_locality') )
                    ->addressRegion( wp_ulike_pro_get_metabox_value('address_region') )
                    ->postalCode( wp_ulike_pro_get_metabox_value('postal_code') )
                    ->addressCountry( wp_ulike_pro_get_metabox_value('address_country') )
                );
                break;

            case 'HowTo':
                $this->schema->totalTime( wp_ulike_pro_get_metabox_value('duration') );
                $this->schema->image( wp_ulike_pro_get_metabox_images_list() );
                $this->schema->estimatedCost(
                    Schema::MonetaryAmount()
                    ->currency( wp_ulike_pro_get_metabox_value('price_currency') )
                    ->value( wp_ulike_pro_get_metabox_value('price') )
                );

                $get_supplies = wp_ulike_pro_get_metabox_value('supply');
                if( ! empty( $get_supplies ) ){
                    $supply_stack = array();
                    foreach ($get_supplies as $key => $supply) {
                        if( empty( $supply ) ){
                            continue;
                        }
                        $supply_stack[] = Schema::HowToSupply()
                        ->name( $supply['name'] );
                    }
                    $this->schema->supply( $supply_stack );
                }

                $get_tools = wp_ulike_pro_get_metabox_value('tool');
                if( ! empty( $get_tools ) ){
                    $tool_stack = array();
                    foreach ($get_tools as $key => $tool) {
                        if( empty( $tool ) ){
                            continue;
                        }
                        $tool_stack[] = Schema::HowToTool()
                        ->name( $tool['name'] );
                    }
                    $this->schema->tool( $tool_stack );
                }

                $get_steps = wp_ulike_pro_get_metabox_value('step');
                if( ! empty( $get_steps ) ){
                    $step_stack = array();
                    foreach ($get_steps as $key => $step) {
                        if( empty( $step ) ){
                            continue;
                        }
                        $step_list_stack = array();
                        foreach ( $step['list'] as $step_key => $step_value ) {
                            if( empty( $step_value ) ){
                                continue;
                            }
                            $step_list_stack[] = Schema::HowToDirection()
                            ->text( $step_value['name'] );
                        }
                        $step_stack[] = Schema::HowToStep()
                        ->name( $step['name'] )
                        ->url( $step['url'] )
                        ->image( $step['image'] )
                        ->itemListElement( $step_list_stack );
                    }
                    $this->schema->step( $step_stack );
                }

                break;
        }

        do_action( 'wp_ulike_pro_generate_schema_properties', $this->schema );
    }


    private function GetAggregateRating(){
        $rating = $this->getRatingInfo();
        if( empty( $rating['count'] ) ){
            return '';
        }
        return Schema::AggregateRating()
        ->worstRating(1)
        ->bestRating(5)
        ->ratingValue($rating['value'])
        ->ratingCount($rating['count']);
    }


    private function getRatingInfo(){
        // Get total likes
        $totalLikes = wp_ulike_get_post_likes( $this->item_ID, 'like' );
        // Get total dislikes
        $totalDislikes = wp_ulike_get_post_likes( $this->item_ID, 'dislike' );

        $totalCount = $totalLikes + $totalDislikes;
        $calcValue  = $totalCount ? ( ( $totalLikes * 5 )  + ( $totalDislikes ) ) / $totalCount : 0;

        if( wp_ulike_is_true( wp_ulike_pro_get_metabox_value('enable_time_factor_rating') ) ){
            $calcValue  = wp_ulike_get_rating_value( $this->item_ID );
        } else {
            $calcValue  = $calcValue < 1 ? 1 : round( $calcValue, 2 );
        }

        return array(
            'count' => $totalCount,
            'value' => $calcValue
        );
    }


    private function getRatingsQuery( $item_ID ){
		// generate query string
		// $query  = sprintf( "SELECT user.user_id,
        //     (SELECT COUNT(DISTINCT `user_id`) FROM `%1$sulike` WHERE status='like' and user_id = user.user_id and post_id = %2$d ) as totalLikes,
        //     (SELECT COUNT(DISTINCT `user_id`) FROM `%1$sulike` WHERE status='dislike' and user_id = user.user_id and post_id = %2$d ) as totalDislikes,
        //     (SELECT COUNT(DISTINCT `user_id`) FROM `%1$sulike` WHERE user_id = user.user_id) as TotalCount
        //     FROM (SELECT DISTINCT user_id FROM `%1$sulike`) user",
		// 	$this->wpdb->prefix,
		// 	$item_ID
		// );
		$query  = sprintf( "SELECT
            sum(case when `status` = 'like' then 1 else 0 end) AS totalLikes,
            sum(case when `status` = 'dislike' then 1 else 0 end) AS totalDislikes
            FROM `%sulike` WHERE `post_id`= %d",
			$this->wpdb->prefix,
			$item_ID
        );

        return $this->wpdb->get_row( $query );
    }

    private function printScript(){
        echo $this->schema->toScript();
    }

}