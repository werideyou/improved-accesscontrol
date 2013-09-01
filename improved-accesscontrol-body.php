<?php

/****************
 * Registration *
 ****************/
$wgExtensionCredits['parserhook'][] =
  array(
      'name' => 'Improved Access Control',
      'version' => 1.0,
      'author' => 'Jonathan Eisenstein',
      'url' => 'http://www.mediawiki.org/wiki/User:JEisen',
      'description' => 'Enables group-based access control on a '.
      'page-by-page basis.',
      'descriptionmsg' => 'extension-desc'
      );

// Register internationalization
$wgExtensionMessagesFiles['improved-accesscontrol'] =
 dirname(__FILE__).'/improved-accesscontrol.i18n.php';

// Register the <accesscontrol> tag hook
$wgExtensionFunctions[] = 'efIACSetupAccessExtension';

// Register the user rights hook
$wgHooks['userCan'][] = 'efIACAccessControlUserCanHook';

// Register the edit filter hook to prevent transclusion of protected pages
$wgHooks['EditFilter'][] = 'efIACAccessControlEditFilterHook';

// Register the hook to hide unauthorized pages from recent changes
$wgHooks['FetchChangesList'][] = 'efIACAccessControlFetchChangesHook';

// Register the hook to hide search results
$wgHooks['ShowSearchHitTitle'][] = 'efIACAccessControlShowSearchHitTitle';

/******************
 * Initialization *
 ******************/

/** Function: efIACSetupAccessExtention
 * Delayed setup of tag
 * Returns: void
 */
function efIACSetupAccessExtension(){
  global $wgParser;
  // Add the <accesscontrol> tag
  $wgParser->setHook( 'accesscontrol', 'efIACAccessControlTag' );
}

/************
 * Includes *
 ************/
require_once('includes/accessHooks.php');
require_once('includes/accessHookSupport.php');
require_once('includes/AccessControlChangesList.php');

/******************
 * Misc Functions *
 ******************/

/** Function: efIACDebugLog( $msg )
 * Log debug output to the extension log file
 * $msg --> Message to log
 * Returns: void
 */
function efIACDebugLog( $msg ) {
  global $egAccessControlDebug;
  global $egAccessControlDebugFile;

  if( $egAccessControlDebug ) {
    $debugFile = fopen( $egAccessControlDebugFile, 'a+' );
    fputs( $debugFile, "\r\n".$msg );
    fclose( $debugFile );
  }
}

/** Function: efIACDebugList ($msg, $in_array )
 * Log debug output including an array as a list of items
 * $msg --> Message to preface list with
 * $in_array --> An array of items to list
 * Returns: void
 */
function efIACDebugList( $msg, $in_array ) {
  global $egAccessControlDebug;

  if ($egAccessControlDebug ) {
    $out_list = "";
    foreach( $in_array as $item ) {
      $out_list = $out_list." ".$item;
    }

    efIACDebugLog( $msg.$out_list );
  }
}

/** Function: efIACDebug_r( $var );
 * Log an object's structure to the extension log file
 * $var --> Object to log
 * Returns: void
 */
function efIACDebug_r( $var ) {
  efIACDebugLog( print_r( $var, true ) );
}

/** Function: efIACGetMessage( $name[, $msg] )
 * Load an internationalized message
 * $name --> Name of the message to return
 * $msg --> Optional text that replaces $1 in message to be returned
 * Returns: The message in the appropriate language
 */
function efIACGetMessage( $name, $msg='' ){
  return wfMsg( $name,$msg );
}

?>