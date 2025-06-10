<?php
/* wppa-non-admin.php
* Package: wp-photo-album-plus
*
* Contains all the non admin stuff
* Version: 9.0.08.007
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

/* API FILTER and FUNCTIONS */
require_once 'wppa-filter.php';
require_once 'wppa-breadcrumb.php';
require_once 'wppa-album-covers.php';
require_once 'wppa-cart.php';
if ( ! is_admin() ) {
	require_once 'wppa-tinymce-photo-front.php';
}

/* LOAD STYLESHEET */
add_action('wp_enqueue_scripts', 'wppa_add_style');

function wppa_add_style() {
global $wppa_version;

	// Are we allowed to look in theme?
	if ( wppa_get_option( 'wppa_use_custom_style_file', 'no' ) == 'yes' ) {

		// In child theme?
		$userstyle = get_theme_root() . '/' . wppa_get_option('stylesheet') . '/wppa-style.css';
		if ( wppa_is_file($userstyle) ) {
			wp_register_style('wppa_style', get_theme_root_uri() . '/' . wppa_get_option('stylesheet')  . '/wppa-style.css', array(), $wppa_version);
			wp_enqueue_style('wppa_style');
			wp_add_inline_style( 'wppa_style', wppa_create_wppa_dynamic_css() );
			return;
		}

		// In theme?
		$userstyle = get_theme_root() . '/' . wppa_get_option('template') . '/wppa-style.css';
		if ( wppa_is_file($userstyle) ) {
			wp_register_style('wppa_style', get_theme_root_uri() . '/' . wppa_get_option('template')  . '/wppa-style.css', array(), $wppa_version);
			wp_enqueue_style('wppa_style');
			wp_add_inline_style( 'wppa_style', wppa_create_wppa_dynamic_css() );
			return;
		}
	}

	// Use standard
	$style_file = dirname( __FILE__ ) . '/wppa-style.css';
	if ( wppa_is_file( $style_file ) ) {
		$ver = wppa_local_date( "ymd-Gis", filemtime( $style_file ) );
	}
	else {
		$ver = $wppa_version;
	}
	wp_register_style('wppa_style', WPPA_URL.'/wppa-style.css', array(), $ver);
	wp_enqueue_style('wppa_style');

	$the_css = wppa_create_wppa_dynamic_css();
	wp_add_inline_style( 'wppa_style', $the_css );
}

/* SEO META TAGS AND SM SHARE DATA */
add_action('wp_head', 'wppa_add_metatags', 5);

function wppa_add_metatags() {
global $wpdb;

	// Share info for sm that uses og
	$id = wppa_get( 'photo' );
	if ( ! wppa_photo_exists( $id ) ) {
		$id = false;
	}
	if ( $id ) {

		// SM may not accept images from the cloud.
		wppa( 'for_sm', true );

		// SM does not want version numbers
		wppa( 'no_ver', true );

		$imgurl = wppa_get_photo_url( $id );
		wppa( 'no_ver', false );
		wppa( 'for_sm', false );
	}
	else {
		$imgurl = '';
	}

	if ( $id ) {

		if ( wppa_switch( 'share_twitter' ) ) {
			$thumb = wppa_cache_photo( $id );

			// Twitter wants at least 280px in width, and at least 150px in height
			if ( $thumb ) {
				$x = wppa_get_photo_item( $id, 'photox' );
				$y = wppa_get_photo_item( $id, 'photoy' );
			}
			if ( $thumb && $x >= 280 && $y >= 150 ) {
				$card = 'summary_large_image';
			}
			else {
				$card = 'summary';
			}
			$title  = wppa_get_photo_name( $id );
			$desc 	= wppa_get_og_desc( $id, 'short' );
			$url 	= ( is_ssl() ? 'https://' : 'http://' ) . wppa_http_host() . wppa_request_uri();
			$site   = get_bloginfo( 'name' );
			$creat 	= wppa_opt( 'twitter_account' );

				wppa_echo( '
<!-- WPPA+ Twitter Share data -->
<meta name="twitter:card" content="' . $card . '">
<meta name="twitter:site" content="' . esc_attr( $site ) . '">
<meta name="twitter:title" content="' . esc_attr( sanitize_text_field( $title ) ) . '">
<meta name="twitter:text:description" content="' . esc_attr( sanitize_text_field( $desc ) ) . '">
<meta name="twitter:image" content="' . esc_url( $imgurl ) . '">' );
if ( $creat ) {
	wppa_echo( '
<meta name="twitter:creator" content="' . $creat . '">' );
}
wppa_echo( '
<!-- WPPA+ End Twitter Share data -->
' );
		}

		if ( wppa_switch( 'og_tags_on' ) ) {
			$thumb = wppa_cache_photo( $id );
			if ( $thumb ) {
				$title  = wppa_get_photo_name( $id );
				$desc 	= wppa_get_og_desc( $id );
				$url 	= ( is_ssl() ? 'https://' : 'http://' ) . wppa_http_host() . wppa_request_uri();
				$url 	= wppa_convert_to_pretty( $url, false, true );
				$site   = get_bloginfo('name');
				$mime 	= wppa_get_mime_type( $id );
				wppa_echo( '
<!-- WPPA+ Og Share data -->
<meta property="og:site_name" content="' . esc_attr( sanitize_text_field( $site ) ) . '" />
<meta property="og:type" content="article" />
<meta property="og:url" content="' . $url . '" />
<meta property="og:title" content="' . esc_attr( sanitize_text_field( $title ) ) . '" />' );
if ( $mime ) {
	wppa_echo( '
<meta property="og:image" content="' . esc_url( sanitize_text_field( $imgurl ) ) . '" />
<meta property="og:image:type" content="' . $mime . '" />
<meta property="og:image:width" content="' . wppa_get_photox( $id ) . '" />
<meta property="og:image:height" content="' . wppa_get_photoy( $id ) . '" />' );
}
if ( $desc ) {
	wppa_echo( '
<meta property="og:description" content="' . esc_attr( sanitize_text_field( $desc ) ) . '" />' );
}
wppa_echo( '
<!-- WPPA+ End Og Share data -->
' );
			}
		}
	}

	// If we want page specific metatags, make them
	if ( wppa_switch( 'meta_page' ) || wppa_switch( 'meta_all' ) ) {

		// Try to find albums on the current page where featured items may be
		$the_album = '';
		$the_photo = '';
		$the_ids = array();

		// Case 1: Query arg contains album spec
		$album = wppa_get( 'album' );
		if ( wppa_is_posint( $album ) ) {
			$the_album = $album;
		}

		// Case 2: [wppa] shortcode
		if ( ! $the_album ) {
			$the_page = get_post();
			$the_content = $the_page->post_content;

			$shortcodes = wppa_find_shortcodes( $the_content );
			foreach ( $shortcodes as $shortcode ) {

				$albums_used = '';
				$photo_used = '';

				// Album specified
				if ( isset( $shortcode['attributs']['album'] ) ) {
					$a = $shortcode['attributs']['album'];
					if ( substr( $a, 0, 1 ) == '$' ) {
						$a = wppa_get_album_id_by_name( substr( $a, 1 ) );
					}
					$albums_used = wppa_expand_enum( $a );
				}

				// Photo specified
				if ( isset( $shortcode['attributs']['photo'] ) ) {
					$photo_used = wppa_expand_enum( $shortcode['attributs']['photo'] );
				}
				if ( $shortcode['shortcode'] == 'photo' ) {
					$photo_used = wppa_expand_enum( $shortcode['attributs'][0] );
				}

				// wppa but no album and no photo = generic including subalbums
				if ( $shortcode['shortcode'] == 'wppa' && !isset( $shortcode['attributs']['album'] ) && !isset( $shortcode['attributs']['photo'] ) ) {
					$albums_used =  wppa_alb_to_enum_children( '0' );
				}

				// Cover(s) or comtent implies subalbums are reacheable
				if ( wppa_is_int( $albums_used ) && isset( $shortcode['type'] ) && in_array( $shortcode['type'], ['content', 'cover', 'covers'] ) ) {
					$albums_used = wppa_alb_to_enum_children( $albums_used );
				}

				if ( $albums_used ) $the_album .= '.'.$albums_used;
				if ( $photo_used ) $the_photo .= '.'.$photo_used;
			}

			$the_albums = explode( '.', $the_album );
			foreach( array_keys( $the_albums ) as $k ) {
				if ( ! wppa_is_int( $the_albums[$k] ) ) {
					if ( substr( $the_albums[$k], 0, 1 ) == '$' ) {
						$id = wppa_get_album_id_by_name( substr( $the_albums[$k], 1 ) );
						if ( wppa_is_posint( $id ) ) {
							$the_albums[$k] = $id;
						}
						else unset( $the_albums[$k] );
					}
					else unset( $the_albums[$k] );
				}
			}

			$the_album = implode( '.', $the_albums );
			$the_photos = explode( '.', $the_photo );

			foreach( array_keys( $the_photos ) as $k ) {
				if ( ! wppa_is_posint( $the_photos[$k] ) ) {
					unset( $the_photos[$k] );
				}
			}
			$the_photo = implode( '.', $the_photos );
		}

		// Case 3: All always
		if ( wppa_switch( 'meta_all' ) ) {
			$the_album = 'all';
		}

		// Now interprete the_album and find item ids
		$where = 'page';
		if ( $the_album ) {
			if ( wppa_is_posint( $the_album ) ) {
				$query 	 = "SELECT id FROM $wpdb->wppa_photos WHERE album = $the_album AND status = 'featured'";
			}
			elseif ( wppa_is_enum( $the_album ) ) {
				$the_album = str_replace( '.', ',', wppa_expand_enum( $the_album ) );
				$query 	= "SELECT id FROM $wpdb->wppa_photos WHERE album IN ($the_album) AND status = 'featured'";
			}
			elseif ( 'all' == $the_album ) {
				$where = 'site';
				$query 	= "SELECT id FROM $wpdb->wppa_photos WHERE status = 'featured'";
			}
			else {
				$query 	= "SELECT id FROM $wpdb->wppa_photos WHERE album = 0 AND status = 'featured'";
			}

			$the_ids = wppa_get_col( $query );
		}

		// Photos used need not to be featured
		if ( $the_photo ) {
			$the_ids = array_merge( $the_ids, explode( '.', $the_photo ) );
		}

		$keywords = wppa_get_keywords( $the_ids );
		if ( $keywords ) {
			wppa_echo( "\n<!-- WPPA+ BEGIN Featured photos on this $where -->" );
			wppa_echo( "\n".'<meta name="keywords" content="' . esc_attr( $keywords ) . '">', ['keeplinebreaks' => true] );
			wppa_echo( "\n<!-- WPPA+ END Featured photos on this $where -->\n" );
		}
	}

	// Facebook Admin and App
	if ( ( wppa_switch( 'share_on' ) ||  wppa_switch( 'share_on_widget' ) ) &&
		( wppa_switch( 'facebook_comments' ) || wppa_switch( 'facebook_like' ) || wppa_switch( 'share_facebook' ) ) ) {
		wppa_echo( "\n<!-- WPPA+ BEGIN Facebook meta tags -->" );
		if ( wppa_opt( 'facebook_admin_id' ) ) {
			wppa_echo( "\n\t<meta property=\"fb:admins\" content=\"" . wppa_opt( 'facebook_admin_id' ) . "\" />" );
		}
		if ( wppa_opt( 'facebook_app_id' ) ) {
			wppa_echo( "\n\t<meta property=\"fb:app_id\" content=\"" . wppa_opt( 'facebook_app_id' ) . "\" />" );
		}
		if ( $imgurl ) {
			wppa_echo( '
<link rel="image_src" href="'.esc_url( $imgurl ).'" />' );
		}
		wppa_echo( '
<!-- WPPA+ END Facebook meta tags -->
' );
	}
}

/* LOAD WPPA+ THEME */
add_action( 'init', 'wppa_load_theme', 100 );

function wppa_load_theme() {

	// Are we allowed to look in theme?
	if ( wppa_switch( 'use_custom_theme_file' ) ) {

		$usertheme = get_theme_root() . '/' . wppa_get_option( 'template' ) . '/wppa-theme.php';
		if ( wppa_is_file( $usertheme ) ) {
			require_once $usertheme;
			return;
		}
	}
	require_once 'wppa-theme.php';
}

/* LOAD FOOTER REQD DATA */
//add_action( 'wp_footer', 'wppa_load_footer', 100 );

function wppa_load_footer() {
global $wppa_session;

	// Do the upload if required and not yet done
	wppa_user_upload();

}

/* FACEBOOK COMMENTS */
add_action( 'wp_footer', 'wppa_fbc_setup', 1 );

function wppa_fbc_setup() {
global $wppa_locale;

	if ( wppa_switch( 'load_facebook_sdk' ) &&  			// Facebook sdk requested
		( 	wppa_switch( 'share_on' ) ||
			wppa_switch( 'share_on_widget' ) ||
			wppa_switch( 'share_on_thumbs' ) ||
			wppa_switch( 'share_on_lightbox' ) ||
			wppa_switch( 'share_on_mphoto' ) ) &&
		(	wppa_switch( 'share_facebook' ) ||
			wppa_switch( 'facebook_like' ) ||
			wppa_switch( 'facebook_comments' ) )			// But is it used by wppa?
	) {

		$the_html = '
		<!-- Facebook Comments for WPPA+ -->
		<div id="fb-root"></div>';

		$the_js = '
		(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/' . esc_attr( $wppa_locale ) . '/all.js#xfbml=1";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, \'script\', \'facebook-jssdk\'));';

		wppa_echo( $the_html );
		wppa_add_inline_script( 'wppa', $the_js, false );
	}
}

/* SKIP JETPACK FOTON ON WPPA+ IMAGES */
add_filter('jetpack_photon_skip_image', 'wppa_skip_photon', 10, 3);
function wppa_skip_photon($val, $src, $tag) {
	$result = $val;
	if ( strpos($src, WPPA_UPLOAD_URL) !== false ) $result = true;
	return $result;
}

/* MAKE SURE TEXT WIDGET SUPPORTS SHORTCODES */
add_filter( 'widget_text', 'do_shortcode' );

/* We use bbPress */
// editor bbpress in tinymce mode
function wppa_enable_visual_editor_in_bbpress( $args = array() ) {

	if ( wppa_switch( 'photo_on_bbpress' ) ) {
		$args['tinymce'] = true;
		$args['teeny'] = false;
	}
    return $args;
}
add_filter( 'bbp_after_get_the_content_parse_args', 'wppa_enable_visual_editor_in_bbpress' );

// remove insert wp image button
function wppa_remove_image_button_in_bbpress( $buttons ) {

	if ( wppa_switch( 'photo_on_bbpress' ) ) {
		if ( ( $key = array_search( 'image', $buttons ) ) !== false ) {
			unset( $buttons[$key] );
		}
	}
	return $buttons ;
}
add_filter( 'bbp_get_teeny_mce_buttons', 'wppa_remove_image_button_in_bbpress' );

// enable processing shortcodes
function wppa_enable_shortcodes_in_bbpress( $content ) {

	if ( wppa_switch( 'photo_on_bbpress' ) ) {
		$content = do_shortcode( $content );
	}
	return $content;
}
add_filter( 'bbp_get_topic_content', 'wppa_enable_shortcodes_in_bbpress', 1000 );
add_filter( 'bbp_get_reply_content', 'wppa_enable_shortcodes_in_bbpress', 1000 );

// Disable Autoptimize from optimizing our javascript
add_filter( 'autoptimize_filter_js_noptimize', 'wppa_nopti_js', 10, 2 );
function wppa_nopti_js( $nopt_in, $html_in ) {
	if ( strpos( $html_in, 'data-wppa="yes"' ) !== false ) {
		return true;
	}
	else {
		return false;
	}
}

// This function contains strings for i18n from files not included
// in the search for frontend required translatable strings
// Mainly from widgets
function wppa_dummy() {

	// Commet widget
	__( 'wrote' , 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no commented photos (yet)', 'wp-photo-album-plus' );

	// Featen widget
	__( 'View the featured photos', 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no featured photos (yet)', 'wp-photo-album-plus' );

	// Lasten widget
	__( 'View the most recent uploaded photos', 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no uploaded photos (yet)', 'wp-photo-album-plus' );

	// Potd widget
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'By:', 'wp-photo-album-plus' );

	// Slideshow widget
	__( 'No album defined (yet)', 'wp-photo-album-plus' );

	// Thumbnail widget
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no photos (yet)', 'wp-photo-album-plus' );

	// Upldr widget
	__( 'There are too many registered users in the system for this widget' , 'wp-photo-album-plus' );
	__( 'Photos uploaded by', 'wp-photo-album-plus' );

	// Topten widget
	/* translators: integer number */
	_n( '%d vote', '%d votes', $n, 'wp-photo-album-plus' );
	/* translators: integer number */
	_n( '%d view', '%d views', $n, 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no rated photos (yet)', 'wp-photo-album-plus' );

	// From wppa-filter.php
	__( 'delay', 'wp-photo-album-plus' );
	__( 'cache', 'wp-photo-album-plus' );
	__( 'single image', 'wp-photo-album-plus' );

}