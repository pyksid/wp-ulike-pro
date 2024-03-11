<?php
/**
 * Posts Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$exclude_display = array();
if( ! empty( $query_args['exclude'] ) ){
    $exclude_display = explode( ',', $query_args['exclude'] );
}

// The Loop
if ( $wp_ulike_query->have_posts() ) {
    echo '<div class="wp-ulike-pro-single-post ulp-flex-row ulp-flex-middle-xs">';
    // Start Loop
    while ( $wp_ulike_query->have_posts() ) {
        $wp_ulike_query->the_post();
?>
<div class="wp-ulike-pro-item-container ulp-flex-col-xl-<?php echo ! empty( $query_args['desktop_column'] ) ? 12 / intval( $query_args['desktop_column'] ) : 12; ?> ulp-flex-col-md-<?php echo ! empty( $query_args['tablet_column'] ) ? 12 / intval( $query_args['tablet_column'] ) : 12; ?> ulp-flex-col-xs-<?php echo ! empty( $query_args['mobile_column'] ) ? 12 / intval( $query_args['mobile_column'] ) : 12; ?> wp-ulike-pro-item-col">
    <div class="wp-ulike-pro-content-wrapper">
        <?php do_action( 'wp_ulike_pro_posts_before_hook', $query_args ); ?>
        <?php if ( has_post_thumbnail() && ! in_array( 'thumbnail', $exclude_display ) ) : ?>
        <div class="wp-ulike-pro-item-thumbnail">
            <?php the_post_thumbnail( 'large' ); ?>
        </div>
        <?php endif; ?>
        <?php if ( ! in_array( 'title', $exclude_display ) ) : ?>
        <h3 class="wp-ulike-pro-item-title">
            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
        </h3>
        <?php endif; ?>
        <?php if ( ! in_array( 'description', $exclude_display ) ) : ?>
        <div class="wp-ulike-pro-item-desc">
            <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
        </div>
        <?php endif; ?>
        <div class="wp-ulike-pro-item-info">
            <?php if ( ! in_array( 'date', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-date">
                <i class="ulp-icon-clock"></i>
                <span><?php echo get_the_date( get_option( 'date_format', 'F j, Y' ) ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( ! in_array( 'author', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-author">
                <i class="ulp-icon-torso"></i>
                <span><?php echo get_the_author_meta( 'display_name' ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( ! in_array( 'votes', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-votes">
                <?php
                $is_distinct = wp_ulike_setting_repo::isDistinct('post');
                $likes       = wp_ulike_get_counter_value( wp_ulike_get_the_id( $post->ID ), 'post', 'like', $is_distinct  );
                $dislikes    = wp_ulike_get_counter_value( wp_ulike_get_the_id( $post->ID ), 'post', 'dislike', $is_distinct );

                if( ! empty( $likes ) ){ ?>
                <span class="wp-ulike-up-votes">
                    <i class="ulp-icon-like"></i>
                    <span><?php echo $likes; ?></span>
                </span>
                <?php }
                if( ! empty( $dislikes ) ){ ?>
                <span class="wp-ulike-down-votes">
                    <i class="ulp-icon-dislike"></i>
                    <span><?php echo $dislikes;?></span>
                </span>
                <?php } ?>
            </div>
            <?php endif; ?>
        </div>
        <?php do_action( 'wp_ulike_pro_posts_after_hook', $query_args ); ?>
    </div>
</div>
<?php
    }
    // End Loop
    echo '</div>';
}
/* Restore original Post Data */
wp_reset_postdata();