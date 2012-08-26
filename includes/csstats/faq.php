<?php
/**
*
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*
*/

if( !defined('IN_SITE') )
{
	exit;
}

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'FAQ', 'information_balloon');

page_header('FAQ');

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>