<?php 

/** Class: AccessControlChangesList
 * Special RecentChanges list that integrates with protection
 */
class AccessControlChangesList extends OldChangesList {
  
  /** Function: __construct( &$skin )
   * Pass-through constructor
   */
  function __construct ( &$skin ) {
    parent::__construct( $skin );
  }
  
  /** Function: recentChangesLine( &$rc, $watched )
   * Override the regular line to remove a change that should not be visible
   * &$rc --> (editable) Recent change line
   * $watched --> Whether this is being watched (unused)
   * Returns: Either nothing (to remove it from list) or the regular line, 
   *          passing through
   */
  public function recentChangesLine( &$rc, $watched = false ) {
    
    // Get current user
    global $wgUser;
    
    // Get title of article with change
    $title = $rc->mAttribs['rc_title'];
    
    // Get namespace of article with change
    $namespace = $rc->mAttribs['rc_namespace'];
    
    $fullTitle = efIACMakeTitle( $title, $namespace );
    
    // Return the change only if the user would have read access    
    $result = true;
    if( efIACAccessControlUserCanHook( $fullTitle, $wgUser, 'read', $result )){
      return parent::recentChangesLine( $rc, $watched );
    } 
    
    unset($rc);
    return null;
  }  
}

?>