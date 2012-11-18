<?php
/**
* Gallery2 Integration
*
* @package	phpBB3
* @version 1.2.1
* @copyright (c) 2007 jettyrat
* @license	http://opensource.org/licenses/gpl-license.php GNU Public License
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
* @package phpBB3
*/
class g2helper
{
	var $_compatibleEmbedVersionMajor = 1;
	var $_compatibleEmbedVersionMinor = 1;

	var $_init_array;
	var $_use_phpass;

	/**
	* Gallery2 helper constructor
	* check and initialize some settings and load the main Gallery embed file
	*/
	function g2helper()
	{
		global $config, $user, $phpEx;

		$user->add_lang('mods/g2i/g2helper');

		if (empty($config['g2i_fullPath']) || empty($config['g2i_embedUri']) || empty($config['g2i_g2Uri']))
		{
			trigger_error($user->lang['OBTAIN_SETTINGS_FAILED'], E_USER_ERROR);
		}

		$this->_init_array = array(
			'embedUri'		=> $config['g2i_embedUri'], 
			'g2Uri'			=> $config['g2i_g2Uri'],
			'loginRedirect' => dirname($config['g2i_embedUri']) . "/ucp.$phpEx?mode=login",
			'apiVersion'	=> array($this->_compatibleEmbedVersionMajor, $this->_compatibleEmbedVersionMinor)
		);

		require_once($config['g2i_fullPath']);

		// embed version 1,5 = G2.3 - This version and higher uses phpass for password encryption
		$version = GalleryEmbed::getApiVersion();
		$this->_use_phpass = ($version[0] == 1 && $version[1] < 5) ? false : true;
	}

	/**
	* initialize a G2 session with the current user
	*
	* @param bool $validG2User valid Gallery user
	*/
	function init($validG2User)
	{
		global $user, $gallery;

		// not anonymous user or bot
		if ($user->data['user_type'] != USER_IGNORE)
		{
			if ($validG2User) // if known valid user, go ahead and login as that user
			{
				$this->_galleryInit($user->data['user_id']);
			}
			else // login as guest for now
			{
				$this->_galleryInit(false);
			}

			// check if current user exists in Gallery
			$ret = GalleryEmbed::isExternalIdMapped($user->data['user_id'], 'GalleryUser');
			if (empty($ret))
			{
				// load Gallery user entity
				$entityId = $this->_loadEntityByExternalId($user->data['user_id']);

				// auto-update users Gallery password if using G2.3 or higher to use phpass
				if ($this->_use_phpass && strlen($user->data['user_password']) == 34 && (substr($entityId->getHashedPassword(), 3) !== substr($user->data['user_password'], 3)	|| $entityId->getHashedPassword() === $user->data['user_password']))
				{
					$this->updateUser($user->data['user_id'], array('user_password' => $user->data['user_password']));

					GalleryDataCache::removeFromDisk(array('type' => 'entity', 'itemId' => $entityId->getId()));
					GalleryDataCache::reset();

					$entityId = $this->_loadEntityByExternalId($user->data['user_id']);
				}

				// check or set users Gallery album link
				$this->_mapGalleryLink($entityId);

				// this user switching vs re-initializing must be done this way to make G2.3- and G2.3+ work with the user albums module
				if ($this->_use_phpass && !$validG2User) // if G2.3 or higher, simply switch to logged in user
				{
					$gallery->setActiveUser($entityId);
				}
				elseif (!$this->_use_phpass && !$validG2User) // elseif less than G2.3, finish the transaction and re-initialize as logged in user
				{
					$this->_done();
					$this->_galleryInit($user->data['user_id']);
				}
				// else we are already logged in as a known user
			}
			// check if current user exists in Gallery and add external id map if so
			elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
			{
				list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($user->data['username']);
				if (empty($ret))
				{
					$ret = GalleryEmbed::addExternalIdMapEntry($user->data['user_id'], $userId->getId(), 'GalleryUser');
					if ($ret)
					{
						trigger_error(sprintf($user->lang['G2_ADDEXTERNALMAPENTRY_FAILED'], $user->data['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
					}
				}
				// create new user account if current user does not have a Gallery account and no external id mapping
				elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
				{
					list ($g2Password, $g2PassType) = $this->_setGalleryHash($user->data['user_password']);

					$this->_createUser($user->data['user_id'], array(
						'username'			=> $user->data['username'],
						'hashedpassword'	=> $g2Password,
						'email'				=> $user->data['user_email'],
						'fullname'			=> $user->data['username'],
						'language'			=> $user->data['user_lang'],
						'creationtimestamp' => time(),
						'hashmethod'		=> $g2PassType) 
					);

					// finish the transaction and re-initialize as new user
					$this->_done();
					$this->_galleryInit($user->data['user_id']);
				}
			}
			else
			{
				trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $user->data['username']), E_USER_ERROR);
			}
		}
		// login phpBB anonymous user or bot as Gallery guest
		else
		{
			$this->_galleryInit(false, true);
		}
	}

	/**
	* utility method to init Gallery user
	*
	* @param int $userId currrent user_id or false for guest user
	* @param bool $anonymousUser guaranteed anonymous user
	*
	* @access private
	*/
	function _galleryInit($userId, $anonymousUser = false)
	{
		global $user;

		$this->_init_array['activeUserId'] = (empty($userId)) ? false : $userId;

		$ret = GalleryEmbed::init($this->_init_array);
		// catch a condition where user existed in Gallery but has been deleted via the Gallery admin page (Gallery does not update the externalidmap table when deleting users for some reason)
		if ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			$ret = GalleryCoreApi::removeMapEntry('ExternalIdMap', array('externalId' => $user->data['user_id'], 'entityType' => 'GalleryUser'));
			if ($ret)
			{
				trigger_error($user->lang['G2_REMOVEMAPENTRY_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}

			$this->_done();
			$this->init(false);
		}
		elseif ($ret)
		{
			trigger_error(sprintf($user->lang['G2_INITUSER_FAILED'], $this->_init_array['activeUserId']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		// set Gallery guest user to actual guest id to prevent ERROR_BAD_PARAMETER from user albums module if no user is logged in and not a true anonymous user
		if (empty($userId) && !$anonymousUser)
		{
			global $gallery;

			$guestUserId = $this->_getPluginParameter('id.anonymousUser');

			$entityId = $this->_loadEntitiesById($guestUserId);

			$gallery->setActiveUser($entityId);
		}
	}

	/**
	* make phpbb password compatible with standard phpass used by Gallery2.3 or higher
	*
	* @param string $password current phpBB user password
	*
	* @return array Gallery user password and password type
	*
	* @access private
	*/
	function _setGalleryHash($password)
	{
		if (substr($password, 0, 3) == '$H$' && $this->_use_phpass)
		{
			$g2Password = '$P$' . substr($password, 3);
			$g2PassType = 'phpass';
		}
		else
		{
			$g2Password = $password;
			$g2PassType = 'md5';
		}

		return array($g2Password, $g2PassType);
	}

	/**
	* complete the Gallery transaction
	*
	* @access private
	*/
	function _done()
	{
		$ret = GalleryEmbed::done();
		if ($ret)
		{
			global $user;

			trigger_error($user->lang['G2_TRANSACTION_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}
	}

	/**
	* create new user in Gallery and add user to existing groups in Gallery
	*
	* @access private
	*/
	function _createUser($id, $newUserData)
	{
		global $db, $user;

		// create the user
		$ret = GalleryEmbed::createUser($id, $newUserData);
		if ($ret)
		{
			trigger_error(sprintf($user->lang['G2_CREATEUSER_FAILED'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		$phpbbDefaultGroups = array(
			'GUESTS',
			'REGISTERED',
			'REGISTERED_COPPA',
			'GLOBAL_MODERATORS',
			'ADMINISTRATORS',
			'BOTS',
			'NEWLY_REGISTERED'
		);

		// add user to groups
		$g_ary = array();
		$g_ary['SELECT'] = 'g.group_name';
		$g_ary['FROM'] = array(
			GROUPS_TABLE	 => 'g',
			USER_GROUP_TABLE => 'ug'
		);
		$g_ary['WHERE'] = "ug.user_id = $id 
			AND ug.group_id = g.group_id 
			AND " . $db->sql_in_set('g.group_name', $phpbbDefaultGroups, true);
		$g_sql = $db->sql_build_query('SELECT_DISTINCT', $g_ary);

		$result = $db->sql_query($g_sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$ret = GalleryEmbed::addUserToGroup($id, $row['group_name']);
			if ($ret)
			{
				trigger_error(sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $row['group_name'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}
		}

		$db->sql_freeresult($result);

		// set default Gallery UCP settings parameters
		//todo: make this configurable from ACP
		$links_data = array();
		$links_data['link'] = 1;
		$links_data['itemLinks'] = 1;
		$links_data['randomLinks'] = 0;
		$links_data['ucp_images'] = array();

		$sql = 'UPDATE ' . USERS_TABLE . "
			SET user_gallery_links_data = '" . $db->sql_escape(serialize($links_data)) . "'
			WHERE user_id = $id";
		$db->sql_query($sql);
	}

	/**
	* update user data in Gallery
	*/
	function updateUser($id, $userData)
	{
		global $user;

		$this->_galleryInit($user->data['user_id']);

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret))
		{
			if (isset($userData['user_password']))
			{
				list ($g2Password, $g2PassType) = $this->_setGalleryHash($userData['user_password']);
			}
			else
			{
				$g2Password = null;
				$g2PassType = null;
			}

			$ret = GalleryEmbed::updateUser($id, array(
				'username'			=> (isset($userData['username'])) ? $userData['username'] : null,
				'hashedpassword'	=> $g2Password,
				'hashmethod'		=> $g2PassType,
				'email'				=> (isset($userData['user_email'])) ? $userData['user_email'] : null)
			);
			if ($ret)
			{
				trigger_error(sprintf($user->lang['G2_UPDATEUSER_FAILED'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}
		}
		elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			/*
			* user doesn't exist in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), E_USER_ERROR);
		}

		$this->_done();
	}

	/**
	* delete user in Gallery
	*/
	function deleteUser($id)
	{
		global $db, $user;

		$this->_galleryInit($user->data['user_id']);

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret))
		{
			$ret = GalleryEmbed::deleteUser($id);
			if ($ret)
			{
				trigger_error(sprintf($user->lang['G2_DELETEUSER_FAILED'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}
		}
		elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			/*
			* user doesn't exist in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), E_USER_ERROR);
		}

		$this->_done();
	}

	/**
	* create group in Gallery
	*/
	function createGroup($groupName)
	{
		global $user;

		$this->_galleryInit($user->data['user_id']);

		$ret = GalleryEmbed::isExternalIdMapped($groupName, 'GalleryGroup');
		if ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			list ($ret, $groupId) = GalleryCoreApi::fetchGroupByGroupName($groupName);
			if (empty($ret))
			{
				$ret = GalleryEmbed::addExternalIdMapEntry($groupName, $groupId->getId(), 'GalleryGroup');
				if ($ret)
				{
					trigger_error(sprintf($user->lang['G2_ADDEXTERNALMAPENTRY_FAILED'], $groupName) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
				}
			}
			elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
			{
				$ret = GalleryEmbed::createGroup($groupName, $groupName);
				if ($ret)
				{
					trigger_error(sprintf($user->lang['G2_CREATEGROUP_FAILED'], $groupName) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
				}
			}
		}
		elseif (empty($ret))
		{
			/*
			* Group already exists in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $groupName), E_USER_ERROR);
		}

		$this->_done();
	}

	/**
	* update group in Gallery
	*/
	function updateGroup($group_id, $newGroupName)
	{
		global $db, $user;

		$this->_galleryInit($user->data['user_id']);

		$sql = 'SELECT group_name 
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $group_id";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1)))
		{
			trigger_error($user->lang['FETCH_GROUPDATA_FAILED'], E_USER_ERROR);
		}

		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret))
		{
			$ret = GalleryEmbed::updateGroup($row['group_name'], array('groupname' => $newGroupName));
			if ($ret)
			{
				trigger_error(sprintf($user->lang['G2_UPDATEGROUP_FAILED'], $newGroupName) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}

			$ret = GalleryCoreApi::updateMapEntry('ExternalIdMap', array('externalId' => $row['group_name']) , array('externalId' => $newGroupName));
			if ($ret)
			{
				trigger_error(sprintf($user->lang['G2_UPDATEMAPENTRY_FAILED'], $row['group_name']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}
		}
		elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), E_USER_ERROR);
		}

		$this->_done();
	}

	/**
	* delete group in Gallery
	*/
	function deleteGroup($groupId)
	{
		global $db, $user;

		$this->_galleryInit($user->data['user_id']);

		$users = array();

		// gather users for this group
		$sql = 'SELECT user_id 
			FROM ' . USER_GROUP_TABLE . " 
			WHERE group_id = $groupId";
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$users[] = $row['user_id'];
		}

		$db->sql_freeresult($result);

		// fetch group name from group id
		$sql = 'SELECT group_name 
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $groupId";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1)))
		{
			trigger_error($user->lang['FETCH_GROUPDATA_FAILED'], E_USER_ERROR);
		}

		// delete Gallery group if it exists
		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret))
		{
			$ret = GalleryEmbed::deleteGroup($row['group_name']);
			if ($ret)
			{
				trigger_error(sprintf($user->lang['G2_DELETEGROUP_FAILED'], $row['group_name']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}
		}
		elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), E_USER_ERROR);
		}

		$this->_done();

		// update admin permissions for users in this group in case it had admin permissions
		if (sizeof($users) > 0)
		{
			$this->updateAdminPermissions($users, 'user');
		}
	}

	/**
	* add users to Gallery group
	*
	* @param array $members array of user_id's
	* @param int $groupId phpbb group_id
	*/
	function addUserToGroup($members, $groupId)
	{
		global $db, $user;

		$this->_galleryInit(false);

		$sql = 'SELECT group_name 
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $groupId";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1)))
		{
			trigger_error($user->lang['FETCH_GROUPDATA_FAILED'], E_USER_ERROR);
		}

		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret))
		{
			foreach ($members as $member)
			{
				$ret = GalleryEmbed::isExternalIdMapped($member, 'GalleryUser');
				if (empty($ret))
				{
					$ret = GalleryEmbed::addUserToGroup($member, $row['group_name']);
					if ($ret)
					{
						trigger_error(sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $row['group_name'], $member) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
					}
				}
				elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
				{
					/*
					* User does not exist in gallery, so do nothing!
					*/
				}
				else
				{
					trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $member), E_USER_ERROR);
				}
			}
		}
		elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), E_USER_ERROR);
		}

		$this->_done();
	}

	/**
	* remove users from Gallery group
	*
	* @param array $members array of user_id's
	* @param int $groupId phpbb group_id
	*/
	function removeUserFromGroup($members, $groupId)
	{
		global $db, $user;

		$this->_galleryInit($user->data['user_id']);

		$sql = 'SELECT group_name 
			FROM ' . GROUPS_TABLE . " 
			WHERE group_id = $groupId";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1)))
		{
			trigger_error($user->lang['FETCH_GROUPDATA_FAILED'], E_USER_ERROR);
		}

		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret))
		{
			foreach ($members as $member)
			{
				$ret = GalleryEmbed::isExternalIdMapped($member, 'GalleryUser');
				if (empty($ret))
				{
					$ret = GalleryEmbed::removeUserFromGroup($member, $row['group_name']);
					if ($ret)
					{
						trigger_error(sprintf($user->lang['G2_REMOVEUSERFROMGROUP_FAILED'], $row['group_name'], $member) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
					}
				}
				elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
				{
					/*
					* User does not exist in gallery, so do nothing!
					*/
				}
				else
				{
					trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $member), E_USER_ERROR);
				}
			}
		}
		elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), E_USER_ERROR);
		}

		$this->_done();

	}

	/**
	* update Gallery admin permissions for a set of users
	*
	* @param array $ids array of id's single role, multiple groups, multiple users
	* @param string $type role, group, user
	*/
	function updateAdminPermissions($ids, $type)
	{
		global $db, $user, $auth;

		$this->_galleryInit(false, true);

		$ret = GalleryEmbed::isExternalIdMapped($user->data['user_id'], 'GalleryUser');
		if ($ret)
		{
			// if current user does not exist in Gallery there is point continuing because they are not an admin (adding users to non-admin groups can cause this return)
			return;
		}

		$this->_done();
		$this->_galleryInit($user->data['user_id']);

		$users = $groups = $userData = $userToAdmin = $adminToUser = array();

		switch ($type)
		{
			case 'role' : // grab groups belonging to this role and all users belonging to these groups and the role
				$sql = 'SELECT group_id 
					FROM ' . ACL_GROUPS_TABLE . ' 
					WHERE auth_role_id = ' . $ids[0];
				$result = $db->sql_query($sql);

				while ($row = $db->sql_fetchrow($result))
				{
					$groups[] = $row['group_id'];
				}

				if (sizeof($groups) > 0)
				{
					$sql = 'SELECT user_id 
						FROM ' . USER_GROUP_TABLE . ' 
						WHERE ' . $db->sql_in_set('group_id', $groups);
					$result = $db->sql_query($sql);
				}

				while ($row = $db->sql_fetchrow($result))
				{
					$users[] = $row['user_id'];
				}

				$sql = 'SELECT user_id 
					FROM ' . ACL_USERS_TABLE . ' 
					WHERE auth_role_id = ' . $ids[0];
				$result = $db->sql_query($sql);

				while ($row = $db->sql_fetchrow($result))
				{
					$users[] = $row['user_id'];
				}

			break;

			case 'group' : // grab users belonging to these groups
				$sql = 'SELECT user_id 
					FROM ' . USER_GROUP_TABLE . ' 
					WHERE ' . $db->sql_in_set('group_id', $ids);;
				$result = $db->sql_query($sql);

				while ($row = $db->sql_fetchrow($result))
				{
					$users[] = $row['user_id'];
				}

			break;

			case 'user' : // grab users
				foreach ($ids as $userId)
				{
					$users[] = $userId;
				}

			break;
		}

		// determine permissions for each of these users
		if (sizeof($users) > 0)
		{
			$sql = 'SELECT user_id, user_type, user_permissions 
				FROM ' . USERS_TABLE . ' 
				WHERE ' . $db->sql_in_set('user_id', $users);
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$userData['user_id'] = $row['user_id'];
				$userData['user_type'] = $row['user_type'];
				$userData['user_permissions'] = $row['user_permissions'];

				$auth->acl($userData);

				if ($auth->acl_get('a_gallery2') > 0)
				{
					$userToAdmin[] = $row['user_id']; // user has Gallery admin permissions
				}
				else
				{
					$adminToUser[] = $row['user_id']; // user does not have Gallery admin permissions
				}
			}

			$db->sql_freeresult($result);
		}

		if (sizeof($userToAdmin) > 0)
		{
			$this->_mapUserToAdmin($userToAdmin); // move array of users to admin status
		}

		if (sizeof($adminToUser) > 0)
		{
			$this->_mapAdminToUser($adminToUser); // remove array of admins to user status
		}

		$this->_done();
	}

	/**
	* move users to admin status in Gallery
	*
	* @param array $ids array of id's user id's
	*
	* @access private
	*/
	function _mapUserToAdmin($ids)
	{
		global $db, $user;

		$adminGroupId = $this->_getPluginParameter('id.adminGroup');

		$sql = 'SELECT user_id, user_regdate, username, user_password, user_email, user_lang 
			FROM ' . USERS_TABLE . ' 
			WHERE ' . $db->sql_in_set('user_id', $ids);
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$ret = GalleryEmbed::isExternalIdMapped($row['user_id'], 'GalleryUser');
			if (empty($ret))
			{
				$this->_addUserToAdminGroup($row['username'], $adminGroupId);
			}
			elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT) // if user does not exist, create them since they are now admins
			{
				list ($g2Password, $g2PassType) = $this->_setGalleryHash($row['user_password']);

				$this->_createUser($row['user_id'], array(
					'username'			=> $row['username'],
					'hashedpassword'	=> $g2Password,
					'email'				=> $row['user_email'],
					'fullname'			=> $row['username'],
					'language'			=> $row['user_lang'],
					'creationtimestamp'	=> $row['user_regdate'],
					'hashmethod'		=> $g2PassType) 
				);

				$this->_addUserToAdminGroup($row['username'], $adminGroupId);
			}
			else
			{
				trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['user_id']), E_USER_ERROR);
			}
		}

		$db->sql_freeresult($result);
	}
	
	/**
	* move admins to user status in Gallery
	*
	* @param array $ids array of id's user id's
	*
	* @access private
	*/
	function _mapAdminToUser($ids)
	{
		global $db, $user;

		$adminGroupId = $this->_getPluginParameter('id.adminGroup');

		$sql = 'SELECT user_id, username 
			FROM ' . USERS_TABLE . ' 
			WHERE ' . $db->sql_in_set('user_id', $ids);
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$ret = GalleryEmbed::isExternalIdMapped($row['user_id'], 'GalleryUser');
			if (empty($ret))
			{
				list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($row['username']);
				if ($ret)
				{
					trigger_error(sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], $row['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
				}

				$ret = GalleryCoreApi::removeUserFromGroup($userId->getId(), $adminGroupId);
				if ($ret)
				{
					trigger_error(sprintf($user->lang['G2_REMOVEUSERFROMGROUP_FAILED'], $adminGroupId, $userId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
				}
			}
			elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
			{
				/*
				* user doesn't exist in gallery, so do nothing!
				*/
			}
		}

		$db->sql_freeresult($result);
	}

	/**
	* map users Gallery album id to user_gallery in phpbb_users table (album)
	*
	* @param object $entityId currrent Gallery user object
	* @param int $userId currrent user_id
	* @param int $userGallery current user gallery id
	*
	* @access private
	*/
	function _mapGalleryLink($entityId)
	{
		global $db, $user;

		$userItemIds = $this->_fetchItemsForUser($entityId);

		$allItemIds = $this->_fetchAllItemIds('GalleryAlbumItem');

		$itemIds = array_intersect($userItemIds, $allItemIds);

		if (isset($user->data['user_gallery'])) // check if current album id is valid
		{
			if (in_array($user->data['user_gallery'], $itemIds, true))
			{
				return;
			}
		}

		// search for valid album id for current user
		if (sizeof($itemIds) > 0)
		{
			$rootAlbumId = $this->_getPluginParameter('id.rootAlbum');

			$itemIds = array_values($itemIds);

			$itemId = ($itemIds[0] == $rootAlbumId && sizeof($itemIds) > 1) ? $itemIds[1] : $itemIds[0];

			$sql = 'UPDATE ' . USERS_TABLE . " 
				SET user_gallery = $itemId 
				WHERE user_id = " . $user->data['user_id'];
			$db->sql_query($sql);

			$user->data['user_gallery'] = $itemId;

			return;
		}

		$sql = 'UPDATE ' . USERS_TABLE . " 
			SET user_gallery = NULL 
			WHERE user_id = " . $user->data['user_id'];
		$db->sql_query($sql);

		$user->data['user_gallery'] = '';
	}

	/**
	* map all users Gallery item id's up to limit
	*
	* @param int $userId requested user_id
	* @param bool $albums true for albums, false for photos
	* @param int $limit search result limit
	* @param bool $get_count to return just a users item count
	* @param bool $random to randomize the items returned 
	*
	* @return array of int item count and array of Gallery item links for current user or int current Gallery userId
	*/
	function mapAllGalleryLinks($userId, $albums, $limit, $get_count = false, $random = false)
	{
		global $gallery, $user;

		$this->_galleryInit(false);

		$ret = GalleryEmbed::isExternalIdMapped($userId, 'GalleryUser');
		if (empty($ret))
		{
			$currentUser = null;
			$ret = GalleryEmbed::isExternalIdMapped($user->data['user_id'], 'GalleryUser');
			if (empty($ret))
			{
				$currentUser = $this->_loadEntityByExternalId($user->data['user_id']);

				$gallery->setActiveUser($currentUser);
			}

			$entityId = $this->_loadEntityByExternalId($userId);

			$itemIds = $this->_fetchItemsForUser($entityId);

			$entityType = ($albums) ? 'GalleryAlbumItem' : 'GalleryPhotoItem';
			$typeIds = $this->_fetchAllItemIds($entityType);

			$itemIds = array_intersect($itemIds, $typeIds);

			if (empty($itemIds))
			{
				/*
				* User has no items to display!
				*/
				$this->_done();

				return array(null, null);
			}

			$count = sizeof($itemIds);

			if (!empty($get_count))
			{
				$currentUserId = (is_object($currentUser)) ? $currentUser->getId() : null;

				$this->_done();

				return array($count, $currentUserId);
			}

			if (!empty($random))
			{
				$limit = ($limit > $count) ? $count : $limit;

				$itemKeys = array_rand($itemIds, $limit);

				$items = array();

				if (is_array($itemKeys))
				{
					foreach ($itemKeys as $value)
					{
						$items[] = $itemIds[$value];
					}
				}
				else
				{
					$items[0] = $itemIds[$itemKeys];
				}

				$itemIds = $items;
			}
			else
			{
				$itemIds = ($count > $limit) ? array_slice($itemIds, (-$limit - 1), $limit) : $itemIds;
			}

			list ($ret, $thumbNails) = GalleryCoreApi::fetchThumbnailsByItemIds($itemIds);
			if ($ret)
			{
				trigger_error(sprintf($user->lang['G2_FETCHTHUMBSBYIDS_FAILED'], $entityId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}

			$urlGenerator =& $gallery->getUrlGenerator();

			$itemLinks = array();

			foreach ($itemIds as $id)
			{
				if (!empty($thumbNails[$id]))
				{
					$itemLinks[$id] = $urlGenerator->generateUrl(array('view' => 'core.DownloadItem', 'itemId' => $thumbNails[$id]->getId()), array('fullUrl' => true, 'forceSessionId' => false));
				}
				else
				{
					$count--;
				}
			}

			$this->_done();

			return array($count, $itemLinks);
		}
		elseif ($ret && $ret->getErrorCode() & ERROR_MISSING_OBJECT)
		{
			/*
			* User does not exist in gallery, so do nothing!
			*/
		}
		else
		{
			trigger_error(sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $userId), E_USER_ERROR);
		}

		$this->_done();

		return array(null, null);
	}

	/**
	* add user to Gallery admin group
	*
	* @param string $username currrent username
	* @param int $adminGroupId Gallery admin group id
	*
	* @access private
	*/
	function _addUserToAdminGroup($username, $adminGroupId)
	{
		global $user;

		list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($username);
		if ($ret)
		{
			trigger_error(sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], $username) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $adminGroupId);
		if ($ret)
		{
			trigger_error(sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $adminGroupId, $userId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}
	}

	/**
	* utility method to fetch all Gallery items for given phpbb user_id
	*
	* @param int $entityId currrent user id
	*
	* @return array of item id's for current user
	*
	* @access private
	*/
	function _fetchItemsForUser($entityId)
	{
		list ($ret, $itemIds) = GalleryCoreApi::fetchAllItemIdsByOwnerId($entityId->getId());
		if ($ret)
		{
			global $user;

			trigger_error(sprintf($user->lang['G2_FETCHITEMSBYOWNER_FAILED'], $entityId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		return $itemIds;

	}

	/**
	* utility method to fetch all Gallery items
	*
	* @param string $type item type to fetch
	*
	* @return array of all item id's
	*
	* @access private
	*/
	function _fetchAllItemIds($type)
	{
		list ($ret, $itemIds) = GalleryCoreApi::fetchAllItemIds($type);
		if ($ret)
		{
			global $user;

			trigger_error($user->lang['G2_FETCHALLITEMIDS_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		return $itemIds;
	}

	/**
	* utility method to load Gallery entity or entities
	*
	* @param string/array $type single item to fetch or array of items to fetch
	* @param string $type item type to fetch
	*
	* @return array Gallery entity objects
	*
	* @access private
	*/
	function _loadEntitiesById($ids, $type = false)
	{
		list ($ret, $entityIds) = GalleryCoreApi::loadEntitiesById($ids, $type);
		if ($ret)
		{
			global $user;

			trigger_error(sprintf($user->lang['G2_LOADENTITIESBYID_FAILED'], implode(', ', $ids)) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		return $entityIds;
	}

	/**
	* utility method to load Gallery user entity for given phpbb user_id
	*
	* @return object Gallery user entity
	*
	* @access private
	*/
	function _loadEntityByExternalId($externalId)
	{
		list ($ret, $entityId) = GalleryCoreApi::loadEntityByExternalId($externalId, 'GalleryUser');
		if ($ret)
		{
			global $user;

			trigger_error(sprintf($user->lang['G2_LOADENTITYBYEXTID_FAILED'], $externalId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		return $entityId;
	}

	/**
	* utility method to get Gallery plugin parameter
	*
	* @param string $parameter parameter to fetch
	*
	* @return int $parameterId Gallery plugin parameter id
	*
	* @access private
	*/
	function _getPluginParameter($parameter)
	{
		list ($ret, $parameterId) = GalleryCoreApi::getPluginParameter('module', 'core', $parameter);
		if ($ret)
		{
			global $user;

			trigger_error(sprintf($user->lang['G2_FETCHPLUGINPARAMETER_FAILED'], $parameter) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
		}

		return $parameterId;
	}

	/**
	* fetch embedded Gallery image block
	*
	* this is not used by the default integration
	* it is included as a method for those who want to use the embedded imageblock capability
	* this method will enforce Gallery user view permissions
	* see -
	* http://codex.gallery2.org/Gallery2:GalleryEmbed:getImageBlock
	* http://codex.gallery2.org/Gallery2:Modules:imageblock
	* - for more info on how to use the imageblock
	*
	* @return string Gallery images in html format
	*/
	function fetchImageBlock()
	{
		global $gallery, $user;

		// initially default to guest login
		$this->_galleryInit(false);

		// check if imageblock plugin is installed and active
		list ($ret, $pluginStatus) = GalleryCoreApi::fetchPluginStatus('module');
		// ignore Gallery return status intentional

		if (isset($pluginStatus[1]['imageblock']['active']) && !empty($pluginStatus[1]['imageblock']['active']))
		{
			if ($user->data['user_type'] != USER_IGNORE)
			{
				// check if current user exists in Gallery
				$ret = GalleryEmbed::isExternalIdMapped($user->data['user_id'], 'GalleryUser');
				if (empty($ret))
				{
					$entityId = $this->_loadEntityByExternalId($user->data['user_id']);

					// switch to current user
					$gallery->setActiveUser($entityId);
				}
			}

			// fetch 2 random images from Gallery
			list ($ret, $g2_images) = GalleryEmbed::getImageBlock(array('blocks' => 'randomImage|randomImage', 'show' => 'none'));
			if ($ret)
			{
				trigger_error($user->lang['G2_GETIMAGEBLOCK_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), E_USER_ERROR);
			}
		}
		else
		{
				$g2_images = sprintf($user->lang['G2_PLUGINMODULE_NOTACTIVE'], $USER->LANG['G2_PLUGINMODULE_IMAGEBLOCK']);
		}
		
		$this->_done();

		return $g2_images;
	}
}

?>