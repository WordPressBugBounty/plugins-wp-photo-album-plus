<?php
/* wppa-audio.php
* Package: wp-photo-album-plus
*
* Contains all audio routines
* Version 9.0.00.007
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// Audio files support. Define supported filetypes.
global $wppa_supported_audio_extensions;
	$wppa_supported_audio_extensions = array( 'mp3', 'wav', 'ogg' );

// See if a photo has audio
// Returns array with all available file extensions or false if not audio
function wppa_has_audio( $id ) {
global $wppa_supported_audio_extensions;

	if ( ! $id ) return false;					// No id

	$ext = wppa_get_photo_item( $id, 'ext' );
	if ( $ext != 'xxx' ) return false;	// This is not a audio

	$result = array();
	$path = wppa_get_photo_path( $id, false );
	$raw_path = wppa_strip_ext( $path );
	foreach ( $wppa_supported_audio_extensions as $ext ) {
		if ( wppa_is_file( $raw_path.'.'.$ext ) ) {
			$result[$ext] = $ext;
		}
	}
	if ( empty( $result ) ) {
		return false;				// Its multimedia but not audio
	}

	return $result;
}

// Return the html for audio display
function wppa_get_audio_html( $args ) {

	// Audio enabled?
	if ( ! wppa_switch( 'enable_audio' ) ) {
		return '';
	}

	extract( wp_parse_args( (array) $args, array (
					'id'			=> 0,
					'width'			=> 0,
					'height' 		=> 0,
					'controls' 		=> true,
					'margin_top' 	=> 0,
					'margin_bottom' => 0,
					'tagid' 		=> 'audio-' . wppa ( 'mocc' ),
					'cursor' 		=> '',
					'events' 		=> '',
					'title' 		=> '',
					'onclick' 		=> '',
					'lb' 			=> false,
					'class' 		=> '',
					'style' 		=> '',
					'use_thumb' 	=> false,
					'autoplay' 		=> false
					) ) );

	// No id? no go
	if ( ! $id ) return '';

	// Not a audio? no go
	if ( ! wppa_has_audio( $id ) ) return '';

	extract( wp_parse_args( (array) wppa_has_audio( $id ), array (
					'mp3' 	=> false,
					'wav' 	=> false,
					'ogg' 	=> false
					) ) );

	// Prepare attributes
	if ( $width ) {
		if ( wppa_is_int( $width ) ) $width .= 'px;';
		$w = 'width:'.$width.';';
	}
	else {
		if ( wppa_is_chrome() ) {
			$w = 'width:-webkit-fill-available;';
		}
		elseif ( wppa_is_firefox() ) {
			$w = 'width:-moz-available;';
		}
		else {
			$w = 'width:auto;';
		}
	}
	$h 		= $height ? 'height:'.$height.'px;' : '';
	$t 		= $margin_top ? 'margin-top:'.$margin_top.'px;' : '';
	$b 		= $margin_bottom ? 'margin-bottom:'.$margin_bottom.'px;' : '';
	$ctrl 	= $controls ? ' controls' : '';

	$style 	= $style ? rtrim( trim( $style ), ';' ) . ';' : '';
	$play 	= $autoplay ? ' autoplay' : '';

	// Do we have html5 audio tag supported filetypes on board?
	if ( $mp3 || $wav || $ogg ) {

		// Assume the browser supports html5
		$attribs = ['id' => $tagid, 'data-from' => "wppa", 'class' => $class, 'style' => $style.$w.$h.$t.$b.$cursor, 'preload' => "metadata", 'title' => $title, 'onclick' => $onclick];
		if ( $controls ) $attribs['controls'] = 'controls';
		if ( $autoplay ) $attribs['autoplay'] = 'autoplay';
		$result = wppa_html_tag( 'audio', $attribs, wppa_get_audio_body( $id ) );
	}

	// Done
	return $result;
}

// Get the content of the audio tag for photo(audio)id = $id
function wppa_get_audio_body( $id ) {

	// Audio enabled?
	if ( ! wppa_switch( 'enable_audio' ) ) {
		return '';
	}

	$is_audio = wppa_has_audio( $id, true );

	// Not a audio? no go
	if ( ! $is_audio ) return '';

	// Find video url with no version and no extension
	$source = wppa_strip_ext( wppa_get_photo_url( $id, false ) );

	$result = '';
	foreach ( $is_audio as $ext ) {
		$result .= wppa_html_tag( 'source', ['src' => $source.'.'.$ext, 'type' => 'audio/'.$ext] );
	}
	$result .= esc_js( __( 'There is no filetype available for your browser, or your browser does not support html5 audio', 'wp-photo-album-plus' ) );

	return $result;
}

// Copy the files only
function wppa_copy_audio_files( $fromid, $toid ) {
global $wppa_supported_audio_extensions;

	// Is it an audio?
	if ( ! wppa_has_audio( $fromid ) ) return false;

	// Get paths
	$from_path 		= wppa_get_photo_path( $fromid, false );
	$raw_from_path 	= wppa_strip_ext( $from_path );
	$to_path 		= wppa_get_photo_path( $toid, false );
	$raw_to_path 	= wppa_strip_ext( $to_path );

	// Copy the media files
	foreach ( $wppa_supported_audio_extensions as $ext ) {
		$file = $raw_from_path . '.' . $ext;
		if ( wppa_is_file( $file ) ) {
			if ( ! wppa_copy( $file, $raw_to_path . '.' . $ext ) ) return false;
		}
	}

	// Done!
	return true;
}

function wppa_get_audio_control_height() {

	if ( ! wppa_user_agent() ) {
		$result = 24;
	}
	elseif ( strpos( wppa_user_agent(), 'Edge' ) ) {
		$result = 30;
	}
	elseif ( strpos( wppa_user_agent(), 'Firefox' ) ) {
		$result = 40;
	}
	elseif ( strpos( wppa_user_agent(), 'Chrome' ) ) {
		if ( wppa_is_mobile() ) {
			$result = 48;
		}
		else {
			$result = 32;
		}
	}
	elseif ( strpos( wppa_user_agent(), 'Safari' ) ) {
		$result = 16;
	}
	else {
		$result = 28;
	}

	return $result;
}
