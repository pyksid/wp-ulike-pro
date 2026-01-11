<?php

class WP_Ulike_Pro_User {

	public function __construct(){}

	/**
	 * Generate User Profile Slug and save to meta
	 *
	 * @param int $user_id
	 * @param bool $force
	 */
	function generate_profile_slug( $user_id, $force = false, $skip_check = false ) {
		$userdata = get_userdata( $user_id );

		if ( empty( $userdata ) ) {
			return;
		}

		$permalink_base = WP_Ulike_Pro_Options::getProfilePermalinkBase();

		// Get current slug - skip get_profile_slug() call if we're being called from it (to avoid recursion)
		if ( $skip_check ) {
			$current_profile_slug = get_user_meta( $user_id, "ulp_user_profile_url_slug_{$permalink_base}", true );
		} else {
			$current_profile_slug = $this->get_profile_slug( $user_id );
		}

		$user_in_url = '';
		// User ID
		if ( $permalink_base == 'user_id' ) {
			$user_in_url = $user_id;
		}

		// Username
		if ( $permalink_base == 'user_login' ) {
			// get user login
			$user_in_url = $userdata->user_login;
			// If is email
			if ( is_email( $user_in_url ) ) {
				$user_email  = $user_in_url;
				$user_in_url = WP_Ulike_Pro_Validation::extract_username_from_email( $user_in_url );
				update_user_meta( $user_id, "ulp_email_as_username_{$user_in_url}", $user_email );

			} else {
				$user_in_url = urlencode( $user_in_url );
			}
		}

		// Fisrt and Last name
		$full_name_permalinks = array( 'name', 'name_dash', 'name_plus' );
		if ( in_array( $permalink_base, $full_name_permalinks ) ) {
			$separated    = array( 'name' => '.', 'name_dash' => '-', 'name_plus' => '+' );
			$separate     = $separated[ $permalink_base ];
			$first_name   = $userdata->first_name;
			$last_name    = $userdata->last_name;
			$full_name    = trim( sprintf( '%s %s', $first_name, $last_name ) );
			$full_name    = preg_replace( '/\s+/', ' ', $full_name );                                      // Remove double spaces
			$profile_slug = WP_Ulike_Pro_Permalinks::profile_slug( $full_name, $first_name, $last_name );

			$append    = 0;
			$username  = $full_name;
			$_username = $full_name;

			while ( 1 ) {
				$username = $_username . ( empty( $append ) ? '' : " $append" );
				$slug_exists_user_id = WP_Ulike_Pro_Permalinks::slug_exists_user_id( $profile_slug . ( empty( $append ) ? '' : "{$separate}{$append}" ) );
				// SECURITY: Use strict comparison for user IDs
				if ( empty( $slug_exists_user_id ) || (int) $user_id === (int) $slug_exists_user_id ) {
					break;
				}
				$append++;
			}

			$user_in_url = WP_Ulike_Pro_Permalinks::profile_slug( $username, $first_name, $last_name );
			if ( empty( $user_in_url ) ) {
				$user_in_url = $userdata->user_login;

				if ( is_email( $user_in_url ) ) {
					$user_email  = $user_in_url;
					$user_in_url = WP_Ulike_Pro_Validation::extract_username_from_email( $user_in_url );
					update_user_meta( $user_id, "ulp_email_as_username_{$user_in_url}", $user_email );

				} else {
					$user_in_url = sanitize_title( $user_in_url );
				}
			}

			// Trim separator from ends of the slug
			$user_in_url = trim( $user_in_url, $separate );
		}

		$user_in_url = apply_filters( 'ulp_change_user_profile_slug', $user_in_url, $user_id );

		// Only save if we have a valid slug and conditions are met
		if ( ! empty( $user_in_url ) && ( $force || empty( $current_profile_slug ) || $current_profile_slug != $user_in_url ) ) {
			update_user_meta( $user_id, "ulp_user_profile_url_slug_{$permalink_base}", $user_in_url );
		}
	}

	/**
	 * @param $user_id
	 *
	 * @return bool|mixed
	 */
	function get_profile_slug( $user_id ) {
		// Permalink base
		$permalink_base = WP_Ulike_Pro_Options::getProfilePermalinkBase();
		$profile_slug   = get_user_meta( $user_id, "ulp_user_profile_url_slug_{$permalink_base}", true );

		// If slug doesn't exist, try to generate it first
		if ( empty( $profile_slug ) ) {
			// Try to generate the slug (skip_check = true to avoid recursion)
			$this->generate_profile_slug( $user_id, false, true );
			// Check again after generation
			$profile_slug = get_user_meta( $user_id, "ulp_user_profile_url_slug_{$permalink_base}", true );
		}

		// If still empty, fall back to user ID (except for user_login)
		if ( empty( $profile_slug ) ) {
			if ( $permalink_base != 'user_login' ) {
				$profile_slug = $user_id;
			}

			if ( empty( $profile_slug ) ) {
				return false;
			}
		}

		return $profile_slug;
	}

	/**
	 * Get user by id
	 *
	 * @param integer $user_id
	 * @return false|integer
	 */
	public static function user_exists_by_id( $user_id ) {
		$aux = get_userdata( absint( $user_id ) );
		if ( $aux == false ) {
			return false;
		} else {
			return $user_id;
		}
	}

	/**
	 * Get user by email
	 *
	 * @param string $slug
	 * @return false|integer
	 */
	public static function user_exists_by_email_as_username( $slug ) {

		$user_id = false;

		$ids = get_users( array( 'fields' => 'ID', 'meta_key' => 'ulp_email_as_username_' . $slug ) );
		if ( ! empty( $ids[0] ) ) {
			$user_id = $ids[0];
		}

		return $user_id;
	}

	/**
	 * @param $user_id
	 *
	 * @return bool|string
	 */
	function get_profile_link( $user_id ) {
		$profile_slug = $this->get_profile_slug( $user_id );

		if ( empty( $profile_slug ) ) {
			return false;
		}

		return WP_Ulike_Pro_Permalinks::profile_permalink( $profile_slug );
	}

	/**
	 * User exists by name
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public static function user_exists_by_name( $value ) {

		// Permalink base
		$permalink_base = WP_Ulike_Pro_Options::getProfilePermalinkBase();

		// Search by Profile Slug
		// Note: $value comes from URL and may be encoded. Slugs in DB are stored encoded.
		// We need to try both encoded and decoded comparisons for compatibility
		$args = array(
			'fields' => array( 'ID' ),
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'       =>  'ulp_user_profile_url_slug_' . $permalink_base,
					'value'     => $value, // Try exact match first (encoded)
					'compare'   => '=',
				),
				array(
					'key'       =>  'ulp_user_profile_url_slug_' . $permalink_base,
					// UTF-8: Try decoded comparison (for backward compatibility)
					'value'     => rawurldecode( $value ),
					'compare'   => '=',
				),
			),
		);

		$ids = new \WP_User_Query( $args );

		if ( $ids->total_users > 0 ) {
			$user_query = current( $ids->get_results() );
			return $user_query->ID;
		}

		// Validate query string
		$value = WP_Ulike_Pro_Validation::safe_name_in_url( $value );
		$value = wp_ulike_pro_clean_user_basename( $value );

		// Search by Display Name or ID
		$args = array(
			'fields'         => array( 'ID' ),
			'search'         => $value,
			'search_columns' => array( 'display_name', 'ID' ),
		);

		$ids = new \WP_User_Query( $args );

		if ( $ids->total_users > 0 ) {
			$user_query = current( $ids->get_results() );
			return $user_query->ID;
		}


		// Search By User Login
		$value = str_replace( ".", "_", $value );
		$value = str_replace( " ", "", $value );

		$args = array(
			'fields'            => array( 'ID' ),
			'search'            => $value,
			'search_columns'    => array(
				'user_login',
			)
		);

		$ids = new \WP_User_Query( $args );

		if ( $ids->total_users > 0 ) {
			$user_query = current( $ids->get_results() );
			return $user_query->ID;
		}

		return false;
	}

}