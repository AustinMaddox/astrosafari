<?php
/**
*
* @package phpBB3
* @version $Id: groups.php,v 1.0.1 2009-08-16 09:28:00 EST rmcgirr83 $
* @copyright (c) Rich McGirr
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

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('groups');
$user->setup('mods/groups_lang');

// grab user count of groups
$sql = 'SELECT group_id
	FROM ' . USER_GROUP_TABLE . '
		WHERE user_pending <> 1
			ORDER BY group_id';
$result = $db->sql_query($sql);

$groups_count = array();
while ($row = $db->sql_fetchrow($result))
{
	$groups_count[] = $row['group_id'];
}
$db->sql_freeresult($result);
$total_groups_count = sizeof($groups_count);

// now get the group(s) info

$sql_where = "";

// don't want coppa group?
if (!$config['coppa_enable'])
{
	$sql_where .= "WHERE group_name <> 'REGISTERED_COPPA'";
}

$sql = 'SELECT *
	FROM ' . GROUPS_TABLE . '
	' . $sql_where . '
	ORDER BY group_name';
$result = $db->sql_query($sql);

// we gots us some results ?
if ($row = $db->sql_fetchrow($result))
{
	do
	{
		if ($row['group_type'] == GROUP_HIDDEN && !$auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel'))
		{
			continue;
		}
		// how many users does the group have?
		if ($total_groups_count)
		{
			$user_count = 0;
			for ($i = 0; $i < $total_groups_count; $i++)
			{
	            if ($row['group_id'] == $groups_count[$i])
	            {
					$user_count++;
	            }
			}
		}
		// Grab rank information
		$ranks = $cache->obtain_ranks();
		
		$rank_title = $rank_img = $rank_img_src = '';
		if ($row['group_rank'])
		{
			if (isset($ranks['special'][$row['group_rank']]))
			{
				$rank_title = $ranks['special'][$row['group_rank']]['rank_title'];
			}
			$rank_img = (!empty($ranks['special'][$row['group_rank']]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$row['group_rank']]['rank_image'] . '" alt="' . $ranks['special'][$row['group_rank']]['rank_title'] . '" title="' . $ranks['special'][$row['group_rank']]['rank_title'] . '" /><br />' : '';
			$rank_img_src = (!empty($ranks['special'][$row['group_rank']]['rank_image'])) ? $config['ranks_path'] . '/' . $ranks['special'][$row['group_rank']]['rank_image'] : '';
		}

		// open, closed, hidden blah blah blah
		switch ($row['group_type'])
		{
			case GROUP_OPEN:
				$row['l_group_type'] = 'OPEN';
			break;
				
			case GROUP_HIDDEN:
				$row['l_group_type'] = 'HIDDEN';
			break;
			
			case GROUP_CLOSED:
				$row['l_group_type'] = 'CLOSED';
			break;

			case GROUP_SPECIAL:
				$row['l_group_type'] = 'SPECIAL';
			break;

			case GROUP_FREE:
				$row['l_group_type'] = 'FREE';
			break;
		}

		// Misusing the avatar function for displaying group avatars...
		$avatar_img = get_user_avatar($row['group_avatar'], $row['group_avatar_type'], $row['group_avatar_width'], $row['group_avatar_height'], 'GROUP_AVATAR');		
		$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];
		$u_group = append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=group&amp;g=' . $row['group_id']);
		$s_bot_group = ($row['group_name'] == 'BOTS') ? true : false;
		$s_guest_group = ($row['group_name'] == 'GUESTS') ? true : false;

		$template->assign_block_vars('group_row', array(
			'GROUP_NAME'	=> $group_name,
			'GROUP_RANK'	=> $rank_title,
			'GROUP_DESC'	=> generate_text_for_display($row['group_desc'], $row['group_desc_uid'], $row['group_desc_bitfield'], $row['group_desc_options']),
			'GROUP_COLOR'	=> $row['group_colour'],
			'GROUP_TYPE'	=> $user->lang['GROUP_IS_' . $row['l_group_type']],
			'GROUP_COUNT'	=> number_format($user_count),
			'AVATAR_IMG'	=> $avatar_img,
			'RANK_TITLE'	=> $rank_title,
			'RANK_IMG'		=> $rank_img,
			'RANK_IMG_SRC'	=> $rank_img_src,			
			
			'S_BOT_GROUP'	=> $s_bot_group,
			'S_GUEST_GROUP'	=> $s_guest_group,
			'U_GROUP'		=> $u_group,
		));
	}
	while ($row = $db->sql_fetchrow($result));

	$db->sql_freeresult($result);
}


// BEGAN - Groups page broken down into members
$user_id	= request_var('u', 0);
$group_id	= request_var('g', 0);

/**
* Config settings - these are some settings you can use to change what features you want to enable and what you don't want to display :)
*/
// Set to false if you don't want the group rank image to be displayed
$group_rank_img = true;

// Set to true if you want the group rank title to be displayed
$group_rank_title = true;

// Set to false if you don't want the group description to be displayed
$group_desc = true;

/**
*  End: Config settings
*/

// I would like to thank the wiki many times over <3 - http://wiki.phpbb.com/Template_Syntax
$sql = 'SELECT * FROM ' . GROUPS_TABLE . '
	WHERE group_type <> 2
		AND group_id > 3
		AND group_id <> 6
		AND group_name != "NEWLY_REGISTERED"
	ORDER BY group_name';
$result = $db->sql_query($sql);

while($groups = $db->sql_fetchrow($result))
{

	// Grab rank information for later
	$ranks = $cache->obtain_ranks();

	// Do we have a Group Rank?
	if ($groups['group_rank'])
	{
		if (isset($ranks['special'][$groups['group_rank']]))
		{
			$rank_title = $ranks['special'][$groups['group_rank']]['rank_title'];
		}
		$rank_img = (!empty($ranks['special'][$groups['group_rank']]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$groups['group_rank']]['rank_image'] . '" alt="' . $ranks['special'][$groups['group_rank']]['rank_title'] . '" title="' . $ranks['special'][$groups['group_rank']]['rank_title'] . '" /><br />' : '';
		$rank_img_src = (!empty($ranks['special'][$groups['group_rank']]['rank_image'])) ? $config['ranks_path'] . '/' . $ranks['special'][$groups['group_rank']]['rank_image'] : '';
	}
	else
	{
		$rank_title = '';
		$rank_img = '';
		$rank_img_src = '';
	}

	$template->assign_block_vars('groups', array(
		'GROUP_ID'				=> $groups['group_id'],
		'GROUP_NAME'			=> ($groups['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $groups['group_name']] : $groups['group_name'],
		'GROUP_DESC'			=> generate_text_for_display($groups['group_desc'], $groups['group_desc_uid'], $groups['group_desc_bitfield'], $groups['group_desc_options']),
		'GROUP_COLOUR'			=> $groups['group_colour'],
		'GROUP_RANK'			=> $rank_title,

		'RANK_IMG'				=> $rank_img,
		'RANK_IMG_SRC'			=> $rank_img_src,

		'U_VIEW_GROUP'			=> append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=group&amp;g=' . $groups['group_id']),

		'S_SHOW_RANK'			=> true,
		'S_SHOW_RANK_IMG'		=> $group_rank_img,
		'S_SHOW_GROUP_RANK'		=> $group_rank_title,
		'S_SHOW_GROUP_DESC'		=> $group_desc,
	));

	// Grab the leaders - always, on every page...
	$lsql = 'SELECT u.user_id, u.username, u.username_clean, u.user_colour, u.group_id, ug.group_leader
	FROM ' . USERS_TABLE . ' u, ' . USER_GROUP_TABLE . " ug
		WHERE ug.group_id = " . $groups['group_id'] . "
			AND u.user_id = ug.user_id
			AND ug.group_leader = 1
			ORDER BY ug.group_leader DESC, u.username_clean";
	$lresult = $db->sql_query($lsql);

	while($leaders = $db->sql_fetchrow($lresult))
	{
		$template->assign_block_vars('groups.leaders', array(
			'USERNAME'			=> $leaders['username'],
			'USERNAME_FULL'		=> get_username_string('full', $leaders['user_id'], $leaders['username'], $leaders['user_colour']),
			'U_VIEW_PROFILE'	=> get_username_string('profile', $leaders['user_id'], $leaders['username']),
			'S_GROUP_DEFAULT'	=> ($leaders['group_id'] == $group_id) ? true : false,
			'USER_ID'			=> $leaders['user_id'],
		));
	}
	$db->sql_freeresult($lresult);

	// We have the leaders, so lets find other peeps from the group
	$msql = 'SELECT u.user_id, u.username, u.username_clean, u.user_colour, u.group_id, ug.group_leader, ug.group_leader
	FROM ' . USERS_TABLE . ' u, ' . USER_GROUP_TABLE . " ug
		WHERE ug.group_id = " . $groups['group_id'] . "
			AND u.user_id = ug.user_id
			AND ug.group_leader = 0
			ORDER BY u.username_clean";
	$mresult = $db->sql_query($msql);
			
	while($members = $db->sql_fetchrow($mresult))
	{
		$template->assign_block_vars('groups.members', array(
			'USER_ID'			=> $members['user_id'],
			'USERNAME'			=> $members['username'],
			'USERNAME_FULL'		=> get_username_string('full', $members['user_id'], $members['username'], $members['user_colour']),
			'U_VIEW_PROFILE'	=> get_username_string('profile', $members['user_id'], $members['username']),
			'S_GROUP_DEFAULT'	=> ($members['group_id'] == $group_id) ? true : false,
		));
	}
	$db->sql_freeresult($mresult);
}
$db->sql_freeresult($result);
// ENDED - Groups page broke down into members

// BEGAN - phpBB Gallery mod
if (class_exists('phpbb_gallery_integration'))
{
	phpbb_gallery_integration::index_total_images();
}
/**
* RRC of phpbb gallery
* http://www.flying-bits.org/rrc_configurator.php
*/
$gallery_block = new phpbb_gallery_block();
$gallery_block->set_modes(array('recent', 'comment'));
$gallery_block->set_display_options(array('albumname', 'imagename', 'imagetime', 'imageviews', 'username', 'ratings', 'ip'));
$gallery_block->set_nums(array('rows' => 6, 'columns' => 1, 'comments' => 0, 'contests' => 0));
$gallery_block->set_toggle(false);
$gallery_block->set_pegas(true);
//$gallery_block->add_albums(array(1, 2, 3));
//$gallery_block->add_users(array(4, 5, 6));
$gallery_block->display();
// ENDED - phpBB Gallery mod

$template->assign_vars(array(
	'S_IN_GROUPS'		=> true,
	'FORUM_NAME'		=> $user->lang['GROUPS_TITLE'],
	'FORUM_DESC_SIDE'	=> $user->lang['GROUPS_DESC_SIDE'],
	'FORUM_KEY'			=> $user->lang['GROUPS_KEY_SIDE'],
));

$l_title = $user->lang['GROUPS'];

// Set up the Navlinks for the forums navbar
$template->assign_block_vars('navlinks', array(
	'FORUM_NAME'       => $l_title,
	'U_VIEW_FORUM'     => append_sid("{$phpbb_root_path}groups.$phpEx")
	));

// Output page
page_header($l_title);
	
$template->set_filenames(array(
	'body' => 'groups_body.html'
	)
);
make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"));

page_footer();

?>