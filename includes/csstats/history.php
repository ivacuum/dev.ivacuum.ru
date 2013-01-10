<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*/

if( !defined('IN_SITE') )
{
	exit;
}

$date = getvar('date', '');

$sql = '
	SELECT DISTINCT
		eventTime
	FROM
		hlstats_Players_History
	ORDER BY
		eventTime DESC
	LIMIT
		0, 30';
$result = $csstats_db->query($sql);
$i = 1;

while( $row = $csstats_db->fetchrow($result) )
{
	if( !$date && $i == 1 )
	{
		$date = $row['eventTime'];
	}

	$template->cycle_vars('dates', array(
		'TEXT' => $row['eventTime'])
	);
}

$csstats_db->freeresult($result);

/**
* Проверка даты
*/
if( strlen($date) != 10 )
{
	trigger_error('PAGE_NOT_FOUND');
}
elseif( !preg_match('#\d{4}-\d{2}-\d{2}#', $date) )
{
	trigger_error('PAGE_NOT_FOUND');
}

$sql = '
	SELECT
		COUNT(h.playerId) as total_players
	FROM
		hlstats_Players_History h,
		hlstats_Players p
	WHERE
		h.playerId = p.playerId
	AND
		h.eventTime = ' . $csstats_db->check_value($date) . '
	AND
		p.hideranking = 0';
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);
$total_players = $row['total_players'];

$pagination = pagination(50, $total_players, ilink(sprintf('%s/%s.html?date=%s', $page['furl'], $mode, $date)));

$sort_key_sql = array(
	'a'	=> 'h.skill_change',
	'b' => 'h.connection_time',
	'c' => 'h.kills',
	'd' => 'h.deaths',
	'e' => 'h.headshots',
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
		h.*,
		IFNULL(h.hits / h.shots, 0) AS accuracy,
		IFNULL(h.headshots / h.kills, 0) AS hpk,
		p.lastName,
		p.lastAddress
	FROM
		hlstats_Players_History h,
		hlstats_Players p
	WHERE
		h.playerId = p.playerId
	AND
		h.eventTime = ' . $csstats_db->check_value($date) . '
	AND
		p.hideranking = 0
	ORDER BY
		' . $order_by . '
	LIMIT
		' . $pagination['start'] . ', ' . $pagination['on_page'];
$result = $csstats_db->query($sql);

$i = $pagination['start'] + 1;

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars('players', array(
		'ACCURACY'     => ( $row['accuracy'] ) ? sprintf('%d%%', $row['accuracy'] * 100) : '-',
		'DEATHS'       => num_format($row['deaths']),
		'HEADSHOTS'    => num_format($row['headshots']),
		'HPK'          => ( $row['hpk'] ) ? sprintf('%d%%', $row['hpk'] * 100) : '-',
		'IP'           => $row['lastAddress'],
		'KILLS'        => num_format($row['kills']),
		'N'            => $i,
		'NAME'         => htmlspecialchars(abridgement($row['lastName'], 20)),
		'SKILL'        => num_format($row['skill']),
		'SKILL_CHANGE' => ( $row['skill_change'] != 0 ) ? sprintf('%+d', $row['skill_change']) : 0,
		'SPARK'        => is_spark($row['lastAddress']),
		'TIME'         => ( $row['connection_time'] > 86400 ) ? sprintf('%d&nbsp;дн.&nbsp;%02d:%02d:%02d', $row['connection_time'] / 86400, $row['connection_time'] / 3600 % 24, $row['connection_time'] / 60 % 60, $row['connection_time'] % 60) : sprintf('%02d:%02d:%02d', $row['connection_time'] / 3600 % 24, $row['connection_time'] / 60 % 60, $row['connection_time'] % 60),

		'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
	);

	$i++;
}

$csstats_db->freeresult($result);

$template->vars(array(
	'DATE'     => $date,
	'SORT_DIR' => $sort_dir,
	'SORT_KEY' => $sort_key,

	'U_ACTION'         => ilink(sprintf('%s/%s.html', $page['furl'], $mode)),
	'U_SORT_ACCURACY'  => create_sort_link('g', 'date=' . $date),
	'U_SORT_DEATHS'    => create_sort_link('d', 'date=' . $date),
	'U_SORT_HEADSHOTS' => create_sort_link('e', 'date=' . $date),
	'U_SORT_HPK'       => create_sort_link('f', 'date=' . $date),
	'U_SORT_KILLS'     => create_sort_link('c', 'date=' . $date),
	'U_SORT_SKILL'     => create_sort_link('a', 'date=' . $date),
	'U_SORT_TIME'      => create_sort_link('b', 'date=' . $date))
);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'История', 'clock_history');

page_header('История');

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>