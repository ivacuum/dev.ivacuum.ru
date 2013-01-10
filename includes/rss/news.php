<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2010
*/

/**
* Получаем данные из БД
*/
$sql = '
	SELECT
		n.*,
		u.username
	FROM
		' . NEWS_TABLE . ' n,
		' . USERS_TABLE . ' u
	WHERE
		n.news_author_id = u.user_id
	AND
		n.news_language = ' . $db->check_value($user->lang['.']) . '
	ORDER BY
		n.news_time DESC
	LIMIT
		0, 30';
$result = $db->query($sql);

while( $row = $db->fetchrow($result) )
{
	$template->cycle_vars('news', array(
		'AUTHOR' => $row['username'],
		'DATE'   => date('D, j M Y H:i:s', $row['news_time']) . ' +0' . $config['site_tz'] . '00',
		'LINK'   => ilink(sprintf('%s/%d-%s.html', $user->lang['URL_NEWS'], $row['news_id'], $row['news_url']), 'http://ivacuum.ru'),
		'TEXT'   => htmlspecialchars($row['news_text']),
		'TITLE'  => htmlspecialchars($row['news_subject']))
	);
}

$db->freeresult($result);

$template->vars(array(
	'S_LANGUAGE' => $user->lang['.'],

	'U_RSS'      => ilink($user->lang['URL_NEWS'], 'http://ivacuum.ru'))
);

$template->go('feeds/news.xml');

?>