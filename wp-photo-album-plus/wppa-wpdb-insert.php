<?php
/* wppa-wpdb-insert.php
* Package: wp-photo-album-plus
*
* Contains low-level wpdb routines that add new records
* Version 9.0.00.010
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// Session
function wppa_create_session_entry() {
global $wpdb;

	$table 	= $wpdb->wppa_session;
	$data 	= array(
					'session' 			=> wppa_get_session_id(),
					'timestamp' 		=> time(),
					'user'				=> wppa_get_user(),
					'ip'				=> wppa_get_user_ip(),
					'status' 			=> 'valid',
					'data'				=> false,
					'count' 			=> 1,
	);

	$bret = wppa_insert( $table, $data );
	if ( $bret ) {
		return $wpdb->insert_id;
	}

	wppa_log( 'err', 'Could not insert into db table wppa_session' );
	return false;
}

// Index
function wppa_create_index_entry( $args ) {
global $wpdb;

	$table 	= $wpdb->wppa_index;
	$data 	= wp_parse_args( (array) $args, array (
					'slug' 				=> '',
					'albums' 			=> '',
					'photos' 			=> ''
	) );

	if ( strlen( $data['slug'] ) > 32 ) {
		return true; 	// slug too long
	}

	$bret = wppa_insert( $table, $data );
	if ( $bret ) {
		return $wpdb->insert_id;
	}

	wppa_log( 'err', 'Could not insert ' . $data['slug'] . ' into db table wppa_index' );
	return false;
}

// EXIF
function wppa_create_exif_entry( $args ) {
global $wpdb;
static $last;

	if ( ! $last ) $last = array( 'photo' => '', 'tag' => '' );

	$table 	= $wpdb->wppa_exif;
	$data 	= wp_parse_args( (array) $args, array (
					'photo' 			=> 0,
					'tag' 				=> '',
					'description' 		=> '',
					'f_description' 	=> '',
					'status' 			=> '',
					'brand' 			=> '',
	) );
	$data['description'] = sanitize_text_field( $data['description'] );
	$data['description'] = str_replace( array(chr(0),chr(1),chr(2),chr(3),chr(4),chr(5),chr(6),chr(7)), '', $data['description'] );
	$data['tag'] = str_replace( '0x', '', $data['tag'] );

	// Skip unwanted garbage
	if ( strlen( $data['description'] ) > 255 ) return true; 					// is array or nonsense anyway
	if ( ! $data['description'] ) return true; 									// is empty
	if ( strpos( $data['description'], 'UndefinedTag:' ) === 0 ) return true; 	// skip undefined tags

	// Skip if duplecate of last done. $wpdb->insert() bug due to delayed actual writing to db???
	if ( $last['photo'] === $data['photo'] && $last['tag'] == $data['tag'] ) {
		return true;
	}

	// Skip if already exits
	$query = $wpdb->prepare( "SELECT id FROM $wpdb->wppa_exif WHERE photo = %d AND tag = %s", $data['photo'], $data['tag'] );
	$exists = wppa_get_var( $query );
	if ( $exists ) return true;

	// Save last
	$last['photo'] = $data['photo'];
	$last['tag'] = $data['tag'];

	// No garbage and not in there yet, do it
	$bret = wppa_insert( $table, $data );
	if ( $bret ) {
		return $wpdb->insert_id;
	}

	wppa_log( 'err', 'Could not insert into db table wppa_exif.' ); // data = ' . var_export( $data, true ) );
	return false;
}

// IPTC
function wppa_create_iptc_entry( $args ) {
global $wpdb;
static $last;

	if ( ! $last ) $last = array( 'photo' => '', 'tag' => '' );

	$table 	= $wpdb->wppa_iptc;
	$data 	= wp_parse_args( (array) $args, array (
					'photo' 			=> 0,
					'tag' 				=> '',
					'description' 		=> '',
					'status' 			=> ''
	) );
	$data['description'] = sanitize_text_field( $data['description'] );
	$data['description'] = str_replace( array(chr(0),chr(1),chr(2),chr(3),chr(4),chr(5),chr(6),chr(7)), '', $data['description'] );

	// Skip unwanted garbage
	if ( ! $data['description'] ) return true; 									// is empty
	if ( strpos( $data['description'], 'UndefinedTag:' ) === 0 ) return true; 	// skip undefined tags

	// Skip if duplicate of last done. $wpdb->insert() bug due to delayed actual writing to db???
	if ( $last['photo'] === $data['photo'] && $last['tag'] == $data['tag'] ) {
		return true;
	}

	// Skip if already exits
	$query = $wpdb->prepare( "SELECT id FROM $wpdb->wppa_iptc WHERE photo = %d AND tag = %s", $data['photo'], $data['tag'] );
	$exists = wppa_get_var( $query );
	if ( $exists ) return true;

	// Save last
	$last['photo'] = $data['photo'];
	$last['tag'] = $data['tag'];

	// No garbage and not in there yet, do it
	$bret = wppa_insert( $table, $data );
	if ( $bret ) {
		return $wpdb->insert_id;
	}

	wppa_log( 'err', 'Could not insert into db table wppa_iptc.' ); //  data = ' . var_export( $data, true ) );
	return false;
}

// Comments
function wppa_create_comments_entry( $args ) {
global $wpdb;

	$table 	= $wpdb->wppa_comments;
	$hope 	= isset( $args['id'] ) ? $args['id'] : null;
	$id 	= wppa_nextkey( $table, $hope );
	$data 	= wp_parse_args( (array) $args, array (
					'id' 				=>  $id,
					'timestamp' 		=> time(),
					'photo' 			=> 0,
					'user' 				=> wppa_get_user(),
					'userid' 			=> wppa_get_user_id(),
					'ip'				=> wppa_get_user_ip(),
					'email' 			=> '',
					'comment' 			=> '',
					'status' 			=> ''
					) );
	$format = array( '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' );

	$bret = wppa_insert( $table, $data, $format );
	if ( $bret ) {
		if ( wppa_switch( 'search_comments' ) ) {
			wppa_update_photo( $data['photo'] );
			wppa_clear_cache( array( 'photo' => $data['photo'], 'other' => 'C' ) );
		}
		return $data['id'];
	}

	wppa_log( 'err', 'Could not insert into db table wppa_comments' );
	return false;
}

// Rating
function wppa_create_rating_entry( $args ) {
global $wpdb;

	$table 	= $wpdb->wppa_rating;
	$hope 	= isset( $args['id'] ) ? $args['id'] : null;
	$id 	= wppa_nextkey( $table, $hope );
	$data 	= wp_parse_args( (array) $args, array (
					'id' 				=> $id,
					'timestamp' 		=> time(),
					'photo' 			=> 0,
					'value' 			=> 0,
					'user' 				=> wppa_get_user(),
					'userid' 			=> wppa_get_user_id(),
					'ip' 				=> wppa_get_user_ip(),
					'status' 			=> 'publish'
					) );
	$format = array( '%d', '%s', '%d', '%d', '%s', '%d', '%s', '%s' );

	$bret = wppa_insert( $table, $data, $format );
	if ( $bret ) {
		wppa_clear_cache( array( 'photo' => $data['photo'], 'other' => 'R' ) );
		return $data['id'];
	}

	wppa_log( 'err', 'Could not insert into db table wppa_comments' );
	return false;
}

// Photo
function wppa_create_photo_entry( $args ) {
global $wpdb;

	$table 	= $wpdb->wppa_photos;
	$hope 	= isset( $args['id'] ) ? $args['id'] : null;
	$id 	= wppa_nextkey( $table, $hope );
	$data 	= wp_parse_args( (array) $args, array (
					'id'				=>  $id,
					'album' 			=> 0,
					'ext' 				=> 'jpg',
					'name'				=> '',
					'description' 		=> ( wppa_switch( 'apply_newphoto_desc' ) ? wppa_opt( 'newphoto_description' ) : '' ),
					'p_order' 			=> 0,
					'mean_rating'		=> '',
					'linkurl' 			=> '',
					'linktitle' 		=> '',
					'linktarget' 		=> '_self',
					'owner'				=> ( wppa_opt( 'newphoto_owner' ) ? wppa_opt( 'newphoto_owner' ) : wppa_get_user() ),
					'timestamp'			=> time(),
					'status'			=> wppa_opt( 'status_new' ),
					'rating_count'		=> 0,
					'tags' 				=> '',
					'alt' 				=> '',
					'filename' 			=> '',
					'modified' 			=> time(),
					'location' 			=> '',
					'views' 			=> 0,
					'clicks' 			=> 0,
					'page_id' 			=> 0,
					'exifdtm' 			=> '',
					'videox' 			=> 0,
					'videoy' 			=> 0,
					'thumbx' 			=> 0,
					'thumby' 			=> 0,
					'photox' 			=> 0,
					'photoy' 			=> 0,
					'scheduledtm' 		=> '',
					'scheduledel' 		=> '',
					'custom'			=> '',
					'stereo' 			=> 0,
					'crypt' 			=> wppa_get_unique_crypt(),
					'magickstack' 		=> '',
					'indexdtm' 			=> '',
					'panorama' 			=> 0,
					'angle' 			=> 0,
					'sname' 			=> '',
					'dlcount' 			=> 0,
					'thumblock' 		=> 0,
					'duration' 			=> '',
					'rml_id' 			=> '',
					'usedby' 			=> '',
					'misc' 				=> '',
					) );

	$data = apply_filters( 'wppa_photo_entry', $data );

	$data['name'] 			= trim( $data['name'] );
	$data['description'] 	= trim( $data['description'] );
	$data['sname'] 			= wppa_name_slug( $data['sname'] );
	$data['tags'] 			= str_replace( '-none-,', '', $data['tags'] );
	$sdtm = wppa_get_var( $wpdb->prepare( "SELECT scheduledtm FROM $wpdb->wppa_albums WHERE id = %s", $data['album'] ) );
	if ( $sdtm ) {
		$data['scheduledtm'] = $sdtm;
	}

	if ( $data['scheduledtm'] ) $data['status'] = 'scheduled';

	if ( $data['filename'] ) {
		if ( ! seems_utf8( $data['filename'] ) ) {
			$data['filename'] = utf8_encode( $data['filename'] );
		}
		if ( wppa_switch( 'remove_accents' ) ) {
			$data['filename'] = remove_accents( $data['filename'] );
		}
	}

	$bret = wppa_insert( $table, $data );

	if ( $bret ) {

		// Housekeeping
		wppa_schedule_maintenance_proc( 'wppa_remake_index_photos' );
		wppa_clear_cache( array( 'album' => $data['album'] ) );
		wppa_fix_seq_nums( 'media', $data['id'] );
		wppa_get_the_auto_page( $data['id'] );
		return $data['id'];
	}

	wppa_log( 'err', 'Could not insert into db table wppa_photos' );
	return false;
}

// Album
function wppa_create_album_entry( $args ) {
global $wpdb;

	$table 	= $wpdb->wppa_albums;
	$hope 	= isset( $args['id'] ) ? $args['id'] : null;
	$id 	= wppa_nextkey( $table, $hope );
	$data 	= wp_parse_args( (array) $args, array (
					'id' 				=> $id,
					'name' 				=> __( 'New Album', 'wp-photo-album-plus' ),
					'description' 		=> '',
					'a_order' 			=> 0,
					'main_photo' 		=> 0,
					'a_parent' 			=> wppa_opt( 'default_parent' ),
					'p_order_by' 		=> 0,
					'cover_linktype' 	=> wppa_opt( 'default_album_linktype' ),
					'cover_linkpage' 	=> 0,
					'cover_link' 		=> '',
					'owner' 			=> wppa_get_user(),
					'timestamp' 		=> time(),
					'modified' 			=> time(),
					'upload_limit' 		=> wppa_opt( 'upload_limit_count' ).'/'.wppa_opt( 'upload_limit_time' ),
					'alt_thumbsize' 	=> 0,
					'default_tags' 		=> '',
					'cover_type' 		=> '',
					'suba_order_by' 	=> '',
					'views' 			=> 0,
					'cats'				=> '',
					'scheduledtm' 		=> '',
					'custom' 			=> '',
					'crypt' 			=> wppa_get_unique_crypt(),
					'treecounts' 		=> serialize( array( 1,0,0,0,0,0,0,0,0,0,0 ) ),
					'wmfile' 			=> '',
					'wmpos' 			=> '',
					'indexdtm' 			=> '',
					'sname' 			=> '',
					'zoomable' 			=> '',
					'displayopts' 		=> '0,0,0,0',
					'upload_limit_tree' => 0,
					'scheduledel' 		=> '',
					'status' 			=> 'publish',
					'max_children' 		=> 0,
					'rml_id' 			=> '',
					'usedby' 			=> '',
					) );

	$data['name'] 			= trim( $data['name'] );
	$data['description'] 	= trim( $data['description'] );
	$data['sname'] 			= wppa_name_slug( $data['sname'] );
	$data['cats'] 			= str_replace( '-none-,', '', $data['cats'] );

	$bret = wppa_insert( $table, $data );

	if ( $bret ) {

		// Housekeeping
		wppa_invalidate_treecounts( $data['id'] );
		wppa_childlist_remove( $data['a_parent'] );
		wppa_schedule_maintenance_proc( 'wppa_remake_index_albums' );
		wppa_clear_cache( array( 'album' => $data['a_parent'] ) );
		wppa_schedule_mailinglist( 'newalbumnotify', $data['id'] );
		wppa_create_pl_htaccess();
		if ( $data['a_parent'] > 0 ) {
			wppa_invalidate_treecounts( $data['a_parent'] );
		}
		wppa_fix_seq_nums( 'album', $data['id'] );

		return $data['id'];
	}

	wppa_log( 'err', 'Could not insert into db table wppa_albums' );
	return false;
}

// Cache metadata
function wppa_create_cache_entry( $args ) {
global $wpdb;

	$table 	= $wpdb->wppa_caches;
	$data 	= wp_parse_args( (array) $args, array (
				'filename'	=> '',
				'albums' 	=> '',
				'photos' 	=> '',
				'other' 	=> '',
				'page' 		=> 0,
				));

	$data['filename'] 	= sanitize_text_field( $data['filename'] );
	$data['albums'] 	= '.' . trim( wppa_expand_enum( $data['albums'] ), '.' ) . '.';
	$data['photos'] 	= '.' . trim( wppa_expand_enum( $data['photos'] ), '.' ) . '.';
	if ( ! in_array( $data['other'], ['C', 'R'] ) ) {
		$other = '';
	}

	$bret = wppa_insert( $table, $data );
	if ( $bret ) {
		return $wpdb->insert_id;
	}

	wppa_log( 'err', 'Could not insert into db table wppa_caches' );
	return false;
}

// Find the next available id in a table
//
// Creating a keyvalue of an auto increment primary key incidently returns the value of MAXINT,
// and thereby making it impossible to add a next record.
// This happens when a time-out occurs during an insert query.
// This is not theoretical, i have seen it happen two times on different installations.
// This routine will find a free positive keyvalue larger than any key used, ignoring the fact that the MAXINT key may be used.
function wppa_nextkey( $table, $hope = 0 ) {
global $wpdb;

	if ( $hope && wppa_is_id_free( $table, $hope ) ) {
		return $hope;
	}

	$name = 'wppa_' . $table . '_lastkey';
	$lastkey = wppa_get_option( $name, 'nil' );

	if ( $lastkey == 'nil' ) {	// Init option
		$query = $wpdb->prepare( "SELECT id FROM %s WHERE id < `9223372036854775806` ORDER BY id DESC LIMIT 1", $table );
		$query = wppa_fix_query( $query );
		$lastkey = wppa_get_var( $query );
		if ( ! is_numeric( $lastkey ) || $lastkey <= 0 ) {
			$lastkey = 0;
		}
		wppa_update_option( $name, $lastkey );
	}

	$result = $lastkey + 1;
	while ( ! wppa_is_id_free( $table, $result ) ) {
		$result++;
	}
	wppa_update_option( $name, $result );
	return $result;
}

// Check whether a given id value is not used
function wppa_is_id_free( $table, $id ) {
global $wpdb;

	if ( ! is_numeric( $id ) ) return false;
	if ( ! wppa_is_int( $id ) ) return false;
	if ( $id <= 0 ) return false;

	$query = $wpdb->prepare( "SELECT * FROM %s WHERE id = %s", $table, $id );
	$query = wppa_fix_query( $query );
	$exists = wppa_get_row( $query );
	if ( $exists ) return false;
	return true;
}

