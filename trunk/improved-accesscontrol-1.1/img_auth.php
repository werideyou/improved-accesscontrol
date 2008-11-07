<?php

/**
 * Image authorization script for improved-accesscontrol extension
 * Extended from standard MediaWiki-included img_auth.php
 *
 * To use this:
 *
 * Put this file in the base wiki directory (where index.php is)
 * Set $wgUploadDirectory to a non-public directory (not web accessible)
 * Set $wgUploadPath to point to this file
 *
 * Your server needs to support PATH_INFO; CGI-based configurations
 * usually don't.
 * 
 * Make sure that $wgUploadDirectory is writable from your web process
 * You can usually give the apache group permissions with:
 *  chgrp -R apache image_directory
 */

define( 'MW_NO_OUTPUT_COMPRESSION', 1 );
require_once( dirname( __FILE__ ) . '/includes/WebStart.php' );
wfProfileIn( 'img_auth.php' );
require_once( dirname( __FILE__ ) . '/includes/StreamFile.php' );

require_once( dirname( __FILE__) . '/LocalSettings.php' );

// Extract path and image information
if( !isset( $_SERVER['PATH_INFO'] ) ) {
  wfDebugLog( 'img_auth', 'Missing PATH_INFO' );
  wfForbidden();
}

$path = $_SERVER['PATH_INFO'];
wfDebugLog( 'img_auth', "\$wgUploadDirectory is {$wgUploadDirectory}" );
$filename = realpath( $wgUploadDirectory . $_SERVER['PATH_INFO'] );
$realUpload = realpath( $wgUploadDirectory );
wfDebugLog( 'img_auth', "\$path is {$path}" );
wfDebugLog( 'img_auth', "\$filename is {$filename}" );
wfDebugLog( 'img_auth', "\$realUpload is {$realUpload}" );

// Basic directory traversal check
if( substr( $filename, 0, strlen( $realUpload ) ) != $realUpload ) {
  wfDebugLog( 'img_auth', 'Requested path not in upload directory' );
  wfForbidden();
}

// Extract the file name and chop off the size specifier
// (e.g. 120px-Foo.png => Foo.png)
$name = wfBaseName( $path );
if( preg_match( '!\d+px-(.*)!i', $name, $m ) )
     $name = $m[1];
     wfDebugLog( 'img_auth', "\$name is {$name}" );
     
     $title = Title::makeTitleSafe( NS_IMAGE, $name );
     if( !$title instanceof Title ) {
       wfDebugLog( 'img_auth', "Unable to construct a valid Title from `{$name}`" );
       wfForbidden();
     }
$title = $title->getPrefixedText();

if( !file_exists( $filename ) ) {
  wfDebugLog( 'img_auth', "`{$filename}` does not exist" );
  wfForbidden();
}
if( is_dir( $filename ) ) {
  wfDebugLog( 'img_auth', "`{$filename}` is a directory" );
  wfForbidden();
}

// Hook into accesscontrol
$accesscontrolResult = true;
if( !efIACAccessControlUserCanHook( $title, $wgUser, 'read', $accesscontrolResult ) ){
  wfDebugLog( 'img_auth', "`{$wgUser->getName()}` does not have access to `{$filename}`" );
  wfForbidden();
}

// Stream the requested file
wfDebugLog( 'img_auth', "Streaming `{$filename}`" );
wfStreamFile( $filename, array( 'Cache-Control: private', 'Vary: Cookie' ) );
wfLogProfilingData();

/**
 * Issue a standard HTTP 403 Forbidden header and a basic
 * error message, then end the script
 */
function wfForbidden() {
  header( 'HTTP/1.0 403 Forbidden' );
  header( 'Vary: Cookie' );
  header( 'Content-Type: text/html; charset=utf-8' );
  echo <<<ENDS
    <html>
    <body>
    <h1>Access Denied</h1>
    <p>You need to log in to access files on this server.</p>
    </body>
    </html>
    ENDS;
  wfLogProfilingData();
  exit();
}
