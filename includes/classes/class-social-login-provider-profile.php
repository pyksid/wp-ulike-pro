<?php
/**
 * Social login provider profile
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
*/
defined( 'ABSPATH' ) or exit;

/**
 * Provider Profile class
 */
class WP_Ulike_Pro_Social_Login_Provider_Profile {


	/** @var string provider ID */
	private $provider_id;

	/** @var array user profile data */
	private $profile;

	/**
	 * Sets up a profile.
	 *
	 * @param string $provider_id provider id
	 * @param array $profile user profile data
	 */
	public function __construct( $provider_id, $profile ) {

		$this->provider_id = $provider_id;

		/**
		 * Filter provider's profile.
		 *
		 * Allows providers to normalize the profile before any processing.
		 *
		 * @param array $profile User's profile data from HybridAuth
		 * @param string $provider_id provider ID
		 */
		$this->profile = apply_filters( 'wp_ulike_pro_social_login_' . $provider_id . '_profile', $profile, $provider_id );
	}


	/**
	 * Get the provider ID for this profile
	 *
	 * @return string provider ID, e.g. 'facebook'
	 */
	public function get_provider_id() {
		return $this->provider_id;
	}


	/**
	 * Get the full profile returned by HybridAuth, transformed into associative
	 * array with snake_case keys (HA uses camelCase).
	 *
	 * @return array transformed profile
	 */
	public function get_full_profile() {
		return $this->profile;
	}


	/**
	 * Meta-method for returning profile data, currently:
	 *
	 * + identifier
	 * + web_site_url
	 * + profile_url
	 * + photo_url
	 * + display_name
	 * + description
	 * + first_name
	 * + last_name
	 * + gender
	 * + language
	 * + age
	 * + birth_day
	 * + birth_year
	 * + email
	 * + email_verified
	 * + phone
	 * + address
	 * + country
	 * + region
	 * + zip
	 *
	 * Providers may also provide additional properties, such as `username` (Facebook).
	 *
	 * sample usage:
	 *
	 * `$email = $profile->get_email()`
	 * @param string $method called method
	 * @param array $args method arguments
	 * @return string|bool
	 */
	public function __call( $method, $args ) {

		// get_* method
		if ( 0 === strpos( $method, 'get_' ) ) {

			$property = str_replace( 'get_', '', $method );

			return $this->get_profile_value( $property );
		}

		// has_* method
		if ( 0 === strpos( $method, 'has_' ) ) {

			$property = str_replace( 'has_', '', $method );

			return (bool) $this->get_profile_value( $property );
		}

		return null;
	}


	/**
	 * Get the specified profile info value or return an empty string if
	 * the specified info does not exist
	 *
	 * @param string $key key for profile info, e.g. `email`
	 * @return mixed property value or null if not defined
	 */
	private function get_profile_value( $key ) {

		if ( isset( $this->profile[ $key ] ) ) {

			return $this->profile[ $key ];

		} else {

			return null;
		}
	}


	/**
	 * Store user profile for the current provider on user meta
	 *
	 * Will only store the details if they are new or updated
	 *
	 * @param int $user_id
	 * @param bool $new_user
	 */
	public function update_user_profile( $user_id, $new_user ) {

		$profile_sha    = sha1( serialize( $this->get_full_profile() ) );
		$stored_profile = get_user_meta( $user_id, '_ulp_social_login_' . $this->get_provider_id() . '_profile', true );

		// do not update profile if it's already up do date
		if ( $stored_profile && sha1( serialize( $stored_profile ) ) === $profile_sha ) {
			return;
		}

		update_user_meta( $user_id, '_ulp_social_login_' . $this->get_provider_id() . '_profile',    $this->get_full_profile() );
		update_user_meta( $user_id, '_ulp_social_login_' . $this->get_provider_id() . '_identifier', $this->get_identifier() );

		// update avatar if provided
		// $this->update_user_profile_image( $user_id );

		// Only update user profile if this is not a new user
		if ( ! $new_user ) {
			$this->update_current_user_profile( $user_id );
		}

		// allow plugins to know when a user account is linked to a new provider
		if ( ! $stored_profile ) {
			do_action( 'wp_ulike_pro_social_login_user_account_linked', $user_id, $this->get_provider_id() );
		}
	}


	/**
	 * Update a user's profile based on the social profile
	 *
	 * @param int $user_id
	 */
	public function update_current_user_profile( $user_id ) {

		// Bail out if no user ID or profile
		if ( ! $user_id ) {
			return;
		}

		$user_data = get_userdata( $user_id );

		// Bail out if no user data was found
		if ( ! $user_data ) {
			return;
		}

		// Only update data that is not already present
		$update_data = array();

		if ( $this->has_first_name() && ! $user_data->first_name ) {
			$update_data['first_name'] = $this->get_first_name();
		}

		if ( $this->has_last_name() && ! $user_data->last_name ) {
			$update_data['last_name'] = $this->get_last_name();
		}

		if ( $this->has_email() && ! $user_data->email ) {
			$update_data['email'] = $this->get_email();
		}

		// Bail out if no data to update
		if ( empty( $update_data ) ) {
			return;
		}

		$update_data['ID'] = $user_id;

		wp_update_user( $update_data );
	}

	/**
	 * Update user's profile image (avatar)
	 * @param int $user_id
	 */
	// public function update_user_profile_image( $user_id ) {

	// 	if ( $image = $this->get_photo_url() ) {
	// 		update_user_meta( $user_id, 'ulp_avatar', array(
	// 			'url' => esc_url( $image )
	// 		) );
	// 	}
	// }
}
