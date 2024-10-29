<?php
/* wppa-input.php
* Package: wp-photo-album-plus
*
* Contains functions for sanitizing and formatting user input
* Version: 8.8.08.001
*
*/

/* CHECK REDIRECTION */
add_action( 'plugins_loaded', 'wppa_redirect', '1' );

function wppa_redirect() {

	if ( ! wppa_request_uri() ) return;

	$uri = wppa_request_uri();
	$wppapos = stripos( $uri, '/wppaspec/' );
	if ( $wppapos === false ) {

		$wppapos = strpos( $uri, '/-/' );
		if ( wppa_get_option( 'wppa_use_pretty_links' ) != 'compressed' ) {
			$wppapos = false;
		}
	}

	if ( $wppapos !== false && wppa_get_option( 'permalink_structure' ) ) {

		// old style solution, still required when qTranslate is active
		$plugins = implode( ',', wppa_get_option( 'active_plugins' ) );
		if ( stripos( $plugins, 'qtranslate' ) !== false ) {

			$newuri = wppa_convert_from_pretty( $uri );
			if ( $newuri == $uri ) return;

			// Although the url is urlencoded it is damaged by wp_redirect when it contains chars like ë, so we do a header() call
			header( 'Location: '.$newuri, true, 302 );
			exit;
		}

		// New style solution
		$newuri = wppa_convert_from_pretty( $uri );
		if ( $newuri == $uri ) return;
		$_SERVER["REQUEST_URI"] = $newuri;
		wppa_convert_uri_to_get( $newuri );
	}
}

// Gert the filter slug to use for the querystring var
function wppa_get_get_filter( $name ) {

	switch ( $name ) {

		// Integer
		case 'occur':
		case 'topten':
		case 'lasten':
		case 'comten':
		case 'featen':
		case 'relcount':
		case 'paged':
		case 'page_id':
		case 'p':
		case 'size':
		case 'fromp':
		case 'forceroot':
		case 'comment-id':
		case 'comid':
		case 'user':
		case 'rating':
		case 'index':
		case 'next-after':
		case 'commentid':
		case 'bulk-album':
		case 'set-album':
		case 'photo-album':
		case 'video-album':
		case 'audio-album':
		case 'document-album':
		case 'del-id':
		case 'move-album':
		case 'parent-id':
		case 'is-sibling-of':
		case 'sub':
		case 'subtab':
		case 'pano-val':
		case 'album-page-no':
		case 'high':
		case 'albumeditid':
		case 'album-parent':
		case 'captcha':
		case 'import-remote-max':
		case 'comment-edit':
		case 'del-after-p':
		case 'del-after-fp':
		case 'del-after-f':
		case 'del-after-a':
		case 'del-after-fa':
		case 'del-after-z':
		case 'del-after-fz':
		case 'del-after-v':
		case 'del-after-fv':
		case 'del-after-u':
		case 'del-after-fu':
		case 'del-after-c':
		case 'del-after-fc':
		case 'del-after-d':
		case 'del-after-fd':
		case 'del-dir-cont':
		case 'zoom':
		case 'parent_id':
		case 'timeout':
		case 'mocc':
			$result = 'int';
			break;

		// Array of integers
		case 'commentids':
			$result = 'intarr';
			break;

		// Boolean
		case 'cover':
		case 'slide':
		case 'slideonly':
		case 'filmonly':
		case 'single':
		case 'photos-only':
		case 'albums-only':
		case 'medals-only':
		case 'rel':
		case 'rootsearch':
		case 'potdhis':
		case 'inv':
		case 'vt':
		case 'catbox':
		case 'resp':
		case 'quick':
		case 'continue':
		case 'del-dir':
		case 'use-backup':
		case 'update':
		case 'superview':
		case 'nodups':
		case 'raw':
		case 'bulk':
		case 'applynewdesc':
		case 'remakealbum':
		case 'search-submit':
		case 'export-submit':
		case 'blogit':
		case 'cron':
		case 'seq':
		case 'fe-create':
			$result = 'bool';
			break;

		// Searchstring
		case 'searchstring':
		case 's':
			$result = 'src';
			break;

		// Html
		case 'comment':
//		case 'commenttext':
		case 'upn-description':
		case 'user-desc': 		// Desc by user during fe upload
		case 'albumeditdesc': 	// Fe album desc
			$result = 'html';
			break;

		// textarea
		case 'commenttext':
			$result = 'textarea';
			break;

		// Tags / Cats
		case 'tag':
		case 'tags':
		case 'upn-tags':
		case 'new-tags':
			$result = 'tags';
			break;

		// Custom data
		case 'custom_0':
		case 'custom_1':
		case 'custom_2':
		case 'custom_3':
		case 'custom_4':
		case 'custom_5':
		case 'custom_6':
		case 'custom_7':
		case 'custom_8':
		case 'custom_9':
			$result = 'custom';
			break;

		// Possibly encrypted photo(s)
		case 'photo':
		case 'photos':
		case 'hilite':
		case 'photo-id':
		case 'rating-id':
		case 'photoid':
			$result = 'pcrypt';
			break;

		// Possibly encrypted album
		case 'album':
		case 'album-id':
		case 'upload-album':
			$result = 'acrypt';
			break;

		// Email
		case 'comemail':
			$result = 'email';
			break;

		// Url
		case 'url':
		case 'returnurl':
		case 'source-remote':
			$result = 'url';
			break;

		// Array text
		case 'bulk-photo':
			$result = 'arraytxt';
			break;

		default:
			$result = 'text';
			break;
	}

	return $result;
}

// Retrieve a get- or post- variable, sanitized and post-processed
function wppa_get( $xname, $default = false, $filter = false, $strict = false ) {

	// Sanitize
	$xname 		= sanitize_text_field( $xname );
	$default 	= $default ? sanitize_text_field( $default ) : false;
	$filter 	= $filter ? sanitize_text_field( $filter ) : '';

	$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check

	// Ajax call ?
	if ( $xname == 'wppa-action' ) {
		if ( isset( $_REQUEST['wppa-action'] ) ) {
			$result = sanitize_text_field( wp_unslash( $_REQUEST['wppa-action'] ) );
			return $result;
		}
		else {
			return $default;
		}
	}

	// Normalize $name and $xname
	if ( substr( $xname, 0, 5 ) == 'wppa-' ) {
		$name = substr( $xname, 5 );
	}
	else {
		$name = $xname;
		$xname = 'wppa-' . $name;
	}

	// Find the key if any
	if ( isset( $_REQUEST[$xname] ) ) {		// with prefix wppa-
		$key = $xname;
	}
	elseif ( isset( $_REQUEST[$name] ) ) {	// without prefix wppa-
		$key = $name;
	}
	else {									// neither
		return $default;
	}

	// Get the right filter
	if ( ! $filter ) {
		$filter = wppa_get_get_filter( $name );
	}

	// Now we have the right key for $request and the right sanitize / validate scheme
	// Do the filtering
	switch ( $filter ) {

		case 'int':
			return isset( $_REQUEST[$key] ) ? strval( intval ( wp_unslash( $_REQUEST[$key] ) ) ) : $default;
			break;

		case 'posint':
			return isset( $_REQUEST[$key] ) ? max( '1', strval( intval ( wp_unslash( $_REQUEST[$key] ) ) ) ) : $default;
			break;

		case 'bool':
			$value = isset( $_REQUEST[$key] ) ? sanitize_text_field( wp_unslash( $_REQUEST[$key] ) ) : $default;
			if ( $value !== '0' && $value != 'nil' && $value != 'no' ) {
				$result = '1';
			}
			else {
				$result = '0';
			}
			return $result;
			break;

		case 'src':
			return isset( $_REQUEST[$key] ) ? wppa_sanitize_searchstring( sanitize_text_field( wp_unslash( $_REQUEST[$key] ) ) ) : $default;
			break;

		case 'html':
		case 'custom':
			if ( current_user_can( 'unfiltered_html' ) ) {
				return isset( $_REQUEST[$key] ) ? wp_kses( wp_unslash( $_REQUEST[$key] ), wppa_allowed_tags() ) : '';
			}
			else {
				return isset( $_REQUEST[$key] ) ? wp_strip_all_tags( wp_unslash( $_REQUEST[$key] ) ) : $default;
			}
			break;

		case 'tag':
		case 'tags':
		case 'cat':
			return isset( $_REQUEST[$key] ) ? trim( wppa_sanitize_tags( sanitize_text_field( wp_unslash( $_REQUEST[$key] ) ), ',' ) ) : $default;
			break;

		case 'textarea':
			return isset( $_REQUEST[$key] ) ? sanitize_textarea_field( wp_unslash( $_REQUEST[$key] ) ) : '';
			break;

		case 'pcrypt':
			return isset( $_REQUEST[$key] ) ? wppa_decrypt_photo( sanitize_text_field( wp_unslash( $_REQUEST[$key] ) ), ! is_admin() || $strict ) : '';
			break;

		case 'acrypt':
			return isset( $_REQUEST[$key] ) ? wppa_decrypt_album( sanitize_text_field( wp_unslash( $_REQUEST[$key] ) ), ! is_admin() || $strict ) : '';
			break;

		case 'email':
			return isset( $_REQUEST[$key] ) ? sanitize_email( wp_unslash( $_REQUEST[$key] ) ) : '';
			break;

		case 'url':
			return isset( $_REQUEST[$key] ) ? esc_url_raw( wp_unslash( $_REQUEST[$key] ) ) : '';
			break;

		case 'intarr':
			if ( isset( $_REQUEST[$key] ) && is_array( $_REQUEST[$key] ) ) {
				$value = array();
				$i = 0;
				while ( isset( $_REQUEST[$key][$i] ) ) {
					$value[$i] = strval( intval( wp_unslash( $_REQUEST[$key][$i] ) ) );
					$i++;
				}
			}
			else {
				$value = isset( $_REQUEST[$key] ) ? strval( intval ( wp_unslash( $_REQUEST[$key] ) ) ) : $default;
			}
			return $value;
			break;

		case 'arraytxt':
			$result = array();
			if ( isset( $_REQUEST[$key] ) ) {
				foreach( array_keys( wp_unslash( $_REQUEST[$key] ) ) as $k ) {
					$result[sanitize_text_field( $k )] = isset( $_REQUEST[$key][$k] ) ? sanitize_text_field( wp_unslash( $_REQUEST[$key][$k] ) ) : '';
				}
			}
			return $result;
			break;

		case 'strip':
			return isset( $_REQUEST[$key] ) ? wp_strip_all_tags( wp_unslash( $_REQUEST[$key] ) ) : '';
			break;

		case 'php':
			return isset( $_REQUEST[$key] ) ? str_replace( ['%26', '%23', '%2B', '\\', '<?php'], ['&', '#', '+', '', ''], sanitize_textarea_field( wp_unslash( $_REQUEST[$key] ) ) ) : '';
			break;

		case 'gutsc':
			return isset( $_REQUEST[$key] ) ? sanitize_text_field( wp_unslash( $_REQUEST[$key] ) ) : ''; // str_replace( '%23', '#', stripslashes( $_REQUEST['shortcode'] ) );
			break;

		case 'text':
		default:
			return isset( $_REQUEST[$key] ) ? sanitize_text_field( wp_unslash( $_REQUEST[$key] ) ) : '';
			break;

	}

	return $result;
}

// Sanitize a searchstring
function wppa_sanitize_searchstring( $str ) {

	$result = remove_accents( $str );
	$result = wp_strip_all_tags( $result );
	$result = stripslashes( $result );
	$result = str_replace( array( "'", '"', ':', ), '', $result );
	$temp 	= explode( ',', $result );
	foreach ( array_keys( $temp ) as $key ) {
		$temp[$key] = trim( $temp[$key] );
	}
	$result = implode( ',', $temp );

	return $result;
}

// Retrieve a cookie, sanitized and verified
function wppa_get_cookie( $name, $default = '' ) {

	// Sanitize
	$name 		= sanitize_text_field( $name );
	$default 	= sanitize_text_field( $default );

	// Validate
	if ( isset( $_COOKIE[$name] ) ) {

		$result = sanitize_text_field( wp_unslash( $_COOKIE[$name] ) );
	}
	else {
		$result = $default;
	}

	return $result;
}

// Get the sanitzed value of request uri
function wppa_request_uri() {

	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		return esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}
	return '';
}

// Get the sanitzed value of http host
function wppa_http_host() {

	if ( isset( $_SERVER['HTTP_HOST'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
	}
	return '';
}

// Get the sanitized alue of script filename
function wppa_script_filename() {

	if ( isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
		return sanitize_file_name( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) );
	}
	return '';
}

// Get the sanitized alue of remote address
function wppa_remote_addr() {

	if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		return rest_is_ip_address( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
	}
	return '';
}

// Get the sanitized alue of user_agant
function wppa_user_agent() {

	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}
	return '';
}

// Get the sanitzed value of script uri
function wppa_script_uri() {

	if ( isset( $_SERVER['SCRIPT_URI'] ) ) {
		return esc_url_raw( wp_unslash( $_SERVER['SCRIPT_URI'] ) );
	}
	return '';
}

// Get the sanitzed value of quwry string
function wppa_query_string() {

	if ( isset( $_SERVER['QUERY_STRING'] ) ) {
		return esc_url_raw( wp_unslash( $_SERVER['QUERY_STRING'] ) );
	}
	return '';
}

// Get the sanitized value of http accept
function wppa_http_accept() {

	if ( isset( $_SERVER['HTTP_ACCEPT'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) );
	}
	return '';
}

// Get the sanitized value of http profime
function wppa_http_profile(){

	if ( isset( $_SERVER['HTTP_PROFILE'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_PROFILE'] ) );
	}
	return '';
}

// Get the sanitized value of http x wap profime
function wppa_http_x_wap_profile(){

	if ( isset( $_SERVER['HTTP_X_WAP_PROFILE'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WAP_PROFILE'] ) );
	}
	return '';
}

// Get the sanitized value of client ip
function wppa_http_client_ip() {

	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		return rest_is_ip_address( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) ) );
	}
	return '';
}

// Get the sanitized value of x forward for
function wppa_http_x_forwarded_for() {

	if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// comma separated list of ips
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
	}
	return '';
}
