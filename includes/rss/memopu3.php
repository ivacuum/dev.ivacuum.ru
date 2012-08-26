<?php
/**
*
* @package vacuum.kaluga.spark
* @copyright (c) 2010
*
*/

/**
* Получаем данные из БД
*/
$sql = '
	SELECT
		q.*,
		u.username
	FROM
		' . QUOTES_TABLE . ' q,
		' . USERS_TABLE . ' u
	WHERE
		q.quote_approver_id = u.user_id
	AND
		q.quote_approver_time > 0
	ORDER BY
		q.quote_sender_time DESC
	LIMIT 0, 100';
$result = $db->query($sql);

while( $row = $db->fetchrow($result) )
{
	$template->cycle_vars('quotes', array(
		'AUTHOR' => $row['username'],
		'DATE'   => date('D, j M Y H:i:s', $row['quote_approver_time']) . ' +0' . $config['site_tz'] . '00',
		'LINK'   => ilink('memopu3/цитата/' . $row['quote_id'] . '.html', 'http://ivacuum.ru'),
		'TEXT'   => htmlspecialchars(nl2br($row['quote_text'])),
		'TITLE'  => htmlspecialchars('Цитата #' . $row['quote_id']))
	);
}

$db->freeresult($result);

$template->vars(array(
	'U_RSS' => ilink('memopu3', 'http://ivacuum.ru'))
);

$template->go('feeds/memopu3.xml');

?>