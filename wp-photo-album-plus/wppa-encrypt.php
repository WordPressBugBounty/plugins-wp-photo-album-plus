<?php
/* wppa-encrypt.php
* Package: wp-photo-album-plus
*
* Contains all ecryption/decryption logic
* Version 9.1.05.002
*
*/

// Find a unique crypt
function wppa_get_unique_crypt() {
global $wpdb;

	$result = 0;
	while ( wppa_is_int( $result ) ) {
		$result = substr( md5( microtime( true ) ), wp_rand( 0, 16 ), 16 );
	}
	return $result;
}

// Convert photo id to crypt
function wppa_encrypt_photo( $id ) {

	// If enumeration, split
	if ( strpos( $id, '.' ) !== false ) {
		$ids = explode( '.', $id );
		foreach( array_keys( $ids ) as $key ) {
			if ( strlen( $ids[$key] ) ) {
				$ids[$key] = wppa_encrypt_photo( $ids[$key] );
			}
		}
		$crypt = implode( '.', $ids );
		return $crypt;
	}

	// Encrypt single item
	if ( wppa_is_posint( $id ) ) {
		$crypt = wppa_get_photo_item( $id, 'crypt' );
	}
	else {
		$crypt = $id; 	// Already encrypted
	}

	if ( ! $crypt ) {
		$crypt = 'yyyyyyyyyyyyyyyy';
	}
	return $crypt;
}

// Decode photo crypt to photo id
function wppa_decrypt_photo( $photo ) {
global $wpdb;

	// Assume single encrypted
	$result = wppa_get_var( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE crypt = %s", $photo ) );
	if ( wppa_is_posint( $result ) ) {
		return $result;
	}

	// Check for enum
	if ( $photo && strpos( $photo, '.' ) !== false ) {
		$photos = str_replace( '.', "','", $photo );
		$ids = wppa_get_col( stripslashes( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE crypt IN (%s)", $ids ) ) );
		if ( is_array( $ids ) ) {
			$result = implode( '.', $ids );
		}
		else {
			$result = false;
		}
		return $result;
	}

	// Check for zero
	if ( $photo == 0 ) return '';

	/* translators: integer photo id */
	wp_die( esc_html( sprintf( __( 'Invalid or outdated url. Media item id must be encrypted, %d given', 'wp-photo-album-plus' ), $photo ) ) );
}

// Convert album id to crypt
function wppa_encrypt_album( $album ) {

	// Encrypted album enumeration must always be expanded
	$album = wppa_expand_enum( $album );

	// Decompose possible album enumeration
	$album_ids 		= strpos( $album, '.' ) === false ? array( $album ) : explode( '.', $album );
	$album_crypts 	= array();
	$i 				= 0;

	// Process all tokens
	while ( $i < count( $album_ids ) ) {
		$id = $album_ids[$i];

		// Check for existance of album, otherwise return dummy
		if ( wppa_is_posint( $id ) && ! wppa_album_exists( $id ) ) {
			$id= '999999';
		}

		// Check for already encrypted
		if ( ! wppa_is_int( $id ) && strlen( $id ) > 0 ) {
			$crypt = $id;
		}
		else {
			switch ( $id ) {
				case '-3':
					$crypt = wppa_get_option( 'wppa_album_crypt_3', false );
					break;
				case '-2':
					$crypt = wppa_get_option( 'wppa_album_crypt_2', false );
					break;
				case '-1':
					$crypt = wppa_get_option( 'wppa_album_crypt_1', false );
					break;
				case '':
				case '0':
					$crypt = wppa_get_option( 'wppa_album_crypt_0', false );
					break;
				case '999999':
					$crypt = wppa_get_option( 'wppa_album_crypt_9', false );
					break;
				default:
					$crypt = wppa_get_album_item( $id, 'crypt' );
					break;
			}
		}
		$album_crypts[$i] = $crypt;
		$i++;
	}

	// Compose result
	$result = implode( '.', $album_crypts );

	if ( ! $result ) {
//		wppa_log('misc', 'enc alb called with '.var_export($album,true));
		$result = 'xxxxxxxxxxxxxxxx';
	}
	return $result;
}

// Decode album crypt to album id
function wppa_decrypt_album( $album ) {
global $wpdb;

	// Assume single encrypted
	$result = wppa_get_var( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_albums WHERE crypt = %s", $album ) );
	if ( wppa_is_posint( $result ) ) {
		return $result;
	}

	// Check for enum
	if ( $album && strpos( $album, '.' ) !== false ) {
		$albums = str_replace( '.', "','", $album );
		$ids = wppa_get_col( stripslashes( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_albums WHERE crypt IN (%s)", $albums ) ) );
		if ( is_array( $ids ) ) {
			$result = implode( '.', $ids );
		}
		else {
			$result = false;
		}
		return $result;
	}

	// Check for zero
	if ( $album === 0 ) return '';

	// Check for special cases
	if ( $album == wppa_get_option( 'wppa_album_crypt_9' ) ) return false;
	if ( $album == wppa_get_option( 'wppa_album_crypt_0' ) ) return '0';
	if ( $album == wppa_get_option( 'wppa_album_crypt_1' ) ) return '-1';
	if ( $album == wppa_get_option( 'wppa_album_crypt_2' ) ) return '-2';
	if ( $album == wppa_get_option( 'wppa_album_crypt_3' ) ) return '-3';

	
	if ( wppa_is_posint( $album ) ) {
		/* translators: integer album id */
		wp_die( esc_html( sprintf( __( 'Invalid or outdated url. Media item id must be encrypted, %d given', 'wp-photo-album-plus' ), $album ) ) );
	}
//	wppa_log('misc', 'album = '.$album);
	return false; //$album;
}

// Encrypt a full url
function wppa_encrypt_url( $url ) {

	// Querystring present?
	if ( strpos( $url, '?' ) === false ) {
		return $url;
	}

	// Has it &amp; 's ?
	if ( strpos( $url, '&amp;' ) === false ) {
		$hasamp = false;
	}
	else {
		$hasamp = true;
	}

	// Disassemble url
	$temp = explode( '?', $url );

	// Has it a querystring?
	if ( count( $temp ) == 1 ) {
		return $url;
	}

	// Disassemble querystring
	$qarray = explode( '&', str_replace( '&amp;', '&', $temp[1] ) );

	// Search and replace album and photo ids by crypts
	$i = 0;
	while ( $i < count( $qarray ) ) {
		$item = $qarray[$i];
		$t = explode( '=', $item );
		if ( isset( $t[1] ) ) {
			switch ( $t[0] ) {
				case 'wppa-album':
				case 'album':
					if ( ! $t[1] ) $t[1] = 0;
					$t[1] = wppa_encrypt_album( $t[1] );
					break;
				case 'wppa-photo':
				case 'wppa-photos':
				case 'photo':
					$t[1] = wppa_encrypt_photo( $t[1] );
					break;
				default:
					break;
			}
		}
		$item = implode( '=', $t );
		$qarray[$i] = $item;
		$i++;
	}

	// Re-assemble url
	$temp[1] = implode( '&', $qarray );
	$newurl = implode( '?', $temp );
	if ( $hasamp ) {
		$newurl = str_replace( '&', '&amp;', $newurl );
	}

	return $newurl;
}

// Functions to en/decrypt url extensions that contain setting changes created by the [wppa_set] shortcode.
// This must be encrypted to avoid unwanted/malicious setting changes by hackers
// There is one wp option (array) called wppa_set that contains items like wppa_set[md5(settingchanges) => settingchanges]
function wppa_encrypt_set() {
global $wppa_url_set_extension;

	// Are we enabled?
	if ( ! wppa_switch( 'enable_shortcode_wppa_set' ) ) {
		return;
	}

	// Empty?
	if ( ! $wppa_url_set_extension ) {
		return; // nothing to do
	}

	// Compute crypt
	$key = md5($wppa_url_set_extension);

	// Get existing
	$all = get_option( 'wppa-set', array() );

	// If not save yet, save it
	if ( !isset( $all[$key] ) ) {
		$all[$key] = $wppa_url_set_extension;
		update_option( 'wppa-set', $all );
	}

	// return new query arg
	return 'wppa-set=' . $key;
}

function wppa_decrypt_set() {
global $wppa_url_set_extension;
global $wppa_opt;

	// Are we enabled?
	if ( ! wppa_switch( 'enable_shortcode_wppa_set' ) ) {
		return;
	}

	// Get the date to be decrypted
	$crypt = wppa_get( 'set', '', 'text' );

	// Empty?
	if ( ! $crypt ) {
		return; // nothing to do
	}

	// Get existing
	$all = get_option( 'wppa-set', array() );

	// Fill global with decrypted value
	if ( isset( $all[$crypt] ) ) {
		$wppa_url_set_extension = $all[$crypt];
	}

	// Process items
	if ( $wppa_url_set_extension ) {
		$temp = str_replace( '&amp;', '&', $wppa_url_set_extension );
		$temp = explode( '&', trim( $temp, '&' ) );
		foreach( $temp as $t ) {
			$key = substr( $t, 0, strpos( $t, '=' ) );
			$val = substr( $t, strpos( $t, '=' ) + 1 );
			$wppa_opt[$key] = $val;
		}
	}
}

function wppa_looks_encrypted( $str ) {
	if ( strlen( $str ) != 16 ) return false;
	if ( strpos( $str, '#', ) !== false ) return false;
	return true;
}