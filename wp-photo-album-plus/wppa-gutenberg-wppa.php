<?php
/* wppa-gutenberg-wppa.php
* Pachkage: wp-photo-album-plus
*
* Version 9.2.01.001
*/

if ( ! defined( 'ABSPATH' ) ) exit();

function wppa_gutenberg_wppa_block() {
global $wppa_version;

	// Gutenberg installed?
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

    wp_register_script( 'wppa-gutenberg-wppa', plugins_url( 'js/wppa-gutenberg-wppa.js', __FILE__ ), array( 'wp-blocks', 'wp-element' ), $wppa_version, true );

    register_block_type( 'wppa/gutenberg-wppa', array(
        'editor_script' => 'wppa-gutenberg-wppa',
    ) );
}
add_action( 'init', 'wppa_gutenberg_wppa_block' );
