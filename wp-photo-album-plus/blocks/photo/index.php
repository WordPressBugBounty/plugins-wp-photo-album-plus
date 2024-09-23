<?php
/**
 * WPPA photo block
 * Generates a block with the shortcode for a photo box
 *
 * Version: 8.5.01.002
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 */
function wp_photo_album_plus_photo_register_block() {

	if ( ! function_exists( 'register_block_type' ) ) {

		// Gutenberg is not active.
		return;
	}

	register_block_type( __DIR__ );

	wppa_set_script_translations( 'wp-photo-album-plus-photo-editor-script' );
}

add_action( 'init', 'wp_photo_album_plus_photo_register_block' );