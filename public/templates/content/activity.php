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
    echo '<div class="wp-ulike-pro-single-activity ulp-flex-row ulp-flex-middle-xs">';
    // Start Loop
    foreach ( $wp_ulike_query as $activity ) {
    $activity_permalink = bp_activity_get_permalink( $activity->id );
    $activity_action    = ! empty( $activity->content ) ? $activity->content : $activity->action;
    $activity_author    = get_user_by( 'id', $activity->user_id );
    $activity_media     = function_exists('bp_media_get_activity_media') ? bp_media_get_activity_media( $activity->id ) : array();
?>
<div class="wp-ulike-pro-item-container ulp-flex-col-xl-<?php echo ! empty( $query_args['desktop_column'] ) ? 12 / intval( $query_args['desktop_column'] ) : 12; ?> ulp-flex-col-md-<?php echo ! empty( $query_args['tablet_column'] ) ? 12 / intval( $query_args['tablet_column'] ) : 12; ?> ulp-flex-col-xs-<?php echo ! empty( $query_args['mobile_column'] ) ? 12 / intval( $query_args['mobile_column'] ) : 12; ?> wp-ulike-pro-item-col">
    <div class="wp-ulike-pro-content-wrapper">
        <?php do_action( 'wp_ulike_pro_activities_before_hook', $query_args ); ?>
        <?php if ( ! in_array( 'description', $exclude_display ) ) : ?>
        <div class="wp-ulike-pro-item-desc">
            <a href="<?php echo esc_url( $activity_permalink ); ?>"><?php echo $activity_action; ?></a>
            <?php echo ! empty($activity_media['content']) ? $activity_media['content'] : ''; ?>
        </div>
        <?php endif; ?>
        <div class="wp-ulike-pro-item-info">
            <?php if ( ! in_array( 'date', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-date">
                <i class="ulp-icon-clock"></i>
                <span><?php echo date_i18n( get_option( 'date_format', 'F j, Y' ), strtotime( $activity->date_recorded ) ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( ! in_array( 'author', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-author">
                <i class="ulp-icon-torso"></i>
                <span><?php echo esc_html( $activity_author->display_name ); ?></span>
            </div>
            <?php endif; ?>
            <?php if ( ! in_array( 'votes', $exclude_display ) ) : ?>
            <div class="wp-ulike-entry-votes">
                <?php
                $is_distinct = wp_ulike_setting_repo::isDistinct('activity');
                $likes       = wp_ulike_get_counter_value( $activity->id, 'activity', 'like', $is_distinct  );
                $dislikes    = wp_ulike_get_counter_value( $activity->id, 'activity', 'dislike', $is_distinct );

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
        <?php do_action( 'wp_ulike_pro_activities_after_hook', $query_args ); ?>
    </div>
</div>
<?php
    }
    // End Loop
    echo '</div>';
}