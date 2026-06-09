<?php
/* wppa-widget-functions.php
/* Package: wp-photo-album-plus
/*
/* Version 9.2.01.003
/*
*/

if ( ! defined( 'ABSPATH' ) ) exit();

/*
This file contans functions to get the photo of the day selection pool and to get THE photo of the day.
This fila also contains functions for the use in the widget activation screens for all widgets.
*/

// This function returns an array of photos that meet the current photo of the day selection criteria
function wppa_get_widgetphotos( $alb ) {
global $wpdb;

	$photos = array();

	// Get all the photos from the supplied album indicator
	if ( wppa_opt( 'potd_album_type' ) ) {
		if ( wppa_is_posint( $alb ) ) {
			if ( wppa_switch( 'potd_include_subs' ) ) {
				$albs = explode( '.', wppa_alb_to_enum_children( $alb ) );
				$placeholders = implode( '.', array_fill( 0, count( $albs ), '%d' ) );
				$photos = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE album IN ($placeholders)", $albs ) );
				wppa_show_query();
			}
			else {
				$photos = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE album = %d", $alb ) );
				wppa_show_query();
			}
		}
		elseif ( $alb == 'all' ) {
			$photos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos" );
			wppa_show_query();
		}
		elseif ( $alb == 'sep' ) {
			if ( wppa_switch( 'potd_include_subs' ) ) {
				$sepalbs = explode( '.', wppa_alb_to_enum_children( '-1' ) );
			}
			else {
				$sepalbs = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_albums WHERE parent = -1" );
				wppa_show_query();
			}
			$placeholders = implode( ',', array_fill( 0, count( $sepalbs ), '%d' ) );
			$photos = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE album IN ($placeholders)", $sepalbs ) );
			wppa_show_query();
		}
		elseif ( $alb == 'all-sep' ) {
			if ( wppa_switch( 'potd_include_subs' ) ) {
				$allminsepalbs = explode( '.', wppa_alb_to_enum_children( '0' ) );
			}
			else {
				$allminsepalbs = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_albums WHERE parent = 0" );
			}
			$placeholders = implode( ',', array_fill( 0, count( $allminsepalbs ), '%d' ) );
			$photos = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE album IN ($placeholders)", $allminsepalbs ) );
			wppa_show_query();
		}
		if ( wppa_switch( 'potd_inverse' ) ) {
			$allphotos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos" );
			$photos = array_diff( $allphotos, $photos );
		}
	}
	else { // virtual
		if ( $alb == 'topten' ) {

			// Find the 'top' policy
			switch ( wppa_opt( 'topten_sortby' ) ) {
				case 'mean_rating':
					$photos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos ORDER BY mean_rating DESC, rating_count DESC, views DESC LIMIT 100" );
					wppa_show_query();
					break;
				case 'rating_count':
					$photos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos ORDER BY rating_count DESC, mean_rating DESC, views DESC LIMIT 100" );
					wppa_show_query();
					break;
				case 'views':
					$photos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos ORDER BY views DESC, mean_rating DESC, rating_count DESC LIMIT 100" );
					wppa_show_query();
					break;
				default:
					wppa_log( 'err', 'Unimplemented sortig method '.wppa_opt( 'topten_sortby' ).' in wppa_get_widgetphotos()' );
					return [];
					break;
			}
		}
		elseif ( wppa_opt( 'potd_method' ) == '3' ) {	// Last uplad?
			$placeholders = implode( ',', array_fill( 0, count( $photos ), '%d' ) );
			$photos = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE id IN ($placeholders) ORDER BY timestamp DESC", $photos ) );
			wppa_show_query();
		}
	}

	// Compile status clause
	$sfilter =  wppa_opt( 'potd_status_filter' );
	$voidphotos = [];
	switch( $sfilter ) {
		case 'publish':
		case 'featured':
		case 'gold':
		case 'silver':
		case 'bronze':
			$voidphotos = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE status <> %s", $sfilter ) );
			wppa_show_query();
			break;
		case 'anymedal':
			$voidphotos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos WHERE status NOT IN ('gold', 'silver', 'bronze')" );
			wppa_show_query();
			break;
		default:
			if ( is_user_logged_in() ) {
				$voidphotos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos WHERE status = 'scheduled'" );
				wppa_show_query();
			}
			else {
				$voidphotos = $wpdb->get_col( "SELECT id FROM $wpdb->wppa_photos WHERE status IN ('private', 'scheduled')" );
				wppa_show_query();
			}
			break;
	}
	$photos = array_diff( $photos, $voidphotos );
//	$photos = wppa_strip_void_photos( $photos );

	if ( ! count( $photos ) ) return [];

	// If change every ..., order by p_order
	if ( wppa_opt( 'potd_method' ) == '4' ) {
		$placeholders = implode( ',', array_fill( 0, count( $photos ), '%d' ) );
		$photos = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $wpdb->wppa_photos WHERE id IN ($placeholders) ORDER BY p_order", $photos ) );
		wppa_show_query();
	}

	// Make sure indexes are successive so you van get the nth value by key = n
	$photos = explode( '.', implode( '.', $photos ) );

	// Ready
	return $photos;
}

// get the photo of the day
function wppa_get_potd( $details = false ) {
global $wpdb;

	$id = 0;
	$seqno = 0;
	$offset = 0;

	switch ( wppa_opt( 'potd_method' ) ) {

		// Random
		case '2':
			$album = wppa_opt( 'potd_album' );
			if ( $album == 'topten' ) {
				$images = wppa_get_widgetphotos( $album );
				if ( count( $images ) > 1 ) {	// Select a random first from the current selection
					$idx = wp_rand( 0, min( count( $images )-1, 10 ) );
					$id = $images[$idx];
				}
			}
			elseif ( $album != '' ) {
				$images = wppa_get_widgetphotos( $album );
				if ( count( $images ) ) {
					$idx = wp_rand( 0, min( count( $images )-1, 100 ) );
					$id = $images[$idx];
				}
			}
			break;

		// Last upload
		case '3':
			$album = wppa_opt( 'potd_album' );
			if ( $album == 'topten' ) {
				$images = wppa_get_widgetphotos( $album );
				if ( $images ) {

					$id = $image[0];
				}
			}
			elseif ( $album != '' ) {
				$images = wppa_get_widgetphotos( $album );
				$id = $images[0];
			}
			break;

		// Change every
		case '4':
			$album = wppa_opt( 'potd_album' );
			if ( $album != '' ) {
				$per = wppa_opt( 'potd_period' );
				$photos = wppa_get_widgetphotos( $album );
				if ( $per == 0 ) {
					if ( $photos ) {
						$id = $photos[wp_rand( 0, count( $photos )-1 )];
					}
				}
				elseif ( $per == 'day-of-week' ) {
					$offset = strval( intval( wppa_get_option( 'wppa_potd_offset', 0 ) ) % 7 );
					wppa_update_option( 'wppa_potd_offset', $offset );
					if ( $photos ) {
						$d = date_i18n( "w" );
						$d -= wppa_get_option( 'wppa_potd_offset', 0 );
						while ( $d < 1 ) $d += '7';
						$seqno = $d;
						foreach ( $photos as $img ) {
							if ( wppa_get_photo_item( $img, 'p_order' ) == $d ) $id = $img;
						}
					}
				}
				elseif ( $per == 'day-of-month' ) {
					$offset = strval( intval( wppa_get_option( 'wppa_potd_offset', 0 ) ) % 31 );
					wppa_update_option( 'wppa_potd_offset', $offset );
					if ( $photos ) {
						$d = strval(intval(date_i18n( "d" )));
						$d -= wppa_get_option( 'wppa_potd_offset', 0 );
						while ( $d < 1 ) $d += '31';
						$seqno = $d;
						foreach ( $photos as $img ) {
							if ( wppa_get_photo_item( $img, 'p_order' ) == $d ) $id = $img;
						}
					}
				}
				elseif ( $per == 'day-of-year' ) {
					$offset = strval( intval( wppa_get_option( 'wppa_potd_offset', 0 ) ) % 366 );
					wppa_update_option( 'wppa_potd_offset', $offset );
					if ( $photos ) {
						$d = strval(intval(date_i18n( "z" )));
						$d -= wppa_get_option( 'wppa_potd_offset', 0 );
						while ( $d < 0 ) $d += '366';
						$seqno = $d;
						foreach ( $photos as $img ) {
							if ( wppa_get_photo_item( $img, 'p_order' ) == $d ) $id = $img;
						}
					}
				}
				elseif ( $per == 'week' ) {
					$offset = strval( intval( wppa_get_option( 'wppa_potd_offset', 0 ) ) % 53 );
					wppa_update_option( 'wppa_potd_offset', $offset );
					if ( $photos ) {
						$w = strval(intval(date_i18n( "W" )));
						$seqno = $w;
						foreach ( $photos as $img ) {
							if ( wppa_get_photo_item( $img, 'p_order' ) == $w ) $id = $img;
						}
					}
				}
				else {
					$u = wppa_local_date( "U" ); // Seconds since 1-1-1970, local
					$u /= 3600;		//  hours since
					$u = floor( $u );
					$u /= $per;
					$u = floor( $u );


					// Cached value?
					$cache = wppa_get_option( 'wppa_potd_id_cache', false );
					if ( $cache ) {
						if ( isset( $cache[$u] ) ) {
							$id = $cache[$u];
							if ( ! wppa_photo_exists( $id ) ) {
								$id = 0;
							}
						}
					}

					// Not found in cache
					if ( ! $id ) {

						// Find the right photo out of the photos found by wppa_get_widgetphotos(),
						// based on the Change every { any timeperiod } algorithm.
						if ( $photos ) {
							$p = count( $photos );
							$idn = fmod( $u, $p );
							$ids = $photos;
							$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
							$photos = $wpdb->get_results( $wpdb->prepare( "SELECT id, p_order FROM $wpdb->wppa_photos WHERE id IN ($placeholders) ORDER BY RAND(%d)", array_merge( $ids, [$idn] ) ), ARRAY_A );
							wppa_show_query();

							// Image found
							$id = $photos[$idn]['id'];
							wppa_update_option( 'wppa_potd_id_cache', array( $u => $id ) );
						}
					}
				}
			}
			break;

		// Fixed photo
		default:
			$id = wppa_opt( 'potd_photo' );
			break;
	}

	if ( $id ) {
		$photo_data = wppa_cache_photo( $id );
		wppa_log_potd( $id );
	}
	else {
		$photo_data = false;
	}


	if ( $details ) {
		$result = ['id' => $id, 'potddata' => $photo_data, 'seqno' => $seqno, 'offset' => $offset];
	}
	else {
		$result = $photo_data;
	}
	return $result;
}

// Get widget checkbox html
function wppa_widget_checkbox( $class, $item, $value, $label, $subtext = '', $disabled = false, $onchange = '' ) {

	$result = '
	<p style="clear:both">
		<input
			id="' . $class->get_field_id( $item ) . '"
			name="' . $class->get_field_name( $item ) . '"
			type="checkbox"' .
			wppa_checked( $value ) .
			( $disabled ? ' disabled' : '' ) .
			( $onchange ? ' onchange="' . esc_attr( $onchange ) . '"' : '' ) .
		' />&nbsp;
		<label
			for="' . $class->get_field_id( $item ) . '"
			>' .
			$label . '
		</label>';
		if ( $subtext ) {
			$result .= '<small>' . strip_tags( wp_check_invalid_utf8( $subtext ), ["<br>", "<a>", "<i>", "<b>"] ) . '</small>';
		}
	$result .= '
	</p>';

	wppa_echo( $result );
}

// Widget input html
//
// Typical usage:
//
// wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );
//
function wppa_widget_input( $class, $item, $value, $label, $subtext = '' ) {

	$result =
	'<p style="clear:both">' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		'<input' .
			' class="widefat"' .
			' id="' . $class->get_field_id( $item ) . '"' .
			' name="' . $class->get_field_name( $item ) . '"' .
			' type="text"' .
			' value="' . esc_attr( $value ) . '"' .
		'/>';
		if ( $subtext ) {
			$result .= '<small>' . strip_tags( wp_check_invalid_utf8( $subtext ), ["<br>", "<a>", "<i>", "<b>"] ) . '</small>';
		}
	$result .= '
	</p>';

	wppa_echo( $result );
}

// Widget input text area
function wppa_widget_textarea( $class, $item, $value, $label ) {

	$result =
	'<p>' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		'<textarea' .
			' class="widefat"' .
			' rows="16"' .
			' id="' . $class->get_field_id( 'text' ) . '"' .
			' name="' . $class->get_field_name( 'text' ) . '"' .
			' >' .
			$value .
		'</textarea>' .
	'</p>';

	wppa_echo( $result );
}

// Widget input number_format
function wppa_widget_number( $class, $item, $value, $label, $min, $max, $subtext = '', $float = false ) {

	$_50 = wppa_is_ie() ? '60px;': '60%';

	$result = '
	<p' . ( $float ? ' style="width:50%;float:left"' : '' ) . '>
		<label
			for="' . $class->get_field_id( $item ) . '">' .
			$label . ':
		</label>
		<br>
		<input
			id="' . $class->get_field_id( $item ) . '"
			name="' . $class->get_field_name( $item ) . '"
			style="' . ( $float ? 'width:' . $_50 . ';' : '' ) . '"
			type="number"
			min="' . $min . '"
			max="' . $max . '"
			value="' . esc_attr( $value ) . '"
			onchange="' . esc_attr(
				'if(jQuery(this).val()<' . $min . '||jQuery(this).val()>' . $max . '){
					alert(\'' .
					/* Translators: lowest possib,e number, highest possible number */
					esc_js( sprintf( __( 'Please enter a number >= %1$d and <= %2$d', 'wp-photo-album-plus' ),$min, $max ) ) .
					'\');
					jQuery(this).val(\'' . $max . '\');return false;}').'"
		/>';
		if ( $subtext ) {
			$result .= '<small>' . ( $cls ? '' : '<br>' ) . strip_tags( wp_check_invalid_utf8( $subtext ), ["<br>", "<a>", "<i>", "<b>"] ) . '</small>';
		}
	$result .= '
	</p>';

	wppa_echo( $result );
}

// Widget selection box
function wppa_widget_selection( $class, $item, $value, $label, $options, $values, $disabled = array(), $cls = 'widefat', $subtext = '' ) {

	$result = '
	<p>
		<label
			for="' . $class->get_field_id( $item ) . '">' .
			$label . ':
		</label>' .
		( $cls ? '' : '<br>' ) . '
		<select
			class="' . $cls . '"
			id="' . $class->get_field_id( $item ) . '"
			name="' . $class->get_field_name( $item ) . '">';

			foreach( array_keys( $options ) as $key ) {
				$result .= '
				<option
					value="' . $values[$key] . '"' .
					( $value == $values[$key] ? ' selected' : '' ) .
					( isset( $disabled[$key] ) && $disabled[$key] ? ' disabled' : '' ) .
					'>' .
					$options[$key] . '
				</option>';
			}

		$result .= '</select>';
		if ( $subtext ) {
			$result .= '<small>' . ( $cls ? '' : '<br>' ) . strip_tags( wp_check_invalid_utf8( $subtext ), ["<br>", "<a>", "<i>", "<b>"] ) . '</small>';
		}
	$result .= '
	</p>';

	wppa_echo( $result );
}

// Widget selection box frame
function wppa_widget_selection_frame( $class, $item, $body, $label, $multi = false, $subtext = '' ) {

	$result =
	'<p>' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		'<select' .
			' class="widefat"' .
			' id="' . $class->get_field_id( $item ) . '"' .
			' name="' . $class->get_field_name( $item ) . ( $multi ? '[]' : '' ) . '"' .
			( $multi ? ' multiple' : '' ) .
			' >' .
			$body .
		'</select>';
		if ( $subtext ) {
			$result .= '<small>' . strip_tags( wp_check_invalid_utf8( $subtext ), ["<br>", "<a>", "<i>", "<b>"] ) . '</small>';
		}
	$result .= '
	</p>';

	wppa_echo( $result );
}

// Get checked html
function wppa_checked( $arg ) {

	// Backward compat yes/no selectionbox
	if ( $arg == 'no' ) {
		$result = '';
	}

	// 0
	elseif ( $arg == 0 ) {
		$result = '';
	}

	// 'yes' or 'on'
	elseif ( $arg ) {
		$result = ' checked';
	}

	// ''
	else {
		$result = '';
	}

	return $result;
}

// Log photo of the day
function wppa_log_potd( $id ) {

	// Feature enabled?
	if ( wppa_switch( 'potd_log' ) ) {

		// Get existig history
		$his = wppa_get_option( 'wppa_potd_log_data', array() );

		// If history exists and last one is current id, quit
		if ( ! empty( $his ) ) {
			if ( $his[0]['id'] == $id ) {
				return;
			}
		}

		// Compose current entry
		$now = array( 'id' => $id, 'tm' => time() );

		// Log current potd at the beginning of the existing array
		$cnt = array_unshift( $his, $now );

		// Truncate array if larger than max
		$max = wppa_opt( 'potd_log_max' );
		if ( $cnt > $max ) {
			$his = array_slice( $his, 0, $max );
		}

		// Save result
		wppa_update_option( 'wppa_potd_log_data', $his );
	}
}

// Timer
function wppa_widget_timer( $key = '', $title = '', $cached = false ) {
static $queries;
static $time;

	switch( $key ) {
		case 'init':
			$queries = get_num_queries();
			$time = microtime( true );
			break;

		case 'show':
			$queries = get_num_queries() - $queries;
			$time = microtime( true ) - $time;
			$result = "\n" .
				'<!-- End ' . $title . ' ' .
				sprintf( '%d queries in %3.1f ms. at %s',
					$queries,
					$time * 1000,
					wppa_local_date( wppa_get_option( 'time_format' ) ) ) .
				( $cached ? ' (cached) ' : ' ' ) .
				'-->';
				wppa_log( 'tim', trim( $result, "\n<>" ) );
			return $result;
			break;

		default:
			wppa_log( 'err', 'Unimplemented key in wppa_widget_timer (' . $key . ')' );
			break;
	}
}

// Cache this widget?
function wppa_cache_widget( $instance_cache ) {
global $wppa;

	if ( is_admin() ) return false;

	switch( wppa_opt( 'cache_overrule' ) ) {
		case 'always':
			$wppa['cache'] = true;
			return true;
			break;
		case 'never':
			return false;
			break;
		default:
			$wppa['cache'] = $instance_cache;
			return $instance_cache;
	}
}
