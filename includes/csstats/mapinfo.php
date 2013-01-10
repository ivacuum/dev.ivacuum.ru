<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*/

if( !defined('IN_SITE') )
{
	exit;
}

/**
* Информация о карте
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Maps
	WHERE
		map_game = ' . $csstats_db->check_value('cstrike') . '
	AND
		map_name = ' . $csstats_db->check_value($url);
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);

if( !$row )
{
	trigger_error('MAP_NOT_FOUND');
}

/**
* Определяем переменные
*/
$bombs    = $row['map_bombs_planted'] + $row['map_bombs_defused'];
$map_name = htmlspecialchars($row['map_name']);
$rounds   = $row['map_ct_wins'] + $row['map_ts_wins'];

$template->vars(array(
	'BOMBS_DEFUSED_PERCENT'  => ( $bombs > 0 ) ? sprintf('%d', ($row['map_bombs_defused'] / $bombs) * 100) : 0,
	'BOMBS_EXPLOSED_PERCENT' => ( $bombs > 0 ) ? sprintf('%d', ($row['map_bombs_planted'] / $bombs) * 100) : 0,
	'CT_WINS_PERCENT'        => ( $rounds > 0 ) ? sprintf('%d', ($row['map_ct_wins'] / $rounds) * 100) : 0,
	'T_WINS_PERCENT'         => ( $rounds > 0 ) ? sprintf('%d', ($row['map_ts_wins'] / $rounds) * 100) : 0,

	'BOMBS'            => $bombs,
	'BOMBS_DEFUSED'    => $row['map_bombs_defused'],
	'BOMBS_EXPLOSED'   => $row['map_bombs_planted'],
	'CT_WINS'          => $row['map_ct_wins'],
	'HEADSHOTS'        => num_format($row['map_headshots']),
	'HOSTAGES_KILLED'  => $row['map_hostages_killed'],
	'HOSTAGES_RESCUED' => $row['map_hostages_rescued'],
	'HOSTAGES_TOUCHED' => $row['map_hostages_touched'],
	'HPK'              => num_percent_of($row['map_headshots'], $row['map_kills']),
	'KILLS'            => num_format($row['map_kills']),
	'MAP'              => $row['map_name'],
	'PREFIX'           => strtolower(substr($row['map_name'], 0, 2)),
	'T_WINS'           => $row['map_ts_wins'])
);

$sql = '
	SELECT
		f.killerId,
		p.lastName,
		COUNT(f.map) as kills,
		SUM(f.headshot = 1) as headshots
	FROM
		hlstats_Events_Frags f,
		hlstats_Players p
	WHERE
		p.playerId = f.killerId
	AND
		f.map = ' . $csstats_db->check_value($url) . '
	AND
		p.game = ' . $csstats_db->check_value('cstrike') . '
	AND
		p.hideranking = 0
	GROUP BY
		f.killerId
	ORDER BY
		kills DESC
	LIMIT
		0, 50';
$result = $csstats_db->query($sql);

while( $row = $db->fetchrow($result) )
{
	$template->cycle_vars('players', array(
		'HEADSHOTS' => $row['headshots'],
		'HPK'       => num_percent_of($row['headshots'], $row['kills']),
		'KILLS'     => $row['kills'],
		'NAME'      => htmlspecialchars(abridgement($row['lastName'], 20)),

		'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['killerId'])))
	);
}

$csstats_db->freeresult($result);

/*
$sql = '
	SELECT
		COUNT(DISTINCT f.killerId) as players
	FROM
		hlstats_Events_Frags f,
		hlstats_Servers s
	WHERE
		s.serverId = f.serverId
	AND
		f.map = ' . $csstats_db->check_value($url) . '
	AND
		s.game = ' . $csstats_db->check_value('cstrike');
$result = $csstats_db->query($sql);
$total_players = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);
*/

$sql = '
	SELECT
		m.server_id,
		m.map_last_time,
		m.active,
		s.name,
		s.active AS server_active
	FROM
		hlstats_Servers s,
		hlstats_Servers_Maps m
	WHERE
		s.serverId = m.server_id
	AND
		m.map = ' . $csstats_db->check_value($url) . '
	ORDER BY
		m.server_id';
$result = $csstats_db->query($sql);

while( $row = $csstats_db->fetchrow($result) )
{
	/**
	* Исключаем карты, которые никогда не были запущены на серверах
	*/
	if( !$row['active'] || !$row['server_active'] )
	{
		continue;
	}

	$template->cycle_vars('servers', array(
		'NAME' => $row['name'],
		'TIME' => $user->create_date($row['map_last_time']),

		'U_SERVERINFO' => ilink(sprintf('%s/serverinfo/%d.html', $page['furl'], $row['server_id'])))
	);
}

$csstats_db->freeresult($result);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/maps.html', $page['furl'])), 'Карты', 'maps_stack');
navigation_link_custom(ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $map_name)), $map_name, 'map');

page_header($map_name);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
