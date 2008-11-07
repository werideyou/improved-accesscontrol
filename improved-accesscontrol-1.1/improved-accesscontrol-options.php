<?php

/************************
 * CONFIGURABLE OPTIONS *
 ************************/

/* egUseMediaWikiGroups
   Use MediaWiki user groups instead of the extension Usergroup:X form
     true: Use MediaWikiGroups
     false (default): Use extension groups
   THIS IS CURRENTLY THE ONLY METHOD SUPPORTED
*/
$egUseMediaWikiGroups = false; 

/* egUsergroupsRequireAdmin
   Configure who can maintain extension usergroups
   true: Only admin (sysop) can access Usergroup: pages
   false (default): <accesscontrol> rules apply
*/
$egUsergroupsRequireAdmin = false;

/* egAdminCanReadAll
   Set access permissions for admin/sysop
   true: Admin bypasses all access controls
   false (default): Admin must pass access control rules
*/
$egAdminCanReadAll = false;

/* egBlockRestrictedTransclusions
   Configure transclusion rules for editing
   true: Wiki transclusions in the form of {{:Article}} will be checked for 
         access control and replaced if found
   false (default): Let all transclusions through, which will bypass access 
                    controls on them
*/
$egBlockRestrictedTransclusions = true;

/* egReadOnlyActionAccess
   Configure which actions can be used by a user with read-only access
   Suggested: array('read', 'view', 'render', 'watch', 'unwatch', 'purge')
*/
$egReadOnlyActionAccess = array('read', 'view', 'render', 'watch', 'unwatch', 
				'purge');

/* egPreventSaveDenyingAccess
   Stop saves that would remove access for current user
     'edit' means saves must maintain full access
     'read' means read-only access is fine
     'none' (default) means allow saves that would lock user out of the page
*/
$egPreventSaveDenyingAccess = 'edit';

/* egAccessControlDebug
   Log debugging information
   true: Log debug information -- $egAccessControlDebugFile is required
   false (default): No logging
*/
$egAccessControlDebug = true;

/* egAccessControlDebugFile
   File to log debugging information to
   Must be set if $egAccessControlDebug=true, no default
*/
$egAccessControlDebugFile = '/home/jeisen/wiki.log';

?>