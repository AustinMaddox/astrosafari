<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
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
$user->setup('viewforum');

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

	/* RECENT function
	attached_recent_images($forum_ids, $max_limit_arg, $orientation_arg, $num_chars, $max_width, $resize_after)
*/	attached_recent_images($forum_id, 5, 'vertical', 32, 200, 201);
// ENDED - Attached Images Block mod

display_forums('', $config['load_moderators']);

// Set some stats, get posts count from forums data if we... hum... retrieve all forums data
$total_posts	= $config['num_posts'];
$total_topics	= $config['num_topics'];
$total_users	= $config['num_users'];

$l_total_user_s = ($total_users == 0) ? 'TOTAL_USERS_ZERO' : 'TOTAL_USERS_OTHER';
$l_total_post_s = ($total_posts == 0) ? 'TOTAL_POSTS_ZERO' : 'TOTAL_POSTS_OTHER';
$l_total_topic_s = ($total_topics == 0) ? 'TOTAL_TOPICS_ZERO' : 'TOTAL_TOPICS_OTHER';

// Grab group details for legend display
if ($auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel'))
{
	$sql = 'SELECT group_id, group_name, group_colour, group_type
		FROM ' . GROUPS_TABLE . '
		WHERE group_legend = 1
		ORDER BY group_name ASC';
}
else
{
	$sql = 'SELECT g.group_id, g.group_name, g.group_colour, g.group_type
		FROM ' . GROUPS_TABLE . ' g
		LEFT JOIN ' . USER_GROUP_TABLE . ' ug
			ON (
				g.group_id = ug.group_id
				AND ug.user_id = ' . $user->data['user_id'] . '
				AND ug.user_pending = 0
			)
		WHERE g.group_legend = 1
			AND (g.group_type <> ' . GROUP_HIDDEN . ' OR ug.user_id = ' . $user->data['user_id'] . ')
		ORDER BY g.group_name ASC';
}
$result = $db->sql_query($sql);

$legend = array();
while ($row = $db->sql_fetchrow($result))
{
	$colour_text = ($row['group_colour']) ? ' style="color:#' . $row['group_colour'] . '"' : '';
	$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];

	if ($row['group_name'] == 'BOTS' || ($user->data['user_id'] != ANONYMOUS && !$auth->acl_get('u_viewprofile')))
	{
		$legend[] = '<span' . $colour_text . '>' . $group_name . '</span>';
	}
	else
	{
		$legend[] = '<a' . $colour_text . ' href="' . append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=group&amp;g=' . $row['group_id']) . '">' . $group_name . '</a>';
	}
}
$db->sql_freeresult($result);

$legend = implode(', ', $legend);

// Generate birthday list if required ...
$birthday_list = '';
if ($config['load_birthdays'] && $config['allow_birthdays'] && $auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel'))
{
	$now = phpbb_gmgetdate(time() + $user->timezone + $user->dst);

	// Display birthdays of 29th february on 28th february in non-leap-years
	$leap_year_birthdays = '';
	if ($now['mday'] == 28 && $now['mon'] == 2 && !$user->format_date(time(), 'L'))
	{
		$leap_year_birthdays = " OR u.user_birthday LIKE '" . $db->sql_escape(sprintf('%2d-%2d-', 29, 2)) . "%'";
	}

	$sql = 'SELECT u.user_id, u.username, u.user_colour, u.user_birthday
		FROM ' . USERS_TABLE . ' u
		LEFT JOIN ' . BANLIST_TABLE . " b ON (u.user_id = b.ban_userid)
		WHERE (b.ban_id IS NULL
			OR b.ban_exclude = 1)
			AND (u.user_birthday LIKE '" . $db->sql_escape(sprintf('%2d-%2d-', $now['mday'], $now['mon'])) . "%' $leap_year_birthdays)
			AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		$birthday_list .= (($birthday_list != '') ? ', ' : '') . get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

		if ($age = (int) substr($row['user_birthday'], -4))
		{
			$birthday_list .= ' (' . max(0, $now['year'] - $age) . ')';
		}
	}
	$db->sql_freeresult($result);
}

// BEGAN - Anniversary List mod
if (($anniversary_list = $cache->get('_anniversary_list')) === false)
{
	$anniversary_list = array();
	$current_date = date('m-d');
	$current_year = date('Y');
	$leap_year = date('L');
	$sql = 'SELECT user_id, username, user_colour, user_regdate
		FROM ' . USERS_TABLE . "
		WHERE user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')
		ORDER BY user_regdate ASC';
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		// We are compensating for leap year here.  If the year is not a leap year, the current date is Feb 28, and they joined Feb 29 we will list their names.
		if (date('m-d', $row['user_regdate']) == $current_date || (!$leap_year && $current_date == '02-28' && date('m-d', $row['user_regdate']) == '02-29'))
		{
			if (($current_year - date('Y', $row['user_regdate'])) > 0)
			{
				$anniversary_list[$row['user_id']] = array(
					'user_id'				=> $row['user_id'],
					'username'				=> $row['username'],
					'user_colour'			=> $row['user_colour'],
					'user_regdate'			=> $row['user_regdate'],
				);
			}
		}
	}
	$db->sql_freeresult($result);

	// Figure out what tomorrow's beginning time is based on the board timezone settings and have the cache expire then.
	$till_tomorrow = gmmktime(0, 0, 0) + 86400 - ($config['board_timezone'] * 3600) - ($config['board_dst'] * 3600) - time();
	
	// Cache this data till tomorrow
	$cache->put('_anniversary_list', $anniversary_list, $till_tomorrow);
}

foreach ($anniversary_list as $row)
{
	$template->assign_block_vars('anniversary_list', array(
		'ANNIVERSARY_USER'			=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
		'ANNIVERSARY_REG_DATE'		=> $user->format_date($row['user_regdate'], 'm/d/y'),
		'ANNIVERSARY_YEARS'			=> (date('Y') - date('Y', $row['user_regdate'])),
	));
}

$template->assign_vars(array(
	'ANNIVERSARY_LIST'		=> (count($anniversary_list > 0)) ? true : false,
	'ANNIVERSARY_COUNT'		=> count($anniversary_list),
));
// ENDED - Anniversary List mod

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
$gallery_block->set_nums(array('rows' => 3, 'columns' => 1, 'comments' => 1, 'contests' => 0));
$gallery_block->set_toggle(false);
$gallery_block->set_pegas(true);
//$gallery_block->add_albums(array(1, 2, 3));
//$gallery_block->add_users(array(4, 5, 6));
$gallery_block->display();
// ENDED - phpBB Gallery mod

// Assign index specific vars
$template->assign_vars(array(
	'TOTAL_POSTS'	=> sprintf($user->lang[$l_total_post_s], number_format($total_posts)),
	'TOTAL_TOPICS'	=> sprintf($user->lang[$l_total_topic_s], number_format($total_topics)),
	'TOTAL_USERS'	=> sprintf($user->lang[$l_total_user_s], number_format($total_users)),
	'NEWEST_USER'	=> sprintf($user->lang['NEWEST_USER'], get_username_string('full', $config['newest_user_id'], $config['newest_username'], $config['newest_user_colour'])),

	'LEGEND'		=> $legend,
	'BIRTHDAY_LIST'	=> $birthday_list,

	'FORUM_IMG'				=> $user->img('forum_read', 'NO_UNREAD_POSTS'),
	'FORUM_UNREAD_IMG'			=> $user->img('forum_unread', 'UNREAD_POSTS'),
	'FORUM_LOCKED_IMG'		=> $user->img('forum_read_locked', 'NO_UNREAD_POSTS_LOCKED'),
	'FORUM_UNREAD_LOCKED_IMG'	=> $user->img('forum_unread_locked', 'UNREAD_POSTS_LOCKED'),

	'S_IN_BOARD_INDEX'			=> true,
	'S_LOGIN_ACTION'			=> append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=login'),
	'S_DISPLAY_BIRTHDAY_LIST'	=> ($config['load_birthdays']) ? true : false,

	'U_MARK_FORUMS'		=> ($user->data['is_registered'] || $config['load_anon_lastread']) ? append_sid("{$phpbb_root_path}index.$phpEx", 'hash=' . generate_link_hash('global') . '&amp;mark=forums') : '',
	'U_MCP'				=> ($auth->acl_get('m_') || $auth->acl_getf_global('m_')) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=main&amp;mode=front', true, $user->session_id) : '')
);

// BEGAN - Top Latest mod
	include($phpbb_root_path . 'includes/top_latest.' . $phpEx);
// ENDED - Top Latest mod

// BEGAN - NV Recent Topics mod
if ($config['rt_index'])
{
	if (!function_exists('display_recent_topics'))
	{
		include($phpbb_root_path . 'includes/functions_recenttopics.' . $phpEx);
	}
	display_recent_topics($config['rt_number'], $config['rt_page_number'], $config['rt_anti_topics'], 'recent_topics', request_var('f', 0), true, $config['rt_parents']);
}
// ENDED - NV Recent Topics mod

// BEGAN - mChat mod
$mchat_installed = (!empty($config['mchat_version']) && !empty($config['mchat_enable'])) ? true : false;
if ($mchat_installed && $auth->acl_get('u_mchat_view'))
{
	if(!defined('MCHAT_INCLUDE') && $config['mchat_on_index'] && !empty($user->data['user_mchat_index']))
	{
		define('MCHAT_INCLUDE', true);
		$mchat_include_index = true;
		include($phpbb_root_path . 'mchat.' . $phpEx);
	}	

	if (!empty($config['mchat_stats_index']) && !empty($user->data['user_mchat_stats_index']))
	{
		if (!function_exists('mchat_users'))
		{
			include($phpbb_root_path . 'includes/functions_mchat.' . $phpEx);
		}
		// Add lang file
		$user->add_lang('mods/mchat_lang');
		// stats display
		$mchat_session_time = !empty($config_mchat['timeout']) ? $config_mchat['timeout'] : 3600;// you can change this number to a greater number for longer chat sessions
		$mchat_stats = mchat_users($mchat_session_time);
		$template->assign_vars(array(
			'MCHAT_INDEX_STATS'	=> true,
			'MCHAT_INDEX_USERS_COUNT'	=> $mchat_stats['mchat_users_count'],
			'MCHAT_INDEX_USERS_LIST'	=> $mchat_stats['online_userlist'],
			'L_MCHAT_ONLINE_EXPLAIN'	=> $mchat_stats['refresh_message'],	
		));
	}
}	
// ENDED - mChat mod

// BEGAN - phpBB-SEO Dynamic Meta Tags mod
$seo_meta->collect('description', $config['sitename'] . ' : ' .  $config['site_desc']);
$seo_meta->collect('keywords', 'astrosafari chevrolet astro gmc safari vans forums community photos gallery enthusiasts chevy gm astrosafarivans');
// ENDED - phpBB-SEO Dynamic Meta Tags mod

// Output page
page_header('Chevy Astro Vans - GMC Safari Van');

$template->set_filenames(array(
	'body' => 'index_body.html')
);

page_footer();

?>