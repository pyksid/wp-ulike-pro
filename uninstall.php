<?php
/**
 * Uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Uninstall class
 *
 * @class wp_ulike_pro_uninstall
 * @since 1.0.0
 */
class wp_ulike_pro_uninstall {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		if ( is_multisite() ) {
			$this->uninstall_sites();
		} else {
			$this->uninstall_site();
		}
	}

	/**
	 * Process uninstall on each sites (multisite)
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function uninstall_sites() {

		global $wpdb;

		// Save current blog ID.
		$current  = $wpdb->blogid;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

		// Create tables for each blog ID.
		foreach ( $blog_ids as $blog_id ) {

			switch_to_blog( $blog_id );
			$this->uninstall_site();

		}

		// Go back to current blog.
		switch_to_blog( $current );

	}

	/**
	 * Process uninstall on current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function uninstall_site() {
		/*
		* Only remove ALL data if WP_ULIKE_REMOVE_ALL_DATA constant is set to true in user's
		* wp-config.php. This is to prevent data loss when deleting the plugin from the backend
		* and to ensure only the site owner can perform this action.
		*/
		if ( defined( 'WP_ULIKE_REMOVE_ALL_DATA' ) && true === WP_ULIKE_REMOVE_ALL_DATA ) {
			$this->drop_tables();
			$this->delete_pages();
			$this->delete_meta();
			$this->delete_transients();
			$this->delete_options();
		}
	}

	/**
	 * Drop plugin custom tables from current site
	 *
	 * @access public
	 */
	public function drop_tables() {

		global $wpdb;

		$wpdb->query(
			"DROP TABLE IF EXISTS
			{$wpdb->prefix}ulike_sessions"
		);

	}

	/**
	 * Delete plugin metadata from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_meta() {

		global $wpdb;

        // posts
		$wpdb->query( "DELETE from $wpdb->postmeta WHERE meta_key IN ('like_amount','dislike_amount','net_votes','likes_counter_quantity','dislikes_counter_quantity')" );
		$wpdb->query( "DELETE from $wpdb->postmeta WHERE meta_key LIKE '%wp_ulike_pro%'" );

        // comments
        $wpdb->query( "DELETE from $wpdb->commentmeta WHERE meta_key IN ('like_amount','dislike_amount','net_votes','likes_counter_quantity','dislikes_counter_quantity')" );
		$wpdb->query( "DELETE from $wpdb->commentmeta WHERE meta_key LIKE '%wp_ulike_pro%'" );

        // usermeta
        $wpdb->query( "DELETE from $wpdb->usermeta WHERE meta_key LIKE '%ulp_%'" );

	}

	/**
	 * Delete plugin transients from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_transients() {

		global $wpdb;

		// Delete all plugin metadata.
		$wpdb->query( "DELETE from $wpdb->options WHERE option_name LIKE '_transient_wp_ulike_pro%'" );
	}

	/**
	 * Delete plugin options from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_options() {

		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%wp_ulike_pro%';" );

	}

	/**
	 * Delete plugin pages from current site
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function delete_pages() {

        $pages = get_posts( array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_wp_ulike_pro_core',
                    'compare' => 'EXISTS',
                )
            )
        ) );
        if( ! empty( $pages ) ) {
            foreach ( $pages as $page ) {
                // Delete all products.
                wp_delete_post( $page->ID, true ); // Set to False if you want to send them to Trash.
            }
        }

	}
}

new wp_ulike_pro_uninstall();