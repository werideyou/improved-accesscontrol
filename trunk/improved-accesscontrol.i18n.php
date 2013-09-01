<?php

global $egPreventSaveDenyingAccess;

$messages = array();
$messages['en'] = 
 array(
       'extension-desc' => 
         'Enables group-based access control on a page-by-page basis.',
       'protected-page' => 
         '<b>This page is restricted</b><br/>',
       'protected-transclusion' => 
         '<b>Permission to embed page "$1" denied</b>',
       'locking-self-out' => 
         '<b>You cannot save content that you will be unable to '.
          $egPreventSaveDenyingAccess.'.'
       );
?>