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
	$images_list = wp_ulike_pro_get_metabox_value( 'image_list', $post_ID );

	if( ! empty( $images_list ) ){
		$images_list = explode( ',', $images_list );
		foreach ($images_list as $key => $value) {
			$images_list[$key] = wp_get_attachment_url( $value );
		}
	}

	return $images_list;
}