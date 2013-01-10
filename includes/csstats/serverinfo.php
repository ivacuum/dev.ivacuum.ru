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
* Информация о сервере
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Servers
	WHERE
		serverId = ' . $csstats_db->check_value($url);
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);

if( !$row )
{
	trigger_error('SERVER_NOT_FOUND');
}

/**
* Определяем переменные
*/
$map         = getvar('map', '');
$server_name = htmlspecialchars($row['name']);
$time_played = $user->ctime - $row['map_started'];

if( $action == 'activate_map' || $action == 'deactivate_map' )
{
	if( $map && $auth->check('admin_lead') )
	{
		$sql = '
			UPDATE
				hlstats_Servers_Maps
			SET
				active = ' . ( ( $action == 'activate_map' ) ? 1 : 0 ) . '
			WHERE
				server_id = ' . $row['serverId'] . '
			AND
				map = ' . $csstats_db->check_value($map);
		$csstats_db->query($sql);
	}
}
elseif( $action == 'add_map' )
{
	/**
	* Добавление новой карты в maplist сервера
	*/
	if( $submit && $map && $auth->check('admin_lead') )
	{
		$sql_ary = array(
			'server_id' => $row['serverId'],
			'map'       => $map,
			'active'    => 0
		);

		$sql = 'INSERT INTO hlstats_Servers_Maps ' . $csstats_db->build_array('INSERT', $sql_ary);
		$csstats_db->query($sql);
	}
}

$template->vars(array(
	'ACT_PLAYERS'   => $row['act_players'],
	'ADDRESS'       => $row['address'],
	'BOMBS_DEFUSED' => num_format($row['bombs_defused']),
	'BOMBS_PLANTED' => num_format($row['bombs_planted']),
	'CT_WINS'       => num_format($row['ct_wins']),
	'HEADSHOTS'     => num_format($row['headshots']),
	'HPK'           => num_percent_of($row['headshots'], $row['kills']),
	'ID'            => $row['serverId'],
	'KICKS'         => num_format($row['kicks']),
	'KILLS'         => num_format($row['kills']),
	'MAP'           => $row['act_map'],
	'MAP_CHANGES'   => num_format($row['map_changes']),
	'MAX_PLAYERS'   => $row['max_players'],
	'NAME'          => htmlspecialchars($row['name']),
	'NEXTMAP'       => $row['nextmap'],
	'PLAYED'        => get_time_played($time_played),
	'PLAYERS'       => num_format($row['players']),
	'PORT'          => $row['port'],
	'ROUNDS'        => num_format($row['rounds']),
	'SLAPS'         => num_format($row['slaps']),
	'SLAYS'         => num_format($row['slays']),
	'SUICIDES'      => num_format($row['suicides']),
	'T_WINS'        => num_format($row['ts_wins']),

	'U_ACTION'  => ilink(sprintf('%s/serverinfo/%s.html', $page['furl'], $url)),
	'U_MAP'     => ilink(sprintf('%s/mapinfo/%s.html', $page['furl'], $row['act_map'])),
	'U_NEXTMAP' => ilink(sprintf('%s/mapinfo/%s.html', $page['furl'], $row['nextmap'])))
);

/**
* Список карт сервера
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Servers_Maps
	WHERE
		server_id = ' . $csstats_db->check_value($url) . '
	ORDER BY
		active DESC,
		map_last_time DESC';
$result = $csstats_db->query($sql);

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars('maplist', array(
		'ACTIVE'    => $row['active'],
		'NAME'      => $row['map'],
		'LAST_TIME' => $user->create_date($row['map_last_time']),
		
		'U_ACTIVATE'   => ilink(sprintf('%s/serverinfo/%s.html?action=activate_map&amp;map=%s', $page['furl'], $url, $row['map'])),
		'U_DEACTIVATE' => ilink(sprintf('%s/serverinfo/%s.html?action=deactivate_map&amp;map=%s', $page['furl'], $url, $row['map'])),
		'U_MAPINFO'    => ilink(sprintf('%s/mapinfo/%s.html', $page['furl'], $row['map'])))
	);
}

$csstats_db->freeresult($result);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/serverinfo/%s.html', $page['furl'], $url)), $server_name, 'server');

page_header($server_name);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
