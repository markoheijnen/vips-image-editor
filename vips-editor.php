<?php
/*
 * 	Plugin Name: Vips Editor
 * 	Description: Enables Vips in WordPress
 * 	Version:     1.0
 * 	Plugin URI:  
 * 	Author:      Marko Heijnen
 * 	Author URI:  https://markoheijnen.com
 * 	Donate link: https://markoheijnen.com/donate
 */

function image_editors_add_vips( $editors ) {
	if ( ! class_exists('WP_Image_Editor_Vips') ) {
		include_once 'editors/vips.php';
	}

	if ( ! in_array( 'WP_Image_Editor_Vips', $editors ) ) {
		array_unshift( $editors, 'WP_Image_Editor_Vips' );
	}

	return $editors;
}
add_filter( 'wp_image_editors', 'image_editors_add_vips' );
