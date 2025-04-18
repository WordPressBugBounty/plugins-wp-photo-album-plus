<?php
/* wppa-wrappers.php
* Package: wp-photo-album-plus
*
* Contains wrappers for standard php functions
* For security and bug reasons
*
* Version 9.0.05.005
*
*/

// Init wp filesystem
require_once ( ABSPATH . '/wp-admin/includes/file.php' );
global $wp_filesystem;

if ( ! $wp_filesystem )	{
	WP_Filesystem();
}

// To fix a bug in PHP as that photos made with the selfie camera of an android smartphone
// erroneously cause the PHP warning 'is not a valid JPEG file' and cause imagecreatefromjpag crash.
function wppa_imagecreatefromjpeg( $file ) {

	$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check

	if ( ! wppa_is_path_safe( $file ) && ! $_FILES ) {
		wppa_log( 'Err', 'Unsafe from path detected in wppa_imagecreatefromjpeg(): ' . wppa_shortpath(  $file ) );
		return false;
	}
//	ini_set( 'gd.jpeg_ignore_warning', true );

	$img = @imagecreatefromjpeg( $file );
	if ( ! $img ) {
		wppa_log( 'Err', 'Could not create memoryimage from file ' . wppa_shortpath( $file ) );
	}
	return $img;
}

// Wrapper for imagecreatefromgif( $file ), verifies safe pathnames
function wppa_imagecreatefromgif( $file ) {

	$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check

	if ( ! wppa_is_path_safe( $file ) && ! $_FILES ) {
		wppa_log( 'Err', 'Unsafe from path detected in wppa_imagecreatefromgif(): ' . wppa_shortpath( $file ) );
		return false;
	}

	$img = imagecreatefromgif( $file );
	if ( ! $img ) {
		wppa_log( 'Err', 'Could not create memoryimage from file ' . wppa_shortpath( $file ) );
	}
	return $img;
}

// Wrapper for imagecreatefrompng( $file ), verifies safe pathnames
function wppa_imagecreatefrompng( $file ) {

	$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check

	if ( ! wppa_is_path_safe( $file ) && ! $_FILES ) {
		wppa_log( 'Err', 'Unsafe from path detected in wppa_imagecreatefrompng(): ' . wppa_shortpath( $file ) );
		return false;
	}

	$img = imagecreatefrompng( $file );
	if ( ! $img ) {
		wppa_log( 'Err', 'Could not create memoryimage from file ' . wppa_shortpath( $file ) );
	}
	return $img;
}

// Wrapper for imagecreatefromwebp( $file ), verifies safe pathnames
function wppa_imagecreatefromwebp( $file ) {

	$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check

	if ( ! wppa_is_path_safe( $file ) && ! $_FILES ) {
		wppa_log( 'Err', 'Unsafe from path detected in wppa_imagecreatefromwebp(): ' . wppa_shortpath( $file ) );
		return false;
	}

	$img = imagecreatefromwebp( $file );
	if ( ! $img ) {
		wppa_log( 'Err', 'Could not create memoryimage from file ' . wppa_shortpath( $file ) );
	}
	return $img;
}

// Wrapper for getimagesize( $file ), verifies safe pathnames
function wppa_getimagesize( $file ) {

	if ( ! wppa_is_path_safe( $file ) ) {
		wppa_log( 'Err', 'Unsafe from path detected in wppa_getimagesize(): ' . wppa_shortpath( $file ) );
		return false;
	}

	$result = getimagesize( $file );
	if ( ! $result ) {
		wppa_log( 'Err', 'Could not read image size from ' . wppa_shortpath( $file ) );
	}
	return $result;
}

// Wrapper for imagegif
function wppa_imagegif( $image, $file ) {
global $wp_filesystem;

	$bret = imagegif( $image, $file );
	$wp_filesystem->chmod( $file );
	return $bret;
}

// Wrapper for imagejpeg
function wppa_imagejpeg( $image, $file, $prec = 0 ) {
global $wp_filesystem;

	$ext = wppa_get_ext( $file );
	if ( $ext != 'jpg' ) {
		wppa_log( 'ERR', 'Trying to save a jpg with extension ' . $ext, true );
		return false;
	}
	if ( ! $prec ) {
		$prec = wppa_opt( 'jpeg_quality' );
	}
	$bret = imagejpeg( $image, $file, $prec );
	$wp_filesystem->chmod( $file );
	return $bret;
}

// Wrapper for imagepng
function wppa_imagepng( $image, $file ) {
global $wp_filesystem;

	$bret = imagepng( $image, $file );
	$wp_filesystem->chmod( $file );
	return $bret;
}

function wppa_imagewebp( $image, $file ) {
global $wp_filesystem;

	$bret = imagewebp( $image, $file );
	$wp_filesystem->chmod( $file );
	return $bret;
}

// Wrapper for copy( $from, $to ) that verifies that the pathnames are safe for our application
// In case of unexpected operation: Generates a warning in the wppa log, and does not perform the copy.
function wppa_copy( $from, $to, $from_upload = false ) {
global $wp_filesystem;

	$dummy = wp_verify_nonce( 'dummy-code', 'dummy-action' ); // Just to satisfy Plugin Check

	// First test if we are uploading
	if ( ! wppa_is_path_safe( $from ) && $_FILES && ! $from_upload ) {
		if ( ! wppa_is_path_safe( $to ) ) {
			wppa_log( 'Err', '1 Unsafe to path detected in wppa_copy(): ' . wppa_shortpath( $to ) );
			return false;
		}

		// If already exists, delete old one
		if ( wppa_is_file( $to ) ) {
			wp_delete_file( $to );
		}
		$bret = wppa_move_uploaded_file( $from, $to );
		return $bret;
	}

	if ( ! wppa_is_path_safe( $from ) && ! $from_upload ) {
		wppa_log( 'Err', '2 Unsafe from path detected in wppa_copy(): ' . wppa_shortpath( $from ) );
		return false;	// For diagnostic purposes, no return here yet
	}
	if ( ! wppa_is_path_safe( $to ) ) {
		wppa_log( 'Err', '3 Unsafe to path detected in wppa_copy(): ' . wppa_shortpath( $to ) );
		return false; // For diagnostic purposes, no return here yet
	}

	$overwrite = true;
	$bret = $wp_filesystem->copy( $from, $to, $overwrite );
	$wp_filesystem->chmod( $to );
	return $bret;
}

function wppa_filesize( $file ) {
global $wp_filesystem;

	if ( ! wppa_is_path_safe( $file ) ) {
		wppa_log( 'Err', 'Unsafe path detected in wppa_filesize(): ' . wppa_shortpath( $file ) );
		return false;	// For diagnostic purposes, no return here yet
	}

	return $wp_filesystem->size( $file );
}

// Wrapper for move_uploaded_file( $from, $to ) that verifies that the pathnames are safe for our application
function wppa_move_uploaded_file( $from, $to ) {
global $wp_filesystem;

	if ( ! wppa_is_path_safe( $to ) ) {
		wppa_log( 'Err', 'Unsafe to path detected in wppa_move_uploaded_file(): ' . wppa_shortpath( $to ) );
		return false;
	}
	if ( strpos( $from, '../' ) !== false ) {
		wppa_log( 'Err', 'Unsafe from path detected in wppa_move_uploaded_file(): ' . $from );
		return false;
	}

	$bret = wppa_copy( $from, $to, true );	// Set 'from uploaded file' to prevent inf loop from wppa_copy and wppa_move_uploaded_file

	return $bret;
}

// Wrapper for rename
function wppa_rename( $from, $to ) {
global $wp_filesystem;

	if ( ! wppa_is_path_safe( $from ) ) {
		wppa_log( 'Err', 'Unsafe from path detected in wppa_rename(): ' . wppa_shortpath( $from ) );
		return false;
	}
	if ( ! wppa_is_path_safe( $to ) ) {
		wppa_log( 'Err', 'Unsafe to path detected in wppa_rename(): ' . wppa_shortpath( $to ) );
		return false;
	}

	$bret = false;
	if ( file_exists( $from ) ) {
		$bret = $wp_filesystem->move( $from, $to );
		if ( $bret ) {
			wppa_log( 'Fso', wppa_shortpath( $from ) . ' renamed to ' . wppa_shortpath( $to ) );
		}
		else {
			wppa_log( 'Fso', 'Could not rename file ' . wppa_shortpath( $from ) . ' to ' . wppa_shortpath( $to ) );
		}
	}
	else {
		wppa_log( 'Fso', 'Could not rename non existent file ' . wppa_shortpath( $from ) . ' to ' . wppa_shortpath( $to ) );
	}

	return $bret;
}

// Wrapper for fopen
function wppa_fopen( $file, $mode ) {

	// Is path safe?
	if ( ! wppa_is_path_safe( $file ) ) {
		wppa_log( 'Err', 'Unsafe to path detected in wppa_fopen(): ' . wppa_shortpath( $file ) );
		return false; // For diagnostic purposes, no return here yet
	}

	// When opening for reading, the file must exist
	if ( strpos( $mode, 'r' ) !== false && ! is_file( $file ) ) {
		return false;
	}
	return fopen( $file, $mode );
}

// Wrapper for fclose
function wppa_fclose( $handler ) {

	if ( $handler ) {
		fclose( $handler );
	}
}

function wppa_fwrite( $handle, $string ) {
	return fwrite( $handle, $string );
}

// Wrapper for glob
// This wrapper never returns the . and .. dirs
// Returns always an array
// Additional flags: WPPA_ONLYDIRS === GLOB_ONLYDIR, WPPA_ONLYFILES
define( 'WPPA_ONLYDIRS', GLOB_ONLYDIR );
define( 'WPPA_ONLYFILES', 1024 );
function wppa_glob( $pattern, $flags = 0, $wp_content = false ) {

	// Is path safe?
	$dir = dirname( $pattern );
	if ( ! wppa_is_path_safe( $dir, $wp_content ) ) {
		wppa_log( 'Err', 'Unsafe path detected in wppa_glob(): ' . wppa_shortpath( $dir ) );
		return array();
	}

	// Get all items
	$all_items = glob( $pattern );

	// Init result;
	$result = array();

	// Process dirlist
	if ( ! empty( $all_items ) ) foreach( $all_items as $item ) {

		if ( $flags & WPPA_ONLYDIRS ) {
			if ( is_dir( $item ) && basename( $item ) != '.' && basename( $item ) != '..' ) {
				$result[] = wppa_flips( $item );
			}
		}
		elseif ( $flags & WPPA_ONLYFILES ) {
			if ( is_file( $item ) ) {
				$result[] = wppa_flips( $item );
			}
		}
		elseif ( basename( $item ) != '.' && basename( $item ) != '..' ) {
			$result[] = wppa_flips( $item );
		}
	}

	return $result;
}

// Wrapper for unlink
function wppa_unlink( $file, $log = true ) {
global $wppa_nodelete;

	if ( ! wppa_is_path_safe( $file ) ) {
		wppa_log( 'Err', 'Unsafe path detected in wppa_unlink(): ' . wppa_shortpath( $file ) );
		return false;
	}
	clearstatcache();
	if ( ! $wppa_nodelete ) {
		if ( is_file( $file ) ) {
			wp_delete_file( $file );
			clearstatcache();
			if ( ! is_file ( $file ) && $log ) {
				wppa_log( 'Fso', wppa_shortpath( $file ) . ' removed' );
			}
			else {
				wppa_log( 'War', wppa_shortpath( $file ) . ' could not be removed' );
			}
		}
	}
	return true;
}

// Make directory tree recursively
function wppa_mktree( $path ) {

	$bret = _wppa_mktree( $path );
	if ( ! $bret ) {
		wppa_log( 'Err', 'Could not create ' . $path );
	}
	return $bret;
}

function _wppa_mktree( $path ) {

	if ( wppa_is_dir( $path ) ) {
		wppa_chmod( $path );
		return true;
	}

	// To prevent infinite recursion on faulty instalations
	if ( $path == dirname( $path ) ) {

		// We are at the top: /
		return false;
	}

	$bret = _wppa_mktree( dirname( $path ) );
	if ( $bret ) {
		wppa_mkdir( $path );
	}

	return ( is_dir( $path ) );
}

// Wrapper for mkdir
function wppa_mkdir( $dir ) {
global $wp_filesystem;

	// Path safe?
	if ( ! wppa_is_path_safe( $dir ) ) {
		wppa_log( 'Err', 'Unsafe path detected in wppa_mkdir(): ' . wppa_shortpath( $dir ) );
		return false;
	}

	// Already exists?
	elseif ( $wp_filesystem->is_dir( $dir ) ) {
		$wp_filesystem->chmod( $dir );
		return true;
	}

	// Create dir
	else {
		$wp_filesystem->mkdir( $dir );

		if ( $wp_filesystem->is_dir( $dir ) ) {
			wppa_log( 'Fso', 'Created path ' . wppa_shortpath( $dir ) );
			return true;
		}
		else {
			wppa_log( 'Err', 'Could not create ' . wppa_shortpath( $dir ) );
			return false;
		}
	}
}

function wppa_rmdir( $dir, $when_empty = false ) {
global $wp_filesystem;

	// Path safe?
	if ( ! wppa_is_path_safe( $dir ) ) {
		wppa_log( 'Err', 'Unsafe path detected in wppa_rmdir(): ' . wppa_shortpath( $dir ) );
		return false;
	}

	// If not exists, we're done
	if ( ! wppa_is_dir( $dir ) ) return;

	// Get content of the dir
	$files = wppa_glob( $dir . '/*' );

	// If $when_empty, do not remove when not empty
	if ( $when_empty && ! empty( $files ) ) {
		return;
	}

	// Remove all files
	foreach( $files as $file ) {
		if ( is_file( $file ) ) {
			wppa_unlink( $file );
		}
	}

	// Empty all dirs
	foreach( $files as $file ) {
		if ( is_dir( $file ) ) {
			wppa_rmdir( $file );
		}
	}

	// Remove dir
	$files = wppa_glob( $dir . '/*' );
	if ( empty( $files ) ) {
		$wp_filesystem->delete( $dir );
		clearstatcache( true, $dir );
	}
	if ( wppa_is_dir( $dir ) ) {
		wppa_log( 'Err', 'Could not remove dir ' . wppa_shortpath( $dir ) );
	}
	else {
		wppa_log( 'Fso', 'Successfully removed dir ' . wppa_shortpath( $dir ) );
	}
	return;
}

function wppa_chmod( $fso ) {
global $wp_filesystem;

	$fso = rtrim( $fso, '/' );

	$wp_filesystem->chmod( $fso );

	return;
}

// Wrapper for is_dir
function wppa_is_dir( $dir ) {
global $wp_filesystem;

	if ( ! $dir ) return false;

	$bret = $wp_filesystem->is_dir( $dir );
	return $bret;
}

// Wrapper for is_file
function wppa_is_file( $path ) {
global $wp_filesystem;

	if ( ! $path ) return false;

	$bret = $wp_filesystem->is_file( $path );
	return $bret;
}

// Write an entire file
function wppa_put_contents( $path, $contents, $log = true ) {
global $wp_filesystem;

	if ( ! wppa_is_path_safe( $path ) ) {
		if ( $log ) wppa_log( 'Err', 'Unsafe path detected in wppa_put_contents(): ' . wppa_shortpath( $path ) );
		return false;
	}

	if ( ! $wp_filesystem->is_dir( dirname( $path ) ) ) {
		wppa_mktree( dirname( $path ) );
	}

	$fp = @fopen( $path, 'wb' );
	if ( ! $fp )
		return false;

//	mbstring_binary_safe_encoding();

//	$data_length = strlen( $contents );

	$bytes_written = fwrite( $fp, $contents );
	clearstatcache();

//	reset_mbstring_encoding();

	fclose( $fp );

//	if ( $data_length !== $bytes_written ) {
//		return false;
//	}

	wppa_chmod( $path );

	return true;
}

// Read an entire file
function wppa_get_contents( $file ) {
global $wp_filesystem;

	// May be inside wp-content, may not be remote
	if ( ! wppa_is_path_safe( $file, true, false ) ) {
		wppa_log( 'Err', 'Unsafe path detected in wppa_get_contents(): ' . wppa_shortpath( $path ) );
		return false;
	}

	if ( is_file( $file ) ) {
		$result = $wp_filesystem->get_contents( $file );
	}
	else {
		$result = false;
	}
	return $result;
}

// Read entire file into array
function wppa_get_contents_array( $path, $log = true ) {

	if ( ! wppa_is_path_safe( $path ) ) {
		if ( $log ) wppa_log( 'Err', 'Unsafe path detected in wppa_get_contents_array(): ' . wppa_shortpath( $path ) );
		return false;
	}
	if ( is_file( $path ) ) {
		$result = file( $path );
	}
	else {
		$result = false;
	}
	return $result;
}

// Utility to check if a given full filepath is safe to manipulate upon
function wppa_is_path_safe( $path, $wp_content = false, $may_remote = true ) {
global $wppa_lang;
global $wppa_log_file;

	// Normalize in case of windows server
	$path = wppa_flips( $path );

	// Unsafe protocols
	if ( stripos( $path, 'phar://' ) !== false ) {
		return false;
	}

	// Safe protocols
	if ( strpos( strtolower( $path ), 'http://' ) === 0 ) {
		return $may_remote;
	}
	if ( strpos( strtolower( $path ), 'https://' ) === 0 ) {
		return $may_remote;
	}

	// During activation/setup
	if ( ! defined( 'WPPA_UPLOAD_PATH' ) ) return true;

	// The following files are safe to read or write to
	$safe_files = array( WPPA_PATH . '/index.php',
						 WPPA_PATH . '/wppa-dump.txt',
						 WPPA_CONTENT_PATH . '/uploads/index.php',
						 $wppa_log_file,
						 WPPA_CONTENT_PATH . '/plugins/wp-photo-album-plus/img/audiostub.jpg',
						 WPPA_CONTENT_PATH . '/plugins/wp-photo-album-plus/img/documentstub.png',
						 );

	// Verify specific files
	if ( in_array( $path, $safe_files ) ) {
		return true;
	}

	// wp-content is only safe if explixitely asked for (glob in import proc)
	if ( $wp_content ) {
		if ( strpos( $path, WPPA_CONTENT_PATH ) === 0 ) {
			return true;
		}
	}

	// The following root dirs are safe, including all their subdirs, to read/write into
	$source_dir = wppa_opt( 'source_dir' ) ? wppa_opt( 'source_dir' ) : WPPA_CONTENT_PATH;
	$safe_roots = array( WPPA_CONTENT_PATH,
						 WPPA_UPLOAD_PATH,
						 WPPA_DEPOT_PATH,
						 $source_dir,
						);

	// Verify roots
	foreach( array_keys( $safe_roots ) as $key ) {

		if ( $path == $safe_roots[$key] ) {
			return true;
		}

		// Starts the path with a safe root?
		if ( strpos( $path, $safe_roots[$key] ) === 0 ) {

			// Path traversal attempt?
			if ( strpos( $path, '../' ) !== false || strpos( $path, '/..' ) !== false ) {
				return false;
			}

			// Passed tests
			return true;
		}
	}

	// No safe root
	return false;
}

// PHP unserialize() is unsafe because it can produce dangerous objects
// This function unserializes arrays only, except when scabn is on board
// In case of error or dangerous data, returns an empty array
function wppa_unserialize( $xstring, $is_session = false ) {

	if ( version_compare( PHP_VERSION, '7.0.0') >= 0 ) {
		if ( $is_session && wppa_get_option( 'wppa_use_scabn' ) == 'yes' ) {
			return unserialize( $xstring, array( 'allowed_classes' => array( 'wfCart' ) ) );
		}
		else {
			return unserialize( $xstring, array( 'allowed_classes' => false ) );
		}
	}
	else {

		$string = $xstring;
		$result = array();

		// Assume its an array, else return the input string
		$type 	= substr( $string, 0, 2 );
		$string	= substr( $string, 2 );

		$cpos 	= strpos( $string, ':' );
		$count 	= substr( $string, 0, $cpos );
		$string = substr( $string, $cpos + 1 );
		$string	= trim( $string, '{}' );

		if ( $type != 'a:' ) {
			return array();
		}

		// Process data items
		while ( strlen( $string ) ) {

			// Decode the key
			$keytype = substr( $string, 0, 2 );
			$string  = substr( $string, 2 );
			switch ( $keytype ) {

				// Integer key
				case 'i:':
					$cpos 	 = strpos( $string, ';' );
					$key 	= intval( substr( $string, 0, $cpos ) );
					$string = substr( $string, $cpos + 1 );
					break;

				// String key
				case 's:':
					$cpos 	= strpos( $string, ':' );
					$keylen	= intval( substr( $string, 0, $cpos ) );
					$string = substr( $string, $cpos + 1 );
					$cpos 	= strpos( $string, ';' );
					$key 	= substr( $string, 1, $keylen );
					$string = substr( $string, $cpos + 1 );
					break;

				// Unimplemented key type
				default:
					return array();
			}

			// Decode the data
			$datatype = substr( $string, 0, 2 );
			$string   = substr( $string, 2 );

			switch ( $datatype ) {

				// Integer data
				case 'i:':
					$cpos 	= strpos( $string, ';' );
					$data 	= intval( substr( $string, 0, $cpos ) );
					$string = substr( $string, $cpos + 1 );
					break;

				// String data
				case 's:':
					$cpos 	 = strpos( $string, ':' );
					$datalen = intval( substr( $string, 0, $cpos ) );
					$string  = substr( $string, $cpos + 1 );
					$data 	 = substr( $string, 1, $datalen );
					$string  = substr( $string, $datalen + 3 );
					break;

				// Boolean
				case 'b:':
					$data 	 = substr( $string, 0, 1 ) == 1;
					$string  = substr( $string, 2 );
					break;

				// NULL
				case 'N;':
					$data 	 = NULL;
					break;

				// Array data
				case 'a:':
					$cbpos  = strpos( $string, '}' );
					$data 	= wppa_unserialize( 'a:' . substr( $string, 0, $cbpos + 1 ) );
					$string = substr( $string, $cbpos + 1 );
					break;

				// Unimplemented data type
				default:
					return array();
			}

			// Add to result array
			$result[$key] = $data;
		}

		return $result;
	}
}

function wppa_shortpath( $path ) {

	$result = str_replace( WPPA_ABSPATH, '.../', $path );
	return $result;
}

function wppa_filetime( $path, $log = false ) {

	clearstatcache();

	if ( ! file_exists( $path ) ) {
		if ( $log ) wppa_log( 'Err', 'File not found in wppa_filetime(): ' . wppa_shortpath( $path ) );
		return false;
	}

	if ( ! wppa_is_path_safe( $path ) ) {
		if ( $log ) wppa_log( 'Err', 'Unsafe path detected in wppa_filetime(): ' . wppa_shortpath( $path ) );
		return false;
	}

	return filemtime( $path );
}

function wppa_echo( $html, $flags = array() ) {

	$flags = wp_parse_args( $flags, ['return' => false, 'keeplinebreaks' => false, 'needjs' => false, 'needonerror' => false] );

	$t = wppa_allowed_tags();
	if ( $flags['needjs'] ) {
		$t['script'] = true;
		$t['style'] = true;
	}
	$p = wp_allowed_protocols();

	$html = wppa_compress_html( $html, $flags['keeplinebreaks'] );

	if ( $flags['return'] ) {
		return wp_kses( $html, $t, $p );
	}
	else {
		echo wp_kses( $html, $t, $p );
	}
}

function wppa_allowed_tags( $flags = ['return' => false, 'keeplinebreaks' => false, 'needjs' => false] ) { //, 'needonerror' => false] ) {
static $allowed_tags;

	if ( ! is_array( $allowed_tags ) ) {

		// Standard allowed attributes
		$sa = array(
			'id' => true,
			'name' => true,
			'title' => true,
			'class' => true,
			'style' => true,
			'onclick' => true,
			'ondblclick' => true,
			'onmouseover' => true,
			'onmouseout' => true,
			'onwheel' => true,
			'onscroll' => true,
			'data-wppa' => true,
			'data-alt' => true,
			'ontouchstart' => true,
			'ontouchend' => true,
			'onfocus' => true,
			'onblur' => true,
			'rel' => true,
			'z-index' => true,
			'background-image' => true,
			'background-position' => true,
			'background-repeat' => true,
			);

		$allowed_tags =
		array(
			'a' => array_merge( $sa, array(
				'href' => true,
				'target' => true,
				'onclick' => true,
				'data-rel' => true,
				'data-id' => true,
				'data-lbtitle' => true,
				'data-panorama' => true,
				'data-pantype' => true,
				'box-sizing' => true,
				'download' => true,
				) ),
			'aside' => $sa,
			'audio' => array_merge( $sa, array(
				'data-from' => true,
				'controls' => true,
				'preload' => true,
				'type' => true,
				) ),
			'b' => $sa,
			'br' => $sa,
			'canvas' => $sa,
			'div' => $sa,
			'em' => $sa,
			'form' => array_merge( $sa, array(
				'onsubmit' => true,
				'action' => true,
				'method' => true,
				'enctype' => true,
				) ),
			'h1' => $sa,
			'h2' => $sa,
			'h3' => $sa,
			'h4' => $sa,
			'h5' => $sa,
			'h6' => $sa,
			'hr' => $sa,
			'i' => $sa,
			'img' => array_merge( $sa, array(
				'alt' => true,
				'src' => true,
				'data-src' => true,
				'placeholder' => true,
				'srcset' => true,
				'onload' => true,
				'onerror' => true,
				'decoding' => true,
				) ),
			'input' => array_merge( $sa, array(
				'type' => true,
				'value' => true,
				'onchange' => true,
				'checked' => true,
				'min' => true,
				'max' => true,
				'multiple' => true,
				'onkeyup' => true,
				'disabled' => true,
				'accept' => true,
				'placeholder' => true,
				'download' => true,
				'size' => true,
				'aria-describedby' => true,
				'data-type' => true,
				) ),
			'label' => array(
				'for' => true,
				'class' => true,
				'style' => true,
				),
			'link' => array(
				'rel' => true,
				'href' => true,
				),
			'meta' => array(
				'name' => true,
				'content' => true,
				'property' => true,
				),
			'noscript' => array_merge( $sa, array(
				'style' => true,
				) ),
			'option' => array_merge( $sa, array(
				'selected' => true,
				'value' => true,
				'disabled' => true,
				) ),
			'p' => $sa,
			'select' => array_merge( $sa, array(
				'onchange' => true,
				'value' => true,
				'multiple' => true,
				'onwheel' => true,
				'onscroll' => true,
				'onfocus' => true,
				'size' => true,
				) ),
			'small' => $sa,
			'span' => $sa,
			'strong' => $sa,
			'sup' => array(),

			// Start svg
			'svg' => array_merge( $sa, array(
				'width' => true,
				'height' => true,
				'x' => true,
				'y' => true,
				'viewbox' => true,
				'xml:space' => true,
				'xmlns' => true,
				'preserveaspectratio' => true,
				'stroke' => true,
				'version' => true,
				) ),
			'g' => array(
				'transform' => true,
				'fill' => true,
				'fill-rule' => true,
				'stroke-width' => true,
				),
			'path' => array(
				'd' => true,
				),
			'rect' => array(
				'width' => true,
				'height'=> true,
				'rx' => true,
				'ry' => true,
				'x' => true,
				'y' => true,
				'fill' => true,
				'class' => true,
				'transform' => true,
				),
			'animate' => array(
				'attributename' => true,
				'begin' => true,
				'from' => true,
				'to' => true,
				'dur' => true,
				'values' => true,
				'repeatcount' => true,
				'calcmode' => true,
				'opacity' => true,
				),
			'animatetransform' => array(
				'attributename' => true,
				'type' => true,
				'from' => true,
				'to' => true,
				'dur' => true,
				'repeatcount' => true,
				),
			'circle' => array(
				'cx' => true,
				'cy' => true,
				'r' => true,
				'stroke-opacity' => true,
			),

			// End svg

			'table' => $sa,
			'tbody' => $sa,
			'colgroup' => $sa,
			'col' => $sa,
			'textarea' => array_merge( $sa, array(
				'onchange' => true,
				'rows' => true,
				) ),
			'thead' => $sa,
			'tfoot' => $sa,
			'tr' => $sa,
			'td' => array_merge( $sa, array(
				'colspan' => true,
				) ),
			'th' => $sa,
			'title' => $sa,
			'video' => array_merge( $sa, array(
				'preload' => true,
				'type' => true,
				'controls' => true,
				'onmouseover' => true,
				'onmouseout' => true,
				'autoplay' => true,
				'muted' => true,
				'onloadedmetadata' => true,
				'onpause' => true,
				'onplaying' => true,
				'poster' => true,
				'oncanplay' => true,
				) ),
			'source' => array(
				'src' => true,
				'type' => true,
				),
			'ul' => $sa,
			'ol' => $sa,
			'li' => $sa,
			'fieldset' => $sa,
			'legend' => $sa,
			'summary' => $sa,
			'details' => array_merge( $sa, array(
				'open' => true,
				) ),
		);
	}
	return $allowed_tags;
}

add_filter( 'safe_style_css', function( $styles ) {

	$my_styles = [
				'display',
				'visibility',
				'fill',
				'text-decoration',
				'opacity',
				'list-style',
				'position',
				'top',
				'left',
				'right',
				'bottom',
				'z-index',
				'box-shadow',
				'box-sizing',
				];

    return array_merge( $styles, $my_styles );
} );

function wppa_add_inline_script( $where, $script, $compress = false ) {
global $wppa_jquery_loaded;
static $js_accu;

	// Init accumulator
	if ( $js_accu === NULL ) {
		$js_accu = '';
	}

	// Optionally compress
	if ( $compress ) {
		$script = wppa_compress_js( $script );
	}

	wp_add_inline_script( $where, $script );
}

// Wrapper for exif_read_data()
function wppa_exif_read_data( $file, $sections ) {

	if ( function_exists( 'exif_read_data' ) && in_array( wppa_get_ext( $file ), ['jpg', 'jpeg', 'JPG', 'JPEG'] ) ) {
		$result = @ exif_read_data( $file, $sections );
	}
	else {
		$result = array();
	}
	return $result;
}

// Wrapper for wp_set_script_translations()
function wppa_set_script_translations( $slug ) {
static $missing_func_reported;

	if ( $missing_func_reported ) return;
	if ( ! function_exists( 'wp_set_script_translations' ) ) {
		wppa_log( 'war', 'Funcion wp_set_script_translations() does not exist. Update wp to completize translations' );
		$missing_func_reported = true;
		return;
	}

		$bret = wp_set_script_translations( $slug, 'wp-photo-album-plus', plugin_dir_path( __FILE__ ) . 'languages' );
		if ( ! $bret ) {
			wppa_log( 'war', "Could not load script translations for $slug" );
		}

}

// Wrapper for wp_enqueue_script()
function wppa_enqueue_script( $slug, $src = '', $deps = array(), $ver = false, $args = false ) {

	wp_enqueue_script( $slug, $src, (array) $deps, $ver, $args );
	wppa_set_script_translations( $slug );

}

// Wrapper for ewww_image_optimizer() and potentially others
function wppa_optimize_image( $file ) {

	// If file does not exist, it can not be optimized
	if ( ! wppa_is_file( $file ) ) {
		return;
	}

	// ewww present and activated?
	if ( function_exists( 'ewww_image_optimizer' ) ) {
		$s0 = wppa_filesize( $file );
		ewww_image_optimizer( $file, 4, false, false, false );
		$s1 = wppa_filesize( $file );
		$p = ( $s0 - $s1 ) / $s0 * 100;
		wppa_log( 'fso', str_replace( WPPA_CONTENT_PATH, '...', $file ) . ' optimized by ewww_image_optimizer. Compression: ' . sprintf( '%5.2f', $p ) . '%.' );
	}

//	if( ... ) {
//	}

}

// Wrappers for direct db calls
//
// The following functions all produce in Plugin Check the following warnings:
//
// - Use of a direct database call is discouraged.
// - Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete().
//
// These messages are false positive, because wp has no other way to create/maintain structure,
// insert, write or read data to/from db tables that are specifically designed for this plugin.
//
function wppa_get_results( $query, $form = ARRAY_A ) {
global $wpdb;

	wppa_log( 'db', $query );
	return $wpdb->get_results( $query, $form );
}

function wppa_get_var( $query ) {
global $wpdb;

	wppa_log( 'db', $query );
	return $wpdb->get_var( $query );
}

function wppa_get_col( $query ) {
global $wpdb;

	wppa_log( 'db', $query );
	return $wpdb->get_col( $query );
}

function wppa_get_row( $query ) {
global $wpdb;

	wppa_log( 'db', $query );
	return $wpdb->get_row( $query, ARRAY_A );
}

function wppa_query( $query ) {
global $wpdb;

	wppa_log( 'db', $query );
	return $wpdb->query( $query );
}

function wppa_update( $table, $data, $where ) {
global $wpdb;

	wppa_log( 'db', "Update in table $table id = " . $where['id'] ); // . ": " . var_export( $data, true ) );
	return $wpdb->update( $table, $data, $where );
}

function wppa_insert( $table, $data ) {
global $wpdb;

	wppa_log( 'db', "Insert in table $table " ); //  . var_export( $data, true ) );
	return $wpdb->insert( $table, $data );
}

function wppa_is_writable( $path ) {
global $wp_filesystem;

	if ( ! wppa_is_path_safe( $path ) ) {
		if ( $log ) wppa_log( 'Err', 'Unsafe path detected in wppa_is_writable(): ' . wppa_shortpath( $path ) );
		return false;
	}
	return $wp_filesystem->is_writable( $path );
}

function wppa_check_filetype_and_ext( $temp_name, $name, $mimetypes ) {

	if ( in_array( wppa_get_ext( $name ), ['amf', 'pmf', 'csv'] ) ) {
		return array( 'ext' => true, 'type' => true, 'proper_filename' => wppa_sanitize_file_name( $name ) );
	}
	else {
		return wp_check_filetype_and_ext( $temp_name, $name, $mimetypes );
	}
}