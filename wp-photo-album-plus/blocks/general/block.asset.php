<?php
if ( ! defined( 'ABSPATH' ) ) exit();

$version = md5( filemtime( dirname( __file__ ) . '/block.js' ) );

$result = array(
	'dependencies' => array(
		'wp-block-editor',
		'wp-element',
		'wp-i18n'
	),
	'version' => $version
);

return $result;