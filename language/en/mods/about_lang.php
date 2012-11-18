<?php
/**
*
* @author austin881 support@whoa.co - http://whoa.co
* @package language
* @version $Id$
* @copyright (c) 2012 Whoa.Co
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
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
	'ABOUT'			=> 'About',
	'ABOUT_US'		=> 'About us',
    'ABOUT_DESC'	=> 'The AstroSafari.com online community is a discussion forum based on the popular Chevrolet Astro and GMC Safari vans. AstroSafari.com is a one stop resource for all Astro/Safari van related discussions, with forum topics & photos including maintenance, repairs, performance, and care.',
    'ABOUT_01'		=> 'The Astro and Safari both have popular followings due, in part, to their mid-size construction, rear wheel drive, and truck-based design. Some vans have the original 4.3L Vortec V6 engine which can easily be swapped with a small-block V8 engine, such as the Chevrolet 350. This swap is simplified because the 4.3L V6 is based on the GM small-block V8, and most of the factory drivetrain components can be reused.',
    'ABOUT_02'		=> 'Both street and off-road modifications have become popular through the life of the Astro/Safari vans. The combination of a powerful drivetrain, large cargo and passenger space, all-wheel (or rear wheel) drive, and stock locking differential for the rear axle, facilitate off road modifications. The ease and availability of aftermarket parts such as ground effects, air suspension, leaf spring flip-kits, lowering spindles, c-notches, and large wheels make these vans a favorite among the lowered suspension crowd.',
    'ABOUT_03'		=> 'AstroSafari.com has been on the web since 2002 and some say even earlier in some form or another. Known now as the "The original home for Astro & Safari vans" AstroSafari.com has a solid membership of helpful enthusiasts. Membership is free and other supportive members are available to help you get the most out of your van experience.',
));

?>