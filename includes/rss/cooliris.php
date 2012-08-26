<?php
/**
*
* @package vacuum.kaluga.spark
* @copyright (c) 2010
*
*/

$sql = '
	SELECT
		COUNT(*) AS total_images
	FROM
		' . IMAGES_TABLE;
$result = $db->query($sql);
$row = $db->fetchrow($result);
$db->freeresult($result);

$total_images = $row['total_images'];

$pagination = pagination(24, $total_images, ilink($page['page_url']));

/**
* Получаем данные из БД
*/
$sql = '
	SELECT
		*
	FROM
		' . IMAGES_TABLE . '
	ORDER BY
		image_time DESC
	LIMIT
		' . $pagination['start'] . ', ' . $pagination['on_page'];
$result = $db->query($sql);

while( $row = $db->fetchrow($result) )
{
	$template->cycle_vars('images', array(
		'FULL'  => sprintf('http:%s/%s/%s', $config['gallery_path'], $row['image_date'], $row['image_url']),
		'LINK'  => ilink(sprintf('%s/%s/%d.html', $user->lang['URL_GALLERY'], $user->lang['URL_VIEW'], $row['image_id']), 'http://ivacuum.ru'),
		'NAME'  => $row['image_url'],
		'THUMB' => sprintf('http:%s/%s/t/%s', $config['gallery_path'], $row['image_date'], $row['image_url']))
	);
}

$db->freeresult($result);

$template->vars(array(
	'NEXT_PAGE' => ( $pagination['p'] + 1 <= $pagination['pages'] ) ? $pagination['p'] + 1 : 0,
	'PREV_PAGE' => ( $pagination['p'] - 1 >= 0 ) ? $pagination['p'] - 1 : 0,

	'U_RSS' => ilink($user->lang['URL_GALLERY'], 'http://ivacuum.ru'))
);

$template->go('feeds/cooliris.xml');

?>