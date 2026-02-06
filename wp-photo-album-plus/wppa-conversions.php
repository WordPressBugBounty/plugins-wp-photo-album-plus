<?php
/* wppa-conversions.php
* Package: wp-photo-album-plus
*
* Various conversion functions
* Version: 9.1.07.003
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );


// Translate virtual album to runtime paramaters
function wppa_virt_album_to_runparms( $albums ) {
global $wpdb;

	// We start with arg $albumm, so clear start_album first
	wppa( 'start_album', '' );

	if ( $albums ) {

		// Virtual?
		if ( strpos( $albums, '#' ) !== false ) {
			wppa( 'is_virtual', true );
		}

		// Assume multiple tokens in the album arg
		$tokens = explode( '&', $albums );

		// Multivirtual?
		if ( count( $tokens ) > 1 ) {
			wppa( 'is_multi_virtual', true );
		}

		// Do all tokens
		foreach( $tokens as $album ) {

			// Album an int?
			if ( wppa_is_int( $album ) )  {
				wppa( 'start_album', $album );
			}

			// An enum?
			elseif ( wppa_is_enum( $album ) ) {
				wppa( 'start_album', wppa_expand_enum( $album ) );
			}

			// Name?
			elseif ( substr( $album, 0, 1 ) == '$' ) {
				wppa( 'start_album', wppa_album_name_to_number( $album ) );
			}

			// Crypt?
			elseif ( wppa_looks_encrypted( $album ) ) {
				$a = wppa_decrypt_album( $album );
				if ( wppa_is_posint( $a ) ) {
					wppa( 'start_album', $a );
				}
			}

			// See if startalbum exists
			if ( wppa( 'start_album' ) > 0 && ! wppa_is_enum( wppa( 'start_album' ) ) ) {	// -2 is #all
				if ( wppa_is_posint( wppa( 'start_album' ) ) && ! wppa_album_exists( wppa( 'start_album' ) ) ) {
					return wppa_stx_err( 'Album does not exist: ' . wppa( 'start_album' ) );
				}
			}

			// Token is Virtual
			if ( strpos( $album, ',' ) ) {
				$keyword = substr( $album, 0, strpos( $album, ',' ) );
			}
			else {
				$keyword = $album;
			}

			switch ( $keyword ) {
				case '#last':				// Last upload
					$id = wppa_get_youngest_album_id();
					if ( wppa( 'is_cover' ) ) {	// To make sure the ordering sequence is ok.
						$temp = explode( ',', $album );
						if ( isset( $temp[1] ) ) wppa( 'last_albums_parent', $temp[1] );
						else wppa( 'last_albums_parent', 0 );
						if ( isset( $temp[2] ) ) wppa( 'last_albums', $temp[2] );
						else wppa( 'last_albums', false );
					}
					else {		// Ordering seq is not important, convert to album enum
						$temp = explode( ',', $album );
						if ( isset( $temp[1] ) ) $parent = wppa_album_name_to_number( $temp[1] );
						else $parent = 0;
						if ( $parent === false ) {
							return false;
						}
						if ( isset( $temp['2'] ) ) $limit = $temp['2'];
						else $limit = false;
						if ( $limit ) {
							if ( $parent ) {
								if ( $limit ) {
									$q = $wpdb->prepare( "SELECT * FROM $wpdb->wppa_albums
														  WHERE a_parent = %s
														  ORDER BY timestamp DESC
														  LIMIT %d", $parent, $limit );
								}
								else {
									$q = $wpdb->prepare( "SELECT * FROM $wpdb->wppa_albums
														  WHERE a_parent = %s
														  ORDER BY timestamp DESC", $parent );
								}
							}
							else {
								if ( $limit ) {
									$q = $wpdb->prepare( "SELECT * FROM $wpdb->wppa_albums
														  ORDER BY timestamp DESC
														  LIMIT %d", $limit );
								}
								else {
									$q = "SELECT * FROM $wpdb->wppa_albums
										  ORDER BY timestamp DESC";
								}
							}
							$albs = wppa_get_results( $q );
							wppa_cache_album( 'add', $albs );
							if ( is_array( $albs ) ) foreach ( array_keys( $albs ) as $key ) $albs[$key] = $albs[$key]['id'];
							$id = implode( '.', $albs );
							wppa( 'start_album', $id );
						}
					}
					break;
				case '#topten':
					$temp = explode( ',', $album );
					$id = isset( $temp[1] ) ? $temp[1] : 0;
					wppa( 'start_album', $id );
					$cnt = wppa_opt( 'topten_count' );
					if ( isset( $temp[2] ) ) {
						if ( $temp[2] > 0 ) {
							$cnt = $temp[2];
						}
					}
					wppa( 'is_topten', $cnt );
					wppa( 'is_topten', $cnt );
					if ( wppa( 'is_cover' ) ) {
						return false;
					}
					if ( isset( $temp[3] ) ) {
						if ( $temp[3] == 'medals' ) {
							wppa( 'medals_only', true );
						}
					}
					break;
				case '#lasten':
					$temp = explode( ',', $album );
					$id = isset( $temp[1] ) ? $temp[1] : 0;
					wppa( 'start_album', $id );
					wppa( 'is_lasten', isset( $temp[2] ) ? $temp[2] : wppa_opt( 'lasten_count' ) );

					// Limit to owner?
					if ( isset( $temp[3] ) ) {
						wppa( 'is_upldr', $temp[3] );
					}

					if ( wppa( 'is_cover' ) ) {
						return false;
					}
					break;
				case '#comten':
					$temp = explode( ',', $album );
					$id = isset( $temp[1] ) ? $temp[1] : 0;
					wppa( 'start_album', $id );
					wppa( 'is_comten', isset( $temp[2] ) ? $temp[2] : wppa_opt( 'comten_count' ) );
					if ( wppa( 'is_cover' ) ) {
						return false;
					}
					break;
				case '#featen':
					$temp = explode( ',', $album );
					$id = isset( $temp[1] ) ? $temp[1] : 0;
					wppa( 'start_album', $id );
					wppa( 'is_featen', isset( $temp[2] ) ? $temp[2] : wppa_opt( 'featen_count' ) );
					if ( wppa( 'is_cover' ) ) {
						return false;
					}
					break;
				case '#related':
					$temp = explode( ',', $album );
					$type = isset( $temp[1] ) ? $temp[1] : 'tags';	// tags is default type
					wppa( 'related_count', isset( $temp[2] ) ? $temp[2] : wppa_opt( 'related_count' ) );
					wppa( 'is_related', $type );

					$data = wppa_get_related_data();
					if ( $type == 'tags' ) {
						wppa( 'is_tag', str_replace( ',', ';', $data ) );
					}
					if ( $type == 'desc' ) {
						wppa( 'src', true );
						wppa( 'searchstring', str_replace( ';', ',', $data ) );
						wppa( 'photos_only', true );
					}
					wppa( 'photos_only', true );
					$id = 0;
					break;
				case '#tags':
					wppa( 'is_tag', substr( $album, 6 ) );
					wppa( 'photos_only', true );
					break;
				case '#cat':
					wppa( 'is_cat', substr( $album, 5 ) );
					break;
				case '#owner':
				case '#upldr':
					$temp = explode( ',', $album );
					$owner = isset( $temp[1] ) ? $temp[1] : '';
					if ( $owner == '#me' ) {
						$owner = wppa_get_user();
					}
					if ( $owner == '#bpuser' ) {
						if ( function_exists( 'bp_displayed_user_id' ) ) {
							$user_id = bp_displayed_user_id();
							if ( $user_id ) {
								$usr = get_user_by( 'ID', $user_id );
								if ( $usr ) {
									$owner = $usr->user_login;
								}
								else {
									wppa_log( 'err', 'User not found (1)' );
									return false;
								}
							}
							else {
								wppa_log( 'err', 'User not found (2)' );
								return false;
							}
						}
						else {
							wppa_log( 'err', 'Buddy Press not activated' );
							return false;
						}
					}

					if ( ! $owner ) {
						wppa_log( 'err', 'Missing owner in #owner/#upldr spec: ' . $album );
						return false;
					}

					$parent = isset( $temp[2] ) ? wppa_album_name_to_number( $temp[2] ) : 0;
					wppa( 'is_parent', $parent );

					$grandparent = isset( $temp[3] ) ? wppa_album_name_to_number( $temp[3] ) : 0;
					wppa( 'is_grandparent', $grandparent );

					if ( $keyword == '#owner' ) {
						wppa( 'is_owner', $owner );
		//				wppa( 'start_album', $owner );
					}
					else {
						wppa( 'is_upldr', $owner );
						wppa( 'photos_only', true );
					}

					break;
				case '#potdhis':
					wppa( 'is_potdhis', true );
					wppa( 'photos_only', true );
					wppa( 'start_album', '' );
					break;
				case '#all':
					wppa( 'start_album', '-2' );
					break;
				default:
					if ( wppa_is_int( $keyword ) ) {
						wppa( 'start_album', $keyword );
					}
					elseif ( wppa_is_enum( $keyword ) ) {
						wppa( 'start_album', wppa_expand_enum( $keyword ) );
					}
					elseif ( substr( $keyword, 0, 1 ) == '$' ) {
						wppa( 'start_album', wppa_album_name_to_number( substr( $keyword, 1 ) ) );
					}
					else {
						// Error / unimplemented.
						wppa_log( 'err', 'Unrecognized virtual album keyword found: ' . $album );
						wppa_out( 'Unrecognized virtual album keyword found: ' . $album );
					}

			}
		}
	}

	return true;
}

function wppa_virt_photo_to_runparms( $photo ) {

	if ( $photo && ! is_numeric( $photo ) ) {

		if ( substr( $photo, 0, 1 ) == '#' ) {		// Keyword
			switch ( substr( $photo, 0, 5 ) ) {
				case '#potd':				// Photo of the day
					$t = wppa_get_potd();
					if ( is_array( $t ) ) {
						$id = $t['id'];
						wppa( 'start_photo', $id );
					}
					else {
						wppa_out( 'Photo of the day not found' );
						return false;
					}
					break;
				case '#last':				// Last upload
					$t = explode( ',', $photo );

					// Last from album??
					if ( isset( $t[1] ) && is_numeric( $t[1] ) ) {
						$id = wppa_get_youngest_photo_id( $t[1] );
					}
					// Last from album by album="" shortcode arg?
					elseif ( wppa( 'start_album' ) ) {
						$id = wppa_get_youngest_photo_id( wppa( 'start_album' ) );
					}
					// No, last from system
					else {
						$id = wppa_get_youngest_photo_id();
					}
					wppa( 'start_photo', $id );
					break;
				default:
					wppa_out( 'Unrecognized photo keyword found: ' . $photo );
					wppa_reset_occurrance();
					return;	// Forget this occurrance
			}
			wppa( 'single_photo', $id );
		}

		// See if the photo id is a name and convert it if possible
		if ( substr( $photo, 0, 1 ) == '$' ) {		// Name preceeded by $
			$photo = substr( $photo, 1 );

			$id = wppa_get_photo_id_by_name( $photo );

			if ( $id > 0 ) {
				wppa( 'single_photo', $id );
			}
			else {
				wppa_out( 'Photo name not found: ' . $photo );
				return false;
			}
		}
	}

	// Numeric
	else {
		wppa( 'single_photo', $photo );
	}

	return true;
}


function wppa_url_to_runparms() {
global $wppa;
global $wpdb;

	// Check for valid url
	if ( ! wppa_is_url_valid() ) {
		wppa_errorbox( __( 'Invalid or incomplete url supplied', 'wp-photo-album-plus' ) );
		return false;
	}

	// Save album arg
	$wppa['start_album'] = wppa_get( 'album' );

	// Save photo enumeration
	$wppa['start_photos'] = wppa_get( 'photos' );

	// Get various switches / data
	$wppa['is_cover'] = wppa_get( 'cover' );
	$wppa['is_slide'] = wppa_get( 'slide' ) || ( wppa_get( 'album' ) !== false && ( wppa_get( 'photo' ) || wppa_get( 'photos' ) ) );
	if ( wppa_get( 'slideonly' ) ) {
		$wppa['is_slide'] = true;
		$wppa['is_slideonly'] = true;
	}
	if ( wppa_get( 'filmonly' ) ) {
		$wppa['is_slide'] = true;
		$wppa['is_filmonly'] = true;
		$wppa['is_slideonly'] = true;
		$wppa['film_on'] = true;
	}
	if ( $wppa['is_slide'] ) {
		$wppa['start_photo'] = wppa_get( 'photo' );		// Start a slideshow here
		$wppa['is_grid'] = false;
	}
	else {
		$wppa['single_photo'] = wppa_get( 'photo' ); 	// Photo is the single photoid
		$wppa['start_photo'] = wppa_get( 'photo' ); 	// Fix for the potd ussue ?????
	}
	$wppa['is_single'] = wppa_get( 'single' );			// Is a one image slideshow
	$wppa['is_topten'] = wppa_get( 'topten', 0, 'int' );
	$wppa['is_lasten'] = wppa_get( 'lasten', 0, 'int' );
	$wppa['is_comten'] = wppa_get( 'comten', 0, 'int' );
	$wppa['is_featen'] = wppa_get( 'featen', 0, 'int' );
	$wppa['albums_only'] = wppa_get( 'albums-only' );
	$wppa['photos_only'] = wppa_get( 'photos-only' );
	$wppa['medals_only'] = wppa_get( 'medals-only' );
	$wppa['is_related'] = wppa_get( 'rel', '', 'text' ); 			// either '', 'tags', 'desc'
	$wppa['related_count'] = wppa_get( 'relcount', 0, 'int' );
	$wppa['is_potdhis'] = wppa_get( 'potdhis', 0, 'int' );
	$wppa['is_parent'] = wppa_get( 'parent', 0, 'int' );
	$wppa['landscape'] = wppa_get( 'landscape', 0, 'int' );
	$wppa['portrait'] = wppa_get( 'portrait', 0, 'int' );

	if ( wppa_get( 'lbtimeout' ) ) $wppa['lbtimeout'] = wppa_get( 'lbtimeout' );
	if ( wppa_get( 'lbstart' ) ) $wppa['lbstart'] = wppa_get( 'lbstart' );

	if ( $wppa['is_related'] == 'tags' ) {
		$wppa['is_tag'] = wppa_get_related_data();
		if ( ! $wppa['related_count'] ) {
			$wppa['related_count'] = wppa_opt( 'related_count' );
		}
	}
	else {
		$wppa['is_tag'] = wppa_get( 'tag' );
	}

	$wppa['is_cat'] = wppa_get( 'cat' );

	if ( $wppa['is_related'] == 'desc' ) {
		if ( ! $wppa['related_count'] ) {
			$wppa['related_count'] = wppa_opt( 'related_count' );
		}
		$wppa['src'] = true;
		if ( $wppa['related_count'] == 0 ) $wppa['related_count'] = wppa_opt( 'related_count' );
		$wppa['searchstring'] = str_replace( ';', ',', wppa_get_related_data() );
		$wppa['photos_only'] = true;
	}

	$wppa['page'] = wppa_get( 'paged', 1 );

	if ( wppa_get( 'superview' ) ) {
		$wppa_session['superview'] = $wppa['is_slide'] ? 'slide': 'thumbs';
		$wppa_session['superalbum'] = $wppa['start_album'];
		$wppa['photos_only'] = true;
	}
	$wppa['is_upldr'] = wppa_get( 'upldr' );

	if ( $wppa['is_upldr'] ) $wppa['photos_only'] = true;
	$wppa['is_owner'] = wppa_get( 'owner' );

	if ( $wppa['is_owner'] ) {
		$albs = wppa_get_results( $wpdb->prepare( "SELECT * FROM $wpdb->wppa_albums
													 WHERE owner = %s", $wppa['is_owner'] ) );
		wppa_cache_album( 'add', $albs );
		$id = '';
		if ( $albs ) foreach ( $albs as $alb ) {
			$id .= $alb['id'].'.';
		}
		$id = rtrim( $id, '.' );
		$wppa['start_album'] = $id;
	}
	$wppa['supersearch'] = wp_strip_all_tags( wppa_get( 'supersearch' ) );
	$wppa_session['supersearch'] = $wppa['supersearch'];

	if ( $wppa['supersearch'] ) {
		$ss_info = explode( ',', $wppa['supersearch'] );
		if ( $ss_info[0] == 'a' ) {
			$wppa['albums_only'] = true;
		}
		else {
			$wppa['photos_only'] = true;
		}
	}
	$wppa['calendar'] = wp_strip_all_tags( wppa_get( 'calendar' ) );

	// New style calendar and ajax: set is_calendar
	if ( substr( wppa_get( 'calendar' ), 0, 4 ) == 'real' ) {
		$wppa['calendar'] = wppa_get( 'calendar' );
		$wppa['is_calendar'] = true;
	}
	$wppa['caldate'] = wp_strip_all_tags( wppa_get( 'caldate' ) );
	$wppa['is_inverse'] = wppa_get( 'inv' );

	// See if Multivirtual
	$virts = ['is_upldr', 'is_tag', 'is_cat', 'is_owner'];
	$virtc = 0;
	foreach( $virts as $virt ) {
		if ( wppa( $virt ) ) $virtc++;
	}
	if ( $virtc > 1 ) $wppa['is_multi_virtual'] = true;

	// See if random overrule
	$wppa['random'] = wppa_get( 'random', 0, 'int' );

	// See if max overrule
	$wppa['max'] =  wppa_get( 'max', 0, 'int' );

	// In link from supersearch collection
	$wppa['is_name'] = wppa_get( 'name', '', 'text' );
	$wppa['is_iptc'] = wppa_get( 'iptc', '', 'text' );
	$wppa['is_exif'] = wppa_get( 'exif', '', 'text' );

	return true;
}

function wppa_show_item_selection_runparms() {
global $wppa;
global $wppa_current_shortcode;

	if ( wppa_opt( 'print_debug' ) == 'none' ) return;
	if ( ! current_user_can( 'administrator' ) ) return;

	$sparms = ['single_photo', 'is_mphoto', 'is_xphoto', 'start_album', 'current_album', 'searchstring', 'is_topten', 'is_lasten', 'is_featen', 'start_photo',
			   'is_single', 'is_comten', 'is_tag', 'photos_only', 'albums_only', 'medals_only', 'is_upload', 'last_albums', 'last_albums_parent', 'is_multitagbox',
			   'is_tagcloudbox', 'is_related', 'related_count', 'is_owner', 'is_parent', 'is_upldr', 'is_cat', 'bestof', 'is_subsearch', 'is_rootsearch', 'is_superviewbox', 'is_searchbox',
			   'may_sub', 'may_root', 'shortcode_content', 'is_supersearch', 'supersearch', 'is_wppa_tree', 'is_calendar', 'current_photo', 'is_stereobox', 'is_url',
			   'is_inverse', 'is_admins_choice', 'random', 'is_combinedsearch', 'is_potdhis', 'is_contest', 'start_photos', 'landscape', 'portrait', 'is_audioonly',
			   'is_notify', 'is_virtual', 'is_multi_virtual', 'is_cover'];

	$result = '<span style="color: darkred;">' . $wppa_current_shortcode . '</span><br><span style="line-break: anywhere; color: green;">';
	foreach( $sparms as $parm ) {
		if ( $wppa[$parm] ) {
			$result .= $parm . ':';
			$val = $wppa[$parm];
			if ( $val === true ) {
				$result .= 'true';
			}
			elseif ( is_array($val) ) {
				$result .= 'array('.count($val).')';
			}
			else {
				$result .= $val;
			}
			$result .= '; ';
		}
	}
	$result .= '</span><br>';

	wppa_out( $result );
}

function wppa_show_query( $query, $count = 0 ) {
global $wppa_query_cache_hit;

	if ( ! current_user_can( 'administrator' ) ) return;
	if ( ! in_array( wppa_opt( 'print_debug' ), ['all', 'queries'] ) ) return;

	$query = ( defined( 'DOING_WPPA_AJAX' ) ? 'A ' : 'S ' ) . $query;
	if ( strlen( $query ) > 100 ) {
		$opos = strpos( $query, 'ORDER' );
		if ( $opos ) {
			if ( $opos > 100 ) {
				$query = substr( $query, 0, 80 ) . '... ' . substr( $query, $opos );
			}
		}
		else {
			$query = substr( $query, 0, 95 ) . '...';
		}
	}
	if ( $count ) {
		$query .= ' Found '.$count.' items'.($wppa_query_cache_hit?' cached':'');
	}
	wppa_out( '<span style="line-break: anywhere; color: blue;">' . $query . '</span><br>' );
}

function wppa_show_url( $url ) {

	if ( ! current_user_can( 'administrator' ) ) return;
	if ( ! in_array( wppa_opt( 'print_debug' ), ['all', 'urls'] ) ) return;

	$url = ( defined( 'DOING_WPPA_AJAX' ) ? 'A ' : 'S ' ) . $url;
	wppa_out( '<span style="line-break: anywhere; color: brown;">' . $url . '</span><br>' );
}

// Put appropriate value in wppa( 'start_album' ) and return album clause for query
function wppa_interprete_album( $alb ) {
global $wpdb;

	// Default
	wppa( 'start_album', 0 );
	$clause = " album > 0 ";
	if ( ! $alb ) return $clause;

	if ( wppa_is_posint( $alb ) ) {
		wppa( 'start_album', $alb );
		$clause = $wpdb->prepare( " album = %d ", $alb );
	}
	elseif ( substr( $alb, 0, 1 ) == '$' ) {
		$query = $wpdb->prepare( "SELECT id FROM $wpdb->wppa_albums WHERE name = %s LIMIT 1", substr( $alb, 1 ) );
		wppa_show_query( '98: '.$query );
		$id = wppa_get_var( $query );
		if ( $id ) {
			wppa( 'start_album', $id );
			$clause = $wpdb->prepare( " album = %d ", $id );
		}
		else {
			wppa_log( 'err', 'Invalid album identifier '.$alb );
			wppa( 'start_album', -99 );
			$clause = " album = -99 ";
		}
	}
	elseif ( wppa_is_enum( $alb ) ) {
		$exp = wppa_expand_enum( $alb );
		wppa( 'start_album', $exp );
		$clause = " album IN (" . str_replace( '.', ',', $exp ) . ") ";
	}

	return $clause;
}

// Get the content for the IN() clause, default '' for all albums
function wppa_get_album_IN_string( $alb ) {
global $wpdb;

	// Default
	wppa( 'start_album', 0 );
	if ( ! $alb ) $result = '';

	elseif ( wppa_is_posint( $alb ) ) {
		wppa( 'start_album', $alb );
		$result = "$alb";
	}
	elseif ( substr( $alb, 0, 1 ) == '$' ) {
		$query = $wpdb->prepare( "SELECT id FROM $wpdb->wppa_albums WHERE name = %s LIMIT 1", substr( $alb, 1 ) );
		wppa_show_query( '98: '.$query );
		$id = wppa_get_var( $query );
		if ( $id ) {
			$result = "$id";
			wppa( 'start_album', $id );
		}
		else {
			wppa_log( 'err', 'Invalid album identifier '.$alb );
			$result = "-99";
			wppa( 'start_album', -99 );
		}
	}
	elseif ( wppa_is_enum( $alb ) ) {
		$exp = wppa_expand_enum( $alb );
		wppa( 'start_album', $exp );
		$result = str_replace( ".", "','", $exp );
	}

	return $result;
}
