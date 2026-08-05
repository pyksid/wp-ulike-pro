<?php
/**
 * General functions
 *
 * @echo HEADER
 */

/**
 * Get meta box images list in array
 *
 * @param string $post_ID
 * @return array|null
 */
function wp_ulike_pro_get_metabox_images_list( $post_ID = '' ){
	$images_list = wp_ulike_pro_get_metabox_value_raw( 'image_list', $post_ID );

	if ( empty( $images_list ) ) {
		return array();
	}

	// New format: array of image URLs from the schema tool media picker.
	if ( is_array( $images_list ) ) {
		return array_values( array_filter( array_map( 'esc_url_raw', $images_list ) ) );
	}

	// Legacy format: comma-separated attachment IDs.
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $images_list ) ) );
	$urls = array();

	foreach ( $ids as $id ) {
		$url = wp_get_attachment_url( $id );
		if ( $url ) {
			$urls[] = $url;
		}
	}

	return $urls;
}