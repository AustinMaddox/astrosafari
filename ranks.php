<?php
/**
*
* @package Ranks Page
* @version $Id: ranks.php, 0005 19:40 09/12/2008 cherokee red Exp $
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/ranks_lang');

// How many ranks do we have?
$sql = 'SELECT *
	FROM ' . RANKS_TABLE . '
	ORDER BY rank_special ASC, rank_min ASC, rank_title ASC';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result)) 
{
	$template->assign_block_vars('rank', array(
		'RANK_ID'        	=> $row['rank_id'],
		'RANK_TITLE'        => (isset($row['rank_title'])) ? $row['rank_title'] : '',
		'RANK_MIN'        	=> (isset($row['rank_min']) && !$row['rank_special']) ? $row['rank_min'] : $user->lang['RANK_SPECIAL'],
		'S_RANK_SPECIAL'    => (isset($row['rank_special'])) ? true : false,
		'RANK_IMAGE'        => (empty($row['rank_image'])) ? '' : $phpbb_root_path . $config['ranks_path'] . '/' . $row['rank_image'],
	));
}
$db->sql_freeresult($result);

$l_title = $user->lang['RANKS'];

// Set up the Navlinks for the forums navbar
$template->assign_block_vars('navlinks', array(
	'FORUM_NAME'       => $l_title,
	'U_VIEW_FORUM'     => append_sid("{$phpbb_root_path}ranks.$phpEx")
	));

// Output page
page_header($l_title);

$template->set_filenames(array(
	'body' => 'ranks_body.html'
	)
);

page_footer();

?>