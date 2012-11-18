<?php
/**
*
* @package phpBB3 Forum Sponsor
* @copyright (c) 2007 EXreaction, Lithium Studios
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'FORUM_SPONSOR'						=> 'Forum Sponsor',

	'FORUM_SPONSOR_INSIDE'				=> 'Forum Sponsor (Viewforum/Viewtopic)',
	'FORUM_SPONSOR_INSIDE_EXPLAIN'		=> 'Content displayed inside this forum in the sponsor areas. <em>(Recommended: 468x60)</em>',

	'FORUM_SPONSOR_FORUMLIST'			=> 'Forum Sponsor (Forumlist)',
	'FORUM_SPONSOR_FORUMLIST_EXPLAIN'	=> 'Content displayed in the forumlist sponsor area of the list of forums. <em>(Recommended: 50x50)</em>',
	
	'FORUM_SPONSOR_INSTALL'				=> 'Forum Sponsors Mod',
	'FORUM_SPONSOR_INSTALL_CONFIRM'		=> 'Are you ready to install the changes required for the Forum Sponsors Mod?',
	'FOUNDER_ONLY'						=> 'Only Board Founders may access this page.',

	'INSTALL_COMPLETED'					=> 'Install Completed, now delete the install.php file from your root phpBB3 directory.',
	'PARSE_HTML'						=> 'Parse HTML',
));

?>