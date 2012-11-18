<?php
/**
*
* @package phpBB3
* @version $Id: groups_lang.php,v 1.0.1 2009-08-16 09:28:00 EST rmcgirr83 $
* @copyright (c) Rich McGirr
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/
					
/**
* DO NOT CHANGE
*/
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
						
// MODs Catalog
$lang = array_merge($lang, array(
	'GROUPS_TITLE' => 'Usergroups',
	'GROUPS_DESC' => 'Usergroups define how you appear to others and enable site admins to better administer users. Some groups may give you additional permissions to view content or increase your capabilities in other areas.',
	'GROUPS_FAQ_TITLE' => 'UserGroup FAQs',
	'GROUPS_FAQ' => '
<dl>
	<dt>Q: Where can I find the usergroups and how do I join one?</dt>
		<dd>A: A summary of all UserGroups follows these FAQs. You can also view all UserGroups via the UserGroups tab at the top of your User Control Panel. If you would like to join one, proceed by clicking the appropriate Select button, then press Submit at the bottom of the list.</dd>
	<dt>Q: Can I join any group I want?</dt>
		<dd>A: Not all groups have open access. Some may require approval to join, some may be closed and some may even have hidden memberships. If the group does not require approval, you can join it by clicking the appropriate button. If a group requires approval to join you may request to join by clicking the appropriate button. The user group leader will need to approve your request and may ask why you want to join the group. Please respect the group leader’s decision if they deny your request.</dd>
	<dt>Q: What constitutes a UserGroup?</dt>
		<dd>A: Any number of members considered as a unit can be called a group. Members may be grouped together in the forum system for administrative purposes.</dd>
	<dt>Q: Can I propose a new UserGroup, and if so, how do I do that?</dt>
		<dd>A: Yes. Go to the <a href="viewforum.php?f=5">Website Concerns Forum</a> and add a new Topic: Proposed new UserGroup – [name]. Explain what the purpose of the group should be and who would benefit from membership in that group, and what those benefits are.</dd>
</dl>
',

	'GROUP_NAME' => 'Name',
	'GROUP_RANK' => 'Rank',
	'GROUP_AVATAR' => 'Avatar',
	'GROUP_DESC' => 'Description',
	'GROUP_MEMBERS' => 'Members',
	
	'NO_LEADERS' => 'This group does not have a leader assigned.',
	'NO_MEMBERS' => 'There are currently no members in this group.', 
	
	'GROUPS_DESC_SIDE'	=> 'Usergroups are groups of users that provide a specific membership identity within the community allowing board administrators to work with them as a group. Each user can belong to several groups and each group can be assigned individual permissions. This enables administrators to change permissions for many users at once, such as changing moderator permissions or granting users access to a private forum.',
	'GROUPS_KEY_SIDE'	=> 'How do I join a group?, group requests, join groups, usergroups, moderator group, servicemen, automotive technician group, admins',

	'GROUPS_DESC_BOTTOM' => 'Usergroups are groups of users that divide the community into manageable sections board administrators can work with. Each user can belong to several groups and each group can be assigned individual permissions. This provides an easy way for administrators to change permissions for many users at once, such as changing moderator permissions or granting users access to a private forum.<br />
	Where are the usergroups and how do I join one? You can view all usergroups via the Usergroups link within your User Control Panel. If you would like to join one, proceed by clicking the appropriate button. Not all groups have open access, however. Some may require approval to join, some may be closed and some may even have hidden memberships. If the group is open, you can join it by clicking the appropriate button. If a group requires approval to join you may request to join by clicking the appropriate button. The user group leader will need to approve your request and may ask why you want to join the group. Please do not harass a group leader if they reject your request; they will have their reasons.<br />
	Any number of members considered as a unit can be called a group. A set of users grouped together in the forum system for administrative purposes. A cohesive group of individuals that may be interested in receiving information about an entity\'s specific defining attributes and site privileges. A group is a set that is closed, associative, has an identity element and every element has an inverse.
	',
	
));

?>
