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
*/

// this is the main embedded Gallery access handler

define('IN_PHPBB', true);
$phpbb_root_path = './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include ($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

$validG2User = (!empty($user->data['user_gallery']) || $auth->acl_get('a_gallery2') > 0) ? true : false;

require($phpbb_root_path . 'g2helper.' . $phpEx);
$g2h = new g2helper();
$g2h->init($validG2User);

// fetch Gallery output
GalleryCapabilities::set('showSidebarBlocks', true);
$g2data = GalleryEmbed::handleRequest(); 
if ($g2data['isDone']) // request is for a Gallery item
{
	exit;
}
elseif (isset($g2data['headHtml'])) // request is for a Gallery page
{
	$version = GalleryEmbed::getApiVersion();
	if ($version[0] == 1 && $version[1] < 5)
	{
		list($g2data['title'], $g2data['css'], $g2data['js']) = GalleryEmbed::parseHead($g2data['headHtml']);
	}
	else
	{
		list($g2data['title'], $g2data['css'], $g2data['js'], $g2data['meta']) = GalleryEmbed::parseHead($g2data['headHtml']);
	}
}

$template->assign_vars(array(
	'S_GALLERY2'		  => true,
	'GALLERY2_BODY'		  => ($g2data['bodyHtml']) ? $g2data['bodyHtml'] : '',
	'GALLERY2_CSS'		  => ($g2data['css']) ? implode("\n", $g2data['css']) . "\n" : '',
	'GALLERY2_JAVASCRIPT' => ($g2data['js']) ? implode("\n", $g2data['js']) . "\n" : '',
	'GALLERY2_META'		  => (isset($g2data['meta'])) ? implode("\n", $g2data['meta']) . "\n" : '')
);

// Output page
page_header($g2data['title']);

$template->set_filenames(array(
	'body' => 'gallery2.html')
);

page_footer();

?>