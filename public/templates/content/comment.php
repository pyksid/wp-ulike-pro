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
if ( $wp_ulike_query ) {
    echo '<div class="wp-ulike-pro-single-comment ulp-flex-row ulp-flex-middle-xs">';
    // Start Loop
    foreach ( $wp_ulike_query as $comment ) {
?>
<div class="wp-ulike-pro-item-container ulp-flex-col-xl-<?php echo ! empty( $query_args['desktop_column'] ) ? 12 / intval( $query_args['desktop_column'] ) : 12; ?> ulp-flex-col-md-<?php echo ! empty( $query_args['tablet_column'] ) ? 12 / intval( $query_args['tablet_column'] ) : 12; ?> ulp-flex-col-xs-<?php echo ! empty( $query_args['mobile_column'] ) ? 12 / intval( $query_args['mobile_column'] ) : 12; ?> wp-ulike-pro-item-col">
    <div class="wp-ulike-pro-content-wrapper">
        <?php do_action( 'wp_ulike_pro_comments_before_hook', $query_args ); ?>
        <?php if ( ! in_array( 'title', $exclude_display ) ) : ?>
        <h3 class="wp-ulike-pro-item-title">
            <a href="<?php echo get_comment_link( $comment->comment_ID ); ?>"
                title="<?php echo esc_attr( get_the_title( $comment->comment_post_ID ) ); ?>">
                <?php echo get_the_title( $comment->comment_post_ID ); ?>
            </a>
        </h3>
        <?php endif; ?>
        <?php if ( ! in_array( 'description', $exclude_display ) ) : ?>
        <div class="wp-ulike-pro-item-desc">
            <?php echo sprintf( '<p>%s</p>', $comment->comment_content ); ?>
        </div>
        <?php endif; ?>
        <div class="wp-ulike-pro-item-info">
            <?php if ( ! in_array( 'date', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-date">
                <i class="ulp-icon-clock"></i>
                <span><?php echo wp_date( get_option( 'date_format', 'F j, Y' ), strtotime( $comment->comment_date_gmt ) ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( ! in_array( 'author', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-author">
                <i class="ulp-icon-torso"></i>
                <span><?php echo esc_html( $comment->comment_author ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( ! in_array( 'votes', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-votes">
                <?php
                $is_distinct = wp_ulike_setting_repo::isDistinct('comment');
                $likes       = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'like', $is_distinct  );
                $dislikes    = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'dislike', $is_distinct );

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
        <?php do_action( 'wp_ulike_pro_comments_after_hook', $query_args ); ?>
    </div>
</div>
<?php
    }
    // End Loop
    echo '</div>';
}