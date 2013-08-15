<?php

/******************************************************************************
 * improved-accesscontrol version 1.11, tested on MediaWiki 1.13.1 and 1.13.2 *
 * MediaWiki extension that enables group access restriction on a             *
 *  page-by-page basis along with several other features                      *
 *                                                                            *
 * contributed by Jon Eisenstein (http://www.mediawiki.org/wiki/User:JEisen)  *
 *                                                                            *
 * based on Group Based Access Control 0.8 by Martin Gondermann               *
 *  (http://blog.pagansoft.de), originally based on accesscontrol.php by      *
 *  Josh Greenberg                                                            *
 *                                                                            *
 * As of MediaWiki 1.13.x, this extension requires minor changes to MediaWiki *
 *  code for full functionality. Unpatched, the extension will still work but *
 *  will not provide full protection on Search and Edit Preview.              *
 *                                                                            *
 * This extension was written for MediaWiki 1.13.x, but if patches are not    *
 *  used, it should theoretically work on 1.7.0 or later. However, that has   *
 *  not been tested.                                                          *
 *                                                                            *
 * THIS EXTENSION HAS BEEN TESTED ONLY ON 1.13.x. USE ON OTHER VERSIONS AT    *
 *  YOUR OWN RISK.                                                            *
 ******************************************************************************/

/******************************************************************************
 * INSTALLATION                                                               *
 *                                                                            *
 *     A) Apply the patch to MediaWiki (optional):                            *
 *      i.   Make back up copies of includes/EditPage.php and                 *
 *            includes/specials/SpecialSearch.php                             *
 *      ii.  Put improved-accesscontrol.patch.diff into your wiki directory   *
 *      iii. Run: patch -p0 < improved-accesscontrol.patch.diff               *
 *     B) Copy this directory (improved-accesscontrol) into your wiki's       *
 *         extensions directory                                               *
 *     C) Put the following line into your LocalSettings.php:                 *
 *          require_once("extensions/path_here/improved-accesscontrol.php");  *
 *     D) Put the following line into your LocalSettings.php to hide titles   *
 *         of unauthorized search results (optional, requires patch above):   *
 *          $wgHideProtectedSearchResultTitles = true;                        *
 *     E) For image protection, copy img_auth.php into your wiki directory    *
 *          read the directions in that file.                                 *
 *     F) To change the Permission Denied error, log in as sysop and go to    *
 *         Special:AllMessages (listed as "System messages"). Change          *
 *         'badaccess' (title) and 'badaccess-group2' (content)               *
 *     G) Customize options in improved-accesscontrol-options.php             *
 *     H) Customize any messages in improved-accesscontrol.i18n.php           *
 ******************************************************************************/

/*******************************************************************************
 * CAVEAT                                                                      *
 *                                                                             *
 * If you need per-page or partial page access restrictions, you are advised   *
 *  to install an appropriate content management package. MediaWiki was not    *
 *  written to provide per-page access restrictions, and all patches or        *
 *  extensions providing access control likely have flaws somewhere, which     *
 *  could lead to exposure of confidential data. The author, or WikiMedia, is  *
 *  not responsible for anything being leaked, leading to loss of funds or     *
 *  one's job.                                                                 *
 *******************************************************************************/

/*******************************************************************************
 * FEATURES                                                                    *
 *                                                                             *
 * This extension, when fully installed, provides support for the following:   *
 *  - Article access control by group using embedded tags                      *
 *  - Full (edit) access and read-only access supported                        *
 *  - Protection from adding an access control that would lock editor out      *
 *  - Filtering of Recent Changes based on article read access                 *
 *  - Filtering of Search results based on article read access                 *
 *  - Image protection using the same access groups as articles                *
 *  - Transclusion of restricted pages protected in Edit and Edit Preview      *
 *  - Protection from redirection to a restricted page                         *
 *******************************************************************************/

/*******************************************************************************
 * KNOWN ISSUES                                                                *
 *                                                                             *
 * Most known access control issues are addressed in this extension, but the   *
 *  following limitations are still known as of version 1.1:                   *
 *                                                                             *
 *  - Only Usergroup: style groups are supported. MediaWiki groups are not.    *
 *  - A patch is required for full functionality as of MediaWiki 1.13.2.       *
 *  - Only the latest access controls are queried for history. If you have     *
 *     restricted content that was removed and the page later made public,     *
 *     the history will also be public.                                        *
 *  - Caching might need to be disabled for full protection.                   *
 *  - Performance is somewhat significantly impacted due to many page lookups. *
 *  - Transclusion protection may prevent <nowiki>{{:Article}}</nowiki> from   *
 *     working properly in some cases.                                         *
 *  - There is no way to specify read-only access without the users being in   *
 *     a group (i.e., no page can have edits restricted to some groups and     *
 *     reads available to all.)                                                *
 *  - There is no way to transclude a protected page even if you have access.  *
 *  - Titles of restricted pages are visible on pages such as Special:AllPages.*
 *  - If you log out after having access to a restricted page, action=raw may  *
 *     still give you access.                                                  *
 *                                                                             *
 * These issues will likely be addressed in a future release.                  *
 *******************************************************************************/

/*******************************************************************************
 * USAGE                                                                       *
 *                                                                             *
 * To add access controls to an article, including images, insert a tag in the *
 *  following form:                                                            *
 *                                                                             *
 *  <accesscontrol>Name</accesscontrol>                                        *
 *                                                                             *
 * Where Name is defined, depending on configuration, either as a              *
 *  MediaWiki group (NOT CURRENTLY SUPPORTED) or as an article called          *
 *  Usergroup:Name with a list of users. (This page may itself be protected    *
 *  with <accesscontrol>, or limited to sysop accounts.)                       *
 *                                                                             *
 * For example, you could define a page Usergroup:MyGroup with the content:    *
 *  *Bill                                                                      *
 *  *Ted                                                                       *
 *  *Rufus                                                                     *
 *                                                                             *
 * And another article with <accesscontrol>MyGroup</accesscontrol>. This would *
 *  restrict the article to the user accounts Bill, Ted, and Rufus. Note that  *
 *  there should be no space between the asterik and the user name.            *
 *                                                                             *
 * To restrict an article to users in any of a list of groups, separate group  *
 *  names with ',,'. For example:                                              *
 *                                                                             *
 *  <accesscontrol>Admins,,Managers</accesscontrol>                            *
 *                                                                             *
 * To give one of the groups listed read-only access, add (ro) after the name, *
 *  without a space:                                                           *
 *                                                                             *
 *  <accesscontrol>Admins,,Managers,,Clients(ro)</accesscontrol>               *
 *                                                                             *
 * This would give anyone in the Admins or Managers groups full edit access,   *
 *  and read-only access to users in Clients. Users in multiple groups will    *
 *  get the highest level of access specified.                                 *
 *                                                                             *
 * Note that if adding access controls to an article, you cannot lock yourself *
 *  out. That is, if you are in Clients, you cannot leave Clients out of the   *
 *  access control list. (This option can be turned off or fine-tuned.)        *
 *******************************************************************************/

require_once('improved-accesscontrol-options.php');
require_once('improved-accesscontrol-body.php');

?>