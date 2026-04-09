<?php
/* wppa-session.php
* Package: wp-photo-album-plus
*
* Contains all session routines
* Version 9.1.10.010
*
* Firefox modifies data in the superglobal $_SESSION.
* See https://bugzilla.mozilla.org/show_bug.cgi?id=991019
* The use of $_SESSION data is therefor no longer reliable
* This file contains routines to obtain the same functionality, but more secure.
* In the application use the global $wppa_session instead of $_SESSION['wppa_session']

* DB structure:

"CREATE TABLE " . WPPA_SESSION . " (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					session tinytext NOT NULL,
					timestamp tinytext NOT NULL,
					user tinytext NOT NULL,
					ip tinytext NOT NULL,
					status tinytext NOT NULL,
					data text NOT NULL,
					count bigint(20) NOT NULL default 0,
					PRIMARY KEY  (id),
					KEY sessionkey (session(20))
					) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci";
*
*/

if ( ! defined( 'ABSPATH' ) ) exit();

// Generate a unique session id
function wppa_get_session_id() {
global $wppa_version;
global $wpdb;
static $session_id;

	// Found already?
	if ( $session_id ) {
		return $session_id;
	}

	// Look for a cookie
	if ( isset( $_COOKIE['wppa_session_id'] ) ) {
		$session_id = wp_unslash( $_COOKIE['wppa_session_id'] );
		if ( $session_id ) {
			return $session_id;
		}
	}
}

// Dummy for wfcart
function wppa_session_start () {}

// Start a session or retrieve the sessions data. To be called at init.
function wppa_begin_session() {
global $wpdb;
global $wppa_session;

	// If the session table does not yet exist on activation the first time
	if ( is_admin() ) {
		$tables = wppa_get_results( "SHOW TABLES FROM `" . DB_NAME . "`" );
		$found = false;
		foreach( $tables as $table ) {
			if ( in_array( WPPA_SESSION, $table ) ) $found = true;
		}
		if ( ! $found ) {
			$wppa_session['id'] = 0;
			return false;
		}
	}

	// First destroy expired sessions older than 24 hrs
	$n = wppa_query( $wpdb->prepare( "DELETE FROM $wpdb->wppa_session WHERE timestamp < %s", time() - 86400 ) );
	if ( $n ) wppa_log( 'dbg', $n . ' old sessions removed while opening a new one' );

	// Anonimize all expired sessions, except robots (for the statistics widget)
	wppa_query( "UPDATE $wpdb->wppa_session
			   SET ip = '', user = '', data = ''
			   WHERE status = 'expired'
			   AND data NOT LIKE '%\"isrobot\";b:1;%'" );

	// Init
	$lifetime 	= 3600;			// Sessions expire after one hour
	$expire 	= time() - $lifetime;
	$session_id = wppa_get_session_id();

	// Is session already started?
	$session = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->wppa_session
												WHERE session = %s
												AND status = 'valid'
												ORDER BY id DESC
												LIMIT 1", $session_id ), ARRAY_A );

	// Started but expired?
	if ( $session ) {

		$wppa_session = wppa_unserialize( $session['data'], true );

		if ( $session['timestamp'] < $expire ) {

			wppa_query( $wpdb->prepare( "UPDATE $wpdb->wppa_session
										   SET status = 'expired'
										   WHERE session = %s", $session_id ) );
			$session = false;
		}

		// Not expired
		$wppa_session = unserialize( $session['data'] );
//		return;
	}

	// Now create new session
	if ( ! $session ) {

		$iret = wppa_create_session_entry();

		if ( ! $iret ) {
			wppa_log( 'Err', 'Unable to create session for user ' . wppa_get_user() );

			// Give up
			return false;
		}

		else { // get the session
			wppa_read_session();
		}
	}

	// Session exists, Update counter
	else {
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT `count` FROM $wpdb->wppa_session WHERE session = %s", $session_id ) );
		$query = $wpdb->prepare( "UPDATE $wpdb->wppa_session SET `count` = %d WHERE session = %s", $count + 1, $session_id );
		wppa_query( $query );
	}

	// Get info for root and sub search
	/*
	if ( wppa_get( 'search-submit' ) ) {
		$wppa_session['rootbox'] = wppa_get( 'rootsearch' );
		$wppa_session['subbox']  = wppa_get( 'subsearch' );
		if ( $wppa_session['subbox'] ) {
			if ( isset ( $wppa_session['use_searchstring'] ) ) {
				$t = explode( ',', $wppa_session['use_searchstring'] );
				foreach( array_keys( $t ) as $idx ) {
					$t[$idx] .= ' '.wppa_test_for_search( 'at_begin_session' );
					$t[$idx] = trim( $t[$idx] );
					$v = explode( ' ', $t[$idx] );
					$t[$idx] = implode( ' ', array_unique( $v ) );
				}
				$wppa_session['use_searchstring'] = ' '.implode( ',', array_unique( $t ) );
			}
			else {
				$wppa_session['use_searchstring'] = wppa_test_for_search( 'at_begin_session' );
			}
		}
		else {
			$wppa_session['use_searchstring'] = wppa_test_for_search( 'at_begin_session' );
		}
		if ( isset ( $wppa_session['use_searchstring'] ) ) {
			$wppa_session['use_searchstring'] = trim( $wppa_session['use_searchstring'], ' ,' );
			$wppa_session['display_searchstring'] = str_replace ( ',', ' &#8746 ', str_replace ( ' ', ' &#8745 ', $wppa_session['use_searchstring'] ) );
		}
	}
	*/

	if ( ! isset( $wppa_session['page'] ) ) $wppa_session['page'] = 0;
	$wppa_session['page']++;
	if ( ! isset( $wppa_session['uris'] ) ) $wppa_session['uris'] = [];
	if ( wppa_request_uri() ) {
		$new_item = date_i18n("g:i") . ' ' . wppa_request_uri();
		if ( ! in_array( $new_item, (array) $wppa_session['uris'] ) ) {
			$wppa_session['uris'][] = $new_item;
		}
		if ( stripos( wppa_request_uri(), '/robots.txt' ) !== false ) {
			$wppa_session['isrobot'] = true;
		}
	}

// wppa_log('dbg', 'read sesion sstr: '.$wppa_session['use_searchstring']);
	// Reset default randseed conditionally (if wp page id changed)
	wppa_get_randseed();

	return true;
}

// Saves the session data. To be called at shutdown
function wppa_session_end() {
global $wppa_session;

	// May have logged in now
	$wppa_session['user'] = wppa_get_user();
	wppa_save_session();
}

// Save the session data
function wppa_save_session() {
global $wpdb;
global $wppa_session;

	// If no id can be found, give up
	$session_id = wppa_get_session_id();
	if ( ! $session_id ) return false;

	// To prevent data overflow, only save the most recent 100 urls
	$c = isset( $wppa_session['uris'] ) && is_array( $wppa_session['uris'] ) ? count( $wppa_session['uris'] ) : 0;
	if ( $c > 100 ) {
		array_shift( $wppa_session['uris'] );
	}
// wppa_log('dbg', 'up to save '.$wppa_session['use_searchstring']);
	// Compose the query
	$iret = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->wppa_session
											SET data = %s
											WHERE session = %s", serialize( $wppa_session ), $session_id ) );
	// Rreturn result
	return $iret;
}

// Extends session for admin maintenance procedures, to report the right totals
function wppa_extend_session() {
global $wpdb;

	$sessionid = wppa_get_session_id();
	wppa_query( $wpdb->prepare( "UPDATE $wpdb->wppa_session
								   SET timestamp = %d
								   WHERE session = %s", time(), $sessionid ) );
}

// Read session data to be used inside code to make sure you have it
function wppa_read_session() {
global $wppa_session;
global $wpdb;

	$sessionid = wppa_get_session_id();
	if ( ! $wppa_session ) {
		wppa_begin_session();
	}

	$data = $wpdb->get_var( $wpdb->prepare( "SELECT data from $wpdb->wppa_session WHERE session = %s ORDER BY id DESC LIMIT 1", $sessionid ) );
	if ( $data ) $wppa_session = unserialize( $data );
}