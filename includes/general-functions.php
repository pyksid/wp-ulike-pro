<?php
/**
 * General functions
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */


/**
 * Retrieves the current user object.
 *
 * @global WP_User $wp_ulike_pro_current_user Checks if the current user is set.
 *
 * @return WP_User Current WP_User instance.
 */
function wp_ulike_pro_get_current_user() {
    global $wp_ulike_pro_current_user;

    if ( ! empty( $wp_ulike_pro_current_user ) ) {
        if ( $wp_ulike_pro_current_user instanceof WP_User ) {
            return $wp_ulike_pro_current_user;
        }

        // Upgrade stdClass to WP_User.
        if ( is_object( $wp_ulike_pro_current_user ) && isset( $wp_ulike_pro_current_user->ID ) ) {
            $cur_id       = $wp_ulike_pro_current_user->ID;
            $wp_ulike_pro_current_user = null;
            wp_ulike_pro_set_current_user( $cur_id );
            return $wp_ulike_pro_current_user;
        }

        // $wp_ulike_pro_current_user has a junk value. Force to WP_User with ID 0.
        $wp_ulike_pro_current_user = null;
        wp_ulike_pro_set_current_user( 0 );
        return $wp_ulike_pro_current_user;
    }

    if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
        wp_ulike_pro_set_current_user( 0 );
        return $wp_ulike_pro_current_user;
    }

    /**
     * Filters the current user.
     *
     * The default filters use this to determine the current user from the
     * request's cookies, if available.
     *
     * Returning a value of false will effectively short-circuit setting
     * the current user.
     *
     * @param int|bool $user_id User ID if one has been determined, false otherwise.
     */
    $user_id = apply_filters( 'wp_ulike_pro_determine_current_user', false );
    if ( ! $user_id ) {
        wp_ulike_pro_set_current_user( 0 );
        return $wp_ulike_pro_current_user;
    }

    wp_ulike_pro_set_current_user( $user_id );

    return $wp_ulike_pro_current_user;
}

/**
 * Get the current user's ID
 *
 * @return int The current user's ID, or 0 if no user is logged in.
 */
function wp_ulike_pro_get_current_user_id() {
    if ( ! function_exists( 'wp_ulike_pro_get_current_user' ) ) {
        return 0;
    }
    $user = wp_ulike_pro_get_current_user();
    return ( isset( $user->ID ) ? (int) $user->ID : NULL );
}

/**
 * Changes the current user by ID or name.
 *
 * Set $id to null and specify a name if you do not know a user's ID.
 *
 * @global WP_User $wp_ulike_pro_current_user The current user object which holds the user data.
 *
 * @param int    $id   User ID
 * @param string $name User's username
 * @return WP_User Current user User object
 */
function wp_ulike_pro_set_current_user( $id, $name = '' ) {
    global $wp_ulike_pro_current_user;

    // If `$id` matches the current user, there is nothing to do.
    if ( isset( $wp_ulike_pro_current_user )
    && ( $wp_ulike_pro_current_user instanceof WP_User )
    && ( $id == $wp_ulike_pro_current_user->ID )
    && ( null !== $id )
    ) {
        return $wp_ulike_pro_current_user;
    }

    $wp_ulike_pro_current_user = new WP_User( $id, $name );

    setup_userdata( $wp_ulike_pro_current_user->ID );

    /**
     * Fires after the current user is set.
     */
    do_action( 'wp_ulike_pro_set_current_user' );

    return $wp_ulike_pro_current_user;
}

 /**
  * Set pro classess for premium templates support
  *
  * @param array $args
  * @param array $info
  * @return void
  */
function wp_ulike_pro_generate_button_classes( array $args, array $info, $temp_list ){
	//Primary button class name
	$general_class = str_replace( ".", "", apply_filters( 'wp_ulike_pro_button_selector', 'wp_ulike_btn' ) );
	$final_classes = array(
		'up'   => $general_class . ' wp_ulike_btn_up ' . strtolower( ' wp_' . $args['slug'] . '_up_btn_' . $args['id'] ),
		'down' => $general_class . ' wp_ulike_btn_down ' . strtolower( ' wp_' . $args['slug'] . '_down_btn_' . $args['id'] )
	);

 	if( $args['button_type'] == 'image' || ( isset( $temp_list[$args['style']]['is_text_support'] ) && ! $temp_list[$args['style']]['is_text_support'] ) ){
		$final_classes['up']   .= ' wp_ulike_put_image';
		$final_classes['down'] .= ' wp_ulike_put_image';

		if( in_array( $info['status'], array( 2, 4 ) ) && strpos( $info['user_status'], 'dis') === 0 ){
			$final_classes['down'] .= ' image-unlike wp_ulike_btn_is_active';
		} elseif( in_array( $info['status'], array( 2, 4 ) ) && strpos( $info['user_status'], 'dis') !== 0 ) {
			$final_classes['up'] .= ' image-unlike wp_ulike_btn_is_active';
		}
	} else {
		$final_classes['up']   .= ' wp_ulike_put_text';
		$final_classes['down'] .= ' wp_ulike_put_text';
	}

	return $final_classes;
}

 /**
  * Set pro classess for premium templates support
  *
  * @param array $args
  * @param array $info
  * @return void
  */
  function wp_ulike_pro_generate_general_classes( array $args, array $info ){
	//Primary button class name
	$general_class = str_replace( ".", "", apply_filters( 'wp_ulike_pro_general_selector', 'wp_ulike_general_class' ) );
	$final_classes = array(
		'up'   => $general_class . ' wpulike_up_vote',
		'down' => $general_class . ' wpulike_down_vote',
		'sub'  => $general_class . ' wpulike_total_vote'
	);

	switch ($info['status']){
		case 0:
			$final_classes['up']   .= ' wp_ulike_is_not_logged';
			$final_classes['down'] .= ' wp_ulike_is_not_logged';
			$final_classes['sub']  .= ' wp_ulike_is_not_logged';
			break;
		case 1:
			$final_classes['up']   .= ' wp_ulike_is_not_liked';
			$final_classes['down'] .= ' wp_ulike_is_not_liked';
			$final_classes['sub']  .= ' wp_ulike_is_not_liked';
			break;
		case 2:
			if( in_array( $info['status'], array( 2, 4 ) ) && strpos( $info['user_status'], 'dis') === 0 ){
				$final_classes['down'] .= ' wp_ulike_is_liked';
				$final_classes['sub']  .= ' wp_ulike_is_liked';
			} elseif( in_array( $info['status'], array( 2, 4 ) ) && strpos( $info['user_status'], 'dis') !== 0 ) {
				$final_classes['up']  .= ' wp_ulike_is_liked';
				$final_classes['sub'] .= ' wp_ulike_is_liked';
			}
			break;
		case 3:
			$final_classes['up']   .= ' wp_ulike_is_unliked';
			$final_classes['down'] .= ' wp_ulike_is_unliked';
			$final_classes['sub']  .= ' wp_ulike_is_unliked';
			break;
		case 4:
			$final_classes['up']   .= ' wp_ulike_is_already_liked';
			$final_classes['down'] .= ' wp_ulike_is_already_liked';
			$final_classes['sub']  .= ' wp_ulike_is_already_liked';
	}

	return $final_classes;
}


/**
 * Default Up/Down vote template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_default_up_down_voting_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'updown-voting' );
}

/**
 * BookHeart template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_bookheart_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'bookheart' );
}

/**
 * CheckMark template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_checkmark_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'checkmark' );
}

/**
 * Voters template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_voters_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'voters' );
}

/**
 * CheckVote template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_checkvote_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'checkvote' );
}

/**
 * CheckVote template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_brokenheart_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'brokenheart' );
}

/**
 * CheckVote template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_positivecircle_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'positivecircle' );
}

/**
 * FeedBack template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_feedback_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'feedback' );
}

/**
 * Rating Face template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_rating_face_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'ratingface' );
}

/**
 * Rating Boy template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_rating_boy_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'rating-gender boy' );
}

/**
 * Rating Girl template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_rating_girl_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'rating-gender girl' );
}

/**
 * Badge Thumb template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_badge_thumb_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'badge-thumb' );
}

/**
 * Smiley Switch Template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_smiley_switch_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'smiley-switch'  );
}

/**
 * Pin template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_pin_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_global_template( $wp_ulike_template, 'pin' );
}

/**
 * Get us global template structure
 *
 * @param array $wp_ulike_template
 * @param string $template
 * @return string
 */
function wp_ulike_pro_default_global_template( array $wp_ulike_template, $template_name ){
	//This function will turn output buffering on
	ob_start();
	do_action( 'wp_ulike_before_template', $wp_ulike_template );
	// Extract input array
	extract( $wp_ulike_template );
?>
<div class="wpulike wpulike-<?php echo $template_name; ?> <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
    <div class="<?php echo $pro_general_class['up']; ?>">
        <button type="button"
            aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', esc_html__( 'Like Button',WP_ULIKE_PRO_DOMAIN) ) ?>"
            data-ulike-id="<?php echo $ID; ?>" data-ulike-factor="up"
            data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>" data-ulike-type="<?php echo $type; ?>"
            data-ulike-template="<?php echo $style; ?>" data-ulike-display-likers="<?php echo $display_likers; ?>"
            data-ulike-likers-style="<?php echo $likers_style; ?>" class="<?php echo $pro_button_class['up']; ?>">
            <?php
					echo $up_vote_inner_text;
					do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
					if( $button_type == 'text' && $template_name == 'updown-voting' ){
						echo '<span>' . $button_text . '</span>';
					}
				?>
        </button>
        <?php
				echo $display_counters ? sprintf( '<span class="count-box wp_ulike_counter_up" data-ulike-counter-value="%s"></span>', $formatted_total_likes ) : '';
				do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
			?>
    </div>
    <div class="<?php echo $pro_general_class['down']; ?>">
        <button type="button"
            aria-label="<?php echo wp_ulike_get_option( 'dislike_button_aria_label', esc_html__( 'Dislike Button',WP_ULIKE_PRO_DOMAIN) ) ?>"
            data-ulike-id="<?php echo $ID; ?>" data-ulike-factor="down"
            data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>" data-ulike-type="<?php echo $type; ?>"
            data-ulike-template="<?php echo $style; ?>" data-ulike-display-likers="<?php echo $display_likers; ?>"
            data-ulike-likers-style="<?php echo $likers_style; ?>" class="<?php echo $pro_button_class['down']; ?>">
            <?php
					echo $down_vote_inner_text;
					do_action( 'wp_ulike_inside_dislike_button', $wp_ulike_template );
					if( $button_type == 'text' && $template_name == 'updown-voting' ){
						echo '<span>' . $dis_button_text . '</span>';
					}
				?>
        </button>
        <?php
				echo $display_counters ? sprintf( '<span class="count-box wp_ulike_counter_down" data-ulike-counter-value="%s"></span>', $formatted_total_dislikes ) : '';
				do_action( 'wp_ulike_after_down_vote_button', $wp_ulike_template );
			?>
    </div>
    <?php
		do_action( 'wp_ulike_inside_template', $wp_ulike_template );
	?>
</div>
<?php
	do_action( 'wp_ulike_after_template', $wp_ulike_template );
	return ob_get_clean(); // data is now in here
}


/**
 * Stack Votings Template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_stack_votings_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_total_template( $wp_ulike_template, 'stack-votings'  );
}

/**
 * Star Thumb template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_star_thumb_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_total_template( $wp_ulike_template, 'star-thumb' );
}

/**
 * Minimal Votings Template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_minimal_votings_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_total_template( $wp_ulike_template, 'minimal-votings'  );
}

/**
 * Arrow Votings Template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_arrow_votings_template( array $wp_ulike_template ){
	return wp_ulike_pro_default_total_template( $wp_ulike_template, 'arrow-votings'  );
}

/**
 * Fave star Template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_fave_star_template( array $wp_ulike_template ){
	//This function will turn output buffering on
	ob_start();
	do_action( 'wp_ulike_before_template', $wp_ulike_template );
	// Extract input array
	extract( $wp_ulike_template );
?>
<div class="wpulike wpulike-fave-star <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
    <div class="<?php echo $general_class; ?>">
        <button type="button"
            aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', esc_html__( 'Like Button', WP_ULIKE_PRO_DOMAIN) ) ?>"
            data-ulike-id="<?php echo $ID; ?>" data-ulike-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
            data-ulike-type="<?php echo $type; ?>" data-ulike-template="<?php echo $style; ?>"
            data-ulike-display-likers="<?php echo $display_likers; ?>"
            data-ulike-likers-style="<?php echo $likers_style; ?>" class="<?php echo $button_class; ?>">
            <span class="wp_ulike_fave_circle"></span>
            <span class="wp_ulike_fave_shine">
                <span class="wp_ulike_fave_shiner"></span>
                <span class="wp_ulike_fave_shiner"></span>
                <span class="wp_ulike_fave_shiner"></span>
                <span class="wp_ulike_fave_shiner"></span>
                <span class="wp_ulike_fave_shiner"></span>
            </span>
            <i class="wp_ulike_star_icon ulp-icon-star"></i>
            <?php
					echo $up_vote_inner_text;
					do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
				?>
        </button>
        <?php
				echo $display_counters ? sprintf( '<span class="count-box wp_ulike_counter_up" data-ulike-counter-value="%s"></span>', $formatted_total_likes ) : '';
				do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
			?>
    </div>
    <?php
		do_action( 'wp_ulike_inside_template', $wp_ulike_template );
	?>
</div>
<?php
	do_action( 'wp_ulike_after_template', $wp_ulike_template );
	return ob_get_clean(); // data is now in here
}

/**
 * Fave star Template
 *
 * @param array $wp_ulike_template
 * @return string
 */
function wp_ulike_pro_clapping_template( array $wp_ulike_template ){
	//This function will turn output buffering on
	ob_start();
	do_action( 'wp_ulike_before_template', $wp_ulike_template );
	// Extract input array
	extract( $wp_ulike_template );
?>
<div class="wpulike wpulike-clapping <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
    <div class="<?php echo $general_class; ?>">
        <button type="button"
            aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', esc_html__( 'Like Button', WP_ULIKE_PRO_DOMAIN) ) ?>"
            data-ulike-id="<?php echo $ID; ?>" data-ulike-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
            data-ulike-type="<?php echo $type; ?>" data-ulike-template="<?php echo $style; ?>"
            data-ulike-display-likers="<?php echo $display_likers; ?>"
            data-ulike-likers-style="<?php echo $likers_style; ?>" class="<?php echo $button_class; ?>">
            <span class="clap-icon">
                <svg class="clap-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="-549 338 100.1 125">
                    <path
                        d="M-471.2 366.8c1.2 1.1 1.9 2.6 2.3 4.1.4-.3.8-.5 1.2-.7 1-1.9.7-4.3-1-5.9-2-1.9-5.2-1.9-7.2.1l-.2.2c1.8.1 3.6.9 4.9 2.2zm-28.8 14c.4.9.7 1.9.8 3.1l16.5-16.9c.6-.6 1.4-1.1 2.1-1.5 1-1.9.7-4.4-.9-6-2-1.9-5.2-1.9-7.2.1l-15.5 15.9c2.3 2.2 3.1 3 4.2 5.3zm-38.9 39.7c-.1-8.9 3.2-17.2 9.4-23.6l18.6-19c.7-2 .5-4.1-.1-5.3-.8-1.8-1.3-2.3-3.6-4.5l-20.9 21.4c-10.6 10.8-11.2 27.6-2.3 39.3-.6-2.6-1-5.4-1.1-8.3z" />
                    <path
                        d="M-527.2 399.1l20.9-21.4c2.2 2.2 2.7 2.6 3.5 4.5.8 1.8 1 5.4-1.6 8l-11.8 12.2c-.5.5-.4 1.2 0 1.7.5.5 1.2.5 1.7 0l34-35c1.9-2 5.2-2.1 7.2-.1 2 1.9 2 5.2.1 7.2l-24.7 25.3c-.5.5-.4 1.2 0 1.7.5.5 1.2.5 1.7 0l28.5-29.3c2-2 5.2-2 7.1-.1 2 1.9 2 5.1.1 7.1l-28.5 29.3c-.5.5-.4 1.2 0 1.7.5.5 1.2.4 1.7 0l24.7-25.3c1.9-2 5.1-2.1 7.1-.1 2 1.9 2 5.2.1 7.2l-24.7 25.3c-.5.5-.4 1.2 0 1.7.5.5 1.2.5 1.7 0l14.6-15c2-2 5.2-2 7.2-.1 2 2 2.1 5.2.1 7.2l-27.6 28.4c-11.6 11.9-30.6 12.2-42.5.6-12-11.7-12.2-30.8-.6-42.7m18.1-48.4l-.7 4.9-2.2-4.4m7.6.9l-3.7 3.4 1.2-4.8m5.5 4.7l-4.8 1.6 3.1-3.9" />
                </svg>
            </span>
            <span class="clap-radial-dots">
                <span class="clap-radial-dot" style="transform: rotate(213.23057466967123deg);"></span>
                <span class="clap-radial-dot" style="transform: rotate(285.2305746696712deg);"></span>
                <span class="clap-radial-dot" style="transform: rotate(357.2305746696712deg);"></span>
                <span class="clap-radial-dot" style="transform: rotate(429.2305746696712deg);"></span>
                <span class="clap-radial-dot" style="transform: rotate(501.2305746696712deg);"></span>
            </span>
            <span class="clap-radial-triangles">
                <span class="clap-radial-triangle" style="transform: rotate(226.23057466967123deg);"></span>
                <span class="clap-radial-triangle" style="transform: rotate(298.2305746696712deg);"></span>
                <span class="clap-radial-triangle" style="transform: rotate(370.2305746696712deg);"></span>
                <span class="clap-radial-triangle" style="transform: rotate(442.2305746696712deg);"></span>
                <span class="clap-radial-triangle" style="transform: rotate(514.2305746696712deg);"></span>
            </span>
        </button>

        <?php
			echo $display_counters ? sprintf( '<span class="count-box wp_ulike_counter_up" data-ulike-counter-value="%s"></span>', $formatted_total_likes ) : '';
			do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
		?>
    </div>
    <?php
		do_action( 'wp_ulike_inside_template', $wp_ulike_template );
	?>
</div>
<?php
	do_action( 'wp_ulike_after_template', $wp_ulike_template );
	return ob_get_clean(); // data is now in here
}


/**
 * Subtotal Votings Template
 *
 * @param array $wp_ulike_template
 * @param string $template
 * @return string
 */
function wp_ulike_pro_default_total_template( array $wp_ulike_template, $template_name ){
	//This function will turn output buffering on
	ob_start();
	do_action( 'wp_ulike_before_template', $wp_ulike_template );
	// Extract input array
	extract( $wp_ulike_template );

	$total_sub = (int) $total_likes - (int) $total_dislikes;
    // Hide on zero value
    if( wp_ulike_setting_repo::isCounterZeroHidden( $type ) && $total_sub == 0 ){
        $total_sub = '';
    } else {
		$total_sub = wp_ulike_setting_repo::maybeHasUnitFormat( $total_sub );
	}
?>
<div class="wpulike wpulike-<?php echo $template_name; ?> <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
    <div class="<?php echo $pro_general_class['sub']; ?>">
        <button type="button"
            aria-label="<?php echo esc_attr( wp_ulike_get_option( 'like_button_aria_label', esc_html__( 'Like Button',WP_ULIKE_PRO_DOMAIN) ) ); ?>"
            data-ulike-id="<?php echo $ID; ?>" data-ulike-factor="up" data-ulike-is-total="1"
            data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>" data-ulike-type="<?php echo $type; ?>"
            data-ulike-template="<?php echo $style; ?>" data-ulike-display-likers="<?php echo $display_likers; ?>"
            data-ulike-likers-style="<?php echo $likers_style; ?>" class="<?php echo $pro_button_class['up']; ?>">
            <?php
					echo $up_vote_inner_text;
					do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
				?>
        </button>
        <?php
				// Display counter box
				if( isset( $display_counters ) && $display_counters ){
					$formatted_counter_value = wp_ulike_format_number( $total_sub, $total_sub >= 0 ? 'like' : 'dislike' );
					// Remove double minus
					if( $total_sub < 0 ){
						$formatted_counter_value = str_replace( "--", "-", $formatted_counter_value );
					}
					echo sprintf( '<span class="count-box wp_ulike_counter_sub" data-ulike-counter-value="%s"></span>', esc_attr( $formatted_counter_value ) );
				}
				do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
			?>
        <button type="button"
            aria-label="<?php echo esc_attr( wp_ulike_get_option( 'dislike_button_aria_label', esc_html__( 'Dislike Button',WP_ULIKE_PRO_DOMAIN) ) ); ?>"
            data-ulike-id="<?php echo $ID; ?>" data-ulike-factor="down" data-ulike-is-total="1"
            data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>" data-ulike-type="<?php echo $type; ?>"
            data-ulike-template="<?php echo $style; ?>" data-ulike-display-likers="<?php echo $display_likers; ?>"
            data-ulike-likers-style="<?php echo $likers_style; ?>" class="<?php echo $pro_button_class['down']; ?>">
            <?php
					echo $down_vote_inner_text;
					do_action( 'wp_ulike_inside_dislike_button', $wp_ulike_template );
				?>
        </button>
        <?php do_action( 'wp_ulike_after_down_vote_button', $wp_ulike_template ); ?>
    </div>
    <?php
		do_action( 'wp_ulike_inside_template', $wp_ulike_template );
	?>
</div>
<?php
	do_action( 'wp_ulike_after_template', $wp_ulike_template );
	return ob_get_clean(); // data is now in here
}


/**
 * get user roles list
 *
 * @return void
 */
function wp_ulike_pro_get_user_roles_list( $args = array() ){
	global $wp_roles;

	$role_names    = array();
	$limited_roles = wp_parse_args( $args, array( 'Keymaster', 'Spectator', 'Blocked', 'Participant' ) );

	if( ! isset( $wp_roles->roles ) ){
		return $role_names;
	}

    foreach ($wp_roles->roles as $key => $args) {
        if( isset( $args['name'] ) && ! in_array( $args['name'], $limited_roles ) ){
            $role_names[strtolower($args['name'])] = $args['name'];
        }
    }

    return $role_names;
}

/**
 * Get auto ID by it's type
 *
 * @param string $type
 * @return integer
 */
function wp_ulike_pro_get_auto_id( $type ){
	// Check value
	$final_ID = false;

	switch ($type) {
		case 'comment':
			$final_ID = get_comment_ID();
			break;

		case 'activity':
			if( defined( 'BP_VERSION' ) ){
				if ( bp_get_activity_comment_id() != null ){
					$final_ID = bp_get_activity_comment_id();
				} else {
					$final_ID = bp_get_activity_id();
				}
			}
			break;

		case 'topic':
			global $post;
			$reply_ID  = function_exists('bbp_get_reply_id') ? bbp_get_reply_id() : false;
			$final_ID = !$reply_ID ? $post->ID : $reply_ID;
			break;

		case 'post':
			global $post;
			$final_ID = wp_ulike_get_the_id( $post->ID );
			break;
	}

	return !is_wp_error( $final_ID ) ? $final_ID : false;
}

/**
 * Generates and may print a notice for missing required plugins in elementor
 *
 * @param  array $args
 * @return string       May return the notice markup
 */
function wp_ulike_pro_plugin_missing_notice( $args ){
    // default params
    $defaults = array(
        'plugin_name' => '',
        'echo'        => true
    );
    $args = wp_parse_args( $args, $defaults );

    ob_start();
    ?>
<div class="elementor-alert elementor-alert-danger" role="alert">
    <span class="elementor-alert-title">
        <?php echo sprintf( esc_html__( '"%s" Plugin is Not Activated!', WP_ULIKE_PRO_DOMAIN ), $args['plugin_name'] ); ?>
    </span>
    <span class="elementor-alert-description">
        <?php esc_html_e( 'In order to use this element, you need to install and activate this plugin.', WP_ULIKE_PRO_DOMAIN ); ?>
    </span>
</div>
<?php
    $notice =  ob_get_clean();

    if( $args['echo'] ){
        echo $notice;
    } else {
        return $notice;
	}
}

/**
 * Get public post type list
 *
 * @param array $args
 * @return array
 */
function wp_ulike_pro_get_public_post_types( $args = array()  ) {
    $post_type_args = [
        // Default is the value $public.
        'show_in_nav_menus' => true,
    ];

    // Keep for backwards compatibility
    if ( ! empty( $args['post_type'] ) ) {
        $post_type_args['name'] = $args['post_type'];
        unset( $args['post_type'] );
    }

    $post_type_args = wp_parse_args( $post_type_args, $args );

    $_post_types = get_post_types( $post_type_args, 'objects' );

    $post_types = [];

    foreach ( $_post_types as $post_type => $object ) {
        $post_types[ $post_type ] = $object->label;
    }

    return apply_filters( 'wp_ulike_pro_get_public_post_types', $post_types );
}

/**
 * Get post type meta box values
 *
 * @param string $meta_name
 * @param integer $post_ID
 * @return string|array
 */
function wp_ulike_pro_get_metabox_value( $meta_name, $post_ID = '' ){
	$post_ID      = empty( $post_ID ) ? get_the_ID() : $post_ID;
	$meta_value   = NULL;
	$is_serialize = wp_ulike_get_option( 'enable_serialize', false );

	if( wp_ulike_is_true( $is_serialize ) ){
		$meta_box     = get_post_meta( $post_ID, 'wp_ulike_pro_meta_box' , true );
		$meta_value   = isset( $meta_box[$meta_name] ) ? maybe_unserialize( $meta_box[$meta_name] ) : NULL;
	}

	if( empty( $meta_value ) ){
		$prefix     = 'wp_ulike_pro_';
		$meta_value = get_post_meta( $post_ID, $prefix . $meta_name , true );
	}

	return is_array( $meta_value ) ? $meta_value : esc_html( $meta_value );
}

/**
 * Get our meta box values
 *
 * @param string $meta_name
 * @param integer $post_ID
 * @return string|array
 */
function wp_ulike_pro_get_comment_metabox_value( $meta_name, $comment_ID = '' ){
	$comment_ID   = empty( $comment_ID ) ? get_comment_ID() : $comment_ID;
	$meta_value   = NULL;

	$meta_box     = get_comment_meta( $comment_ID, 'wp_ulike_pro_comment_meta_box' , true );
	$meta_value   = isset( $meta_box[$meta_name] ) ? maybe_unserialize( $meta_box[$meta_name] ) : NULL;

	return is_array( $meta_value ) ? $meta_value : esc_html( $meta_value );
}

/**
 * Get counter quantity value
 *
 * @param integer $id
 * @param string $status
 * @param string $type
 * @return integer
 */
function wp_ulike_pro_get_counter_quantity( $id, $status, $type = 'post' ){
	$counter_key = strpos( $status, 'dislike' ) !== false ? 'dislikes_counter_quantity' : 'likes_counter_quantity';
	// Default counter quantity value
	$counter_val = 0;

	switch ($type) {
		case 'post':
			$counter_val = wp_ulike_pro_get_metabox_value( $counter_key, $id );
			break;
		case 'comment':
			$counter_val = wp_ulike_pro_get_comment_metabox_value( $counter_key, $id );
			break;
	}

    return ! empty( $counter_val ) ? (int) $counter_val : 0;
}

/**
 * Get templates list by it's name in array
 *
 * @return array
 */
function wp_ulike_pro_get_templates_list_by_name(){
	$options   = array() ;
	$templates = wp_ulike_generate_templates_list();
	foreach( $templates as $key => $args ) {
		$options[ $key ] = $args['name'];
	}

	return $options;
}

/**
 * Get templates list by it's attribute.
 *
 * @return array
 */
function wp_ulike_pro_get_templates_list_by_attribute( $attr ){
	$options   = array() ;
	$templates = wp_ulike_generate_templates_list();
	foreach( $templates as $key => $args ) {
		if( ! empty( $args[$attr] ) ){
			$options[] = $key;
		}
	}

	return ! empty( $options ) ? implode(',', $options) : NULL;
}

/**
 * Check current user profile page
 *
 * @param integer $target_id
 * @param integer $current_page_id
 * @return void
 */
function wp_ulike_pro_is_profile_page( $target_id = '', $current_page_id = '' ){
	$profiles_core_page = empty( $target_id ) ? WP_Ulike_Pro_Options::getProfilePage() : $target_id;

	if( empty( $current_page_id ) ){
		$current_page_id = get_queried_object_id();
	}

	return is_page( $profiles_core_page ) && $profiles_core_page == $current_page_id;
}

/**
 * Get templates list by it's name in array
 *
 * @return void
 */
function wp_ulike_pro_get_public_template( $template_name, $user_id = '' ){
	// Turn on output buffering
	ob_start();
    // Before load template hook
    do_action( 'wp_ulike_pro/'. $template_name .'/before_load_teamplate', $user_id );
    // Load user profile template
    load_template( WP_ULIKE_PRO_PUBLIC_DIR . '/templates/'. $template_name .'.php', false );
    // After load template hook
	do_action( 'wp_ulike_pro/'. $template_name .'/after_load_teamplate', $user_id );
	// Return current buffer contents
	return apply_filters( 'wp_ulike_pro_public_templates', ob_get_clean(), $template_name );
}

/**
 * Get other templates (e.g. product attributes) passing attributes and including the file.
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function wp_ulike_pro_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$template = wp_ulike_pro_locate_template( $template_name, $template_path, $default_path );

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // @codingStandardsIgnoreLine
	}

	do_action( 'wp_ulike_pro_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'wp_ulike_pro_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return string
 */
function wp_ulike_pro_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = WP_ULIKE_SLUG;
	}

	if ( ! $default_path ) {
		$default_path = WP_ULIKE_PRO_PUBLIC_DIR . '/templates/';
	}

	if ( empty( $template ) ) {
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			)
		);
	}

	// Get default template/.
	if ( ! $template ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'wp_ulike_pro_locate_template', $template, $template_name, $template_path );
}

/**
 * Get user profile ID or Permalink
 *
 * @return string|integer
 */
function wp_ulike_pro_get_user_profile_id(){
    return apply_filters( 'wp_ulike_pro_get_profile_user_id', get_current_user_id() );
}

/**
 * Get user profile ID or Permalink
 *
 * @param string $type
 * @return string|integer
 */
function wp_ulike_pro_get_user_profile_permalink( $user_id = '' ){
	// User profile
	$page_id = WP_Ulike_Pro_Options::getProfilePage();
	$user_id = empty( $user_id ) ? wp_ulike_pro_get_current_user_id() : $user_id;
	$user    = new WP_Ulike_Pro_User();
	$url     = $user->get_profile_link( $user_id );

	if ( empty( $url ) ) {
		//if empty profile slug - generate it and re-get profile URL
		$user->generate_profile_slug( $user_id );
		$url = $user->get_profile_link( $user_id );
	}

	return ! empty( $page_id ) && !empty( $url ) ? esc_url( $url ) : get_site_url();
}


/**
 * Pagination system
 *
 * @param array $args
 * @return string|null
 */
function wp_ulike_pro_pagination( $args = array() ) {

	//Main data
	$defaults = array(
		"total_pages"  => '',
		"per_page"     => 10,
		"custom_query" => NULL,
		"prev_text"    => 'Prev',
		"next_text"    => 'Next'
	);
	$parsed_args  = wp_parse_args( $args, $defaults );
	// Extract values
	extract( $parsed_args );

	// Fix zero division issue
	if( ! $per_page ){
		$per_page = 10;
	}

	if( empty( $total_pages ) ) {
		global $wp_query;
		$query = empty( $custom_query ) ? $wp_query : $custom_query;
		$total_pages = $query->max_num_pages;

		if( ! $total_pages ) {
			$total_pages = 1;
		}
	} else {
		$total_pages = ceil( (int) $total_pages / (int) $per_page );
	}

	$output = null;

	if( $total_pages > 1 ) {
		$big   = 999999999; // need an unlikely integer
		$pages = paginate_links( array(
			'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'    => '?paged=%#%',
			'current'   => max( 1, get_query_var('paged'), get_query_var('page') ),
			'total'     => $total_pages,
			'type'      => 'plain',
			'end_size'  => 1,
			'mid_size'  => 1,
			'prev_next' => true,
			'prev_text' => $prev_text,
			'next_text' => $next_text
		) );

		if( ! empty( $pages ) ){
			$output = sprintf( '<nav class="wp-ulike-pro-pagination" role="navigation" aria-label="Pagination">%s</nav>', $pages );
		}
	}

	return $output;

}

/**
 * Get items
 *
 * @param array $args
 * @return object|null
 */
function wp_ulike_pro_get_items_info( $args = array() ){
	// Global wordpress database object
	global $wpdb;
	//Main data
	$defaults = array(
		"type"     => 'post',
		"rel_type" => 'post',
		"status"   => 'like',
		"user_id"  => '',
		"order"    => 'DESC',
		"period"   => 'all',
		"offset"   => 1,
		"limit"    => 10
	);

	$parsed_args  = wp_parse_args( $args, $defaults );
	$info_args    = wp_ulike_get_table_info( $parsed_args['type'] );
	$period_limit = wp_ulike_get_period_limit_sql( $parsed_args['period'] );

	$limit_records = '';
	if( (int) $parsed_args['limit'] > 0 ){
		$offset = $parsed_args['offset'] > 0 ? ( $parsed_args['offset'] - 1 ) * $parsed_args['limit'] : 0;
		$limit_records = sprintf( "LIMIT %d, %d", $offset, $parsed_args['limit'] );
	}


	$related_condition = '';
	switch ($parsed_args['type']) {
		case 'post':
		case 'topic':
			$post_type = '';
			if( is_array( $parsed_args['rel_type'] ) ){
				$post_type = sprintf( " AND r.post_type IN ('%s')", implode ("','", $parsed_args['rel_type'] ) );
			} elseif( ! empty( $parsed_args['rel_type'] ) ) {
				$post_type = sprintf( " AND r.post_type = '%s'", $parsed_args['rel_type'] );
			}
			$related_condition = 'AND r.post_status IN (\'publish\', \'inherit\', \'private\')'  . $post_type;
			break;
	}

	$user_condition = '';
	if( !empty( $parsed_args['user_id'] ) ){
		if( is_array( $parsed_args['user_id'] ) ){
			$user_condition = sprintf( " AND t.user_id IN ('%s')", implode ("','", $parsed_args['user_id'] ) );
		} else {
			$user_condition = sprintf( " AND t.user_id = '%s'", $parsed_args['user_id'] );
		}
	}

	// create query condition from status
	$status_type  = '';
	if( is_array( $parsed_args['status'] ) ){
		$status_type = sprintf( "t.status IN ('%s')", implode ("','", $parsed_args['status'] ) );
	} else {
		$status_type = sprintf( "t.status = '%s'", $parsed_args['status'] );
	}

	// generate query string
	$query  = sprintf( '
		SELECT DISTINCT t.%1$s AS item_ID
		FROM %2$s t
		INNER JOIN %3$s r ON t.%1$s = r.%4$s %5$s
		WHERE %6$s %7$s
		%8$s
		ORDER BY item_ID
		%9$s %10$s',
		$info_args['column'],
		$wpdb->prefix . $info_args['table'],
		$info_args['related_table_prefix'],
		$info_args['related_column'],
		$related_condition,
		$status_type,
		$user_condition,
		$period_limit,
		$parsed_args['order'],
		$limit_records
	);

	$result = $wpdb->get_results( $query, OBJECT_K );

	return !empty( $result ) ? array_keys( $result ) : null;
}

/**
 * Get posts WP_Query
 *
 * @param array $args
 * @return array
 */
function wp_ulike_pro_get_posts_query( $args ){

	//Main data
	$defaults = array(
		"type"       => 'post',
		"rel_type"   => 'post',
		"is_popular" => false,
		"status"     => 'like',
		"user_id"    => '',
		"order"      => 'DESC',
		"period"     => 'all',
		"offset"     => 1,
		"limit"      => 10
	);
	$parsed_args = wp_parse_args( $args, $defaults );
	if( $parsed_args['type'] === 'topic' ){
		// Get bbpress post types
		$parsed_args['rel_type'] =  array( 'topic', 'reply' );
	}

	if( empty( $parsed_args['rel_type'] ) ){
		// Get post types
		$parsed_args['rel_type'] =  get_post_types_by_support( array(
			'title',
			'editor',
			'thumbnail'
		) );
	}

	$get_items  = wp_ulike_is_true( $parsed_args['is_popular'] ) ? wp_ulike_get_popular_items_ids( $parsed_args ) : wp_ulike_pro_get_items_info( $parsed_args );

	$query_args = array(
		'post_type'      => $parsed_args['rel_type'],
		'post_status'    => array('publish', 'inherit', 'private'),
		'posts_per_page' => $parsed_args['limit']
	);

	if( ! empty( $get_items ) ){
		$query_args['post__in'] = $get_items;
		$query_args['orderby'] = 'post__in';
	} elseif( empty( $get_items ) ) {
		return false;
	}

	return new WP_Query( $query_args );
}


/**
 * Get comments WP_Query
 *
 * @param array $args
 * @return array
 */
function wp_ulike_pro_get_comments_query( $args ){

	//Main data
	$defaults = array(
		"type"       => 'comment',
		"rel_type"   => '',
		"is_popular" => false,
		"status"     => 'like',
		"user_id"    => '',
		"order"      => 'DESC',
		"period"     => 'all',
		"offset"     => 1,
		"limit"      => 10
	);
	$parsed_args = wp_parse_args( $args, $defaults );

	if( empty( $parsed_args['rel_type'] ) ){
		// Get post types
		$parsed_args['rel_type'] =  get_post_types_by_support( array(
			'title',
			'editor',
			'thumbnail'
		) );
	}

	$get_items  = wp_ulike_is_true( $parsed_args['is_popular'] ) ? wp_ulike_get_popular_items_ids( $parsed_args ) : wp_ulike_pro_get_items_info( $parsed_args );

	if( empty( $get_items ) ){
		return false;
	}

	$query_args = array(
		'comment__in' => $get_items,
		'orderby'     => 'comment__in',
		'post_type'   => $parsed_args['rel_type']
	);

	$comments_query = new WP_Comment_Query;

	return $comments_query->query( $query_args );
}

/**
 * Get buddypress activity query
 *
 * @param array $args
 * @return array
 */
function wp_ulike_pro_get_activity_query( $args ){
	// check buddypress activation
	if( ! defined( 'BP_VERSION' ) ) {
		return false;
	}

	//Main data
	$defaults = array(
		"type"       => 'activity',
		"rel_type"   => '',
		"is_popular" => false,
		"status"     => 'like',
		"user_id"    => '',
		"order"      => 'DESC',
		"period"     => 'all',
		"offset"     => 1,
		"limit"      => 10
	);
	$parsed_args = wp_parse_args( $args, $defaults );
	$get_items   = wp_ulike_is_true( $parsed_args['is_popular'] ) ? wp_ulike_get_popular_items_ids( $parsed_args ) : wp_ulike_pro_get_items_info( $parsed_args );

	if( empty( $get_items ) ){
		return false;
	}

	global $wpdb;

	if ( is_multisite() ) {
		$bp_prefix = 'base_prefix';
	} else {
		$bp_prefix = 'prefix';
	}

	// generate query string
	$query_string  = sprintf( '
		SELECT * FROM
		`%1$sbp_activity`
		WHERE `id` IN (%2$s)
		ORDER BY FIELD(`id`, %2$s)',
		$wpdb->$bp_prefix,
		implode( ',',$get_items )
	);

	return $wpdb->get_results( $query_string );
}

/**
 * Get past days time
 *
 * @param integer $days
 * @param string $type
 * @param integer $gmt
 * @return string
 */
function wp_ulike_pro_get_past_time( $days = 30, $type = 'mysql', $gmt = 0 ) {
    if ( 'mysql' === $type ) {
        $type = 'Y-m-d H:i:s';
    }

    $timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();
	$datetime = new DateTime( 'now', $timezone );
	$datetime = $datetime->sub( DateInterval::createFromDateString( $days . ' days' ) );

    return $datetime->format( $type );
}

/**
 * Generate custom length hash
 *
 * @param string $content
 * @return string
 */
function wp_ulike_pro_create_hash( $content ){
	return substr( wp_hash(  $content ), -12, 8 );
}

/**
 * generate unique token for audit API
 *
 * @return string
 */
function wp_ulike_pro_get_audit_token(){
    $option_name = 'wp_ulike_pro_audit_token';
    $site_key = get_option( $option_name );

    if ( ! $site_key ) {
        $site_key = md5( uniqid( wp_generate_password() ) );
        update_option( $option_name, $site_key );
    }

    return $site_key;
}

/**
 * Get reffer url
 *
 * @return string
 */
function wp_ulike_pro_get_referer_url(){
	global $wp;

	if( defined( 'DOING_AJAX' ) && DOING_AJAX ){
		return wp_get_referer();
	} else{
		return  home_url( add_query_arg( array(), $wp->request ) );
	}
}

/**
 * User clean basename
 *
 * @param $value
 *
 * @return mixed|void
 */
function wp_ulike_pro_clean_user_basename( $value ) {
	$raw_value = $value;
	$value = str_replace( '.', ' ', $value );
	$value = str_replace( '-', ' ', $value );
	$value = str_replace( '+', ' ', $value );

	$value = apply_filters( 'wp_ulike_pro_clean_user_basename', $value, $raw_value );

	return $value;
}

/**
 * Get core pages list
 *
 * @return array
 */
function wp_ulike_pro_get_core_pages_list( $selected_core_pages = array() ) {
	return WP_Ulike_Pro_Options::getCorePages( $selected_core_pages );
}

/**
 * verify two factor
 *
 * @param array $otp
 * @param array $secrets
 * @return boolean
 */
function wp_ulike_pro_is_valid_otp( $otp, $secrets ) {
	// check otp value
	if( empty( $otp ) || ! is_array( $otp ) ){
		return false;
	}
	// check digit length
	foreach ($otp as $digit) {
		if( ! is_numeric( $digit ) ){
			return false;
		}
	}

	$tfa  = new RobThree\Auth\TwoFactorAuth();
	$code = (string) implode( "", $otp );

	foreach ( $secrets as $secret_value => $secret_args ) {
		if( ! empty( $secret_value ) && $tfa->verifyCode( $secret_value, $code ) ){
			return true;
		}
	}

	return false;
}

/**
 * Get two factor field
 *
 * @return string
 */
function wp_ulike_pro_get_two_factor_field() {
	ob_start();
?>
<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
    <h3 class="ulp-form-title">
        <?php echo wp_ulike_get_option( 'two_factor_field_title', esc_html__( 'Enter the six-digit code from the application', WP_ULIKE_PRO_DOMAIN ) ); ?>
    </h3>
</div>
<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
    <div id="ulp-2fa-code" class="ulp-flex ulp-flex-center-xs">
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-1-digit"
            name="otp[]" autocomplete="off" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-2-digit"
            name="otp[]" autocomplete="off" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-3-digit"
            name="otp[]" autocomplete="off" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-4-digit"
            name="otp[]" autocomplete="off" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-5-digit"
            name="otp[]" autocomplete="off" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-6-digit"
            name="otp[]" autocomplete="off" />
    </div>
</div>
<script>
function ulpOtpInput() {
    const inputs = document.querySelectorAll('#ulp-2fa-code > *[id]');

    function handleInput(event, index) {
        const input = inputs[index];

        if (event.key === "Backspace") {
            input.value = '';
            if (index !== 0) inputs[index - 1].focus();
        } else {
            const isNumeric = event.keyCode > 47 && event.keyCode < 58;
            const isAlphabetic = event.keyCode > 64 && event.keyCode < 91;

            if (index === inputs.length - 1 && input.value !== '') {
                return true;
            } else if (isNumeric || isAlphabetic) {
                input.value = isNumeric ? event.key : String.fromCharCode(event.keyCode);
                if (index !== inputs.length - 1) inputs[index + 1].focus();
                event.preventDefault();
            }
        }
    }

    inputs.forEach((input, index) => {
        input.addEventListener('keydown', (event) => {
            handleInput(event, index);
        });
    });
}

ulpOtpInput();

// init form ajax
jQuery(function() {
    jQuery(".ulp-ajax-form").WordpressUlikeAjaxForms();
});
</script>
<?php
	return ob_get_clean();
}

function wp_ulike_pro_unstable_get_super_global_value( $super_global, $key ) {
	if ( ! isset( $super_global[ $key ] ) ) {
		return null;
	}

	if ( $_FILES === $super_global ) {
		$super_global[ $key ]['name'] = sanitize_file_name( $super_global[ $key ]['name'] );
		return $super_global[ $key ];
	}

	return wp_kses_post_deep( wp_unslash( $super_global[ $key ] ) );
}

/**
 * Add social logins html
 *
 * @return string
 */
function wp_ulike_pro_get_social_logins( $args = array() ){
    $social_logins = WP_Ulike_Pro_Options::getAvailabeSocialLogins();

    if( empty( $social_logins ) ){
        return;
    }

    $before = isset( $args['before'] ) && ! is_null( $args['before'] ) ? $args['before'] : do_shortcode( wp_ulike_get_option( 'social_login_before', '' ) );
    $after  = isset( $args['after'] ) &&! is_null( $args['after'] ) ? $args['after'] : do_shortcode( wp_ulike_get_option( 'social_login_after', '' ) );
    $view   = isset( $args['view'] ) &&! is_null( $args['view'] ) ? $args['view'] : wp_ulike_get_option( 'social_login_view', 'icon_text' );
    $skin   = isset( $args['skin'] ) &&! is_null( $args['skin'] ) ? $args['skin'] : wp_ulike_get_option( 'social_login_skin', 'gradient' );
    $color  = isset( $args['color'] ) &&! is_null( $args['color'] ) ? $args['color'] : wp_ulike_get_option( 'social_login_color', 'official' );
    $shape  = isset( $args['shape'] ) &&! is_null( $args['shape'] ) ? $args['shape'] : wp_ulike_get_option( 'social_login_shape', 'square' );

	$width  = wp_ulike_get_option( 'social_login_layout', array(
		'desktop' => '12',
		'tablet'  => '12',
		'mobile'  => '12'
	) );

    ob_start();
	foreach ( $social_logins as $key => $value ) {
		// Set label
		$name  = strtolower($value['network']);
		$label = ! empty( $value['login_label'] ) ? $value['login_label'] : ucfirst( $value['network'] );

		if( is_user_logged_in() && ! empty( $value['link_label'] ) ){
			$label = $value['link_label'];
		}

		$url   = WP_Ulike_Pro_Social_Login::getConnectUrl( $value['network'] );
	?>

	<div class="ulp-social-item ulp-flex-center-xs ulp-flex-col-xl-<?php echo esc_attr( $width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $width['mobile'] ); ?>">
		<a class="ulp-share-btn ulp-social-btn ulp-share-<?php echo esc_attr( $name ); ?>"
			href="<?php echo $url; ?>">
			<?php if( in_array( $view, array( 'icon_text', 'icon' ) ) ): ?>
			<span class="ulp-share-btn-icon">
				<i class="ulp-icon-<?php echo esc_attr( $name ); ?>"></i>
				<span class="ulp-screen-only"><?php echo esc_attr( $label ); ?></span>
			</span>
			<?php endif; ?>

			<?php if( in_array( $view, array( 'icon_text', 'text' ) ) ): ?>
			<span class="ulp-share-btn-text">
				<span class="ulp-share-btn-title"><?php echo esc_html( $label ); ?></span>
			</span>
			<?php endif; ?>
		</a>
	</div>
<?php
    }

	$networks = ob_get_clean();

	return $networks ? sprintf( '%s<div class="ulp-social ulp-social-login ulp-social-skin-%s ulp-social-buttons-color-%s ulp-social-shape-%s ulp-social-view-%s"><div class="ulp-social-login-wrapper ulp-flex-row">%s</div></div>%s', $before, $skin, $color, $shape, $view, $networks, $after ) : '';
}

/**
 * Add and store a notice.
 *
 * @param string $message     The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @param array  $data        Optional notice data.
 */
function wp_ulike_pro_add_notice( $message, $notice_type = 'error', $data = array() ) {
	global $ulp_session;

	$notices = $ulp_session->get( 'notices', array() );

	if ( ! empty( $message ) ) {
		$notices[ $notice_type ][] = array(
			'notice' => $message,
			'data'   => $data,
		);
	}

	$ulp_session->set( 'notices', $notices );
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 *
 * @param bool $return true to return rather than echo.
 * @return string|null
 */
function wp_ulike_pro_print_notices( $return = false ) {
	global $ulp_session;

	$all_notices  = $ulp_session->get( 'notices', array() );
	$notice_types = apply_filters( 'wp_ulike_pro_notice_types', array( 'error', 'success', 'notice' ) );

	// Buffer output.
	ob_start();

	foreach ( $notice_types as $notice_type ) {
		if ( wp_ulike_pro_notice_count( $notice_type ) > 0 ) {
			$messages = array();

			foreach ( $all_notices[ $notice_type ] as $notice ) {
				$messages[] = isset( $notice['notice'] ) ? $notice['notice'] : $notice;
			}

			$notices = $all_notices[ $notice_type ];
			wp_ulike_pro_get_template(
				"notices/{$notice_type}.php",
				array(
					'messages' => array_filter( $messages ), // @deprecated 3.9.0
					'notices'  => array_filter( $all_notices[ $notice_type ] ),
				)
			);
		}
	}

	wp_ulike_pro_clear_notices();

	$notices = ob_get_clean();

	if ( $return ) {
		return $notices;
	}

	echo $notices; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get the count of notices added, either for all notices (default) or for one.
 * particular notice type specified by $notice_type.
 *
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return int
 */
function wp_ulike_pro_notice_count( $notice_type = '' ) {
	global $ulp_session;

	$notice_count = 0;
	$all_notices  = $ulp_session->get( 'notices', array() );

	if ( isset( $all_notices[ $notice_type ] ) ) {

		$notice_count = count( $all_notices[ $notice_type ] );

	} elseif ( empty( $notice_type ) ) {

		foreach ( $all_notices as $notices ) {
			$notice_count += count( $notices );
		}
	}

	return $notice_count;
}

/**
 * Unset all notices.
 */
function wp_ulike_pro_clear_notices() {
	global $ulp_session;
	$ulp_session->set( 'notices', null );
}

/**
 * Filters out the same tags as wp_kses_post, but allows tabindex for <a> element.
 *
 * @param string $message Content to filter through kses.
 * @return string
 */
function wp_ulike_pro_kses_notice( $message ) {
	$allowed_tags = array_replace_recursive(
		wp_kses_allowed_html( 'post' ),
		array(
			'a' => array(
				'tabindex' => true,
			),
		)
	);

	return wp_kses( $message, $allowed_tags );
}

/**
 * Login a user (set auth cookie and set global user object).
 *
 * @param int $user_id user ID.
 */
function wp_ulike_pro_set_user_auth_cookie( $user_id ) {
	global $ulp_session;

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );

	// Update session.
	$ulp_session->init_session_cookie();
}

/**
 * Set custom cookie
 *
 * @param string $name
 * @param string $value
 * @param integer $expire
 * @param string $path
 * @return void
 */
function wp_ulike_pro_setcookie( $name, $value = '', $expire = 0, $path = '/' ){
	if ( empty( $value ) ) {
		$expire = time() - YEAR_IN_SECONDS;
	}
	if ( empty( $path ) ) {
		list( $path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}

	$levels = ob_get_level();
	for ( $i = 0; $i < $levels; $i++ ) {
		@ob_end_clean();
	}

	nocache_headers();
	setcookie( $name, $value, $expire, $path, COOKIE_DOMAIN, is_ssl(), true );
}

/**
 * Auto display soical share items
 *
 * @return void
 */
function wp_ulike_pro_social_share_auto_display(){
	// Get list
	$social_items = wp_ulike_get_option( 'social_share', array() );
	// Check for not empty
	if( ! empty( $social_items ) ){
		foreach ( $social_items as $key => $args ) {
			// Check for empty slug
			if( empty( $args['slug'] ) ){
				continue;
			}

			// Generate hooks
			switch ( $args['auto_display'] ) {
				case 'after_button':
					add_action( 'wp_ulike_after_template', function( $data ) use ( $args ){
						$filter_types = ! empty( $args['auto_display_filter_types'] ) ? $args['auto_display_filter_types'] : array();
						// Return if has been disabled
						if( in_array( $data['type'], $filter_types ) ){
							return;
						}

						echo do_shortcode( '[wp_ulike_pro_social_share slug='. $args['slug'] .']' );
					}, 10, 1 );
					break;

				case 'before_button':
					add_action( 'wp_ulike_before_template', function( $data ) use ( $args ){
						$filter_types = ! empty( $args['auto_display_filter_types'] ) ? $args['auto_display_filter_types'] : array();
						// Return if has been disabled
						if( in_array( $data['type'], $filter_types ) ){
							return;
						}

						echo do_shortcode( '[wp_ulike_pro_social_share slug='. $args['slug'] .']' );
					}, 10, 1 );
					break;

				case 'modal_display':
					add_filter( 'wp_ulike_pro_init_modal_after_success', function( $content, $data ) use ( $args ){
						$filter_types  = ! empty( $args['auto_display_filter_types'] ) ? $args['auto_display_filter_types'] : array();
						$filter_status = ! empty( $args['auto_display_filter_status'] ) ? $args['auto_display_filter_status'] : array();
						// Return if has been disabled
						if( ! in_array( $data['slug'], $filter_types ) && ! in_array( $data['status'], $filter_status ) ){
							$content = do_shortcode( '[wp_ulike_pro_social_share slug='. $args['slug'] .']' );
						}

						return $content;
					}, 10 , 2 );
					break;

				case 'custom_hook':
					if( ! empty( $args['auto_custom_hook'] ) ){
						add_action( $args['auto_custom_hook'], function() use ( $args ){
							echo do_shortcode( '[wp_ulike_pro_social_share slug='. $args['slug'] .']' );
						}, 10 );
					}
					break;
			}
		}
	}
}

/**
 * Auto display social logins
 *
 * @return void
 */
function wp_ulike_pro_social_login_auto_display(){

	if( ! WP_Ulike_Pro_Options::getAvailabeSocialLogins() ){
		return;
	}

	// set cookies
	if ( is_page() && ! WP_Ulike_Pro_Options::isCorePage() ) {
		do_action( 'wp_ulike_pro_set_cookies', true );
	}

	$slad = wp_ulike_get_option( 'social_login_auto_display', 'after_login_form' );

	// Generate hooks
	switch ( $slad ) {
		case 'after_login_form':
			add_action( 'wp_ulike_pro_forms_after_hook', function( $type, $args ){
				if( $type == 'login' ){
					echo sprintf( '<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">%s</div>', do_shortcode('[wp_ulike_pro_social_login]' ) );
				}
			}, 15, 2 );
			break;

		case 'before_login_form':
			add_action( 'wp_ulike_pro_forms_before_hook', function( $type, $args ){
				if( $type == 'login' ){
					echo sprintf( '<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">%s</div>', do_shortcode('[wp_ulike_pro_social_login]' ) );
				}
			}, 15, 2 );
			break;

		case 'custom_hook':
			$custom_hook = wp_ulike_get_option( 'social_login_auto_custom_hook', '' );
			if( ! empty( $custom_hook ) ){
				add_action( $custom_hook, function(){
					echo do_shortcode('[wp_ulike_pro_social_login]' );
				}, 15 );
			}
			break;
	}
}

/**
 * remove all HTML tags and their contents
 *
 * @param string $message
 * @return string
 */
function wp_ulike_pro_clean_tags($message) {
    return ! empty( $message ) ? preg_replace('/<[^>]*>(.*?)<\/[^>]*>/', '', $message) : '';
}