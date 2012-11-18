<?php
/**
*
* top_five [English]
*
* @package language
* @version $Id: top_latest_lang.php,v 1.0.0.b 2009/08/17 06:26:00 rmcgirr83 Exp $
* @copyright (c) 2009 Rich McGirr 
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
    'NEWEST_TOPICS'			=> 'Newest Posts',
    'TOP_LATEST_NEWEST'		=> 'Newest Users',
	'TOP_LATEST_ACTIVE'		=> 'Top Posters',
	'TOP_LATEST_ACTIVE_24'	=> 'of the day',
	'NO_ACTIVITY'			=> 'No activity in the last 24 hours.<br />Hurry quick!<br />Somebody post something.',
	'LAST_VISITED_BOTS'		=> 'Last %s Bot Visits',
	'LATEST_ANNOUNCEMENTS'	=> 'Latest Announcements',

));

?>
