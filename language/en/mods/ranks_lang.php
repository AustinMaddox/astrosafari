<?php
/** 
*
* @package language
* @version $Id: ranks_lang.php 0001 05:55 07/12/2008 cherokee red $
* @copyright (c) 2005 phpBB Group 
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
	'RANKS_TITLE' => 'Member Ranks',
	'RANKS_DESC' => 'Complete list of member ranks. What each rank signifies, the requirements to obtain each ranking, and the corresponding rank titles.',
	
	'NO_RANKS' => 'There are currently no ranks installed on this site.',
	'RANK_ID' => '#',
	'RANK_TITLE' => 'Rank Title',
	'RANK_MIN' => 'Minimum Posts',
	'RANK_IMAGE' => 'Rank Image',
	'NO_RANK_IMAGE' => 'No rank image',
	'RANK_SPECIAL' => 'Special Rank',
	
	'RANKS_DESC_BOTTOM' => 'Simple page that displays all the ranks of the site and their titles. Lists all the member ranks on one page - User Ranks at the top, Special ranks at the bottom along with the minimum post counts required to obtain the rank.',
));

?>