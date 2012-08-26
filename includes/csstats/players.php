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

$sql = '
	SELECT
		COUNT(*) as total_players
	FROM
		hlstats_Players
	WHERE
		game = ' . $csstats_db->check_value('cstrike') . '
	AND
		hideranking = 0
	AND
		kills > 100';
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);
$total_players = $row['total_players'];

$pagination = pagination(50, $total_players, ilink(sprintf('%s/%s.html', $page['furl'], $mode)));

$sort_key_sql = array(
	'a'	=> 'skill',
	'b' => 'connection_time',
	'c' => 'kills',
	'd' => 'deaths',
	'e' => 'headshots',
	'f' => 'hpk',
	'g' => 'accuracy'
);

/**
* Проверка значений $sort_key
*/
if( strlen($sort_key) != 1 )
{
	trigger_error('PAGE_NOT_FOUND');
}
elseif( !preg_match('#[abcdefg]#', $sort_key) )
{
	trigger_error('PAGE_NOT_FOUND');
}

/* Порядок вывода */
$order_by = $sort_key_sql[$sort_key] . ' ' . ( ( $sort_dir == 'a' ) ? 'ASC' : 'DESC');

/**
* Список игроков
*/
$sql = '
	SELECT
		*,
		IFNULL(hits / shots, 0) AS accuracy,
		IFNULL(headshots / kills, 0) AS hpk
	FROM
		hlstats_Players
	WHERE
		game = ' . $csstats_db->check_value('cstrike') . '
	AND
		hideranking = 0
	AND
		kills > 100
	ORDER BY
		' . $order_by . '
	LIMIT
		' . $pagination['start'] . ', ' . $pagination['on_page'];
$result = $csstats_db->query($sql);

$i = $pagination['start'] + 1;

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars($mode, array(
		'ACCURACY'   => ( $row['accuracy'] ) ? sprintf('%d%%', $row['accuracy'] * 100) : '-',
		'DEATHS'     => num_format($row['deaths']),
		'HEADSHOTS'  => num_format($row['headshots']),
		'HPK'        => ( $row['hpk'] ) ? sprintf('%d%%', $row['hpk'] * 100) : '-',
		'IP'         => $row['lastAddress'],
		'KILLS'      => num_format($row['kills']),
		'LAST_SKILL' => ( $row['last_skill_change'] != 0 ) ? sprintf('%+d', $row['last_skill_change']) : 0,
		'N'          => $i,
		'NAME'       => htmlspecialchars(abridgement($row['lastName'], 20)),
		'SKILL'      => num_format($row['skill']),
		'SPARK'      => is_spark($row['lastAddress']),
		'TIME'       => ( $row['connection_time'] > 86400 ) ? sprintf('%d&nbsp;дн.&nbsp;%02d:%02d:%02d', $row['connection_time'] / 86400, $row['connection_time'] / 3600 % 24, $row['connection_time'] / 60 % 60, $row['connection_time'] % 60) : sprintf('%02d:%02d:%02d', $row['connection_time'] / 3600 % 24, $row['connection_time'] / 60 % 60, $row['connection_time'] % 60),

		'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
	);

	$i++;
}

$csstats_db->freeresult($result);

$template->vars(array(
	'SORT_DIR'         => $sort_dir,
	'SORT_KEY'         => $sort_key,

	'U_SEARCH_SELF'    => ilink(sprintf('%s/search.html?search=self', $page['furl'])),
	'U_SORT_ACCURACY'  => create_sort_link('g'),
	'U_SORT_DEATHS'    => create_sort_link('d'),
	'U_SORT_HEADSHOTS' => create_sort_link('e'),
	'U_SORT_HPK'       => create_sort_link('f'),
	'U_SORT_KILLS'     => create_sort_link('c'),
	'U_SORT_SKILL'     => create_sort_link('a'),
	'U_SORT_TIME'      => create_sort_link('b'))
);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Игроки', 'user_black');

page_header('Игроки');

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>