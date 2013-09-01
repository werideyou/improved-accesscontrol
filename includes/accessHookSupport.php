<?php

/** Function: efIACGetAccessList( &$content )
 * Get the usergroups that can access the page
 * $content --> Article content (potentially) containing the tag
 * Returns: The access list content
 */
function efIACGetAccessList( &$content ){
  // Set up the tags we're searching for
  $startTag = '<accesscontrol>';
  $endTag = '</accesscontrol>';

  // Get the positions of the tag, if they exist
  $startPos = strpos( $content, $startTag );
  $endPos = strpos( $content, $endTag );

  // Get the start and end of the text inside the tag, if it exists
  if( $startPos === false ){
    $startPos = -1;
  } else {
    $startPos += strlen( $startTag );
  }
  if( $endPos === false ){
    $endPos -1;
  }

  // Get the text inside the tag
  if( ( $startPos >= 0 ) && ( $endPos > 0 ) && ( $endPos > $startPos ) ){
    $tagContent = substr( $content, $startPos, $endPos-$startPos );
    efIACDebugLog( "(efIACGetAccessList) access groups: ".$tagContent );
    return $tagContent;
  }

  return false;
}

/** Function: efIACArticleRequiresAdmin( &$title )
 * Check to see if the page requires an admin
 * This takes effect if the page is a Usergroup and the
 *	egUsergroupsRequireAdmin option is true
 * $title --> Title object for article being accessed
 * Returns: True only if only a sysop can access the page
 */
function efIACArticleRequiresAdmin( &$title ){
  global $egUsergroupsRequireAdmin;
  if( $egUsergroupsRequireAdmin ){
    return ( substr_compare( 'Usergroup:',
			     $title->getText(),0,10 ) == 0 );
  } else {
    return false;
  }
}

/** Function: efIACGetAllowedUsersFromGroupPages( &$groupNames )
 * Get the users with access to an article
 * $groupNames --> Groups that were within <accesscontrol> tags
 * Returns: Two arrays:
 *	[0] --> Users with full access
 *	[1] --> Users with read-only access
 */
function efIACGetAllowedUsersFromGroupPages(&$groupNames){
  // Set up return arrays
  $allowedUsersFull = Array();
  $allowedUsersReadOnly = Array();

  // Go through each group and store the users
  foreach($groupNames as $accessGroup){

    // If group is listed as (ro), set readOnly to true
    $readOnly = false;
    $name = str_replace( '(ro)', '' , $accessGroup );
    if($accessGroup !== $name){
      $readOnly = true;
    }

    // Get the page content for the Usergroup page
    $pageContent = efIACGetArticleContent( 'Usergroup:'.$name );

    if( $pageContent !== false ){

      // Get the list of users
      $usersFromGroup = explode( '*', $pageContent );

      // Put the users in the appropriate return array as lowercase
      foreach($usersFromGroup as $accessUser){
	$trimmedUser = trim( $accessUser );
	if( $trimmedUser != '' ){
	  $userToAdd = strtolower( $trimmedUser );
	  if( $readOnly ){
	    $allowedUsersReadOnly[] = $userToAdd;
	  } else {
	    $allowedUsersFull[] = $userToAdd;
	  }
	}
      }
    }
  }

  $allowedUsers[0] = $allowedUsersFull;
  $allowedUsers[1] = $allowedUsersReadOnly;

  efIACDebugList( "(efIACGetAllowedUsersFromGroupPages) edit users: ",
		  $allowedUsers[0] );
  efIACDebugList( "(efIACGetAllowedUsersFromGroupPages) read users: ",
		  $allowedUsers[1] );

  return $allowedUsers;
}

/** Function: efIACGetArticleContent( $title[, $namespace] ) {
 * Get article content from the wiki
 * $title --> Name of article to get
 * Returns: Contents of the article, or false if no article exists
 */
function efIACGetArticleContent( $title ) {
  // Make a title object
  $title = efIACMakeTitle($title);

  // Get the article ID to make sure it exists
  $articleID = $title->getArticleId();

  // Return false if there is no article
  if( !$articleID ){
    efIACDebugLog( "(efIACGetArticleContent) article '".
		   $title->getText()."' does not exist" );
    return false;
  }

  // Query the database for this article
  $article = new Article( $title, 0 );

  // Get the content
  $content = $article->getContent();

  unset( $title );
  return $content;
}

/** Function: efIACMakeTitle( $title[, $namespace] )
 * Make a Title object out of a string, if needed
 * $title --> The name of the article, or an existing Title object
 * $namespace --> (optional) Namespace ID to look in (Default: Main [0])
 * Returns: A Title object corresponding to the name and namespace,
 * 	  or the same object back if it was already a Title
 */
function efIACMakeTitle( &$title, $namespace=0 ){
  $new_title = &$title;
  if( is_string( $title ) ){
    $new_title = Title::newFromText( $title, $namespace );
  }

  return $new_title;
}

/** Function: efIACReturnResult( $status, &$result )
 * Written to get around https://bugzilla.wikimedia.org/show_bug.cgi?id=17116
 * Return the value provided, setting $result if configured to do so
 * $status --> The status to return
 * $result --> Shared result object to set depending on configuration
 * Returns: Same as $status
 */
function efIACReturnResult( $return_status, &$result ){
  global $egAccessControlOverrideWiki;
  efIACDebugLog ("(efIACReturnResult) Return status is ".$return_status.".");
  efIACDebugLog ("Override is ".$egAccessControlOverrideWiki.".");

  if( !($return_status) || ($egAccessControlOverrideWiki) ){
    $result = $return_status;
  }
  return $return_status;
}

/** Function: efIACUserCanAccess( &$user, &$accessGroups, $action )
 * Determine whether the user can perform a given action, given the
 *	article's access groups
 * $user --> User performing the action
 * $accessGroups --> Groups specified in the <accesscontrol> tag
 * $action --> The action being performed
 * Returns: True if this action should be allowed
 */
function efIACUserCanAccess( &$user, &$accessGroups, $action ){
  // If there's no access list, pass through
  if( !$accessGroups ){
    efIACDebugLog( "(efIACUserCanAccess) no access controls on article" );
    return true;
  }

  // Redirect logic based on whether or not we are using MediaWiki
  // groups or Usergroups
  global $egUseMediaWikiGroups;
  if( $egUseMediaWikiGroups ){
    return efIACUserCanAccessMediaWikiGroups( $user, $accessGroups,
					      $action );
  } else {
    return efIACUserCanAccessPageGroups( $user, $accessGroups,
					 $action );
  }
}

/** Function: efIACUserCanAccessMediaWikiGroups( &$user, &$accessGroups,
 *                                                   $action )
 * CURRENTLY UNIMPLEMENTED
 * Determine whether user can perform a given action with MediaWiki groups
 * $user --> User performing the action
 * $accessGroups --> Groups specified in the <accesscontrol> tag
 * $action --> The action being performed
 * Returns: True if this action should be allowed
 */
function efIACUserCanAccessMediaWikiGroups( &$user, &$accessGroups, $action ){
  return false;
}

/** Function: efIACUserCanAccessPageGroups( &$user, &$accessGroups, $action )
 * Determine whether user can perform a given action with Usergroup: groups
 * $user --> User performing the action
 * $accessGroups --> Groups specified in the <accesscontrol> tag
 * $action --> The action being performed
 * Returns: True if this action should be allowed
 */
function efIACUserCanAccessPageGroups( &$user, &$accessGroups, $action ){
  // Check allowed users according to the access groups
  // $allowedUsers[0] --> full access
  // $allowedUsers[1] --> read-only access
  $explodedGroups = explode( ',,', $accessGroups );
  $allowedUsers = efIACGetAllowedUsersFromGroupPages( $explodedGroups );

  // Get the current user's lowercased name
  $userName = strtolower( trim( $user->getName() ) );

  // Check for the user in the full access list
  if( in_array( $userName, $allowedUsers[0] ) ){
    efIACDebugLog("(efIACUserCanAccessPageGroups)".
		  "full access granted ");
    $result = true;
    return $result;
  }

  // No full access -- return true if they are in the read-only list and
  //  this is a read
  global $egReadOnlyActionAccess;
  $result = ( in_array( $action, $egReadOnlyActionAccess ) &&
	      ( in_array( $userName, $allowedUsers[1] ) ) );

  if( $result ){
    efIACDebugLog( "(efIACUserCanAccessPageGroups)".
		   "read-only access granted ");
  }

  unset($allowedUsers);
  return $result;
}

/** Function: efIACUserIsSysop( &$user )
 * Determine whether a user is a sysop
 * $user --> User being tested
 * Returns: True if user is a sysop
 */
function efIACUserIsSysop( &$user ){
  // Get group types from user
  $groups = $user->mGroups;

  // If there are any, check them
  if( $groups )
    return in_array('sysop', $user->mGroups);

  // False if no groups
  return false;
 }

/** Function: efIACUserLockingThemselvesOut( &$text )
 * Verify that a user isn't making a change that locks themselves out
 * $text --> Text that has been entered and is about to be saved
 * Returns: True if this save should be prevented
 */
function efIACUserLockingThemselvesOut( &$text ){
  global $wgUser;
  global $egPreventSaveDenyingAccess;

  // If the option to prevent this is off, let it through
  if( !$egPreventSaveDenyingAccess ||
      ( $egPreventSaveDenyingAccess == 'none' ) ){
    return false;
  }

  // Get the access control groups for the new content
  $accessGroups = efIACGetAccessList( $text );

  // Determine if the user will be able to have access in the new content
  // egPreventSaveDenyingAccess will be 'read' or 'edit' depending on
  // which right the user must maintain
  $userCanAccess = efIACUserCanAccess( $wgUser, $accessGroups,
				       $egPreventSaveDenyingAccess );

  if( !$userCanAccess ){
    efIACDebugLog( "(efIACUserLockingThemselvesOut) self-lockout save ");
  }

  unset( $accessGroups );
  return !( $userCanAccess );
}

?>