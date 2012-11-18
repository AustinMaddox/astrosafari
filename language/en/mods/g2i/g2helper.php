<?php
/**
* Gallery2 Integration
*
* g2helper [English]
*
* @package language
* @version 1.2.1
* @copyright (c) 2007 jettyrat
* @license	http://opensource.org/licenses/gpl-license.php GNU Public License
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
	// sql errors
	'FETCH_GROUPDATA_FAILED'	=> 'Could not obtain group data from ' . GROUPS_TABLE . '.',

	// settings errors
	'OBTAIN_SETTINGS_FAILED'	=> 'Could not obtain integration config settings. Make sure you have configured the integration.',

	// g2 errors
	'G2_ERROR'							=> ' Here is the error message from G2:<br />',
	'G2_REMOVEMAPENTRY_FAILED'			=> 'removeMapEntry failed.',
	'G2_TRANSACTION_FAILED'				=> 'Failed to complete transaction.',
	'G2_FETCHPLUGINPARAMETER_FAILED'	=> 'fetchPluginParameter failed for %s.',
	'G2_FETCHUSERBYUSERNAME_FAILED'		=> 'fetchUserByUserName failed for %s.',
	'G2_ISEXTERNALIDMAPPED_FAILED'		=> 'isExternalIdMapped failed for %s.',
	'G2_ADDEXTERNALMAPENTRY_FAILED'		=> 'addExternalMapEntry failed for %s.',
	'G2_ADDUSERTOGROUP_FAILED'			=> 'addUserToGroup %1$s failed for %2$s.',
	'G2_INITUSER_FAILED'				=> 'init failed for %s.',
	'G2_CREATEUSER_FAILED'				=> 'createUser failed for %s.',
	'G2_UPDATEUSER_FAILED'				=> 'updateUser failed for %s.',
	'G2_DELETEUSER_FAILED'				=> 'deleteUser failed for %s.',
	'G2_REMOVEUSERFROMGROUP_FAILED'		=> 'removeUserFromGroup %1$s failed for %2$s.',
	'G2_CREATEGROUP_FAILED'				=> 'createGroup failed for %s.',
	'G2_UPDATEGROUP_FAILED'				=> 'updateGroup failed for %s.',
	'G2_UPDATEMAPENTRY_FAILED'			=> 'updateMapEntry failed for %s.',
	'G2_DELETEGROUP_FAILED'				=> 'deleteGroup failed for %s.',
	'G2_LOADENTITYBYEXTID_FAILED'		=> 'loadEntityByExternalId failed for %s.',
	'G2_LOADENTITIESBYID_FAILED'		=> 'loadEntitiesById failed for %s.',
	'G2_FETCHPERMSFORITEMS_FAILED'		=> 'fetchPermissionsForItems failed for %s.',
	'G2_FETCHITEMSBYOWNER_FAILED'		=> 'fetchAllItemIdsByOwnerId for %s.',
	'G2_FETCHALLITEMIDS_FAILED'			=> 'fetchAllItemIds failed.',
	'G2_GETIMAGEBLOCK_FAILED'			=> 'getImageBlock failed.',
	'G2_PLUGINMODULE_NOTACTIVE'			=> 'The %s plugin is either not installed or not activated.',
	'G2_PLUGINMODULE_IMAGEBLOCK'		=> 'imageblock',
));

?>
