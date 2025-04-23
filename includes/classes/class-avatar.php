<?php
/**
 * Avatar
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_Avatar {
	public $options;

	public function __construct( $atts = array() ){
		$this->options = array(
			'use_gravatars'  => wp_ulike_get_option( 'use_gravatars', true ),
			'default_avatar' => wp_ulike_get_option( 'default_avatar', true )
		);
		// Add filter on get avatar data
        add_filter( 'pre_get_avatar_data', array( $this, 'get_avatar_data' ), 10, 2 );
    }

	/**
	 * Retrieve the local avatar for a user who provided a user ID, email address or post/comment object.
	 *
	 * @param string            $avatar      Avatar return by original function
	 * @param int|string|object $id_or_email A user ID, email address, or post/comment object
	 * @param int               $size        Size of the avatar image
	 * @param string            $default     URL to a default image to use if no avatar is available
	 * @param string            $alt         Alternative text to use in image tag. Defaults to blank
	 * @param array             $args        Optional. Extra arguments to retrieve the avatar.
	 *
	 * @return string <img> tag for the user's avatar
	 */
	public static function get_avatar( $id_or_email = '', $size = 96, $default = '', $alt = '', $args = array() ) {
		return apply_filters( 'wp_ulike_local_avatar', get_avatar( $id_or_email, $size, $default, $alt, $args ) );
	}

	/**
	 * Get avatar uploader field
	 *
	 * @param integer $user_id
	 * @param array $args
	 * @return string
	 */
	public static function get_avatar_uploader( $user_id = '', $args = array() ){
		// Check user id
		if( empty( $user_id ) ){
			$user_id = get_current_user_id();
		}
		// Get avatar url
		$avatar_url  = get_avatar_url( $user_id, $args );
		// Fetch local avatar from meta and make sure it's properly set.
		$avatar_data = get_user_meta( $user_id, 'ulp_avatar_data', true );
		// Return avatar upload box
		return sprintf('<input class="ulp_avatar_upload_field" type="file" name="files" data-fileuploader-default="%s" data-fileuploader-files=\'%s\'>', $avatar_url, ! empty( $avatar_data ) ? wp_json_encode( array ( $avatar_data ) ) : '');
	}

	/**
	 * Filter avatar data early to add avatar url if needed. This filter hooks
	 * before Gravatar setup to prevent wasted requests.
	 *
	 * @param array $args        Arguments passed to get_avatar_data(), after processing.
	 * @param mixed $id_or_email The Gravatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
	 *                           user email, WP_User object, WP_Post object, or WP_Comment object.
	 */
	public function get_avatar_data( $args, $id_or_email ) {
		if ( ! empty( $args['force_default'] ) ) {
			return $args;
        }


		$simple_local_avatar_url = $this->get_avatar_url( $id_or_email, $args['size'] );
		if ( $simple_local_avatar_url ) {
			$args['url'] = $simple_local_avatar_url;
		}

		// Local only mode
		if ( empty( $simple_local_avatar_url ) && ! $this->options['use_gravatars'] ) {
			$args['url'] = $this->get_default_avatar_url( $args['size'] );
		}

		if ( ! empty( $args['url'] ) ) {
			$args['found_avatar'] = true;
		}

		return $args;
    }

	/**
	 * Get default avatar url
	 *
	 * @param int $size Requested avatar size.
	 */
	public function get_default_avatar_url( $size ) {
		// Default
		$default = '';

		// Check default avatar
		if( ! empty( $this->options['default_avatar']['url']  ) ){
			return $this->options['default_avatar']['url'];
		}

		if ( empty( $default ) ) {
			$avatar_default = get_option( 'avatar_default' );
			if ( empty( $avatar_default ) ) {
				$default = 'mystery';
			} else {
				$default = $avatar_default;
			}
		}

		$host = is_ssl() ? 'https://secure.gravatar.com' : 'http://0.gravatar.com';

		if ( 'mystery' === $default ) {
			$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
		} elseif ( 'blank' === $default ) {
			$default = includes_url( 'images/blank.gif' );
		} elseif ( 'gravatar_default' === $default ) {
			$default = "$host/avatar/?s={$size}";
		} else {
			$default = "$host/avatar/?d=$default&amp;s={$size}";
		}

		return $default;
	}

	/**
	 * Get local avatar url.
	 *
	 * @param mixed $id_or_email The Gravatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
	 *                           user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param int   $size        Requested avatar size.
	 */
	public function get_avatar_url( $id_or_email, $size ) {
		// Check option active status.
		if( ! WP_Ulike_Pro_Options::isLocalAvatars() ){
			return false;
		}

		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( is_string( $id_or_email ) && ( $user = get_user_by( 'email', $id_or_email ) ) ) {
			$user_id = $user->ID;
		} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
			$user_id = (int) $id_or_email->user_id;
		} elseif ( $id_or_email instanceof WP_Post && ! empty( $id_or_email->post_author ) ) {
			$user_id = (int) $id_or_email->post_author;
        }

		if ( empty( $user_id ) ) {
			return '';
		}

		// Fetch local avatar from meta and make sure it's properly set.
        $local_avatars = get_user_meta( $user_id, 'ulp_avatar', true );
		if ( empty( $local_avatars['url'] ) ) {
			return '';
        }

        // check rating
        $avatar_rating = get_user_meta( $user_id, 'ulp_avatar_rating', true );
		if ( ! empty( $avatar_rating ) && 'G' !== $avatar_rating && ( $site_rating = get_option( 'avatar_rating' ) ) ) {
			$ratings              = array( 'G', 'PG', 'R', 'X' );
			$site_rating_weight   = array_search( $site_rating, $ratings );
			$avatar_rating_weight = array_search( $avatar_rating, $ratings );
			if ( false !== $avatar_rating_weight && $avatar_rating_weight > $site_rating_weight ) {
				return '';
			}
        }

		// handle "real" media
		if ( ! empty( $local_avatars['id'] ) ) {
			// has the media been deleted?
			if ( ! $avatar_full_path = get_attached_file( $local_avatars['id'] ) ) {
				return '';
			}
        }

        $size = (int) $size;

		// Generate a new size.
		if ( ! array_key_exists( $size, $local_avatars ) ) {
			$local_avatars[ $size ] = $local_avatars['url']; // just in case of failure elsewhere

			// allow automatic rescaling to be turned off
			if ( apply_filters( 'wp_ulike_pro_avatars_dynamic_resize', false ) ) :

				$upload_path = wp_upload_dir();

				// get path for image by converting URL, unless its already been set, thanks to using media library approach
				if ( ! isset( $avatar_full_path ) ) {
					$avatar_full_path = str_replace( $upload_path['baseurl'], $upload_path['basedir'], $local_avatars['url'] );
				}

				// generate the new size
				$editor = wp_get_image_editor( $avatar_full_path );
				if ( ! is_wp_error( $editor ) ) {
					$resized = $editor->resize( $size, $size, true );
					if ( ! is_wp_error( $resized ) ) {
						$dest_file = $editor->generate_filename();
						$saved     = $editor->save( $dest_file );
						if ( ! is_wp_error( $saved ) ) {
							$local_avatars[ $size ] = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $dest_file );
						}
					}
				}

				// save updated avatar sizes
				update_user_meta( $user_id, 'ulp_avatar', $local_avatars );

			endif;
		}

		if ( 'http' !== substr( $local_avatars[ $size ], 0, 4 ) ) {
			$local_avatars[ $size ] = home_url( $local_avatars[ $size ] );
		}

		return esc_url( $local_avatars[ $size ] );
	}


}