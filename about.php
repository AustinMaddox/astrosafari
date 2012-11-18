<?php
/**
*
* @author austin881 support@whoa.co - http://whoa.co
* @package phpBB
* @version $Id$
* @copyright (c) 2012 Whoa.Co
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
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/about_lang');

// BEGAN - Attached Images Block mod
if (!function_exists('attached_recent_images') || !function_exists('attached_random_images'))
{
	include($phpbb_root_path . 'includes/functions_attached_images.' . $phpEx);
}
	$sql = 'SELECT forum_id
		FROM ' . FORUMS_TABLE . '
		WHERE forum_type = 1';
	$result = $db->sql_query($sql);
	$forum_id = '';
	while ($row = $db->sql_fetchrow($result))
	{
		$forum_id .= $row['forum_id'] . ',';
	}
	$db->sql_freeresult($result);

	/* RANDOM function
	attached_random_images($forum_ids, $max_limit_arg, $orientation_arg, $num_chars, $max_width, $resize_after)
*/	attached_random_images($forum_id, 3, false, 32, 120, 121);
// ENDED - Attached Images Block mod

// BEGAN - phpBB Gallery mod
/**
* RRC of phpbb gallery
* http://www.flying-bits.org/rrc_configurator.php
*/
$gallery_block = new phpbb_gallery_block();
$gallery_block->set_modes(array('recent', 'comment'));
$gallery_block->set_display_options(array('albumname', 'imagename', 'imagetime', 'imageviews', 'username', 'ratings', 'ip'));
$gallery_block->set_nums(array('rows' => 2, 'columns' => 3, 'comments' => 1, 'contests' => 0));
$gallery_block->set_toggle(false);
$gallery_block->set_pegas(true);
//$gallery_block->add_albums(array(1, 2, 3));
//$gallery_block->add_users(array(4, 5, 6));
$gallery_block->display();
// ENDED - phpBB Gallery mod

page_header($user->lang['ABOUT_US']);

$template->assign_block_vars('navlinks', array(
	'FORUM_NAME'		=> $user->lang['ABOUT_US'],
	'U_VIEW_FORUM'		=> append_sid("{$phpbb_root_path}about.$phpEx"),
));

$template->set_filenames(array(
	'body' => 'about_body.html',
));

page_footer();

?>