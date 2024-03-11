<?php
/**
 * Content template
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_ulike_query_args;

$query_args =  !empty( $wp_ulike_query_args ) ? $wp_ulike_query_args : array(
    "type"           => 'post',
    "rel_type"       => 'post',
    "is_popular"     => false,
    "user_id"        => '',
    "status"         => 'like',
    "period"         => 'all',
    "style"          => 'default',
    "has_pagination" => false,
    "exclude"        => "thumbnail",
    "desktop_column" => 1,
    "tablet_column"  => 1,
    "mobile_column"  => 1,
    "limit"          => 10
);

$wp_ulike_query = NULL;

if( !empty( $query_args['type'] ) ){
    switch ( $query_args['type'] ) {
        case 'post':
        case 'topic':
            $wp_ulike_query = wp_ulike_pro_get_posts_query( $query_args );

            break;
        case 'comment':
            $wp_ulike_query = wp_ulike_pro_get_comments_query( $query_args );

            break;
        case 'activity':
            $wp_ulike_query = wp_ulike_pro_get_activity_query( $query_args );
            break;
    }
}

?>

<!-- items-list -->
<div class="wp-ulike-pro-section-items">

    <?php do_action( 'wp_ulike_pro_items_before_hook', $query_args ); ?>

    <?php if( ! empty( $wp_ulike_query ) ) : ?>
    <div class="wp-ulike-pro-items-container wp-ulike-content-style-<?php echo esc_attr( $query_args['style'] ); ?>">
        <?php
            $template_path = sprintf(  'content/%s.php', $query_args['type'] );
            if( file_exists( stream_resolve_include_path( $template_path ) ) ){
                ob_start();
                include( sprintf(  $template_path, $query_args['type'] ) );
                echo apply_filters( 'wp_ulike_pro_content_template_for_' . $query_args['type'] , ob_get_clean(), $wp_ulike_query, $query_args );
            } else {
                echo esc_html__( 'Template type not exist!', WP_ULIKE_PRO_DOMAIN );
            }
        ?>
    </div>
    <?php else: ?>
    <div class="wp-ulike-pro-items-container wp-ulike-content-not-found">
        <p><?php echo empty( $query_args['empty_text'] ) ? esc_html__( 'No Results Found!', WP_ULIKE_PRO_DOMAIN ) : wp_kses_post( $query_args['empty_text'] ); ?>
        </p>
    </div>
    <?php endif; ?>

    <?php do_action( 'wp_ulike_pro_items_afer_hook', $query_args ); ?>

</div>