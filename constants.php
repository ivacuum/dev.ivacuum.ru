<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

if (false === $app->load_constants($app['cache.driver.options']['prefix']))
{
	$app->set_constants($app['cache.driver.options']['prefix'], [
		/* Таблицы сайта */
		'DOWNLOADS_TABLE'    => 'site_downloads',
		'FILES_TABLE'        => 'site_files',
		'IMAGES_TABLE'       => 'site_images',
		'IMAGE_ALBUMS_TABLE' => 'site_image_albums',
		'IMAGE_REFS_TABLE'   => 'site_image_refs',
		'IMAGE_VIEWS_TABLE'  => 'site_image_views',
		'QUOTES_TABLE'       => 'site_quotes',
		'QUOTES_VOTES_TABLE' => 'site_quotes_votes',
		'RANKS_TABLE'        => 'site_ranks',
		'SMILIES_TABLE'      => 'site_smilies',
	]);
}
