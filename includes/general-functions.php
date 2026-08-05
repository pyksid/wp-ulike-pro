<?php
/**
 * General functions
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
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

		$user_status = (string) ( $info['user_status'] ?? '' );
		// Cast before the strict comparison: the template passes this through
		// esc_attr(), so it arrives as the STRING "4"/"2", and
		// in_array( "4", array( 2, 4 ), true ) is false. That silently dropped
		// the active class on page load, so a liked button rendered unstyled
		// after a refresh even though the vote and the counter were correct
		// (the AJAX path sets the class from the JSON int, which is why the
		// click itself always looked right).
		$status_id = (int) ( $info['status'] ?? 0 );
		if ( in_array( $status_id, array( 2, 4 ), true ) && strpos( $user_status, 'dis' ) === 0 ) {
			$final_classes['down'] .= ' image-unlike wp_ulike_btn_is_active';
		} elseif ( in_array( $status_id, array( 2, 4 ), true ) && strpos( $user_status, 'dis' ) !== 0 ) {
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
			$user_status = (string) ( $info['user_status'] ?? '' );
			if ( strpos( $user_status, 'dis' ) === 0 ) {
				$final_classes['down'] .= ' wp_ulike_is_liked';
				$final_classes['sub']  .= ' wp_ulike_is_liked';
			} else {
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
            aria-label="<?php echo wp_ulike_setting_repo::getLikeAriaLabel();  ?>"
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
            aria-label="<?php echo WP_Ulike_Pro_Options::getDislikeAriaLabel(); ?>"
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
            aria-label="<?php echo wp_ulike_setting_repo::getLikeAriaLabel();  ?>"
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
            aria-label="<?php echo wp_ulike_setting_repo::getLikeAriaLabel();  ?>"
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
 * Emoji reactions template.
 *
 * @param array $wp_ulike_template Template variables.
 * @return string
 */
function wp_ulike_pro_emoji_reactions_template( array $wp_ulike_template ) {
	ob_start();
	do_action( 'wp_ulike_before_template', $wp_ulike_template );
	extract( $wp_ulike_template ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	?>
	<div class="wpulike wpulike-engagement-template <?php echo esc_attr( $wrapper_class ); ?>" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="wp_ulike_general_class">
			<?php echo WP_Ulike_Pro_Engagement_Display::render( (int) $ID, sanitize_key( $type ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php
	do_action( 'wp_ulike_after_template', $wp_ulike_template );
	return ob_get_clean();
}

/**
 * Star rating template.
 *
 * @param array $wp_ulike_template Template variables.
 * @return string
 */
function wp_ulike_pro_star_rating_template( array $wp_ulike_template ) {
	ob_start();
	do_action( 'wp_ulike_before_template', $wp_ulike_template );
	extract( $wp_ulike_template ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	?>
	<div class="wpulike wpulike-engagement-template <?php echo esc_attr( $wrapper_class ); ?>" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="wp_ulike_general_class">
			<?php echo WP_Ulike_Pro_Engagement_Display::render( (int) $ID, sanitize_key( $type ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php
	do_action( 'wp_ulike_after_template', $wp_ulike_template );
	return ob_get_clean();
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
            aria-label="<?php echo wp_ulike_setting_repo::getLikeAriaLabel(); ?>"
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
            aria-label="<?php echo WP_Ulike_Pro_Options::getDislikeAriaLabel(); ?>"
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
	$post_ID      = wp_ulike_get_the_id( empty( $post_ID ) ? get_the_ID() : $post_ID );
	$meta_value   = NULL;
	$is_serialize = wp_ulike_get_option( 'enable_serialize', false );

	if( wp_ulike_is_true( $is_serialize ) ){
		$meta_box     = get_post_meta( $post_ID, 'wp_ulike_pro_meta_box' , true );
		$meta_value   = isset( $meta_box[$meta_name] ) ? maybe_unserialize( $meta_box[$meta_name] ) : NULL;
	}

	if ( null === $meta_value || '' === $meta_value || false === $meta_value ) {
		$prefix     = 'wp_ulike_pro_';
		$meta_value = get_post_meta( $post_ID, $prefix . $meta_name , true );
	}

	// Legacy installs may still store everything inside the serialized blob.
	if ( ( null === $meta_value || '' === $meta_value || false === $meta_value ) && ! wp_ulike_is_true( $is_serialize ) ) {
		$meta_box = get_post_meta( $post_ID, 'wp_ulike_pro_meta_box', true );
		if ( is_array( $meta_box ) && array_key_exists( $meta_name, $meta_box ) ) {
			$meta_value = maybe_unserialize( $meta_box[ $meta_name ] );
		}
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
/**
 * Per-request comment meta cache store.
 *
 * @return array<int, array<string, mixed>>
 */
function &wp_ulike_pro_comment_metabox_cache_store() {
	static $cache = array();
	return $cache;
}

function wp_ulike_pro_get_comment_metabox_blob( $comment_ID ) {
	$cache = &wp_ulike_pro_comment_metabox_cache_store();

	$comment_ID = $comment_ID ? absint( $comment_ID ) : absint( get_comment_ID() );
	if ( ! $comment_ID ) {
		return array();
	}

	if ( ! array_key_exists( $comment_ID, $cache ) ) {
		$meta_box = get_comment_meta( $comment_ID, 'wp_ulike_pro_comment_meta_box', true );
		$cache[ $comment_ID ] = is_array( $meta_box ) ? $meta_box : array();
	}

	return $cache[ $comment_ID ];
}

/**
 * Clear per-request comment meta cache after a save.
 *
 * @param int $comment_ID Comment ID.
 * @return void
 */
function wp_ulike_pro_reset_comment_metabox_cache( $comment_ID ) {
	$cache      = &wp_ulike_pro_comment_metabox_cache_store();
	$comment_ID = absint( $comment_ID );
	if ( $comment_ID ) {
		unset( $cache[ $comment_ID ] );
	}
}

function wp_ulike_pro_get_comment_metabox_value( $meta_name, $comment_ID = '', $raw = false ){
	$comment_ID = $comment_ID ? absint( $comment_ID ) : absint( get_comment_ID() );
	if ( ! $comment_ID ) {
		return '';
	}

	$meta_box = wp_ulike_pro_get_comment_metabox_blob( $comment_ID );
	if ( ! array_key_exists( $meta_name, $meta_box ) ) {
		return '';
	}

	$value = maybe_unserialize( $meta_box[ $meta_name ] );

	if ( $raw || is_array( $value ) ) {
		return $value;
	}

	return esc_html( $value );
}

/**
 * Merge per-comment display overrides into wp_ulike_comments() args.
 *
 * @param int                  $comment_id Comment ID.
 * @param array<string, mixed> $args       Button args.
 * @return array<string, mixed>
 */
function wp_ulike_pro_merge_comment_button_args( $comment_id, $args = array() ) {
	$comment_id = absint( $comment_id );
	if ( ! $comment_id ) {
		return $args;
	}

	$template = wp_ulike_pro_get_comment_metabox_value( 'template', $comment_id, true );
	if ( '' !== $template && null !== $template ) {
		$args['style'] = $template;
	}

	if ( empty( $args['id'] ) ) {
		$args['id'] = $comment_id;
	}

	return $args;
}

/**
 * Wrap comment content with a like button using the chosen position.
 *
 * @param string $button   Button HTML.
 * @param string $content  Comment text.
 * @param string $position top|bottom|top_bottom.
 * @return string
 */
function wp_ulike_pro_wrap_comment_with_button( $button, $content, $position ) {
	switch ( $position ) {
		case 'top':
			return $button . $content;
		case 'top_bottom':
			return $button . $content . $button;
		default:
			return $content . $button;
	}
}

/**
 * Get raw post meta box value (admin-safe, unescaped).
 *
 * @param string  $meta_name Meta key without prefix.
 * @param integer $post_ID   Post ID.
 * @return mixed
 */
function wp_ulike_pro_get_metabox_value_raw( $meta_name, $post_ID = '' ) {
	$post_ID = absint( empty( $post_ID ) ? get_the_ID() : $post_ID );
	if ( ! $post_ID ) {
		return '';
	}

	$meta_value   = null;
	$is_serialize = wp_ulike_get_option( 'enable_serialize', false );

	if ( wp_ulike_is_true( $is_serialize ) ) {
		$meta_box = get_post_meta( $post_ID, 'wp_ulike_pro_meta_box', true );
		if ( is_array( $meta_box ) && array_key_exists( $meta_name, $meta_box ) ) {
			$meta_value = maybe_unserialize( $meta_box[ $meta_name ] );
		}
	}

	if ( null === $meta_value || '' === $meta_value || false === $meta_value ) {
		$meta_value = get_post_meta( $post_ID, 'wp_ulike_pro_' . $meta_name, true );
	}

	// Legacy installs may still store everything inside the serialized blob.
	if ( ( null === $meta_value || '' === $meta_value || false === $meta_value ) && ! wp_ulike_is_true( $is_serialize ) ) {
		$meta_box = get_post_meta( $post_ID, 'wp_ulike_pro_meta_box', true );
		if ( is_array( $meta_box ) && array_key_exists( $meta_name, $meta_box ) ) {
			$meta_value = maybe_unserialize( $meta_box[ $meta_name ] );
		}
	}

	return $meta_value;
}

/**
 * Whether a post metabox flag is enabled (handles stored 'true' / 'false' strings).
 *
 * @param string  $meta_name Meta key without prefix.
 * @param integer $post_ID   Post ID.
 * @return bool
 */
function wp_ulike_pro_is_metabox_true( $meta_name, $post_ID = '' ) {
	return wp_ulike_is_true( wp_ulike_pro_get_metabox_value_raw( $meta_name, $post_ID ) );
}

/**
 * Per-post display meta keys (post editor meta box).
 *
 * @return string[]
 */
function wp_ulike_pro_get_display_meta_keys() {
	return array(
		'auto_display',
		'template',
		'display_position',
		'likes_counter_quantity',
		'dislikes_counter_quantity',
	);
}

/**
 * Schema-related meta keys (Tools → Schema Generator).
 *
 * @return string[]
 */
function wp_ulike_pro_get_schema_meta_keys() {
	return array(
		'enable_schema',
		'schema_type',
		'title',
		'description',
		'name',
		'author',
		'day_of_week',
		'opens',
		'closes',
		'location',
		'street_address',
		'address_locality',
		'address_region',
		'postal_code',
		'address_country',
		'telephone',
		'price_range',
		'start_date',
		'end_date',
		'created_date',
		'price',
		'price_currency',
		'availability',
		'valid_date',
		'url',
		'sku',
		'mpn',
		'application_category',
		'operating_system',
		'software_version',
		'is_accessible_for_free',
		'issn',
		'duration',
		'encoding_format',
		'num_tracks',
		'image',
		'tracks',
		'supply',
		'tool',
		'step',
		'image_list',
		'disable_star_ratings',
		'enable_time_factor_rating',
		'enable_custom_rating',
		'rating_value',
		'rating_count',
		'review_count',
		'worst_rating',
		'best_rating',
		'enable_custom_reviews',
		'reviews',
		'enable_faq',
		'faq',
	);
}

/**
 * Meta keys used to detect stored schema / FAQ configuration.
 *
 * @return string[]
 */
function wp_ulike_pro_get_schema_data_signal_keys() {
	return array( 'enable_schema', 'enable_faq', 'schema_type', 'title', 'description', 'faq', 'reviews' );
}

/**
 * Build schema status array from a flat meta map (admin search batch helper).
 *
 * @param array<string, mixed> $meta Meta values keyed without prefix.
 * @return array<string, mixed>
 */
function wp_ulike_pro_build_schema_status_from_meta( $meta ) {
	$meta = is_array( $meta ) ? $meta : array();

	$schema_enabled = wp_ulike_is_true( $meta['enable_schema'] ?? '' );
	$faq_enabled    = wp_ulike_is_true( $meta['enable_faq'] ?? '' );
	$schema_type    = $meta['schema_type'] ?? '';
	$has_data       = false;

	foreach ( wp_ulike_pro_get_schema_data_signal_keys() as $key ) {
		if ( ! array_key_exists( $key, $meta ) ) {
			continue;
		}

		$value = $meta[ $key ];
		if ( in_array( $key, array( 'enable_schema', 'enable_faq' ), true ) ) {
			if ( wp_ulike_is_true( $value ) ) {
				$has_data = true;
				break;
			}
			continue;
		}

		if ( ! empty( $value ) ) {
			$has_data = true;
			break;
		}
	}

	return array(
		'schema_enabled' => $schema_enabled,
		'faq_enabled'    => $faq_enabled,
		'schema_type'    => is_string( $schema_type ) ? $schema_type : '',
		'has_data'       => $has_data,
	);
}

/**
 * Batch-load schema status for multiple posts (single query, avoids N+1 in search).
 *
 * @param int[] $post_ids Post IDs.
 * @return array<int, array<string, mixed>>
 */
function wp_ulike_pro_batch_get_post_schema_status( array $post_ids ) {
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	if ( empty( $post_ids ) ) {
		return array();
	}

	global $wpdb;

	$signals      = wp_ulike_pro_get_schema_data_signal_keys();
	$meta_by_post = array_fill_keys( $post_ids, array() );
	$meta_keys    = array( 'wp_ulike_pro_meta_box' );

	foreach ( $signals as $key ) {
		$meta_keys[] = 'wp_ulike_pro_' . $key;
	}

	$id_placeholders  = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
	$key_placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
	$prepared_args    = array_merge( $post_ids, $meta_keys );

	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($id_placeholders) AND meta_key IN ($key_placeholders)",
			$prepared_args
		),
		ARRAY_A
	);

	$blob_rows        = array();
	$individual_rows  = array();

	foreach ( (array) $rows as $row ) {
		$meta_key = (string) $row['meta_key'];
		if ( 'wp_ulike_pro_meta_box' === $meta_key ) {
			$blob_rows[] = $row;
			continue;
		}
		$individual_rows[] = $row;
	}

	$use_serialize = wp_ulike_is_true( wp_ulike_get_option( 'enable_serialize', false ) );
	$ordered_rows  = $use_serialize ? array_merge( $blob_rows, $individual_rows ) : array_merge( $individual_rows, $blob_rows );

	foreach ( $ordered_rows as $row ) {
		$post_id  = (int) $row['post_id'];
		$meta_key = (string) $row['meta_key'];

		if ( 'wp_ulike_pro_meta_box' === $meta_key ) {
			$blob = maybe_unserialize( $row['meta_value'] );
			if ( ! is_array( $blob ) ) {
				continue;
			}

			foreach ( $signals as $signal ) {
				if ( ! array_key_exists( $signal, $blob ) ) {
					continue;
				}

				$value = maybe_unserialize( $blob[ $signal ] );
				if ( ! array_key_exists( $signal, $meta_by_post[ $post_id ] ) || wp_ulike_pro_schema_meta_value_is_empty( $meta_by_post[ $post_id ][ $signal ] ) ) {
					$meta_by_post[ $post_id ][ $signal ] = $value;
				}
			}
			continue;
		}

		if ( 0 !== strpos( $meta_key, 'wp_ulike_pro_' ) ) {
			continue;
		}

		$signal = substr( $meta_key, strlen( 'wp_ulike_pro_' ) );
		if ( ! in_array( $signal, $signals, true ) ) {
			continue;
		}

		$value = maybe_unserialize( $row['meta_value'] );
		$meta_by_post[ $post_id ][ $signal ] = $value;
	}

	$statuses = array();
	foreach ( $post_ids as $post_id ) {
		$statuses[ $post_id ] = wp_ulike_pro_build_schema_status_from_meta( $meta_by_post[ $post_id ] );
	}

	return $statuses;
}

/**
 * Whether a schema meta value should be treated as empty.
 *
 * @param mixed $value Meta value.
 * @return bool
 */
function wp_ulike_pro_schema_meta_value_is_empty( $value ) {
	return null === $value || false === $value || '' === $value || array() === $value;
}

/**
 * Whether a post has schema or FAQ configuration stored.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function wp_ulike_pro_post_has_schema_data( $post_id ) {
	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return false;
	}

	$signals = wp_ulike_pro_get_schema_data_signal_keys();

	if ( wp_ulike_is_true( wp_ulike_get_option( 'enable_serialize', false ) ) ) {
		$blob = get_post_meta( $post_id, 'wp_ulike_pro_meta_box', true );
		if ( ! is_array( $blob ) ) {
			return false;
		}

		foreach ( $signals as $key ) {
			if ( ! array_key_exists( $key, $blob ) ) {
				continue;
			}
			$value = maybe_unserialize( $blob[ $key ] );
			if ( in_array( $key, array( 'enable_schema', 'enable_faq' ), true ) ) {
				if ( wp_ulike_is_true( $value ) ) {
					return true;
				}
				continue;
			}
			if ( ! empty( $value ) ) {
				return true;
			}
		}

		return false;
	}

	foreach ( $signals as $key ) {
		$value = wp_ulike_pro_get_metabox_value_raw( $key, $post_id );
		if ( in_array( $key, array( 'enable_schema', 'enable_faq' ), true ) ) {
			if ( wp_ulike_is_true( $value ) ) {
				return true;
			}
			continue;
		}
		if ( ! empty( $value ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Schema status summary for a post (admin UI).
 *
 * @param int $post_id Post ID.
 * @return array<string, mixed>
 */
function wp_ulike_pro_get_post_schema_status( $post_id ) {
	$post_id = absint( $post_id );

	$schema_enabled = wp_ulike_is_true( wp_ulike_pro_get_metabox_value_raw( 'enable_schema', $post_id ) );
	$faq_enabled    = wp_ulike_is_true( wp_ulike_pro_get_metabox_value_raw( 'enable_faq', $post_id ) );
	$schema_type    = wp_ulike_pro_get_metabox_value_raw( 'schema_type', $post_id );

	return array(
		'schema_enabled' => $schema_enabled,
		'faq_enabled'    => $faq_enabled,
		'schema_type'    => is_string( $schema_type ) ? $schema_type : '',
		'has_data'       => wp_ulike_pro_post_has_schema_data( $post_id ),
	);
}

/**
 * Preview aggregate rating output for schema markup (admin + frontend).
 *
 * @param int                  $post_id  Post ID.
 * @param array<string, mixed> $settings Optional rating-related overrides.
 * @return array<string, mixed>
 */
function wp_ulike_pro_get_schema_rating_preview( $post_id, $settings = array() ) {
	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return array(
			'mode'  => 'unavailable',
			'value' => null,
			'count' => 0,
		);
	}

	$resolve = static function ( $key, $default = '' ) use ( $post_id, $settings ) {
		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}
		return wp_ulike_pro_get_metabox_value_raw( $key, $post_id );
	};

	if ( wp_ulike_is_true( $resolve( 'disable_star_ratings', 'false' ) ) ) {
		return array(
			'mode'  => 'disabled',
			'value' => null,
			'count' => 0,
		);
	}

	$worst = (float) $resolve( 'worst_rating', 1 );
	$best  = (float) $resolve( 'best_rating', 5 );

	if ( wp_ulike_is_true( $resolve( 'enable_custom_rating', 'false' ) ) ) {
		$value = trim( (string) $resolve( 'rating_value', '' ) );
		$count = absint( $resolve( 'rating_count', 0 ) );

		return array(
			'mode'  => 'custom',
			'value' => '' === $value ? null : (float) $value,
			'count' => $count,
			'worst' => $worst,
			'best'  => $best,
		);
	}

	// Star-rating engagement template uses ulike_pulse aggregates.
	if (
		class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) &&
		class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) &&
		'star' === WP_Ulike_Pro_Engagement_Settings::get_mode( 'post' )
	) {
		$aggregates = WP_Ulike_Pro_Engagement_Counter::get_star_aggregates( $post_id, 'post' );
		$count      = isset( $aggregates['count'] ) ? (int) $aggregates['count'] : 0;

		if ( $count > 0 ) {
			return array(
				'mode'  => 'engagement_star',
				'value' => round( (float) $aggregates['average'], 1 ),
				'count' => $count,
				'worst' => $worst,
				'best'  => $best,
			);
		}
	}

	$likes     = (int) wp_ulike_get_post_likes( $post_id, 'like' );
	$dislikes  = (int) wp_ulike_get_post_likes( $post_id, 'dislike' );
	$total     = $likes + $dislikes;
	$time_factor = wp_ulike_is_true( $resolve( 'enable_time_factor_rating', 'false' ) );

	if ( ! $total ) {
		return array(
			'mode'        => 'auto',
			'value'       => null,
			'count'       => 0,
			'likes'       => $likes,
			'dislikes'    => $dislikes,
			'worst'       => $worst,
			'best'        => $best,
			'time_factor' => $time_factor,
		);
	}

	// wp_ulike_get_rating_value() was deprecated in WP ULike 5.2.0 and now always
	// returns null (no time-decayed rating calculation is available upstream),
	// so fall back to the standard like/dislike-weighted average instead of a
	// silently wrong 0 rating.
	$value = ( ( $likes * 5 ) + $dislikes ) / $total;
	$value = $value < 1 ? 1 : round( $value, 2 );

	return array(
		'mode'        => 'auto',
		'value'       => $value,
		'count'       => $total,
		'likes'       => $likes,
		'dislikes'    => $dislikes,
		'worst'       => $worst,
		'best'        => $best,
		'time_factor' => $time_factor,
	);
}

/**
 * Schema meta keys that store calendar dates.
 *
 * @return string[]
 */
function wp_ulike_pro_get_schema_date_meta_keys() {
	return array(
		'start_date',
		'end_date',
		'created_date',
		'valid_date',
	);
}

/**
 * Parse a stored schema date to Y-m-d for admin inputs and JSON-LD.
 *
 * Supports plugin date formats: d/m/Y, m/d/Y, and Y-m-d.
 *
 * @param mixed $value Stored date value.
 * @return string ISO date (Y-m-d) or empty string.
 */
function wp_ulike_pro_parse_schema_date( $value ) {
	if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
		return '';
	}

	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches ) ) {
		return checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ? $value : '';
	}

	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})[ T]/', $value, $matches ) ) {
		return sprintf( '%04d-%02d-%02d', (int) $matches[1], (int) $matches[2], (int) $matches[3] );
	}

	if ( ! preg_match( '/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $value, $matches ) ) {
		return '';
	}

	$first  = (int) $matches[1];
	$second = (int) $matches[2];
	$year   = (int) $matches[3];
	$pairs  = array();

	if ( $first > 12 && $second <= 12 ) {
		$pairs[] = array( 'month' => $second, 'day' => $first );
	} elseif ( $second > 12 && $first <= 12 ) {
		$pairs[] = array( 'month' => $first, 'day' => $second );
	} else {
		$wp_format = (string) get_option( 'date_format', '' );
		$day_first = true;

		if ( preg_match( '/(^|[^\\\\])d/', $wp_format ) && preg_match( '/(^|[^\\\\])m/', $wp_format ) ) {
			$day_first = strpos( $wp_format, 'd' ) < strpos( $wp_format, 'm' );
		} elseif ( preg_match( '/(^|[^\\\\])j/', $wp_format ) && preg_match( '/(^|[^\\\\])n/', $wp_format ) ) {
			$day_first = strpos( $wp_format, 'j' ) < strpos( $wp_format, 'n' );
		}

		if ( $day_first ) {
			$pairs[] = array( 'month' => $second, 'day' => $first );
			$pairs[] = array( 'month' => $first, 'day' => $second );
		} else {
			$pairs[] = array( 'month' => $first, 'day' => $second );
			$pairs[] = array( 'month' => $second, 'day' => $first );
		}
	}

	foreach ( $pairs as $pair ) {
		if ( checkdate( $pair['month'], $pair['day'], $year ) ) {
			return sprintf( '%04d-%02d-%02d', $year, $pair['month'], $pair['day'] );
		}
	}

	return '';
}

/**
 * Format a parsed schema date for database storage (d/m/Y).
 *
 * @param mixed $value Date value from admin input or stored meta.
 * @return string
 */
function wp_ulike_pro_format_schema_date_storage( $value ) {
	$parsed = wp_ulike_pro_parse_schema_date( $value );
	if ( '' === $parsed ) {
		return '';
	}

	$parts = explode( '-', $parsed );
	if ( 3 !== count( $parts ) ) {
		return '';
	}

	return sprintf( '%02d/%02d/%04d', (int) $parts[2], (int) $parts[1], (int) $parts[0] );
}

/**
 * Prepare a stored schema field for the admin UI.
 *
 * @param string $key   Meta key without prefix.
 * @param mixed  $value Stored value.
 * @return mixed
 */
function wp_ulike_pro_prepare_schema_admin_value( $key, $value ) {
	if ( in_array( $key, wp_ulike_pro_get_schema_date_meta_keys(), true ) && is_string( $value ) ) {
		return wp_ulike_pro_parse_schema_date( $value );
	}

	if ( 'reviews' === $key && is_array( $value ) ) {
		foreach ( $value as $index => $row ) {
			if ( is_array( $row ) && ! empty( $row['published_date'] ) ) {
				$value[ $index ]['published_date'] = wp_ulike_pro_parse_schema_date( $row['published_date'] );
			}
		}
	}

	return $value;
}

/**
 * Sanitize a single meta box value by key.
 *
 * @param string $key   Meta key.
 * @param mixed  $value Raw value.
 * @return mixed
 */
function wp_ulike_pro_sanitize_metabox_value( $key, $value ) {
	$checkbox_keys = array(
		'auto_display',
		'enable_schema',
		'disable_star_ratings',
		'enable_time_factor_rating',
		'enable_custom_rating',
		'enable_custom_reviews',
		'enable_faq',
		'is_accessible_for_free',
	);

	if ( in_array( $key, $checkbox_keys, true ) ) {
		return wp_ulike_is_true( $value ) ? 'true' : 'false';
	}

	if ( 'display_position' === $key ) {
		$value   = sanitize_key( wp_unslash( $value ) );
		$allowed = array( 'top', 'bottom', 'top_bottom' );
		return in_array( $value, $allowed, true ) ? $value : 'bottom';
	}

	if ( 'template' === $key ) {
		$value = sanitize_key( wp_unslash( $value ) );
		if ( '' === $value ) {
			return '';
		}
		$allowed = array_keys( wp_ulike_pro_get_templates_list_by_name() );
		return in_array( $value, $allowed, true ) ? $value : '';
	}

	if ( 'description' === $key ) {
		return sanitize_textarea_field( wp_unslash( $value ) );
	}

	if ( in_array( $key, wp_ulike_pro_get_schema_date_meta_keys(), true ) ) {
		return wp_ulike_pro_format_schema_date_storage( wp_unslash( $value ) );
	}

	if ( 'faq' === $key && is_array( $value ) ) {
		$clean = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$question = isset( $row['question'] ) ? sanitize_text_field( wp_unslash( $row['question'] ) ) : '';
			$answer   = isset( $row['answer'] ) ? wp_kses_post( wp_unslash( $row['answer'] ) ) : '';
			if ( '' === $question && '' === $answer ) {
				continue;
			}
			$clean[] = array(
				'question' => $question,
				'answer'   => $answer,
			);
		}
		return $clean;
	}

	if ( 'reviews' === $key && is_array( $value ) ) {
		$clean = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean[] = array(
				'author'         => isset( $row['author'] ) ? sanitize_text_field( wp_unslash( $row['author'] ) ) : '',
				'published_date' => isset( $row['published_date'] ) ? wp_ulike_pro_format_schema_date_storage( wp_unslash( $row['published_date'] ) ) : '',
				'name'           => isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '',
				'review_body'    => isset( $row['review_body'] ) ? sanitize_textarea_field( wp_unslash( $row['review_body'] ) ) : '',
				'rating_value'   => isset( $row['rating_value'] ) ? absint( $row['rating_value'] ) : 0,
			);
		}
		return $clean;
	}

	if ( in_array( $key, array( 'tracks', 'supply', 'tool', 'step' ), true ) && is_array( $value ) ) {
		return array_map(
			function ( $row ) {
				if ( ! is_array( $row ) ) {
					return array();
				}
				$clean = array();
				foreach ( $row as $sub_key => $sub_value ) {
					if ( 'list' === $sub_key && is_array( $sub_value ) ) {
						$clean['list'] = array_map(
							function ( $item ) {
								return array(
									'name' => isset( $item['name'] ) ? sanitize_text_field( wp_unslash( $item['name'] ) ) : '',
								);
							},
							$sub_value
						);
						continue;
					}
					if ( 'image' === $sub_key ) {
						$clean[ $sub_key ] = esc_url_raw( wp_unslash( $sub_value ) );
						continue;
					}
					$clean[ $sub_key ] = sanitize_text_field( wp_unslash( $sub_value ) );
				}
				return $clean;
			},
			$value
		);
	}

	if ( 'image_list' === $key ) {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'esc_url_raw', $value ) ) );
		}
		if ( is_string( $value ) && '' !== $value ) {
			$parts = preg_split( '/[\r\n,]+/', wp_unslash( $value ) );
			return array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', $parts ) ) ) );
		}
		return array();
	}

	if ( in_array( $key, array( 'likes_counter_quantity', 'dislikes_counter_quantity', 'rating_count', 'review_count', 'worst_rating', 'best_rating', 'num_tracks' ), true ) ) {
		return absint( $value );
	}

	if ( in_array( $key, array( 'price', 'rating_value' ), true ) ) {
		return is_numeric( $value ) ? $value : sanitize_text_field( wp_unslash( $value ) );
	}

	if ( 'day_of_week' === $key ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', wp_unslash( $value ) );
	}

	if ( 'schema_type' === $key ) {
		$value = sanitize_text_field( wp_unslash( $value ) );
		if ( '' === $value ) {
			return '';
		}
		if ( class_exists( 'WP_Ulike_Pro_Schema_Generator_Tool' ) ) {
			$allowed = array_keys( WP_Ulike_Pro_Schema_Generator_Tool::get_schema_types() );
			return in_array( $value, $allowed, true ) ? $value : '';
		}
		return $value;
	}

	if ( in_array( $key, array( 'image', 'url' ), true ) ) {
		return esc_url_raw( wp_unslash( $value ) );
	}

	if ( is_array( $value ) ) {
		return array_map( 'sanitize_text_field', wp_unslash( $value ) );
	}

	return sanitize_text_field( wp_unslash( $value ) );
}

/**
 * Save post meta box values (serialized or individual keys).
 *
 * @param int   $post_id Post ID.
 * @param array $values  Key/value pairs without prefix.
 * @param bool  $merge   Merge with existing stored values.
 * @return bool
 */
function wp_ulike_pro_save_metabox_values( $post_id, $values, $merge = true ) {
	$post_id = absint( $post_id );
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		return false;
	}

	$sanitized = array();
	foreach ( (array) $values as $key => $value ) {
		$key = sanitize_key( $key );
		if ( '' === $key ) {
			continue;
		}
		$sanitized[ $key ] = wp_ulike_pro_sanitize_metabox_value( $key, $value );
	}

	if ( empty( $sanitized ) ) {
		return false;
	}

	if ( wp_ulike_is_true( wp_ulike_get_option( 'enable_serialize', false ) ) ) {
		$stored = $merge ? get_post_meta( $post_id, 'wp_ulike_pro_meta_box', true ) : array();
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		update_post_meta( $post_id, 'wp_ulike_pro_meta_box', array_merge( $stored, $sanitized ) );
		return true;
	}

	foreach ( $sanitized as $key => $value ) {
		update_post_meta( $post_id, 'wp_ulike_pro_' . $key, $value );
	}

	return true;
}

/**
 * Save comment meta box values.
 *
 * @param int   $comment_id Comment ID.
 * @param array $values     Key/value pairs.
 * @return bool
 */
function wp_ulike_pro_save_comment_metabox_values( $comment_id, $values ) {
	$comment_id = absint( $comment_id );
	if ( ! $comment_id || ! current_user_can( 'edit_comment', $comment_id ) ) {
		return false;
	}

	$sanitized = array();
	foreach ( (array) $values as $key => $value ) {
		$key = sanitize_key( $key );
		if ( '' === $key ) {
			continue;
		}
		$sanitized[ $key ] = wp_ulike_pro_sanitize_metabox_value( $key, $value );
	}

	if ( empty( $sanitized ) ) {
		return false;
	}

	$stored = get_comment_meta( $comment_id, 'wp_ulike_pro_comment_meta_box', true );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	update_comment_meta( $comment_id, 'wp_ulike_pro_comment_meta_box', array_merge( $stored, $sanitized ) );
	wp_ulike_pro_reset_comment_metabox_cache( $comment_id );
	return true;
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
			$counter_val = wp_ulike_pro_get_comment_metabox_value( $counter_key, $id, true );
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
 * Comma-separated template keys that do not have a given attribute flag.
 *
 * @param string $attr Template metadata key.
 * @return string|null
 */
function wp_ulike_pro_get_templates_list_excluding_attribute( $attr ) {
	$options   = array();
	$templates = wp_ulike_generate_templates_list();

	foreach ( $templates as $key => $args ) {
		if ( empty( $args[ $attr ] ) ) {
			$options[] = $key;
		}
	}

	return ! empty( $options ) ? implode( ',', $options ) : null;
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

	$current_page_id = wp_ulike_get_the_id( $current_page_id );

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
	// Extract values safely instead of using extract()
	$total_pages = isset( $parsed_args['total_pages'] ) ? $parsed_args['total_pages'] : '';
	$per_page = isset( $parsed_args['per_page'] ) ? absint( $parsed_args['per_page'] ) : 10;
	$custom_query = isset( $parsed_args['custom_query'] ) ? $parsed_args['custom_query'] : NULL;
	$prev_text = isset( $parsed_args['prev_text'] ) ? sanitize_text_field( $parsed_args['prev_text'] ) : 'Prev';
	$next_text = isset( $parsed_args['next_text'] ) ? sanitize_text_field( $parsed_args['next_text'] ) : 'Next';

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
function wp_ulike_pro_get_items_info( $args = array() ) {
	$defaults = array(
		'type'       => 'post',
		'rel_type'   => 'post',
		'status'     => 'like',
		'user_id'    => '',
		'order'      => 'DESC',
		'period'     => 'all',
		'offset'     => 1,
		'limit'      => 10,
		'is_popular' => false,
	);

	$parsed_args               = wp_parse_args( $args, $defaults );
	$parsed_args['is_popular'] = false;

	$vote_statuses = array( 'like', 'dislike', 'unlike', 'undislike' );
	$status        = $parsed_args['status'];
	$mode          = function_exists( 'wp_ulike_pro_get_engagement_mode_for_type' )
		? wp_ulike_pro_get_engagement_mode_for_type( $parsed_args['type'] )
		: 'none';

	// User history on emoji/star types must read engagement rows (vote-only misses reactions).
	if ( ! empty( $parsed_args['user_id'] ) && in_array( $mode, array( 'emoji', 'star' ), true ) ) {
		if ( is_string( $status ) && in_array( $status, array( 'dislike', 'unlike', 'undislike' ), true ) ) {
			// Explicit classic vote filter.
			$ids = wp_ulike_get_popular_items_ids( $parsed_args );
		} elseif ( is_string( $status ) && ! in_array( $status, $vote_statuses, true ) && 'all' !== $status && '' !== $status ) {
			if ( 'star' === $mode && is_numeric( $status ) ) {
				$parsed_args['values'] = array( absint( $status ) );
				$ids                   = wp_ulike_pro_get_popular_engagement_item_ids( $parsed_args, 'star' );
			} else {
				$parsed_args['engagement_keys'] = array( sanitize_key( $status ) );
				$ids                            = wp_ulike_pro_get_popular_engagement_item_ids( $parsed_args, 'emoji' );
			}
		} else {
			// like / all / empty → that type's engagement history (keeps offset/limit correct).
			$ids = wp_ulike_pro_get_popular_engagement_item_ids( $parsed_args, $mode );
		}
	} else {
		$ids = wp_ulike_get_popular_items_ids( $parsed_args );
	}

	return ! empty( $ids ) ? $ids : null;
}

/**
 * Whether tops query args include content filters that require a wider popular pool.
 *
 * @param array $args Query arguments.
 * @return bool
 */
function wp_ulike_pro_tops_has_content_filters( $args ) {
	$flags = function_exists( 'wp_ulike_pro_tops_engagement_filter_flags' )
		? wp_ulike_pro_tops_engagement_filter_flags( $args )
		: array( 'has_reaction' => false, 'has_rating' => false );

	return ! empty( $args['search'] )
		|| ! empty( $args['category'] )
		|| ! empty( $flags['has_reaction'] )
		|| ! empty( $flags['has_rating'] );
}

/**
 * Max popular candidates to scan when applying search/category filters.
 *
 * @return int
 */
function wp_ulike_pro_tops_search_pool_limit() {
	return max( 50, (int) apply_filters( 'wp_ulike_pro_tops_search_pool_limit', 1000 ) );
}

/**
 * Adjust popular-items args before content filtering.
 *
 * @param array $args Query arguments.
 * @return array
 */
function wp_ulike_pro_prepare_popular_items_args( $args ) {
	$prepared = $args;

	if ( wp_ulike_pro_tops_has_content_filters( $prepared ) ) {
		$prepared['limit']  = wp_ulike_pro_tops_search_pool_limit();
		$prepared['offset'] = 1;
	}

	return $prepared;
}

/**
 * Whether tops request filters by emoji reaction and/or star rating.
 *
 * @param array $args Query arguments.
 * @return array{has_reaction:bool,has_rating:bool}
 */
function wp_ulike_pro_tops_engagement_filter_flags( $args ) {
	$keys = ! empty( $args['engagement_keys'] )
		? array_filter( array_map( 'strval', (array) $args['engagement_keys'] ) )
		: array();
	$vals = ! empty( $args['values'] )
		? array_filter( array_map( 'absint', (array) $args['values'] ) )
		: array();

	return array(
		'has_reaction' => ! empty( $keys ),
		'has_rating'   => ! empty( $vals ),
	);
}

/**
 * Popular item IDs for Top Content, respecting vote vs emoji vs star filters.
 *
 * Reaction filter → only items with those emoji keys (no classic votes / stars).
 * Rating filter   → only items with those star values (no classic votes / emoji).
 * Both            → union of matching emoji + star items.
 * Neither         → votes ∪ emoji ∪ star (data-driven default).
 *
 * @param array $popular_args Prepared popular-items args.
 * @return int[]
 */
function wp_ulike_pro_get_tops_union_item_ids( $popular_args ) {
	$flags       = wp_ulike_pro_tops_engagement_filter_flags( $popular_args );
	$page_limit  = max( 1, (int) ( $popular_args['limit'] ?? 10 ) );
	$page_offset = max( 1, (int) ( $popular_args['offset'] ?? 1 ) );
	$pool_floor  = wp_ulike_pro_tops_search_pool_limit();
	$is_pool     = $page_limit >= $pool_floor;

	// Vote ∪ emoji ∪ star each apply their own LIMIT. Fetch a wide enough
	// candidate set, merge, then slice to the requested page so posts never
	// return 15–30 rows for a "10 per page" request.
	$fetch_args           = $popular_args;
	$fetch_args['offset'] = 1;
	$fetch_args['limit']  = $is_pool
		? $page_limit
		: min( $pool_floor, $page_offset * $page_limit * 3 );

	$ids = array();

	if ( $flags['has_reaction'] ) {
		$ids = array_merge(
			$ids,
			(array) wp_ulike_pro_get_popular_engagement_item_ids( $fetch_args, 'emoji' )
		);
	}

	if ( $flags['has_rating'] ) {
		$ids = array_merge(
			$ids,
			(array) wp_ulike_pro_get_popular_engagement_item_ids( $fetch_args, 'star' )
		);
	}

	if ( $flags['has_reaction'] || $flags['has_rating'] ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	} else {
		// No reaction/rating filter: include every engagement kind.
		$vote_ids = (array) wp_ulike_get_popular_items_ids( $fetch_args );
		$pro_ids  = array_merge(
			(array) wp_ulike_pro_get_popular_engagement_item_ids( $fetch_args, 'emoji' ),
			(array) wp_ulike_pro_get_popular_engagement_item_ids( $fetch_args, 'star' )
		);

		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', array_merge( $vote_ids, $pro_ids ) )
				)
			)
		);
	}

	if ( $is_pool ) {
		return $ids;
	}

	return wp_ulike_pro_paginate_tops_items( $ids, $page_offset, $page_limit );
}

/**
 * Case-insensitive substring search helper.
 *
 * @param string $haystack Content to search in.
 * @param string $search   Search term.
 * @return bool
 */
function wp_ulike_pro_text_matches_search( $haystack, $search ) {
	$search = trim( (string) $search );
	if ( '' === $search ) {
		return true;
	}

	return false !== stripos( wp_strip_all_tags( (string) $haystack ), $search );
}

/**
 * Slice a filtered list using tops pagination args.
 *
 * @param array $items  Filtered items.
 * @param int   $offset Page number (1-based).
 * @param int   $limit  Page size.
 * @return array
 */
function wp_ulike_pro_paginate_tops_items( $items, $offset, $limit ) {
	$items  = array_values( $items );
	$offset = max( 1, (int) $offset );
	$limit  = max( 1, (int) $limit );
	$start  = ( $offset - 1 ) * $limit;

	return array_slice( $items, $start, $limit );
}

/**
 * Filter comments by search term.
 *
 * @param array  $comments Comment objects.
 * @param string $search   Search term.
 * @return array
 */
function wp_ulike_pro_filter_comments_by_search( $comments, $search ) {
	$search = trim( (string) $search );
	if ( '' === $search || empty( $comments ) ) {
		return $comments;
	}

	return array_values(
		array_filter(
			$comments,
			function ( $comment ) use ( $search ) {
				$fields = array(
					$comment->comment_content,
					$comment->comment_author,
					get_the_title( $comment->comment_post_ID ),
				);

				foreach ( $fields as $field ) {
					if ( wp_ulike_pro_text_matches_search( $field, $search ) ) {
						return true;
					}
				}

				return false;
			}
		)
	);
}

/**
 * Filter BuddyPress activities by search term.
 *
 * @param array  $activities Activity objects.
 * @param string $search     Search term.
 * @return array
 */
function wp_ulike_pro_filter_activities_by_search( $activities, $search ) {
	$search = trim( (string) $search );
	if ( '' === $search || empty( $activities ) ) {
		return $activities;
	}

	return array_values(
		array_filter(
			$activities,
			function ( $activity ) use ( $search ) {
				$author = get_user_by( 'id', $activity->user_id );
				$fields = array(
					$activity->action,
					$activity->content,
					$author ? $author->display_name : '',
				);

				foreach ( $fields as $field ) {
					if ( wp_ulike_pro_text_matches_search( $field, $search ) ) {
						return true;
					}
				}

				return false;
			}
		)
	);
}

/**
 * Filter engager rows by user search term.
 *
 * @param array  $users  Query rows/objects with user_id.
 * @param string $search Search term.
 * @return array
 */
function wp_ulike_pro_filter_engagers_by_search( $users, $search ) {
	$search = trim( (string) $search );
	if ( '' === $search || empty( $users ) ) {
		return $users;
	}

	return array_values(
		array_filter(
			$users,
			function ( $user ) use ( $search ) {
				$user_id  = isset( $user->user_id ) ? (int) $user->user_id : 0;
				$userdata = $user_id ? get_userdata( $user_id ) : false;

				if ( ! $userdata ) {
					return false;
				}

				$fields = array(
					$userdata->display_name,
					$userdata->user_login,
					$userdata->user_nicename,
					$userdata->user_email,
				);

				foreach ( $fields as $field ) {
					if ( wp_ulike_pro_text_matches_search( $field, $search ) ) {
						return true;
					}
				}

				return false;
			}
		)
	);
}

/**
 * Get total posts/topics count after search/category filters.
 *
 * @param array $args Query arguments.
 * @return int
 */
function wp_ulike_pro_get_posts_query_total( $args ) {
	$count_args           = $args;
	$count_args['offset'] = 1;
	// Popular tops without content filters paginate in SQL (limit=page size).
	// Counting with limit=1 made found_posts≈1 and hid pagination entirely.
	// Use a wide pool so the total reflects the popular set size.
	$count_args['limit']  = function_exists( 'wp_ulike_pro_tops_search_pool_limit' )
		? wp_ulike_pro_tops_search_pool_limit()
		: 1000;

	$query = wp_ulike_pro_get_posts_query( $count_args );

	if ( ! ( $query instanceof WP_Query ) ) {
		return 0;
	}

	if ( wp_ulike_pro_tops_has_content_filters( $count_args ) ) {
		return (int) $query->found_posts;
	}

	return (int) $query->post_count;
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
		"limit"      => 10,
		"search"     => '',
		"category"   => 0,
		"taxonomy"   => '',
	);
	$parsed_args = wp_parse_args( $args, $defaults );
	if( $parsed_args['type'] === 'topic' ){
		// Get bbpress post types
		$parsed_args['rel_type'] =  array( 'topic', 'reply' );
	}

	if( empty( $parsed_args['rel_type'] ) ){
		// Get post types
		$parsed_args['rel_type'] = get_post_types_by_support( array(
			'title',
			'editor',
			'thumbnail'
		) );
	}

	if ( empty( $parsed_args['rel_type'] ) ) {
		$parsed_args['rel_type'] = array( 'post' );
	}

	$uses_wp_pagination = wp_ulike_pro_tops_has_content_filters( $parsed_args );
	$page_limit         = max( 1, (int) $parsed_args['limit'] );
	$page_offset        = max( 1, (int) $parsed_args['offset'] );

	// Filtered lists need a wide ID pool + WP_Query paging. Unfiltered lists
	// are already sliced to one page inside wp_ulike_pro_get_tops_union_item_ids().
	$id_args = $uses_wp_pagination
		? wp_ulike_pro_prepare_popular_items_args( $parsed_args )
		: $parsed_args;

	if ( wp_ulike_is_true( $parsed_args['is_popular'] ) ) {
		$get_items = wp_ulike_pro_get_tops_union_item_ids( $id_args );
	} else {
		$get_items = wp_ulike_pro_get_items_info( $parsed_args );
	}

	if ( empty( $get_items ) ) {
		return false;
	}

	$query_args = array(
		'post_type'              => $parsed_args['rel_type'],
		'post_status'            => array( 'publish', 'inherit', 'private' ),
		'posts_per_page'         => $uses_wp_pagination ? $page_limit : count( $get_items ),
		'paged'                  => $uses_wp_pagination ? $page_offset : 1,
		'post__in'               => $get_items,
		'orderby'                => 'post__in',
		'ignore_sticky_posts'    => true,
	);

	if ( ! empty( $parsed_args['search'] ) ) {
		$query_args['s'] = $parsed_args['search'];
	}

	if ( ! empty( $parsed_args['category'] ) ) {
		$taxonomy = ! empty( $parsed_args['taxonomy'] ) ? sanitize_key( $parsed_args['taxonomy'] ) : 'category';

		if ( 'category' !== $taxonomy && taxonomy_exists( $taxonomy ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => absint( $parsed_args['category'] ),
				),
			);
		} else {
			$query_args['cat'] = absint( $parsed_args['category'] );
		}
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
		"is_popular" => false,
		"status"     => 'like',
		"user_id"    => '',
		"order"      => 'DESC',
		"period"     => 'all',
		"offset"     => 1,
		"limit"      => 10,
		"search"     => '',
	);
	$parsed_args = wp_parse_args( $args, $defaults );

	$has_filters = wp_ulike_pro_tops_has_content_filters( $parsed_args );
	$id_args     = $has_filters
		? wp_ulike_pro_prepare_popular_items_args( $parsed_args )
		: $parsed_args;

	if ( wp_ulike_is_true( $parsed_args['is_popular'] ) ) {
		$get_items = wp_ulike_pro_get_tops_union_item_ids( $id_args );
	} else {
		$get_items = wp_ulike_pro_get_items_info( $parsed_args );
	}

	if ( empty( $get_items ) ) {
		return false;
	}

	$query_args = array(
		'comment__in' => $get_items,
		'orderby'     => 'comment__in',
		'number'      => 0,
	);

	$comments_query = new WP_Comment_Query();
	$comments       = $comments_query->query( $query_args );

	if ( empty( $comments ) ) {
		return false;
	}

	if ( ! empty( $parsed_args['search'] ) ) {
		$comments = wp_ulike_pro_filter_comments_by_search( $comments, $parsed_args['search'] );
	}

	if ( empty( $comments ) ) {
		return false;
	}

	// Search/filter path still needs an explicit page slice; unfiltered IDs are
	// already one page from wp_ulike_pro_get_tops_union_item_ids().
	if ( $has_filters ) {
		return wp_ulike_pro_paginate_tops_items(
			$comments,
			$parsed_args['offset'],
			$parsed_args['limit']
		);
	}

	return $comments;
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
		"limit"      => 10,
		"search"     => '',
	);
	$parsed_args = wp_parse_args( $args, $defaults );
	$has_filters = wp_ulike_pro_tops_has_content_filters( $parsed_args );
	$id_args     = $has_filters
		? wp_ulike_pro_prepare_popular_items_args( $parsed_args )
		: $parsed_args;

	if ( wp_ulike_is_true( $parsed_args['is_popular'] ) ) {
		$get_items = wp_ulike_pro_get_tops_union_item_ids( $id_args );
	} else {
		$get_items = wp_ulike_pro_get_items_info( $parsed_args );
	}

	if ( empty( $get_items ) ) {
		return false;
	}

	global $wpdb;

	if ( is_multisite() ) {
		$bp_prefix = 'base_prefix';
	} else {
		$bp_prefix = 'prefix';
	}

	$item_ids = array_map( 'absint', $get_items );
	$item_ids = array_filter( $item_ids );
	if ( empty( $item_ids ) ) {
		return false;
	}

	$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );

	// generate query string
	$query_string = $wpdb->prepare(
		"
		SELECT * FROM
		`{$wpdb->$bp_prefix}bp_activity`
		WHERE `id` IN ($placeholders)
		ORDER BY FIELD(`id`, $placeholders)",
		array_merge( $item_ids, $item_ids )
	);

	$activities = $wpdb->get_results( $query_string );

	if ( empty( $activities ) ) {
		return false;
	}

	if ( ! empty( $parsed_args['search'] ) ) {
		$activities = wp_ulike_pro_filter_activities_by_search( $activities, $parsed_args['search'] );
	}

	if ( empty( $activities ) ) {
		return false;
	}

	if ( wp_ulike_pro_tops_has_content_filters( $parsed_args ) ) {
		return wp_ulike_pro_paginate_tops_items(
			$activities,
			$parsed_args['offset'],
			$parsed_args['limit']
		);
	}

	return $activities;
}

/**
 * Count comments after optional search filter.
 *
 * @param array $args Query arguments.
 * @return int
 */
function wp_ulike_pro_count_filtered_comments( $args ) {
	$defaults = array(
		'type'       => 'comment',
		'is_popular' => true,
		'status'     => array( 'like', 'dislike' ),
		'period'     => 'all',
		'search'     => '',
	);
	$parsed_args  = wp_parse_args( $args, $defaults );
	$popular_args = wp_ulike_pro_prepare_popular_items_args( $parsed_args );
	$get_items    = wp_ulike_get_popular_items_ids( $popular_args );

	if ( empty( $get_items ) ) {
		return 0;
	}

	$comments_query = new WP_Comment_Query();
	$comments       = $comments_query->query(
		array(
			'comment__in' => $get_items,
			'orderby'     => 'comment__in',
			'number'      => 0,
		)
	);

	if ( ! empty( $parsed_args['search'] ) ) {
		$comments = wp_ulike_pro_filter_comments_by_search( $comments, $parsed_args['search'] );
	}

	return count( $comments );
}

/**
 * Count activities after optional search filter.
 *
 * @param array $args Query arguments.
 * @return int
 */
function wp_ulike_pro_count_filtered_activities( $args ) {
	if ( ! defined( 'BP_VERSION' ) ) {
		return 0;
	}

	$defaults = array(
		'type'       => 'activity',
		'is_popular' => true,
		'status'     => array( 'like', 'dislike' ),
		'period'     => 'all',
		'search'     => '',
	);
	$parsed_args  = wp_parse_args( $args, $defaults );
	$popular_args = wp_ulike_pro_prepare_popular_items_args( $parsed_args );
	$get_items    = wp_ulike_get_popular_items_ids( $popular_args );

	if ( empty( $get_items ) ) {
		return 0;
	}

	global $wpdb;

	if ( is_multisite() ) {
		$bp_prefix = 'base_prefix';
	} else {
		$bp_prefix = 'prefix';
	}

	$item_ids     = array_map( 'absint', $get_items );
	$item_ids     = array_filter( $item_ids );
	$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );
	$query_string = $wpdb->prepare(
		"SELECT * FROM `{$wpdb->$bp_prefix}bp_activity` WHERE `id` IN ($placeholders)",
		$item_ids
	);

	$activities = $wpdb->get_results( $query_string );

	if ( ! empty( $parsed_args['search'] ) ) {
		$activities = wp_ulike_pro_filter_activities_by_search( $activities, $parsed_args['search'] );
	}

	return count( $activities );
}

/**
 * Count engagers after optional search filter.
 *
 * @param array $args Query arguments.
 * @return int
 */
function wp_ulike_pro_count_filtered_engagers( $args ) {
	$defaults = array(
		'limit'  => 10,
		'period' => 'all',
		'offset' => 1,
		'status' => array( 'like', 'dislike' ),
		'search' => '',
	);
	$parsed_args = wp_parse_args( $args, $defaults );
	$search      = trim( (string) $parsed_args['search'] );

	if ( '' === $search ) {
		return (int) wp_ulike_pro_count_top_combined_engagers( $parsed_args['period'], $parsed_args['status'] );
	}

	$top_likers = wp_ulike_pro_get_top_combined_engagers(
		array(
			'limit'  => wp_ulike_pro_tops_search_pool_limit(),
			'offset' => 1,
			'period' => $parsed_args['period'],
			'status' => $parsed_args['status'],
			'search' => $search,
			'order'  => 'DESC',
		)
	);

	return count( $top_likers );
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
 * Canonical site URL used for license API requests (multisite-aware).
 *
 * @return string
 */
function wp_ulike_pro_get_license_site_url() {
	$use_home_url = apply_filters( 'wp_ulike_pro_license_api_use_home_url', true );
	$site_url     = $use_home_url ? home_url() : get_site_url();

	$apply_multisite_logic = apply_filters( 'wp_ulike_pro_api_apply_multisite_logic', true );

	if ( $apply_multisite_logic && is_multisite() ) {
		$site_url = $use_home_url ? network_home_url() : network_site_url();
	}

	return untrailingslashit( esc_url_raw( $site_url ) );
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

	// filter out empty values and ensure each digit is valid
	// Note: Use !== '' instead of !empty() because "0" is a valid digit but empty("0") returns true
	$otp = array_filter( $otp, function( $digit ) {
		// Ensure $digit is a string before trimming
		if ( ! is_string( $digit ) && ! is_numeric( $digit ) ) {
			return false;
		}
		$digit = trim( (string) $digit );
		return $digit !== '' && is_numeric( $digit ) && strlen( $digit ) === 1;
	} );

	// re-index array after filtering
	$otp = array_values( $otp );

	// ensure we have exactly 6 digits
	if( count( $otp ) !== 6 ){
		return false;
	}

	$tfa  = new RobThree\Auth\TwoFactorAuth();
	$code = (string) implode( "", $otp );

	// ensure code is exactly 6 digits
	if( strlen( $code ) !== 6 ){
		return false;
	}

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
    <h3 class="ulp-form-title" id="ulp-2fa-title">
        <?php echo wp_ulike_get_option( 'two_factor_field_title', esc_html__( 'Enter the six-digit code from the application', WP_ULIKE_PRO_DOMAIN ) ); ?>
    </h3>
</div>
<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
    <div id="ulp-2fa-code" class="ulp-flex ulp-flex-center-xs" role="group" aria-labelledby="ulp-2fa-title" aria-describedby="ulp-2fa-description">
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-1-digit"
            name="otp[]" autocomplete="one-time-code" aria-label="<?php esc_attr_e( 'First digit of verification code', WP_ULIKE_PRO_DOMAIN ); ?>" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-2-digit"
            name="otp[]" autocomplete="one-time-code" aria-label="<?php esc_attr_e( 'Second digit of verification code', WP_ULIKE_PRO_DOMAIN ); ?>" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-3-digit"
            name="otp[]" autocomplete="one-time-code" aria-label="<?php esc_attr_e( 'Third digit of verification code', WP_ULIKE_PRO_DOMAIN ); ?>" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-4-digit"
            name="otp[]" autocomplete="one-time-code" aria-label="<?php esc_attr_e( 'Fourth digit of verification code', WP_ULIKE_PRO_DOMAIN ); ?>" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-5-digit"
            name="otp[]" autocomplete="one-time-code" aria-label="<?php esc_attr_e( 'Fifth digit of verification code', WP_ULIKE_PRO_DOMAIN ); ?>" />
        <input class="ulp-digit-input" type="tel" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ulp-6-digit"
            name="otp[]" autocomplete="one-time-code" aria-label="<?php esc_attr_e( 'Sixth digit of verification code', WP_ULIKE_PRO_DOMAIN ); ?>" />
    </div>
    <div id="ulp-2fa-description" class="ulp-screen-reader-text">
        <?php esc_html_e( 'Enter the 6-digit verification code from your authenticator app', WP_ULIKE_PRO_DOMAIN ); ?>
    </div>
</div>
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
			href="<?php echo esc_url( $url ); ?>">
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

	if ( isset( $all_notices[ $notice_type ] ) && is_array( $all_notices[ $notice_type ] ) ) {

		$notice_count = count( $all_notices[ $notice_type ] );

	} elseif ( empty( $notice_type ) ) {

		foreach ( $all_notices as $notices ) {
			if ( is_countable( $notices ) ) {
				$notice_count += count( $notices );
			}
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
 * @param string|bool $path_or_secure Path for cookie, or secure flag if 5th param is provided
 * @param bool $httponly HttpOnly flag (only used if 4th param is bool/secure flag)
 * @return void
 */
function wp_ulike_pro_setcookie( $name, $value = '', $expire = 0, $path_or_secure = '/', $httponly = true ){
	if ( empty( $value ) ) {
		$expire = time() - YEAR_IN_SECONDS;
	}

	// Handle backward compatibility: if 4th param is bool, it's the secure flag
	if ( is_bool( $path_or_secure ) ) {
		$secure = $path_or_secure;
		$path = '/';
		$httponly = is_bool( $httponly ) ? $httponly : true;
	} else {
		$path = $path_or_secure;
		$secure = is_ssl();
		if ( empty( $path ) ) {
			list( $path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
	}

	$levels = ob_get_level();
	for ( $i = 0; $i < $levels; $i++ ) {
		@ob_end_clean();
	}

	nocache_headers();
	setcookie( $name, $value, $expire, $path, COOKIE_DOMAIN, $secure, $httponly );
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


/**
 * Get the MaxMind DB reader for GeoLite2-Country (lazy-loaded, one per request).
 *
 * Uses the same .mmdb file as before; filter wp_ulike_pro_geoip_db_path to override path.
 *
 * @return \MaxMind\Db\Reader|null
 */
function wp_ulike_pro_get_geoip_db_reader() {
    static $reader = null;

    if ( $reader !== null ) {
        return $reader;
    }

    $path = WP_ULIKE_PRO_ADMIN_DIR . '/assets/data/GeoLite2-Country.mmdb';
    $path = apply_filters( 'wp_ulike_pro_geoip_db_path', $path );

    if ( ! is_file( $path ) || ! is_readable( $path ) ) {
        return null;
    }

    try {
        $reader = new \MaxMind\Db\Reader( $path );
        return $reader;
    } catch ( \Exception $e ) {
        return null;
    }
}

/**
 * Get the country code based on the user's IP address (local DB, no API).
 *
 * Uses the GeoLite2-Country .mmdb file and the lightweight MaxMind DB reader.
 * Results are cached via WordPress object cache (wp_cache).
 *
 * @param string $ip_address
 * @return string|null Two-letter ISO country code, or null on failure/invalid IP.
 */
function wp_ulike_pro_get_country_code_from_ip( $ip_address ) {
    $ip_address = trim( (string) $ip_address );
    if ( $ip_address === '' ) {
        return null;
    }

    if ( wp_ulike_pro_is_private_or_local_ip( $ip_address ) ) {
        return null;
    }

    $cache_group = 'wp-ulike-pro-geoip-country';
    $cache_key   = $ip_address;

    $cached = wp_cache_get( $cache_key, $cache_group );
    if ( $cached !== false ) {
        return $cached;
    }

    $code   = null;
    $reader = wp_ulike_pro_get_geoip_db_reader();
    if ( $reader !== null ) {
        try {
            $record = $reader->get( $ip_address );
            if ( is_array( $record ) && isset( $record['country']['iso_code'] ) ) {
                $code = trim( (string) $record['country']['iso_code'] );
                $code = ( strlen( $code ) === 2 ) ? strtoupper( $code ) : null;
            }
        } catch ( \Exception $e ) {
            $code = null;
        }
    }

    wp_cache_set( $cache_key, $code, $cache_group, DAY_IN_SECONDS );

    return $code;
}

/**
 * Check if an IP is private or local (no geolocation lookup needed).
 *
 * @param string $ip
 * @return bool
 */
function wp_ulike_pro_is_private_or_local_ip( $ip ) {
    if ( $ip === '127.0.0.1' || $ip === '::1' ) {
        return true;
    }
    $packed = @inet_pton( $ip );
    if ( $packed === false ) {
        return true;
    }
    $len = strlen( $packed );
    if ( $len === 4 ) {
        $octets = array_values( unpack( 'C*', $packed ) );
        if ( $octets[0] === 10 ) {
            return true;
        }
        if ( $octets[0] === 172 && $octets[1] >= 16 && $octets[1] <= 31 ) {
            return true;
        }
        if ( $octets[0] === 192 && $octets[1] === 168 ) {
            return true;
        }
        return false;
    }
    if ( $len === 16 ) {
        $first  = ord( $packed[0] );
        $second = ord( $packed[1] );
        if ( ( $first === 0xfc || $first === 0xfd ) || ( $first === 0xfe && ( $second & 0xc0 ) === 0x80 ) ) {
            return true;
        }
        return false;
    }
    return true;
}

/**
 * Detect device type for like/dislike analytics.
 *
 * Uses stripos() for speed (no regex on typical desktop/mobile traffic).
 * Runs once per vote; cost is negligible next to the DB write.
 *
 * @param string $user_agent User agent string.
 * @return string One of: desktop, mobile, tablet, television, gaming.
 */
function wp_ulike_pro_detect_device_type( $user_agent ) {
    if ( $user_agent === null || $user_agent === '' ) {
        return 'desktop';
    }

    $user_agent  = (string) $user_agent;
    $is_ios_like = stripos( $user_agent, 'like iPhone' ) === false;

    // iOS — checked first (very common on mobile web).
    if ( $is_ios_like && stripos( $user_agent, 'iPad' ) !== false ) {
        return 'tablet';
    }
    if ( $is_ios_like && ( stripos( $user_agent, 'iPhone' ) !== false || stripos( $user_agent, 'iPod' ) !== false ) ) {
        return 'mobile';
    }

    // Android — "Mobile" in UA is the standard phone vs tablet split.
    if ( stripos( $user_agent, 'Android' ) !== false ) {
        return stripos( $user_agent, 'Mobile' ) !== false ? 'mobile' : 'tablet';
    }

    // Other phones.
    if ( stripos( $user_agent, 'Mobile' ) !== false
        || stripos( $user_agent, 'BlackBerry' ) !== false
        || stripos( $user_agent, 'IEMobile' ) !== false
        || stripos( $user_agent, 'Opera Mini' ) !== false
        || stripos( $user_agent, 'Opera Mobi' ) !== false
        || stripos( $user_agent, 'Windows Phone' ) !== false
        || stripos( $user_agent, 'webOS' ) !== false ) {
        return 'mobile';
    }

    // Other tablets.
    if ( stripos( $user_agent, 'Tablet' ) !== false
        || stripos( $user_agent, 'PlayBook' ) !== false
        || stripos( $user_agent, 'Kindle Fire' ) !== false ) {
        return 'tablet';
    }

    // Gaming & TV — rare; checked last so normal traffic exits early.
    if ( stripos( $user_agent, 'PlayStation' ) !== false
        || stripos( $user_agent, 'Xbox' ) !== false
        || stripos( $user_agent, 'Nintendo' ) !== false ) {
        return 'gaming';
    }

    if ( stripos( $user_agent, 'SMART-TV' ) !== false
        || stripos( $user_agent, 'SmartTV' ) !== false
        || stripos( $user_agent, 'Smart-TV' ) !== false
        || stripos( $user_agent, 'GoogleTV' ) !== false
        || stripos( $user_agent, 'AppleTV' ) !== false
        || stripos( $user_agent, 'Roku' ) !== false
        || stripos( $user_agent, 'NetCast' ) !== false
        || stripos( $user_agent, 'HbbTV' ) !== false
        || ( stripos( $user_agent, 'Tizen' ) !== false && stripos( $user_agent, 'TV' ) !== false ) ) {
        return 'television';
    }

    return 'desktop';
}

/**
 * Get device, OS, and browser info from the user agent.
 *
 * Uses WP ULike's lightweight parser from the free plugin when available.
 *
 * @param string|null $user_agent User agent string. Defaults to current request UA.
 * @return array{device: string, os: string, browser: string}
 */
function wp_ulike_pro_get_device_info( $user_agent = null ) {
    if ( ! $user_agent ) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    if ( ! class_exists( 'WP_Ulike_User_Agent_Parser' ) ) {
        return array(
            'device'  => wp_ulike_pro_detect_device_type( $user_agent ),
            'os'      => '',
            'browser' => '',
        );
    }

    $parser = new WP_Ulike_User_Agent_Parser( $user_agent );
    $parser->parse();

    $client = $parser->get_client();
    $os     = $parser->get_os();

    return array(
        'device'  => wp_ulike_pro_detect_device_type( $user_agent ),
        'os'      => trim( $os['name'] . ' ' . $os['version'] ),
        'browser' => trim( $client['name'] . ' ' . $client['version'] ),
    );
}


if ( ! function_exists( 'wp_ulike_pro_get_user_global_latest_activity' ) ) {
	/**
	 * Latest vote or engagement row for a user across all content types (Pulse-aware).
	 *
	 * @param int $user_id User ID.
	 * @return array{date_time:string,status:string}|null
	 */
	function wp_ulike_pro_get_user_global_latest_activity( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return null;
		}

		$cache_key = 'global_latest_activity_' . $user_id;
		$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
		if ( false !== $cached ) {
			return $cached ?: null;
		}

		$candidates = array();

		$pulse_table = esc_sql( wp_ulike_pro_pulse_table() );
		$mode        = class_exists( 'WP_Ulike_Pulse_Query' ) && method_exists( 'WP_Ulike_Pulse_Query', 'read_mode' )
			? WP_Ulike_Pulse_Query::read_mode()
			: 'pulse';

		// Pulse arm — always run. Emoji/star live only in pulse regardless of
		// read mode, and vote rows in merged mode are valid latest-activity
		// candidates alongside legacy rows. Picking MAX(date_time) across
		// candidates handles any overlap correctly.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT date_time, engagement_kind, engagement_key, status
				FROM `{$pulse_table}` WHERE user_id = %s AND status = %s
				ORDER BY date_time DESC, id DESC LIMIT 1",
				(string) $user_id,
				'active'
			),
			ARRAY_A
		);

		if ( ! empty( $row['date_time'] ) ) {
			$candidates[] = array(
				'date_time' => $row['date_time'],
				'status'    => wp_ulike_pro_map_pulse_activity_status( $row ),
				'ts'        => strtotime( $row['date_time'] ),
			);
		}

		// Legacy arm — pre-cutover activity on dual/legacy sites.
		if ( ( 'legacy' === $mode || 'merged' === $mode ) && class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
			$parts = array();
			foreach ( WP_Ulike_Pulse_Registry::log_table_names() as $table ) {
				if ( ! WP_Ulike_Pulse_Registry::table_exists( $table ) ) {
					continue;
				}
				$t      = esc_sql( $table );
				$parts[] = $wpdb->prepare(
					"SELECT date_time, status FROM `{$t}` WHERE user_id = %s",
					(string) $user_id
				);
			}

			if ( ! empty( $parts ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments built from prepared statements.
				$legacy_row = $wpdb->get_row(
					'SELECT date_time, status FROM (' . implode( ' UNION ALL ', $parts ) . ') AS x ORDER BY date_time DESC LIMIT 1',
					ARRAY_A
				);

				if ( ! empty( $legacy_row['date_time'] ) ) {
					$mapped = class_exists( 'WP_Ulike_Pulse_Vote_Map' )
						? WP_Ulike_Pulse_Vote_Map::legacy_to_row( $legacy_row['status'] )
						: array( 'engagement_key' => 'like', 'status' => 'active' );
					$candidates[] = array(
						'date_time' => $legacy_row['date_time'],
						'status'    => wp_ulike_pro_map_pulse_activity_status(
							array(
								'engagement_kind' => 'vote',
								'engagement_key'  => $mapped['engagement_key'],
								'status'          => $mapped['status'],
							)
						),
						'ts'        => strtotime( $legacy_row['date_time'] ),
					);
				}
			}
		}

		if ( empty( $candidates ) ) {
			wp_cache_set( $cache_key, array(), WP_ULIKE_PRO_DOMAIN, 300 );
			return null;
		}

		usort(
			$candidates,
			static function ( $a, $b ) {
				return $b['ts'] <=> $a['ts'];
			}
		);

		$latest = array(
			'date_time' => $candidates[0]['date_time'],
			'status'    => $candidates[0]['status'],
		);

		wp_cache_set( $cache_key, $latest, WP_ULIKE_PRO_DOMAIN, 300 );

		return $latest;
	}
}

if ( ! function_exists( 'wp_ulike_pro_map_pulse_activity_status' ) ) {
	/**
	 * Map a pulse row to a display status string.
	 *
	 * @param array<string,string> $row Pulse row fragment.
	 * @return string
	 */
	function wp_ulike_pro_map_pulse_activity_status( $row ) {
		$kind = isset( $row['engagement_kind'] ) ? (string) $row['engagement_kind'] : WP_Ulike_Pulse_Registry::KIND_VOTE;

		if ( WP_Ulike_Pulse_Registry::KIND_VOTE === $kind ) {
			return WP_Ulike_Pulse_Vote_Map::row_to_legacy(
				$row['engagement_key'] ?? WP_Ulike_Pulse_Vote_Map::KEY_LIKE,
				$row['status'] ?? 'active'
			);
		}

		return (string) ( $row['engagement_key'] ?? $row['status'] ?? '' );
	}
}

if ( ! function_exists( 'wp_ulike_pro_get_counter_value' ) ) {
	/**
	 * Pulse-native vote/engagement counter (avoids free-plugin legacy table merge).
	 *
	 * @param int          $item_id     Item ID.
	 * @param string       $type        Content type slug (post, comment, ...).
	 * @param string       $status      like|dislike|all|unlike|undislike.
	 * @param bool         $is_distinct Distinct users for vote mode.
	 * @param mixed        $period      Period filter.
	 * @return int
	 */
	function wp_ulike_pro_get_counter_value( $item_id, $type, $status = 'like', $is_distinct = true, $period = null ) {
		$item_id = absint( $item_id );
		$type    = sanitize_key( (string) $type );
		$status  = sanitize_key( (string) $status );

		if ( ! $item_id || ! $type ) {
			return 0;
		}

		// All-time like/dislike: use free meta-aware counter (same as the button).
		// Period filters and unlike|undislike still need a Pulse ledger count.
		$has_period_filter = ! empty( $period ) && 'all' !== $period;
		if ( ! $has_period_filter && in_array( $status, array( 'like', 'dislike' ), true ) && function_exists( 'wp_ulike_get_counter_value' ) ) {
			return (int) wp_ulike_get_counter_value( $item_id, $type, $status, $is_distinct, null );
		}

		// like / dislike / all / unlike / undislike are classic vote metrics.
		// unlike|undislike count Pulse status=removed rows (do not remap to active).
		// Never return emoji/star totals for "like" — engagement totals use
		// wp_ulike_pro_count_engagement_activity() / star stats.
		if ( in_array( $status, array( 'like', 'dislike', 'all', 'unlike', 'undislike' ), true ) ) {
			$period = null === $period ? 'all' : $period;
			return (int) WP_Ulike_Pro_Pulse_Reader::count_item_votes( $item_id, $type, $status, $is_distinct, $period );
		}

		return 0;
	}
}

if ( ! function_exists( 'wp_ulike_pro_get_likers_list_per_post' ) ) {
	/**
	 * Vote likers user IDs for one item (Pulse-native; legacy table args kept for callers).
	 *
	 * Always returns classic vote likers — never remaps to emoji/star engagers
	 * when the type's primary template is engagement (display automation can still
	 * render up/down + likers modal alongside reactions).
	 * Engagement avatars: wp_ulike_pro_get_engagement_engager_user_ids().
	 *
	 * @param string   $log_table  Legacy table key (ulike, ulike_comments, ...).
	 * @param string   $log_column Legacy column name (unused).
	 * @param int      $item_id    Item ID.
	 * @param int|null $limit      Max users; null = 500.
	 * @return int[]
	 */
	function wp_ulike_pro_get_likers_list_per_post( $log_table, $log_column, $item_id, $limit = null ) {
		unset( $log_column );

		$item_id = absint( $item_id );
		if ( ! $item_id ) {
			return array();
		}

		$limit = null === $limit ? 500 : max( 1, absint( $limit ) );
		$type  = 'post';

		if ( class_exists( 'WP_Ulike_Pro_Stats_Type_Resolver' ) ) {
			// Accept bare suffixes (ulike_comments) and prefixed names (wp_ulike_comments)
			// from Pulse_Registry::legacy_source_for_type().
			$stats_type = WP_Ulike_Pro_Stats_Type_Resolver::table_to_stats_type( (string) $log_table );
			if ( $stats_type ) {
				$type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $stats_type );
			}
		}

		return WP_Ulike_Pro_Pulse_Reader::rebuild_likers_list( $item_id, $type, $limit );
	}
}

if( ! function_exists( 'wp_ulike_pro_get_user_latest_activity' ) ) {
	/**
	 * Latest classic vote activity for a user on an item.
	 *
	 * Always returns vote ledger activity — never remaps to emoji/star when the
	 * type's primary template is engagement. Engagement activity:
	 * wp_ulike_pro_get_engagement_user_latest_activity().
	 *
	 * @param integer $item_id
	 * @param integer $user_id
	 * @param string $type
	 * @return array|null
	 */
	function wp_ulike_pro_get_user_latest_activity( $item_id, $user_id, $type ) {
		return WP_Ulike_Pro_Pulse_Reader::get_user_latest_activity( $item_id, $user_id, $type );
	}
}

if( ! function_exists( 'wp_ulike_pro_get_latest_user_activity_date' ) ) {
	/**
	 * Get the latest activity date for a given user across vote and engagement storage.
	 *
	 * @param int $user_id The user ID to check for activity.
	 * @return string|null The latest activity timestamp in 'Y-m-d H:i:s' format, or null if no activity is found.
	 */
	function wp_ulike_pro_get_latest_user_activity_date( $user_id ) {
		$activity = wp_ulike_pro_get_user_global_latest_activity( $user_id );

		return ! empty( $activity['date_time'] ) ? $activity['date_time'] : null;
	}
}

/**
 * Check rate limit for form submissions
 *
 * This function provides rate limiting protection for various form operations
 * (login, signup, password reset) using fingerprint-based identification.
 * Uses WordPress transients which are automatically cached and cleaned up.
 *
 * @param string $action The action type (e.g., 'login', 'signup', 'password_reset')
 * @param int    $max_attempts Maximum number of attempts allowed (default: 5)
 * @param int    $time_window Time window in seconds (default: 15 minutes)
 * @param string $error_message Custom error message (optional)
 * @return void
 * @throws \Exception If rate limit is exceeded
 */
function wp_ulike_pro_check_rate_limit( $action, $max_attempts = 5, $time_window = 900, $error_message = '' ) {

	$fingerprint = wp_ulike_generate_fingerprint();
	// Sanitize transient key (WordPress transient keys have 172 char limit, this should be ~52 chars)
	$transient_key = 'ulp_rate_limit_' . sanitize_key( $action ) . '_' . sanitize_key( $fingerprint );

	// Allow filters to customize limits per action
	$max_attempts = apply_filters( 'wp_ulike_pro_' . $action . '_max_attempts', $max_attempts );
	$time_window = apply_filters( 'wp_ulike_pro_' . $action . '_time_window', $time_window );

	$attempts = get_transient( $transient_key );

	if ( false === $attempts ) {
		$attempts = 0;
	}

	if ( $attempts >= $max_attempts ) {
		// Get remaining time from transient timeout
		// WordPress automatically caches options via get_option(), so this is efficient
		$timeout_key = '_transient_timeout_' . $transient_key;
		$expiration = get_option( $timeout_key, 0 );
		$remaining_time = max( 0, $expiration - time() );
		$minutes = max( 1, ceil( $remaining_time / 60 ) );

		if ( empty( $error_message ) ) {
			$error_message = sprintf( esc_html__( 'Too many attempts. Please try again in %d minute(s).', WP_ULIKE_PRO_DOMAIN ), $minutes );
		} else {
			$error_message = sprintf( $error_message, $minutes );
		}

		throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'rate_limit_exceeded', $error_message ) );
	}

	// Increment attempts - WordPress handles expiration automatically
	// Uses object cache if available, otherwise falls back to database
	set_transient( $transient_key, $attempts + 1, $time_window );
}

/**
 * Clear rate limit for a specific action
 *
 * @param string $action The action type (e.g., 'login', 'signup', 'password_reset')
 * @return void
 */
function wp_ulike_pro_clear_rate_limit( $action ) {
	$fingerprint = wp_ulike_generate_fingerprint();

	$transient_key = 'ulp_rate_limit_' . sanitize_key( $action ) . '_' . sanitize_key( $fingerprint );
	delete_transient( $transient_key );
}

/**
 * SECURITY: Validate if a redirect URL is safe (same domain)
 *
 * @param string $url The URL to validate
 * @return bool True if safe, false otherwise
 */
function wp_ulike_pro_is_safe_redirect( $url ) {
	if ( empty( $url ) ) {
		return false;
	}

	$parsed = wp_parse_url( $url );
	$home_parsed = wp_parse_url( home_url() );

	// Must have a host
	if ( ! isset( $parsed['host'] ) || ! isset( $home_parsed['host'] ) ) {
		return false;
	}

	// Must be same domain
	if ( $parsed['host'] !== $home_parsed['host'] ) {
		return false;
	}

	// Additional check: ensure it's not a dangerous path
	$dangerous_paths = array( 'wp-admin', 'wp-login.php', 'wp-content/uploads' );
	$path = isset( $parsed['path'] ) ? $parsed['path'] : '';

	foreach ( $dangerous_paths as $dangerous ) {
		if ( strpos( $path, $dangerous ) !== false ) {
			return false;
		}
	}

	return true;
}


/**
 * Engagement storage & sanitization
 */

if ( ! function_exists( 'wp_ulike_pro_pulse_table' ) ) {
	/**
	 * Unified Pulse storage table (ulike_pulse, owned by WP ULike free).
	 *
	 * @return string
	 */
	function wp_ulike_pro_pulse_table() {
		return WP_Ulike_Pulse_Schema::table();
	}
}

if ( ! function_exists( 'wp_ulike_pro_engagement_table' ) ) {
	/**
	 * @deprecated 2.3 Use wp_ulike_pro_pulse_table().
	 * @return string
	 */
	function wp_ulike_pro_engagement_table() {
		return wp_ulike_pro_pulse_table();
	}
}

if ( ! function_exists( 'wp_ulike_pro_legacy_votes_pending' ) ) {
	/**
	 * Whether the site is not yet on pure Pulse storage (legacy or dual/merged mode).
	 *
	 * Pro statistics are correct in every storage mode (legacy/dual/pulse) because
	 * vote reads route through the free plugin's mode-aware Pulse_Query. This flag
	 * only signals that a storage upgrade is available — it is a soft nudge, not a
	 * data-completeness gate.
	 *
	 * @return bool
	 */
	function wp_ulike_pro_legacy_votes_pending() {
		if ( function_exists( 'wp_ulike_pulse_reads_legacy_votes' ) && wp_ulike_pulse_reads_legacy_votes() ) {
			return true;
		}

		if ( function_exists( 'wp_ulike_pulse_needs_migration' ) && wp_ulike_pulse_needs_migration() ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'wp_ulike_pro_get_pulse_migration_url' ) ) {
	/**
	 * Admin URL for WP ULike Pulse / storage upgrade (free plugin).
	 *
	 * @return string
	 */
	function wp_ulike_pro_get_pulse_migration_url() {
		if ( class_exists( 'WP_Ulike_Pulse_Admin' ) ) {
			return WP_Ulike_Pulse_Admin::get_page_url();
		}

		return admin_url( 'admin.php?page=wp-ulike-pulse' );
	}
}

if ( ! function_exists( 'wp_ulike_pro_engagements_available' ) ) {
	/**
	 * Whether ulike_pulse is available.
	 *
	 * Pro requires WP ULike 5.2+, which always provisions Pulse storage.
	 *
	 * @return bool
	 */
	function wp_ulike_pro_engagements_available() {
		return true;
	}
}

if ( ! function_exists( 'wp_ulike_pro_pulse_pro_kinds_sql' ) ) {
	/**
	 * SQL fragment limiting queries to Pro-owned pulse rows (emoji + star).
	 *
	 * @param string $column Column reference, e.g. engagement_kind or e.engagement_kind.
	 * @return string
	 */
	function wp_ulike_pro_pulse_pro_kinds_sql( $column = 'engagement_kind' ) {
		$column = (string) $column;
		$emoji  = WP_Ulike_Pulse_Registry::KIND_EMOJI;
		$star   = WP_Ulike_Pulse_Registry::KIND_STAR;

		if ( false !== strpos( $column, '.' ) ) {
			list( $alias, $field ) = explode( '.', $column, 2 );
			return sprintf(
				'`%s`.`%s` IN (\'%s\',\'%s\')',
				esc_sql( $alias ),
				esc_sql( $field ),
				$emoji,
				$star
			);
		}

		return sprintf(
			'`%s` IN (\'%s\',\'%s\')',
			esc_sql( $column ),
			$emoji,
			$star
		);
	}
}

if ( ! function_exists( 'wp_ulike_pro_sanitize_engagement_kind' ) ) {
	/**
	 * @param string $kind Raw kind.
	 * @return string emoji|star or empty.
	 */
	function wp_ulike_pro_sanitize_engagement_kind( $kind ) {
		$kind = sanitize_key( (string) $kind );

		return in_array(
			$kind,
			array( WP_Ulike_Pulse_Registry::KIND_EMOJI, WP_Ulike_Pulse_Registry::KIND_STAR ),
			true
		) ? $kind : '';
	}
}

if ( ! function_exists( 'wp_ulike_pro_sanitize_engagement_status' ) ) {
	/**
	 * @param string $status Raw status.
	 * @return string active|removed
	 */
	function wp_ulike_pro_sanitize_engagement_status( $status ) {
		return in_array( $status, array( 'active', 'removed' ), true ) ? $status : 'active';
	}
}

if ( ! function_exists( 'wp_ulike_pro_sanitize_engagement_key' ) ) {
	/**
	 * @param string $key Reaction slug or rating axis key.
	 * @return string
	 */
	function wp_ulike_pro_sanitize_engagement_key( $key ) {
		$key = sanitize_key( (string) $key );

		return preg_match( '/^[a-z0-9_-]{1,30}$/', $key ) ? $key : '';
	}
}

if ( ! function_exists( 'wp_ulike_pro_engagement_meta_cache_group' ) ) {
	/**
	 * @param string $item_type Content type slug.
	 * @return string
	 */
	function wp_ulike_pro_engagement_meta_cache_group( $item_type ) {
		return 'wp_ulike_engagement_' . sanitize_key( $item_type ) . '_meta';
	}
}

/**
 * Engagement helpers
 */

if ( ! function_exists( 'wp_ulike_pro_engagements' ) ) {
	/**
	 * Render engagement widget for an item.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug (post, comment, activity, topic).
	 * @return string
	 */
	function wp_ulike_pro_engagements( $item_id, $item_type = 'post' ) {
		return WP_Ulike_Pro_Engagement_Display::render( absint( $item_id ), sanitize_key( $item_type ) );
	}
}

if ( ! function_exists( 'wp_ulike_pro_format_engagement_count' ) ) {
	/**
	 * Format engagement counts: locale + optional K/M/B, without vote prefix/postfix (+/-).
	 *
	 * Uses wp_ulike_format_number() with a dedicated status so global like/dislike
	 * prefix settings (meant for vote buttons) are not applied to reaction tallies.
	 *
	 * @param int|float|string $number Raw count.
	 * @return string
	 */
	function wp_ulike_pro_format_engagement_count( $number ) {
		if ( ! function_exists( 'wp_ulike_format_number' ) ) {
			return (string) $number;
		}

		return wp_ulike_format_number( $number, 'engagement' );
	}
}

if ( ! function_exists( 'wp_ulike_pro_get_engagement_counts' ) ) {
	/**
	 * Get engagement counter payload for an item.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @return array
	 */
	function wp_ulike_pro_get_engagement_counts( $item_id, $item_type = 'post' ) {
		$item_id   = absint( $item_id );
		$item_type = sanitize_key( $item_type );
		$mode      = WP_Ulike_Pro_Engagement_Settings::get_mode( $item_type );

		if ( 'emoji' === $mode ) {
			return array(
				'kind'   => 'emoji',
				'counts' => WP_Ulike_Pro_Engagement_Counter::get_all_reaction_counts( $item_id, $item_type ),
				'total'  => WP_Ulike_Pro_Engagement_Counter::get_total_reactions( $item_id, $item_type ),
			);
		}

		if ( 'star' === $mode ) {
			$agg = WP_Ulike_Pro_Engagement_Counter::get_star_aggregates( $item_id, $item_type );

			return array(
				'kind'    => 'star',
				'average' => $agg['average'],
				'count'   => $agg['count'],
			);
		}

		return array();
	}
}


/**
 * Map stats/query type to engagement item type slug.
 *
 * @param string $type post|comment|activity|topic.
 * @return string
 */
function wp_ulike_pro_engagement_item_type_from_query( $type ) {
	$type = sanitize_key( (string) $type );

	if ( class_exists( 'WP_Ulike_Pro_Stats_Type_Resolver' ) ) {
		return WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type );
	}

	if ( class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
		return WP_Ulike_Pulse_Registry::normalize_item_type( $type );
	}

	$map = array(
		'post'       => 'post',
		'posts'      => 'post',
		'comment'    => 'comment',
		'comments'   => 'comment',
		'activity'   => 'activity',
		'activities' => 'activity',
		'topic'      => 'topic',
		'topics'     => 'topic',
	);

	return isset( $map[ $type ] ) ? $map[ $type ] : $type;
}

/**
 * Whether a content type uses emoji or star engagements.
 *
 * @param string $item_type post|comment|activity|topic.
 * @return string none|emoji|star
 */
function wp_ulike_pro_get_engagement_mode_for_type( $item_type ) {
	if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
		return 'none';
	}

	return WP_Ulike_Pro_Engagement_Settings::get_mode( wp_ulike_pro_engagement_item_type_from_query( $item_type ) );
}

/**
 * Engagement kind used for ranking (emoji reactions vs star ratings).
 *
 * @param string $mode none|emoji|star.
 * @return string
 */
function wp_ulike_pro_engagement_kind_for_mode( $mode ) {
	return 'star' === $mode ? 'star' : 'emoji';
}

/**
 * Build SQL period fragment for engagement queries.
 *
 * @param string $period Period key.
 * @param string $column Date column name.
 * @return string
 */
function wp_ulike_pro_engagement_period_sql( $period, $column = 'date_time' ) {
	$period_sql = wp_ulike_get_period_limit_sql( $period );

	if ( empty( $period_sql ) ) {
		return '';
	}

	return str_replace( 'date_time', $column, $period_sql );
}

/**
 * Distinct actor SQL for engagement rows (registered user_id or guest fingerprint).
 *
 * @param string $alias Table alias (empty = unqualified columns).
 * @return string
 */
function wp_ulike_pro_engagement_distinct_actor_sql( $alias = 'e' ) {
	$prefix = $alias ? $alias . '.' : '';

	// CONVERT(... USING utf8mb4): CONCAT() inherits the column's collation, and
	// legacy tables from older WordPress installs frequently differ from the
	// newer pulse table. UNION-ing both arms then fails with "Illegal mix of
	// collations" and the whole metric silently reads 0. Never use an explicit
	// COLLATE utf8mb4_* here -- it errors on utf8mb3 legacy tables.
	return "CONVERT(CASE
		WHEN {$prefix}user_id IS NOT NULL AND CAST({$prefix}user_id AS CHAR) NOT IN ('', '0') THEN CONCAT('u:', {$prefix}user_id)
		WHEN {$prefix}fingerprint IS NOT NULL AND CAST({$prefix}fingerprint AS CHAR) NOT IN ('', '0') THEN CONCAT('f:', {$prefix}fingerprint)
		ELSE NULL
	END USING utf8mb4)";
}

/**
 * Count expression respecting Logging Method (distinct users vs total rows).
 *
 * Star ratings always count one vote per actor regardless of logging method.
 * Distinct mode keys guests by fingerprint so they are not collapsed into one "0" bucket.
 *
 * @param string $item_type Content type slug.
 * @param string $kind      Optional engagement kind (emoji|star).
 * @return string
 */
function wp_ulike_pro_engagement_count_expression( $item_type, $kind = 'emoji' ) {
	$actor_sql = wp_ulike_pro_engagement_distinct_actor_sql( 'e' );

	if ( 'star' === $kind ) {
		return "COUNT(DISTINCT {$actor_sql})";
	}

	if ( class_exists( 'wp_ulike_setting_repo' ) && wp_ulike_setting_repo::isDistinct( $item_type ) ) {
		return "COUNT(DISTINCT {$actor_sql})";
	}

	return 'COUNT(*)';
}

/**
 * Count active engagement rows for a fingerprint on an item.
 *
 * @param string $fingerprint Browser fingerprint.
 * @param int    $item_id     Item ID.
 * @param string $item_type   Content type slug.
 * @param string $kind        Optional engagement kind ('emoji' or 'star') to
 *                            scope the count to a single engagement type. When
 *                            provided, a prior vote of a DIFFERENT engagement
 *                            type does NOT consume this type's vote budget —
 *                            emoji and star are independent. When empty, the
 *                            count spans all Pro engagement kinds (legacy behavior).
 * @return int
 */
function wp_ulike_pro_count_engagement_fingerprint( $fingerprint, $item_id, $item_type, $kind = '' ) {
	global $wpdb;

	$table = wp_ulike_pro_pulse_table();
	if ( empty( $fingerprint ) ) {
		return 0;
	}

	$kind = $kind ? sanitize_key( $kind ) : '';
	$cache_key = 'engagement_fingerprint_' . md5( $item_type . '_' . $item_id . '_' . $fingerprint . '_' . $kind );
	$count     = wp_cache_get( $cache_key, WP_ULIKE_SLUG );

	if ( false === $count ) {
		if ( $kind ) {
			$kinds_sql = $wpdb->prepare( ' AND engagement_kind = %s', $kind );
		} else {
			$kinds_sql = ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql();
		}

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE item_id = %d AND item_type = %s AND fingerprint = %s AND status = %s{$kinds_sql}",
				$item_id,
				sanitize_key( $item_type ),
				$fingerprint,
				'active'
			)
		);

		wp_cache_add( $cache_key, $count, WP_ULIKE_SLUG, 10 );
	}

	return (int) $count;
}

/**
 * Read mode-aware engagement filters (reaction slugs / star ratings) from the request.
 *
 * @return array{engagement_keys:string[],values:int[]}
 */
function wp_ulike_pro_read_engagement_filters() {
	$reaction_raw = isset( $_GET['reaction'] ) ? wp_unslash( $_GET['reaction'] ) : array();
	$reaction     = array();
	if ( is_array( $reaction_raw ) ) {
		$reaction = array_values( array_filter( array_map( 'sanitize_text_field', $reaction_raw ) ) );
	}

	$rating_raw = isset( $_GET['rating'] ) ? wp_unslash( $_GET['rating'] ) : array();
	$rating     = array();
	if ( is_array( $rating_raw ) ) {
		$rating = array_values( array_filter( array_map( 'absint', $rating_raw ) ) );
	}

	return array(
		'engagement_keys' => $reaction,
		'values'          => $rating,
	);
}

/**
 * Get popular item IDs ranked by engagement activity.
 *
 * @param array  $args Query arguments (type, period, offset, limit, rel_type, search, user_id).
 * @param string $mode emoji|star.
 * @return int[]
 */
function wp_ulike_pro_get_popular_engagement_item_ids( $args, $mode ) {
	global $wpdb;

	$table     = wp_ulike_pro_engagement_table();
	$item_type = wp_ulike_pro_engagement_item_type_from_query( $args['type'] ?? 'post' );

	if ( empty( $table ) || ! in_array( $mode, array( 'emoji', 'star' ), true ) ) {
		return array();
	}

	$defaults = array(
		'type'    => 'post',
		'period'  => 'all',
		'offset'  => 1,
		'limit'   => 10,
		'order'   => 'DESC',
		'rel_type'=> '',
		'search'  => '',
		'user_id' => '',
	);
	$args = wp_parse_args( $args, $defaults );

	$kind      = wp_ulike_pro_engagement_kind_for_mode( $mode );
	$count_sql = wp_ulike_pro_engagement_count_expression( $item_type, $kind );
	$join      = '';
	$where     = $wpdb->prepare(
		'e.item_type = %s AND e.engagement_kind = %s AND e.status = %s',
		$item_type,
		$kind,
		'active'
	);

	// Apply mode-specific filters only. Reaction slugs belong to emoji;
	// star rating values belong to star. Cross-applying would leak wrong kinds
	// into the other query (or return empty when key/value don't match).
	if ( 'emoji' === $mode ) {
		if ( ! empty( $args['engagement_keys'] ) ) {
			$keys = array_values( array_filter( array_map( 'strval', (array) $args['engagement_keys'] ) ) );
			if ( $keys ) {
				$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
				$where       .= $wpdb->prepare( " AND e.engagement_key IN ({$placeholders})", ...$keys );
			}
		} elseif ( ! empty( $args['values'] ) ) {
			// Rating-only filter: do not return emoji items.
			return array();
		}
	} elseif ( 'star' === $mode ) {
		if ( ! empty( $args['values'] ) ) {
			$values = array_values( array_filter( array_map( 'absint', (array) $args['values'] ) ) );
			if ( $values ) {
				$placeholders = implode( ',', array_fill( 0, count( $values ), '%d' ) );
				$where       .= $wpdb->prepare( " AND e.value IN ({$placeholders})", ...$values );
			}
		} elseif ( ! empty( $args['engagement_keys'] ) ) {
			// Reaction-only filter: do not return star items.
			return array();
		}
	}

	if ( in_array( $item_type, array( 'post', 'topic' ), true ) ) {
		$join .= " INNER JOIN `{$wpdb->posts}` r ON r.ID = e.item_id ";
		$where .= " AND r.post_status IN ('publish', 'inherit', 'private')";

		$rel_type = $args['rel_type'];
		if ( empty( $rel_type ) && 'post' === $item_type ) {
			$rel_type = get_post_types_by_support( array( 'title', 'editor', 'thumbnail' ) );
		}
		if ( 'topic' === $item_type && empty( $rel_type ) ) {
			$rel_type = array( 'topic', 'reply' );
		}

		if ( is_array( $rel_type ) && ! empty( $rel_type ) ) {
			$rel_type     = array_values( $rel_type );
			$placeholders = implode( ',', array_fill( 0, count( $rel_type ), '%s' ) );
			$where       .= $wpdb->prepare( " AND r.post_type IN ({$placeholders})", ...$rel_type );
		} elseif ( ! empty( $rel_type ) ) {
			$where .= $wpdb->prepare( ' AND r.post_type = %s', $rel_type );
		}
	}

	if ( ! empty( $args['user_id'] ) ) {
		if ( is_array( $args['user_id'] ) ) {
			$user_ids = array_values( array_map( 'strval', array_filter( (array) $args['user_id'] ) ) );
			if ( ! empty( $user_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%s' ) );
				$where       .= $wpdb->prepare( " AND e.user_id IN ({$placeholders})", ...$user_ids );
			}
		} else {
			$where .= $wpdb->prepare( ' AND e.user_id = %s', (string) $args['user_id'] );
		}
	}

	$period_sql = wp_ulike_pro_engagement_period_sql( $args['period'], 'e.date_time' );
	if ( $period_sql ) {
		$where .= ' ' . $period_sql;
	}

	$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
	$limit = max( 1, (int) $args['limit'] );
	$paged = max( 0, ( (int) $args['offset'] - 1 ) * $limit );

	$query = "
		SELECT e.item_id, {$count_sql} AS engagement_total
		FROM `{$table}` e
		{$join}
		WHERE {$where}
		GROUP BY e.item_id
		ORDER BY engagement_total {$order}, e.item_id {$order}
		LIMIT %d, %d
	";

	$rows = $wpdb->get_results( $wpdb->prepare( $query, $paged, $limit ) );

	if ( empty( $rows ) ) {
		return array();
	}

	$item_ids = array_map(
		static function ( $row ) {
			return (int) $row->item_id;
		},
		$rows
	);

	if ( ! empty( $args['search'] ) && in_array( $item_type, array( 'post', 'topic' ), true ) ) {
		$item_ids = wp_ulike_pro_filter_post_ids_by_search( $item_ids, $args['search'] );
	}

	return array_values( array_filter( array_map( 'absint', $item_ids ) ) );
}

/**
 * Count items that have engagement activity (for tops pagination totals).
 *
 * @param array  $args Query arguments.
 * @param string $mode emoji|star.
 * @return int
 */
function wp_ulike_pro_count_popular_engagement_items( $args, $mode ) {
	global $wpdb;

	$table     = wp_ulike_pro_engagement_table();
	$item_type = wp_ulike_pro_engagement_item_type_from_query( $args['type'] ?? 'post' );

	if ( empty( $table ) || ! in_array( $mode, array( 'emoji', 'star' ), true ) ) {
		return 0;
	}

	$args['limit']  = wp_ulike_pro_tops_search_pool_limit();
	$args['offset'] = 1;
	$item_ids       = wp_ulike_pro_get_popular_engagement_item_ids( $args, $mode );

	return count( $item_ids );
}

/**
 * Filter post IDs by search term (title).
 *
 * @param int[]  $post_ids Post IDs.
 * @param string $search   Search term.
 * @return int[]
 */
function wp_ulike_pro_filter_post_ids_by_search( $post_ids, $search ) {
	$search = trim( (string) $search );
	if ( '' === $search || empty( $post_ids ) ) {
		return $post_ids;
	}

	return array_values(
		array_filter(
			$post_ids,
			static function ( $post_id ) use ( $search ) {
				return wp_ulike_pro_text_matches_search( get_the_title( $post_id ), $search );
			}
		)
	);
}

/**
 * Period-aware vote metrics for Top Content rows.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @param string $period    Period key.
 * @return array{likes_count:int,dislikes_count:int,mode:string,star_average:float}
 */
function wp_ulike_pro_get_tops_item_metrics( $item_id, $item_type, $period = 'all', $filters = array() ) {
	$item_id     = absint( $item_id );
	$item_type   = sanitize_key( $item_type );
	$mode        = wp_ulike_pro_get_engagement_mode_for_type( $item_type );
	$is_distinct = wp_ulike_setting_repo::isDistinct( $item_type );

	// Always gather every engagement kind for this item so the tops table
	// reflects historical + display-automation emoji/star even when the
	// type's current template mode is "none" or a different kind. The
	// frontend picks the right pill from engagement/emoji_breakdown/star_*.
	$like_count    = wp_ulike_pro_get_counter_value( $item_id, $item_type, 'like', $is_distinct, $period );
	$dislike_count = wp_ulike_pro_get_counter_value( $item_id, $item_type, 'dislike', $is_distinct, $period );
	$emoji_count   = wp_ulike_pro_count_engagement_activity( $item_id, $item_type, 'emoji', $period, $filters );
	$star_stats    = wp_ulike_pro_get_star_period_stats( $item_id, $item_type, $period, $filters );

	return array(
		'likes_count'    => (int) $like_count,
		'dislikes_count' => (int) $dislike_count,
		'emoji_count'    => (int) $emoji_count,
		'star_count'     => (int) $star_stats['count'],
		'star_average'   => (float) $star_stats['average'],
		'mode'           => $mode,
	);
}

/**
 * Count emoji reactions or star ratings for an item in a period.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @param string $kind      emoji|star.
 * @param string $period    Period key.
 * @return int
 */
function wp_ulike_pro_count_engagement_activity( $item_id, $item_type, $kind, $period = 'all', $filters = array() ) {
	global $wpdb;

	$table = wp_ulike_pro_engagement_table();
	if ( empty( $table ) ) {
		return 0;
	}

	$count_expr = wp_ulike_pro_engagement_count_expression( $item_type, $kind );
	$where      = $wpdb->prepare(
		"item_id = %d AND item_type = %s AND engagement_kind = %s AND status = %s",
		$item_id,
		$item_type,
		$kind,
		'active'
	);

	if ( ! empty( $filters['engagement_keys'] ) ) {
		$keys = array_values( array_filter( array_map( 'strval', (array) $filters['engagement_keys'] ) ) );
		if ( $keys ) {
			$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
			$where       .= $wpdb->prepare( " AND engagement_key IN ({$placeholders})", ...$keys );
		}
	}
	if ( ! empty( $filters['values'] ) ) {
		$values = array_values( array_filter( array_map( 'absint', (array) $filters['values'] ) ) );
		if ( $values ) {
			$placeholders = implode( ',', array_fill( 0, count( $values ), '%d' ) );
			$where       .= $wpdb->prepare( " AND value IN ({$placeholders})", ...$values );
		}
	}

	$period_sql = wp_ulike_pro_engagement_period_sql( $period );
	if ( $period_sql ) {
		$where .= ' ' . $period_sql;
	}

	return (int) $wpdb->get_var( "SELECT {$count_expr} FROM `{$table}` AS e WHERE {$where}" );
}

/**
 * Count engagement activity for a custom date range (stats engagement rate).
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @param string $kind      emoji|star.
 * @param string $start     Start date (Y-m-d).
 * @param string $end       End date (Y-m-d).
 * @return int
 */
function wp_ulike_pro_count_engagement_activity_for_range( $item_id, $item_type, $kind, $start, $end ) {
	global $wpdb;

	$table = wp_ulike_pro_engagement_table();
	if ( empty( $table ) || empty( $start ) || empty( $end ) ) {
		return 0;
	}

	$count_expr = wp_ulike_pro_engagement_count_expression( $item_type, $kind );
	$where      = $wpdb->prepare(
		"item_id = %d AND item_type = %s AND engagement_kind = %s AND status = %s AND date_time >= %s AND date_time <= %s",
		absint( $item_id ),
		sanitize_key( $item_type ),
		sanitize_key( $kind ),
		'active',
		$start . ' 00:00:00',
		$end . ' 23:59:59'
	);

	return (int) $wpdb->get_var( "SELECT {$count_expr} FROM `{$table}` AS e WHERE {$where}" );
}

/**
 * Star rating aggregates for a period.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @param string $period    Period key.
 * @return array{count:int,average:float}
 */
function wp_ulike_pro_get_star_period_stats( $item_id, $item_type, $period = 'all', $filters = array() ) {
	global $wpdb;

	$table = wp_ulike_pro_engagement_table();
	if ( empty( $table ) ) {
		return array( 'count' => 0, 'average' => 0.0 );
	}

	$where = $wpdb->prepare(
		"item_id = %d AND item_type = %s AND engagement_kind = %s AND status = %s",
		$item_id,
		$item_type,
		'star',
		'active'
	);

	if ( ! empty( $filters['values'] ) ) {
		$values = array_values( array_filter( array_map( 'absint', (array) $filters['values'] ) ) );
		if ( $values ) {
			$placeholders = implode( ',', array_fill( 0, count( $values ), '%d' ) );
			$where       .= $wpdb->prepare( " AND value IN ({$placeholders})", ...$values );
		}
	}

	$period_sql = wp_ulike_pro_engagement_period_sql( $period );
	if ( $period_sql ) {
		$where .= ' ' . $period_sql;
	}

	$actor_sql = wp_ulike_pro_engagement_distinct_actor_sql( '' );
	$row       = $wpdb->get_row(
		"SELECT COUNT(DISTINCT {$actor_sql}) AS rating_count, COALESCE(SUM(value), 0) AS rating_sum FROM `{$table}` WHERE {$where}",
		ARRAY_A
	);

	$count = isset( $row['rating_count'] ) ? (int) $row['rating_count'] : 0;
	$sum   = isset( $row['rating_sum'] ) ? (int) $row['rating_sum'] : 0;

	return array(
		'count'   => $count,
		'average' => $count > 0 ? round( $sum / $count, 1 ) : 0.0,
	);
}

/**
 * Latest engagement activity for a user on an item (Top Content engagers column).
 *
 * @param int    $item_id Item ID.
 * @param int    $user_id User ID.
 * @param string $type    Content type slug.
 * @return array|null
 */
function wp_ulike_pro_get_engagement_user_latest_activity( $item_id, $user_id, $type ) {
	global $wpdb;

	$table = wp_ulike_pro_engagement_table();
	if ( empty( $table ) ) {
		return null;
	}

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT date_time, engagement_kind, engagement_key, value, status, country_code, device
			FROM `{$table}`
			WHERE item_id = %d AND item_type = %s AND user_id = %d
			ORDER BY id DESC LIMIT 1",
			$item_id,
			sanitize_key( $type ),
			$user_id
		),
		ARRAY_A
	);

	if ( empty( $row ) ) {
		return null;
	}

	if ( ! empty( $row['date_time'] ) ) {
		$row['date_time'] = wp_ulike_date_i18n( $row['date_time'] );
	}

	if ( 'emoji' === $row['engagement_kind'] && class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
		$reaction = WP_Ulike_Pro_Engagement_Registry::get_reaction( $row['engagement_key'], $type );
		$row['status'] = $reaction ? $reaction['emoji'] . ' ' . wp_strip_all_tags( $reaction['label'] ) : $row['engagement_key'];
		if ( $reaction ) {
			$row['emoji']  = $reaction['emoji'];
			$row['label']  = wp_strip_all_tags( $reaction['label'] );
		}
	} elseif ( 'star' === $row['engagement_kind'] && ! empty( $row['value'] ) ) {
		$row['status'] = sprintf( '★ %d', (int) $row['value'] );
		$row['value']  = (int) $row['value'];
	} else {
		$row['status'] = (string) $row['status'];
	}

	return wp_ulike_pro_normalize_engager_activity( $row, $type );
}

/**
 * Logged-in engager user IDs for tops avatar stacks.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @param int    $limit     Max users.
 * @return int[]
 */
function wp_ulike_pro_get_engagement_engager_user_ids( $item_id, $item_type, $limit = 12 ) {
	if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Engagers' ) ) {
		return array();
	}

	$engagers = WP_Ulike_Pro_Engagement_Engagers::get_engagers( $item_id, $item_type, null, $limit );

	return array_map(
		static function ( $engager ) {
			return (int) $engager['user_id'];
		},
		$engagers
	);
}

/**
 * Registered voters (active like OR dislike) for one item.
 *
 * Unlike rebuild_likers_list() (likes-only, includes guest ip2long IDs), this is
 * members-only and includes dislikes — what the Engaged Users stats page needs.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type post|comment|activity|topic.
 * @param int    $limit     Max users.
 * @return int[]
 */
function wp_ulike_pro_get_item_voter_user_ids( $item_id, $item_type, $limit = 12 ) {
	global $wpdb;

	$item_id   = absint( $item_id );
	$item_type = sanitize_key( (string) $item_type );
	$limit     = max( 1, absint( $limit ) );

	if ( ! $item_id || ! $item_type ) {
		return array();
	}

	$users_table = $wpdb->users;
	$ids         = array();

	if ( class_exists( 'WP_Ulike_Pulse_Schema' ) && class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
		$pulse = WP_Ulike_Pulse_Schema::table();
		// read_mode() returns legacy|merged|pulse — never "dual".
		$mode  = class_exists( 'WP_Ulike_Pulse_Query' ) ? WP_Ulike_Pulse_Query::read_mode() : 'pulse';

		if ( in_array( $mode, array( 'pulse', 'merged' ), true ) && ! empty( $pulse ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT CAST(p.user_id AS UNSIGNED)
					FROM `{$pulse}` p
					INNER JOIN (
						SELECT MAX(id) AS max_id
						FROM `{$pulse}`
						WHERE item_id = %d AND item_type = %s AND engagement_kind = %s
						GROUP BY user_id
					) latest ON p.id = latest.max_id
					INNER JOIN `{$users_table}` u ON u.ID = CAST(p.user_id AS UNSIGNED)
					WHERE p.status = %s AND p.engagement_key IN (%s, %s)
					ORDER BY p.date_time DESC
					LIMIT %d",
					$item_id,
					$item_type,
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					WP_Ulike_Pulse_Vote_Map::ROW_ACTIVE,
					WP_Ulike_Pulse_Vote_Map::KEY_LIKE,
					WP_Ulike_Pulse_Vote_Map::KEY_DISLIKE,
					$limit
				)
			);
		}

		// Legacy / dual: also pull registered voters from the classic log table.
		if ( 'pulse' !== $mode ) {
			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( ! empty( $source['table'] ) && ! empty( $source['column'] ) ) {
				$table  = $source['table'];
				$column = esc_sql( $source['column'] );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$legacy_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT CAST(p.user_id AS UNSIGNED)
						FROM `{$table}` p
						INNER JOIN (
							SELECT MAX(id) AS max_id
							FROM `{$table}`
							WHERE `{$column}` = %d
							GROUP BY user_id
						) latest ON p.id = latest.max_id
						INNER JOIN `{$users_table}` u ON u.ID = CAST(p.user_id AS UNSIGNED)
						WHERE p.status IN (%s, %s)
						ORDER BY p.date_time DESC
						LIMIT %d",
						$item_id,
						WP_Ulike_Pulse_Vote_Map::ACTION_LIKE,
						WP_Ulike_Pulse_Vote_Map::ACTION_DISLIKE,
						$limit
					)
				);
				$ids = array_unique( array_merge( (array) $ids, (array) $legacy_ids ) );
			}
		}
	} elseif ( class_exists( 'WP_Ulike_Pro_Pulse_Reader' ) ) {
		// Older Free without Pulse helpers — likes-only fallback.
		$ids = WP_Ulike_Pro_Pulse_Reader::rebuild_likers_list( $item_id, $item_type, $limit );
	}

	$ids = array_values(
		array_filter(
			array_map( 'absint', (array) $ids ),
			static function ( $id ) {
				return $id > 0;
			}
		)
	);

	return array_slice( $ids, 0, $limit );
}

/**
 * Whether any public content type uses emoji or star engagement.
 *
 * @return bool
 */
function wp_ulike_pro_site_has_engagement_voting() {
	foreach ( array( 'post', 'comment', 'activity', 'topic' ) as $type ) {
		$mode = wp_ulike_pro_get_engagement_mode_for_type( $type );
		if ( in_array( $mode, array( 'emoji', 'star' ), true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Normalize engager activity for stats UI (classic + engagement table).
 *
 * @param array|null $activity  Raw activity row.
 * @param string     $item_type Content type slug.
 * @return array|null
 */
function wp_ulike_pro_normalize_engager_activity( $activity, $item_type = 'post' ) {
	if ( empty( $activity ) || ! is_array( $activity ) ) {
		return null;
	}

	$normalized = array(
		'date_time'    => isset( $activity['date_time'] ) ? (string) $activity['date_time'] : '',
		'status'       => isset( $activity['status'] ) ? (string) $activity['status'] : '',
		'country_code' => isset( $activity['country_code'] ) ? (string) $activity['country_code'] : '',
		'device'       => isset( $activity['device'] ) ? (string) $activity['device'] : '',
	);

	if ( ! empty( $activity['status_key'] ) ) {
		$normalized['status_key'] = sanitize_key( (string) $activity['status_key'] );
	}

	if ( ! empty( $activity['engagement_kind'] ) ) {
		$kind = sanitize_key( $activity['engagement_kind'] );
		$normalized['engagement_kind'] = $kind;

		if ( 'emoji' === $kind ) {
			$key = isset( $activity['engagement_key'] ) ? (string) $activity['engagement_key'] : '';
			$normalized['engagement_key'] = $key;
			if ( class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) && $key ) {
				$reaction = WP_Ulike_Pro_Engagement_Registry::get_reaction( $key, $item_type );
				if ( $reaction ) {
					$normalized['emoji'] = $reaction['emoji'];
					$normalized['label'] = wp_strip_all_tags( $reaction['label'] );
				}
			}
		}

		if ( 'star' === $kind && isset( $activity['value'] ) ) {
			$normalized['value'] = (int) $activity['value'];
		}
	}

	return $normalized;
}

/**
 * Count unique registered engagers for one item (votes ∪ emoji ∪ star).
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type post|comment|activity|topic.
 * @return int
 */
function wp_ulike_pro_count_item_unique_engagers( $item_id, $item_type ) {
	$item_id   = absint( $item_id );
	$item_type = sanitize_key( $item_type );
	if ( ! $item_id || ! $item_type ) {
		return 0;
	}

	$pool = 5000;
	$ids  = array_unique(
		array_merge(
			wp_ulike_pro_get_item_voter_user_ids( $item_id, $item_type, $pool ),
			wp_ulike_pro_get_engagement_engager_user_ids( $item_id, $item_type, $pool )
		)
	);

	return count(
		array_filter(
			array_map( 'absint', $ids ),
			static function ( $id ) {
				return $id > 0;
			}
		)
	);
}

/**
 * Build engaged-users list for Top Content rows.
 *
 * @param int    $item_id    Item ID.
 * @param string $item_type  Content type slug.
 * @param string $mode       none|emoji|star.
 * @param string $log_table  Classic log table key (ulike, ulike_comments, ...).
 * @param string $log_column Classic ID column.
 * @param int    $limit      Max users.
 * @param bool   $include_activity Whether to resolve per-user activity.
 * @return array<int,array<string,mixed>>
 */
function wp_ulike_pro_build_tops_engaged_users_list( $item_id, $item_type, $mode, $log_table, $log_column, $limit = 12, $include_activity = false ) {
	$item_id   = absint( $item_id );
	$item_type = sanitize_key( $item_type );
	unset( $mode, $log_table, $log_column );

	// Members-only pool already filtered in SQL (wp_users join). Fetch a bit over
	// `$limit` so emoji/star union can still fill gaps without a huge guest scan.
	$fetch_limit = max( (int) $limit * 3, (int) $limit );

	// Vote engagers (like + dislike) via item_type — never derive type from a
	// possibly-prefixed log table (API used to mis-map comments → posts).
	// Union with emoji/star engagers for display-automation / historical rows.
	$users = array_unique(
		array_merge(
			wp_ulike_pro_get_item_voter_user_ids( $item_id, $item_type, $fetch_limit ),
			wp_ulike_pro_get_engagement_engager_user_ids( $item_id, $item_type, $fetch_limit )
		)
	);

	$list = array();

	foreach ( $users as $raw_user_id ) {
		if ( count( $list ) >= $limit ) {
			break;
		}

		$user_id   = absint( $raw_user_id );
		$user_info = $user_id ? get_user_by( 'id', $user_id ) : false;
		if ( ! $user_info ) {
			continue;
		}

		$list[] = array(
			'id'       => $user_id,
			'name'     => esc_attr( $user_info->display_name ),
			'avatar'   => get_avatar_url( $user_info->user_email, array( 'size' => 48 ) ),
			'role'     => translate_user_role( $user_info->roles[0] ?? esc_html__( 'Guest User', WP_ULIKE_PRO_DOMAIN ) ),
			// Activity labels are expensive (per-user queries). Skip on the tops
			// list; Engaged Users page passes $include_activity = true.
			'activity' => $include_activity
				? wp_ulike_pro_resolve_tops_user_activity( $item_id, $user_id, $item_type )
				: null,
		);
	}

	return $list;
}

/**
 * Tops UI: prefer newer of vote vs engagement activity for one user+item.
 *
 * @param int    $item_id   Item ID.
 * @param int    $user_id   User ID.
 * @param string $item_type Content type slug.
 * @return array|null
 */
function wp_ulike_pro_resolve_tops_user_activity( $item_id, $user_id, $item_type ) {
	$item_id   = absint( $item_id );
	$user_id   = absint( $user_id );
	$item_type = sanitize_key( $item_type );

	$vote_ts = 0;
	if ( class_exists( 'WP_Ulike_Pulse_Query' ) ) {
		$vote_row = WP_Ulike_Pulse_Query::get_user_latest_activity( $item_id, $user_id, $item_type );
		if ( $vote_row && ! empty( $vote_row->date_time ) ) {
			$vote_ts = (int) strtotime( (string) $vote_row->date_time );
		}
	}

	$eng_ts = 0;
	$table  = wp_ulike_pro_engagement_table();
	if ( $table ) {
		global $wpdb;
		$eng_date = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT date_time FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND user_id = %d
				ORDER BY id DESC LIMIT 1",
				$item_id,
				$item_type,
				$user_id
			)
		);
		if ( $eng_date ) {
			$eng_ts = (int) strtotime( (string) $eng_date );
		}
	}

	if ( $eng_ts > $vote_ts ) {
		return wp_ulike_pro_get_engagement_user_latest_activity( $item_id, $user_id, $item_type );
	}

	if ( $vote_ts > 0 ) {
		return wp_ulike_pro_normalize_engager_activity(
			WP_Ulike_Pro_Pulse_Reader::get_user_latest_activity( $item_id, $user_id, $item_type ),
			$item_type
		);
	}

	return null;
}

/**
 * Emoji reaction breakdown for a single tops item.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @param string $period    Period key.
 * @param int    $limit     Max reactions.
 * @return array<int,array{key:string,emoji:string,label:string,total:int}>
 */
function wp_ulike_pro_get_tops_item_emoji_breakdown( $item_id, $item_type, $period = 'all', $limit = 5 ) {
	global $wpdb;

	$table = wp_ulike_pro_engagement_table();
	if ( empty( $table ) ) {
		return array();
	}

	$count_sql = wp_ulike_pro_engagement_count_expression( $item_type, 'emoji' );
	$where     = $wpdb->prepare(
		'item_id = %d AND item_type = %s AND engagement_kind = %s AND status = %s',
		absint( $item_id ),
		sanitize_key( $item_type ),
		'emoji',
		'active'
	);

	$period_sql = wp_ulike_pro_engagement_period_sql( $period );
	if ( $period_sql ) {
		$where .= ' ' . $period_sql;
	}

	$rows = $wpdb->get_results(
		"SELECT engagement_key, {$count_sql} AS total
		FROM `{$table}` AS e
		WHERE {$where}
		GROUP BY engagement_key
		ORDER BY total DESC
		LIMIT " . absint( $limit ),
		ARRAY_A
	);

	$breakdown = array();
	foreach ( (array) $rows as $row ) {
		$key   = (string) $row['engagement_key'];
		$entry = array(
			'key'   => $key,
			'total' => (int) $row['total'],
			'emoji' => '',
			'label' => $key,
		);

		if ( class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
			$reaction = WP_Ulike_Pro_Engagement_Registry::get_reaction( $key, $item_type );
			if ( $reaction ) {
				$entry['emoji'] = $reaction['emoji'];
				$entry['label'] = wp_strip_all_tags( $reaction['label'] );
			}
		}

		$breakdown[] = $entry;
	}

	return $breakdown;
}

/**
 * Build engagement block + optional emoji breakdown for tops API rows.
 *
 * @param array  $metrics           From wp_ulike_pro_get_tops_item_metrics().
 * @param int    $item_id           Item ID.
 * @param string $item_type         Content type slug.
 * @param string $period            Period key.
 * @param float  $engagement_rate   Rate percentage.
 * @param float  $engagement_growth Growth percentage.
 * @return array{engagement:array,emoji_breakdown:array}
 */
function wp_ulike_pro_build_tops_engagement_payload( $metrics, $item_id, $item_type, $period, $engagement_rate, $engagement_growth ) {
	$engagement = array();

	if ( $engagement_rate > 0 ) {
		$engagement['rate']   = round( (float) $engagement_rate, 2 );
		$engagement['growth'] = $engagement_growth;
	}

	// Always expose star average/count when star rows exist, regardless of
	// the type's current template mode (historical / display automation).
	if ( ! empty( $metrics['star_count'] ) ) {
		$engagement['star_average'] = (float) $metrics['star_average'];
		$engagement['star_count']   = (int) $metrics['star_count'];
	}

	if ( ! empty( $metrics['emoji_count'] ) ) {
		$engagement['emoji_count'] = (int) $metrics['emoji_count'];
	}

	// Data-driven display mode for badges/insights (not template mode):
	// prefer star when star rows exist, else emoji when reactions exist.
	if ( ! empty( $metrics['star_count'] ) ) {
		$engagement['mode'] = 'star';
	} elseif ( ! empty( $metrics['emoji_count'] ) ) {
		$engagement['mode'] = 'emoji';
	}

	$payload = array(
		'engagement'      => $engagement,
		'emoji_breakdown' => array(),
	);

	// Always populate emoji breakdown when emoji rows exist, regardless of
	// the type's current template mode, so historical / display-automation
	// emoji reactions render in the tops table.
	if ( ! empty( $metrics['emoji_count'] ) ) {
		$payload['emoji_breakdown'] = wp_ulike_pro_get_tops_item_emoji_breakdown( $item_id, $item_type, $period );
	}

	return $payload;
}

/**
 * Exact, SQL-side combined engager ranking (votes + emoji + star): one row
 * per registered user, GROUP BY + SUM over a UNION of vote and engagement
 * events, sorted and paginated in SQL.
 *
 * This replaces the old "pull top-N per source, merge in PHP" approach for
 * the no-search path. That approach pulled the top-N vote users and top-N
 * engagement users *separately* (each sorted by its own single-source
 * score) then summed after merging -- a user ranked low in both individual
 * dimensions but with a high *combined* total could be excluded from both
 * pools and never surface, which produced empty/wrong deep pages. GROUP BY
 * over the full matching set has no such gap: every registered user with
 * any qualifying row is counted, regardless of page depth.
 *
 * Search is intentionally not handled here -- name/email matching needs a
 * second lookup against wp_users columns beyond ID, and search results are
 * rarely paginated deep enough for the pool approximation to matter; callers
 * should keep using the pool-based path for search requests.
 *
 * @param array $args period, status, order, limit, offset.
 * @return array<int,object>|null Rows with user_id, likeCount, emoji_count,
 *                                star_count, total_count. Null if no source
 *                                (vote or engagement) is available.
 */
function wp_ulike_pro_combined_engager_union_parts( $period, $status ) {
	if ( ! class_exists( 'WP_Ulike_Pulse_Query' ) || ! method_exists( 'WP_Ulike_Pulse_Query', 'vote_events_sql' ) ) {
		return null;
	}

	$parts = array();

	$vote_sql = WP_Ulike_Pulse_Query::vote_events_sql( $period, $status );
	if ( null !== $vote_sql ) {
		$parts[] = "SELECT user_id, 'vote' AS kind FROM ( {$vote_sql} ) AS vote_src";
	}

	$table = wp_ulike_pro_engagement_table();
	if ( ! empty( $table ) ) {
		$period_sql = wp_ulike_pro_engagement_period_sql( $period, 'e.date_time' );
		// Restrict to Pro-owned kinds (emoji + star). The engagement table IS the
		// pulse table, so without this filter every vote row would be emitted by
		// BOTH arms -- once by vote_events_sql() above and again here -- doubling
		// likeCount/total_count for anyone who has voted.
		$eng_where = "e.status = 'active' AND " . wp_ulike_pro_pulse_pro_kinds_sql( 'e.engagement_kind' );
		if ( $period_sql ) {
			$eng_where .= ' ' . $period_sql;
		}
		$parts[] = "SELECT CAST(e.user_id AS CHAR) AS user_id, e.engagement_kind AS kind FROM `{$table}` e WHERE {$eng_where}";
	}

	return empty( $parts ) ? null : $parts;
}

/**
 * Exact count of distinct registered users matching
 * wp_ulike_pro_combined_engager_union_parts() -- same UNION, same wp_users
 * join, so this total and get_combined_engager_rows()'s list can never
 * disagree (unlike the old approach, which derived the total from a
 * different, separately-approximated computation than the list itself).
 *
 * @param mixed        $period Period filter.
 * @param string|array $status Vote status filter.
 * @return int|null Null if no source (vote or engagement) is available.
 */
function wp_ulike_pro_count_combined_engager_rows( $period, $status ) {
	global $wpdb;

	$parts = wp_ulike_pro_combined_engager_union_parts( $period, $status );
	if ( null === $parts ) {
		return null;
	}

	$users = $wpdb->users;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return (int) $wpdb->get_var(
		'SELECT COUNT(DISTINCT combined.user_id) FROM ( ' . implode( ' UNION ALL ', $parts ) . " ) AS combined
		INNER JOIN `{$users}` u ON u.ID = CAST(combined.user_id AS UNSIGNED)"
	);
}

function wp_ulike_pro_get_combined_engager_rows( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'limit'  => 10,
		'offset' => 1,
		'period' => 'all',
		'status' => array( 'like', 'dislike' ),
		'order'  => 'DESC',
	);
	$args = wp_parse_args( $args, $defaults );

	$limit  = max( 1, absint( $args['limit'] ) );
	$offset = max( 1, absint( $args['offset'] ) );
	$order  = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

	$parts = wp_ulike_pro_combined_engager_union_parts( $args['period'], $args['status'] );
	if ( null === $parts ) {
		return null;
	}

	$users = $wpdb->users;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT combined.user_id,
				SUM(CASE WHEN combined.kind = 'vote' THEN 1 ELSE 0 END) AS likeCount,
				SUM(CASE WHEN combined.kind = 'emoji' THEN 1 ELSE 0 END) AS emoji_count,
				SUM(CASE WHEN combined.kind = 'star' THEN 1 ELSE 0 END) AS star_count,
				COUNT(*) AS total_count
			FROM ( " . implode( ' UNION ALL ', $parts ) . " ) AS combined
			INNER JOIN `{$users}` u ON u.ID = CAST(combined.user_id AS UNSIGNED)
			GROUP BY combined.user_id
			ORDER BY total_count {$order}
			LIMIT %d OFFSET %d",
			$limit,
			( $offset - 1 ) * $limit
		)
	);
}

/**
 * Top members ranked by vote + emoji + star activity (union, not XOR).
 *
 * @param array $args limit, offset, period, search, order, status.
 * @return array<int,object>
 */
function wp_ulike_pro_get_top_combined_engagers( $args = array() ) {
	$defaults = array(
		'limit'  => 10,
		'offset' => 1,
		'period' => 'all',
		'search' => '',
		'order'  => 'DESC',
		'status' => array( 'like', 'dislike' ),
	);
	$args     = wp_parse_args( $args, $defaults );

	$limit  = max( 1, absint( $args['limit'] ) );
	$offset = max( 1, absint( $args['offset'] ) );
	$order  = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
	$search = trim( (string) $args['search'] );

	// No search: exact SQL-side ranking, correct at every page depth (see
	// wp_ulike_pro_get_combined_engager_rows() docblock for why this
	// replaced the old top-N-per-source-then-merge-in-PHP approach, which
	// could produce empty/wrong deep pages).
	if ( '' === $search ) {
		$exact_rows = wp_ulike_pro_get_combined_engager_rows(
			array(
				'limit'  => $limit,
				'offset' => $offset,
				'period' => $args['period'],
				'status' => $args['status'],
				'order'  => $order,
			)
		);

		if ( null !== $exact_rows ) {
			return array_map(
				static function ( $row ) {
					return (object) array(
						'user_id'        => (string) $row->user_id,
						'likeCount'      => (int) $row->likeCount,
						'dislikeCount'   => 0,
						'unlikeCount'    => 0,
						'undislikeCount' => 0,
						'emoji_count'    => (int) $row->emoji_count,
						'star_count'     => (int) $row->star_count,
						'total_count'    => (int) $row->total_count,
					);
				},
				(array) $exact_rows
			);
		}
		// Fall through to the pool-based path below only if the exact query
		// could not run at all (e.g. an older Free version without
		// WP_Ulike_Pulse_Query::vote_events_sql() yet).
	}

	// Pull a wide pool from both ledgers, merge by user, then paginate.
	// Used for search (name/email matching needs a wp_users lookup beyond
	// ID, which this pool-then-filter approach already does) and as a
	// fallback if the exact path above was unavailable.
	$pool = '' !== $search
		? wp_ulike_pro_tops_search_pool_limit()
		: max( $limit * $offset + $limit, $limit * 5, 50 );

	$merged = array();

	$vote_rows = wp_ulike_get_best_likers_info(
		$pool,
		$args['period'],
		1,
		$args['status'],
		$order
	);
	foreach ( (array) $vote_rows as $row ) {
		$uid = absint( $row->user_id ?? 0 );
		if ( ! $uid ) {
			continue;
		}
		$sum = (int) ( $row->SumUser ?? $row->likeCount ?? 0 );
		$key = (string) $uid;
		if ( ! isset( $merged[ $key ] ) ) {
			$merged[ $key ] = (object) array(
				'user_id'        => $key,
				'likeCount'      => $sum,
				'dislikeCount'   => 0,
				'unlikeCount'    => 0,
				'undislikeCount' => 0,
				'emoji_count'    => 0,
				'star_count'     => 0,
				'total_count'    => $sum,
			);
		} else {
			$merged[ $key ]->likeCount   += $sum;
			$merged[ $key ]->total_count += $sum;
		}
	}

	$eng_rows = wp_ulike_pro_get_top_engagement_engagers(
		array(
			'limit'  => $pool,
			'offset' => 1,
			'period' => $args['period'],
			'order'  => $order,
		)
	);
	foreach ( (array) $eng_rows as $row ) {
		$uid = absint( $row->user_id ?? 0 );
		if ( ! $uid ) {
			continue;
		}
		$key = (string) $uid;
		$emoji = (int) ( $row->emoji_count ?? 0 );
		$star  = (int) ( $row->star_count ?? 0 );
		$total = (int) ( $row->total_count ?? ( $emoji + $star ) );
		if ( ! isset( $merged[ $key ] ) ) {
			$merged[ $key ] = (object) array(
				'user_id'        => $key,
				'likeCount'      => 0,
				'dislikeCount'   => 0,
				'unlikeCount'    => 0,
				'undislikeCount' => 0,
				'emoji_count'    => $emoji,
				'star_count'     => $star,
				'total_count'    => $total,
			);
		} else {
			$merged[ $key ]->emoji_count += $emoji;
			$merged[ $key ]->star_count  += $star;
			$merged[ $key ]->total_count += $total;
		}
	}

	$rows = array_values( $merged );

	if ( '' !== $search ) {
		$rows = wp_ulike_pro_filter_engagers_by_search( $rows, $search );
	}

	usort(
		$rows,
		static function ( $a, $b ) use ( $order ) {
			$av = (int) ( $a->total_count ?? 0 );
			$bv = (int) ( $b->total_count ?? 0 );
			if ( $av === $bv ) {
				return 0;
			}
			if ( 'ASC' === $order ) {
				return $av <=> $bv;
			}
			return $bv <=> $av;
		}
	);

	$start = ( $offset - 1 ) * $limit;

	return array_slice( $rows, $start, $limit );
}

/**
 * Unique engagers across votes + emoji + star.
 *
 * @param mixed $period Period key or range.
 * @param array $status Vote status filter for the classic ledger.
 * @return int
 */
function wp_ulike_pro_count_top_combined_engagers( $period = 'all', $status = array( 'like', 'dislike' ) ) {
	global $wpdb;

	// Exact path: same UNION + wp_users join as get_combined_engager_rows(),
	// so this total and the actual ranked list can never disagree.
	$exact = wp_ulike_pro_count_combined_engager_rows( $period, $status );
	if ( null !== $exact ) {
		return $exact;
	}

	// Fallback below only if the exact path was unavailable (e.g. an older
	// Free version without WP_Ulike_Pulse_Query::vote_events_sql() yet).
	// Free count is registered WordPress users only (guest ip2long excluded).
	$vote_total = (int) wp_ulike_get_top_enagers_total_number( $period, $status );

	$table = wp_ulike_pro_engagement_table();
	if ( empty( $table ) ) {
		return $vote_total;
	}

	$users      = $wpdb->users;
	$period_sql = wp_ulike_pro_engagement_period_sql( $period, 'e.date_time' );
	$eng_where  = "e.status = 'active'";
	if ( $period_sql ) {
		$eng_where .= ' ' . $period_sql;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$eng_count = (int) $wpdb->get_var(
		"SELECT COUNT(DISTINCT e.user_id) FROM `{$table}` e
		INNER JOIN `{$users}` u ON u.ID = CAST(e.user_id AS UNSIGNED)
		WHERE {$eng_where}"
	);

	if ( $eng_count <= 0 ) {
		return $vote_total;
	}

	$mode = class_exists( 'WP_Ulike_Pulse_Config' ) ? WP_Ulike_Pulse_Config::read_mode() : 'pulse';
	if ( in_array( $mode, array( 'pulse', 'merged' ), true ) && class_exists( 'WP_Ulike_Pulse_Schema' ) ) {
		$pulse        = WP_Ulike_Pulse_Schema::table();
		$since        = ( 'merged' === $mode && class_exists( 'WP_Ulike_Pulse_Config' ) )
			? $wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() )
			: '';
		$period_limit = wp_ulike_get_period_limit_sql( $period );

		// Exact registered-user union of pulse votes + emoji/star engagers.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$union_total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM (
				SELECT DISTINCT CAST(p.user_id AS CHAR) AS user_id
				FROM `{$pulse}` p
				INNER JOIN `{$users}` u ON u.ID = CAST(p.user_id AS UNSIGNED)
				WHERE p.engagement_kind = 'vote' AND p.status IN ('active','removed')
				{$since} {$period_limit}
				UNION
				SELECT DISTINCT CAST(e.user_id AS CHAR) AS user_id
				FROM `{$table}` e
				INNER JOIN `{$users}` u2 ON u2.ID = CAST(e.user_id AS UNSIGNED)
				WHERE {$eng_where}
			) AS combined_engagers"
		);

		if ( 'merged' === $mode ) {
			// Free vote_total already unions legacy + pulse registered voters.
			// Add engagement-only users not present in the vote ledger count by
			// taking max(vote_total, pulse∪eng) — under-counts engagement-only
			// users who only appear on legacy is impossible (eng is pulse-only).
			return max( $vote_total, $union_total );
		}

		return $union_total;
	}

	return max( $vote_total, $eng_count );
}

/**
 * Top members ranked by engagement-table activity (emoji + star).
 *
 * @param array $args limit, offset, period, search, order.
 * @return array<int,object>
 */
function wp_ulike_pro_get_top_engagement_engagers( $args = array() ) {
	global $wpdb;

	$table = wp_ulike_pro_engagement_table();
	if ( empty( $table ) ) {
		return array();
	}

	$defaults = array(
		'limit'  => 10,
		'offset' => 1,
		'period' => 'all',
		'search' => '',
		'order'  => 'DESC',
	);
	$args     = wp_parse_args( $args, $defaults );

	$limit  = max( 1, absint( $args['limit'] ) );
	$offset = max( 1, absint( $args['offset'] ) );
	$order  = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

	$users      = $wpdb->users;
	$period_sql = wp_ulike_pro_engagement_period_sql( $args['period'], 'e.date_time' );
	$where      = "e.status = 'active'";
	if ( $period_sql ) {
		$where .= ' ' . $period_sql;
	}

	$sql = "
		SELECT e.user_id,
			COUNT(*) AS total_count,
			SUM(CASE WHEN e.engagement_kind = 'emoji' THEN 1 ELSE 0 END) AS emoji_count,
			SUM(CASE WHEN e.engagement_kind = 'star' THEN 1 ELSE 0 END) AS star_count,
			MAX(e.date_time) AS last_activity
		FROM `{$table}` e
		INNER JOIN `{$users}` u ON u.ID = CAST(e.user_id AS UNSIGNED)
		WHERE {$where}
		GROUP BY e.user_id
		ORDER BY total_count {$order}
		LIMIT %d OFFSET %d";

	$rows = $wpdb->get_results(
		$wpdb->prepare( $sql, $limit, ( $offset - 1 ) * $limit ),
		OBJECT
	);

	if ( '' !== trim( (string) $args['search'] ) && ! empty( $rows ) ) {
		$search = strtolower( trim( (string) $args['search'] ) );
		$rows   = array_values(
			array_filter(
				$rows,
				static function ( $row ) use ( $search ) {
					$user = get_userdata( (int) $row->user_id );
					if ( ! $user ) {
						return false;
					}
					return false !== strpos( strtolower( $user->display_name ), $search )
						|| false !== strpos( strtolower( $user->user_login ), $search );
				}
			)
		);
	}

	return $rows;
}

