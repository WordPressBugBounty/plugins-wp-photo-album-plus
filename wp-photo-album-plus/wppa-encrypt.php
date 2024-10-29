<?php
/* wppa-encrypt.php
* Package: wp-photo-album-plus
*
* Contains all ecryption/decryption logic
* Version 8.8.08.004
*
*/

// Find a unique crypt
function wppa_get_unique_crypt() {
global $wpdb;

	$result = '0';
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
function wppa_decrypt_photo( $photo, $strict = false ) {
global $wpdb;
static $cache;
static $hits;

	// Check for not encryoted single item
	if ( wppa_is_int( $photo ) ) {
		if ( is_user_logged_in() ) wppa_log( 'err', "Unencrypted photo $photo found." );
		/* translators: integer photo id */
		wp_die( esc_html( sprintf( __( 'Invalid or outdated url. Media item id must be encrypted, %d given', 'wp-photo-album-plus' ), $photo ) ) );
		return null;
	}

	// Check for enum
	if ( $photo && strpos( $photo, '.' ) !== false ) {

		$result = '';
		$parray = explode( '.', $photo );
		foreach( $parray as $p ) {

			if ( $p == '' ) {
				$result .= '.';
			}
			else {
				$id = wppa_decrypt_photo( $p );
				if ( $id !== false ) {
					$result .= $id . '.';
				}
			}
		}

		return trim( $result, '.' );
	}

	// Single item
	else {

		// Init cache
		if ( ! $cache ) {
			$cache = array();
		}

		// Look in cache
		if ( isset( $cache[$photo] ) ) {
			$hits++;
			return $cache[$photo];
		}

		// Find photo id on crypt code
		$query = $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE crypt = %s", $photo );
		$p = wppa_get_var( $query );
		if ( $p ) {
			$result = $p;
			$cache[$photo] = $p;
			return $result;
		}
		else {
			return false;
		}
	}

	// Done
	return false;
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
				if ( wppa_is_posint( $id ) ) {
					$crypt = wppa_get_album_item( $id, 'crypt' );
				}
				else {
					$crypt = $id; 	// Already encrypted
				}
		}
		$album_crypts[$i] = $crypt;
		$i++;
	}

	// Compose result
	$result = implode( '.', $album_crypts );

	if ( ! $result ) {
		$result = 'xxxxxxxxxxxxxxxx';
	}
	return $result;
}

// Decode album crypt to album id
function wppa_decrypt_album( $album, $strict = false ) {
global $wpdb;
static $cache;
static $hits;

	if ( ! $album ) return '0';

	// Check for not encryoted single item
	if ( wppa_is_posint( $album ) ) {
		if ( is_user_logged_in() ) wppa_log( 'err', "Unencrypted album $album found." );
		/* translators: integer album id */
		wp_die( esc_html( sprintf( __( 'Invalid or outdated url. Album id must be encrypted, %d given', 'wp-photo-album-plus' ), $album ) ) );
		return null;
	}

	// Check for enum
	if ( $album && strpos( $album, '.' ) !== false ) {

		$result = '';
		$aarray = explode( '.', $album );
		foreach( $aarray as $a ) {

			if ( $a == '' ) {
				$result .= '.';
			}
			else {
				$id = wppa_decrypt_album( $a );
				if ( $id !== false ) {
					$result .= $id . '.';
				}
			}
		}

		return trim( $result, '.' );
	}

	// Single item
	else {

		// Init cache
		if ( ! $cache ) {
			$cache = array();
			$cache[wppa_get_option( 'wppa_album_crypt_9' )] = false;
			$cache[wppa_get_option( 'wppa_album_crypt_0' )] = '0';
			$cache[wppa_get_option( 'wppa_album_crypt_1' )] = '-1';
			$cache[wppa_get_option( 'wppa_album_crypt_2' )] = '-2';
			$cache[wppa_get_option( 'wppa_album_crypt_3' )] = '-3';
		}

		// Look in cache
		if ( isset( $cache[$album] ) ) {
			$hits++;
			return $cache[$album];
		}

		// Find album id on crypt code
		$query = $wpdb->prepare( "SELECT id FROM $wpdb->wppa_albums WHERE crypt = %s", $album );
		$a = wppa_get_var( $query );
		if ( $a ) {
			$result = $a;
			$cache[$album] = $a;
			return $result;
		}
		else {
			return false;
		}
	}

	// Done
	return false;
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
	if ( count( $temp ) == '1' ) {
		return $url;
	}

	// Disassemble querystring
	$qarray = explode( '&', str_replace( '&amp;', '&', $temp['1'] ) );

	// Search and replace album and photo ids by crypts
	$i = 0;
	while ( $i < count( $qarray ) ) {
		$item = $qarray[$i];
		$t = explode( '=', $item );
		if ( isset( $t['1'] ) ) {
			switch ( $t['0'] ) {
				case 'wppa-album':
				case 'album':
					if ( ! $t['1'] ) $t['1'] = '0';
					$t['1'] = wppa_encrypt_album( $t['1'] );
					break;
				case 'wppa-photo':
				case 'wppa-photos':
				case 'photo':
					$t['1'] = wppa_encrypt_photo( $t['1'] );
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
	$temp['1'] = implode( '&', $qarray );
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
//			wppa_log( 'misc', 'wppa_set item = ' . $key . ', value = ' . $val );
		}
	}
}
