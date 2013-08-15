<?php

/** Function: efIACAccessControlEditFilterHook( &$editor, $text, $section,
 *            &$error )
 * Hook: EditFilter
 * Intercept saving and previewing a page to protect from:
 *  - Transclusion of protected pages
 *  - Locking editor out of page accidentally
 * $editor --> (editable) Editor object being used. Member variable
 *               textbox1 is what should be changed if needed.
 * $text --> Text of the change. This is a copy of $editor->textbox1
 * $section --> Section being edited (not used)
 * &$error --> (editable) Error to return. If anything is in this variable,
 *               page will not save.
 * Returns: Whether to continue processing or deny access. This extension
 *           always returns true.
 *	      Even if true, if $error has text save will be stopped.
 * i18n variables: locking-self-out, protected-transclusion
 * NOTE: Preview protection requires a change in the MediaWiki source
 */
function efIACAccessControlEditFilterHook( $editor, $text, $section,
					   &$error ){

  efIACDebugLog( "(efIACAccessControlEditFilterHook) checking edit..." );

	// Display an error if this will lock the person saving the page
        // out from reading
  if( efIACUserLockingThemselvesOut( $editor->textbox1 ) ){
    $error = efIACGetMessage( 'locking-self-out' );
  }

  // If we don't care about transclusions, let it through without
  // further processing
  global $egBlockRestrictedTransclusions;
  if( !$egBlockRestrictedTransclusions ){
    return true;
  }

  // Set the expression for a transclusion
  $regexp = '{{:(.*?)}}';

  // Search for transclusions in the edit
  $num_matches = preg_match_all( $regexp, $text, $matches );

  // Let it through if there are no transclusions
  if( $num_matches == 0 ){
    return true;
  }

  // For each transclusion, look for an access control list
  for( $i = 0; $i < $num_matches; $i++ ){
    // Get the title being transcluded
    $link_title = $matches[1][$i];
    // Get the content of the transclusion
    $content = efIACGetArticleContent( $link_title );
    // Get the access list
    $transclude_accessList = efIACGetAccessList( $content );
    // If there's an access list, add it to the error list
    if( $transclude_accessList !== false ){
      efIACDebugLog( "(efIACAccessControlEditFilterHook) ".
		     "blocked transclusion to ".$link_title);
      $protectedList = $protectedList.'<PAD>'.$link_title;
    }
  }

  // Replace the protected transclusions with other text
  $protected_array = explode( '<PAD>' ,trim( $protectedList ) );
  for( $i = 0, $size = sizeof( $protected_array ); $i < $size; ++$i ){
    $restricted_text = $protected_array[$i];
    $replacement_text = efIACGetMessage( 'protected-transclusion',
					 $restricted_text );

    $text = str_replace( '{{:'.$restricted_text.'}}',
			 $replacement_text, $text );

    // Set the new text back into the editor object
    $editor->textbox1 = $text;
  }

  return true;
}

/** Function: efIACAccessControlFetchChangesHook( $user, $skin, &$list )
 * Hook: FetchChangesList
 * Intercept returning the list of Recent Changes and strip out ones user
 *  has no access to read
 * $user --> Current user
 * $skin --> Skin being used by user (not used)
 * &$list --> List of recent changes (editable)
 * Returns: Whether to continue with default list (this extension always
 *		returns false, to replace)
 * NOTE: AccessControlChangesList is a custom class that performs the
 *         protection
 */
function efIACAccessControlFetchChangesHook( $user, $skin, &$list ){
  $list = new AccessControlChangesList( $skin );
  return false;
}

/** Function: efIACAccessControlTag( $input, $argv, $parser )
 * Hook: Parser tag
 * Register the <accesscontrol> tag as a hook and provide some text in its
 *	place when displayed
 * $input --> Contents (foo) in <accesscontrol>foo</accesscontrol>
 * $argv --> Tag arguments (not used)
 * $parser --> Parent parser (not used)
 * Returns:  Message that this page is protected
 * i18n variables: protected-page
 */
function efIACAccessControlTag($input, $argv, $parser ) {
  $identifierCode = '<!-- ACCESSCONTROL PROTECTED PAGES -->';
  return $identifierCode.efIACGetMessage( 'protected-page', $input );
}

/** Function: efIACAccessControlUserCanHook( $title, $wgUser, $action,
 *						&$result )
 * Hook: userCan
 * Check the current user's rights to perform an action on a page
 * $title --> Title object for article being accessed
 * $wgUser --> Current user
 * $action --> Action being attempted
 * &$result --> Result to return (modifiable).
 * Returns: Whether this user has access to the page for this action
 * NOTE: Return value determines whether later functions should be run to
 *		check access
 *	   $result determines whether this function thinks the user should
 *		have access
 *	   This extension always returns the same value as $result
 */
function efIACAccessControlUserCanHook( $title, $wgUser, $action,
					&$result ){
  // Option for whether to pass through if sysop
  global $egAdminCanReadAll;

  // Make sure we're dealing with a Title object
  $title = efIACMakeTitle( $title );

  efIACDebugLog( "(efIACAccessControlUserCanHook) checking access for ".
		 $wgUser->getName()." on '".$title->getText()."'" );

  // Check if the user is a sysop
  $userIsSysop = efIACUserIsSysop( $wgUser );

  // Pass through if user is a sysop and the option is set
  if( $egAdminCanReadAll && $userIsSysop ){
    efIACDebugLog( "(efIACAccessControlUserCanHook) sysop access");
    return efIACReturnResult( true, $result );
  }

  // Fail if article requires sysop and user is not one
  if( efIACArticleRequiresAdmin( $title ) && !( $userIsSysop ) ){
    efIACDebugLog( "(efIACAccessControlUserCanHook) sysop required");
    return efIACReturnResult( false, $result );
  }

  // Get the content of the article
  $content = efIACGetArticleContent( $title );

  // Get the access control list from that content
  $accessList = efIACGetAccessList( $content );

  // Get the result of whether the user can access
  $localResult = efIACUserCanAccess( $wgUser, $accessList, $action );

  unset($accessList);
  unset($content);
  unset($title);

  return efIACReturnResult( $localResult, $result );
}

?>