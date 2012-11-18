<?php
/**
*
* @package phpBB3
* @version $Id: top_latest.php,v 1.0.5 2009/08/17 06:20:00 EST rmcgirr83 Exp $
* @copyright (c) 2009 Rich McGirr
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Include only once.
*/
if (!defined('INCLUDES_TOP_LATEST_PHP'))
{
	define('INCLUDES_TOP_LATEST_PHP', true);
	
	global $auth, $cache, $config, $user, $db, $phpbb_root_path, $phpEx, $template;
	
    $user->add_lang('mods/top_latest_lang');
	
	// an array of user types we dont' bother with
	// could add board founder (USER_FOUNDER) if wanted
	$ignore_users = array(USER_IGNORE, USER_INACTIVE);

	// grab auths that allow a user to read a forum
	$forum_array = array_unique(array_keys($auth->acl_getf('!f_read', true)));

	// we have auths, change the sql query below
	$sql_and = '';
	if (sizeof($forum_array))
	{
		$sql_and = ' AND ' . $db->sql_in_set('p.forum_id', $forum_array, true);
	}

    // ==== NEWEST USERS ====
	if (($newest_users = $cache->get('_top_latest_newest_users')) === false)
	{
		$newest_users = array();
		
		// grab most recent registered users
		$sql = 'SELECT user_id, username, user_colour, user_regdate
			FROM ' . USERS_TABLE . '
			WHERE ' . $db->sql_in_set('user_type', $ignore_users, true) . '
				AND user_inactive_reason = 0
			ORDER BY user_regdate DESC';
		$result = $db->sql_query_limit($sql, 10);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$newest_users[$row['user_id']] = array(
				'user_id'				=> $row['user_id'],
				'username'				=> $row['username'],
				'user_colour'			=> $row['user_colour'],
				'user_regdate'			=> $row['user_regdate'],
			);
		}
		$db->sql_freeresult($result);
		
		// cache this data for 5 minutes, this improves performance
		$cache->put('_top_latest_newest_users', $newest_users, 300);
	}
	 
	foreach ($newest_users as $row)
	{
		$username_string = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
		
		$template->assign_block_vars('top_latest_newest',array(
			'REG_DATE'			=> $user->format_date($row['user_regdate'], $format = 'M d'),
			'USERNAME_FULL'		=> $username_string
		));
	}
	
	// ==== TOP ACTIVE USERS ====
	if (($user_posts = $cache->get('_top_latest_posters')) === false)
	{
	    $user_posts = array();

		// grab users with most posts
	    $sql = 'SELECT user_id, username, user_colour, user_posts
	       	FROM ' . USERS_TABLE . '
			WHERE ' . $db->sql_in_set('user_type', $ignore_users, true) . '
				AND user_posts <> 0
			ORDER BY user_posts DESC';
		$result = $db->sql_query_limit($sql, 5);

		while ($row = $db->sql_fetchrow($result))
		{
			$user_posts[$row['user_id']] = array(
				'user_id'		=> $row['user_id'],
                'username'		=> $row['username'],
                'user_colour'	=> $row['user_colour'],
				'user_posts'    => $row['user_posts'],
			);
		}
        $db->sql_freeresult($result);

		// cache this data for 5 minutes, this improves performance
		$cache->put('_top_latest_posters', $user_posts, 300);
	 }
	 
	 $ranking = 0;
		
	 foreach ($user_posts as $row)
	 {
		$username_string = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
		$ranking++;
		$template->assign_block_vars('top_latest_active',array(
			'S_SEARCH_ACTION'	=> append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . $row['user_id'] . '&amp;sr=posts'),
			'RANKING'			=> '#' . $ranking,
			'USERNAME_FULL'		=> $username_string,
			'POSTS' 			=> $row['user_posts'],
	   ));
    }

	// ==== TOP ACTIVE USERS of the last 24 hours ====
	if (($user_posts_24 = $cache->get('_top_latest_posters_24')) === false)
	{
	    $user_posts_24 = array();
		
		$hours = 24;
		
		$minutes = ( $hours * 3600 );
		$time = time() - $minutes;

		// grab users with most posts
		$sql = "SELECT u.user_id, u.username, u.user_type, u.user_colour, u.user_posts, COUNT(p.post_id) as total_posts
			FROM " . USERS_TABLE . " u, " . POSTS_TABLE . " p 
			WHERE p.post_time > " . (int) $time . "
				AND u.user_id = p.poster_id
					AND u.user_id <> " . (int) ANONYMOUS . "
						AND u.user_type <> " . (int) USER_IGNORE . "
			GROUP BY u.user_id 
			ORDER BY total_posts DESC";
		$result = $db->sql_query_limit($sql, 5);

		while ($row = $db->sql_fetchrow($result))
		{
			$user_posts_24[$row['user_id']] = array(
				'user_id'		=> $row['user_id'],
                'username'		=> $row['username'],
				'user_type'		=> $row['user_type'],
                'user_colour'	=> $row['user_colour'],
				'user_posts'    => $row['user_posts'],
				'total_posts'	=> $row['total_posts'],
			);
		}
        $db->sql_freeresult($result);

		// cache this data for 5 minutes, this improves performance
		$cache->put('_top_latest_posters_24', $user_posts_24, 300);
	 }

	 foreach ($user_posts_24 as $row)
	 {
		$username_string = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
			
		$template->assign_block_vars('top_latest_active_24', array(
			'S_SEARCH_ACTION'	=> append_sid("{$phpbb_root_path}search.$phpEx", 'author_id=' . $row['user_id'] . '&amp;sr=posts'),
			'POSTS' 			=> $row['total_posts'],
			'USERNAME_FULL'		=> $username_string,
		));
    }
	
}
?>