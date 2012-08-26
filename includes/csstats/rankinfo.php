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

/**
* Информация о звании
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Ranks
	WHERE
		game = ' . $csstats_db->check_value('cstrike') . '
	AND
		image = ' . $csstats_db->check_value($url);
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);

if( !$row )
{
	trigger_error('RANK_NOT_FOUND');
}

$rank_image    = $row['image'];
$rank_maxkills = $row['maxKills'];
$rank_minkills = $row['minKills'];
$rank_time     = 0;
$rank_title    = $row['rankName'];

/**
* Количество игроков, получивших звание
*/
$sql = '
	SELECT
		COUNT(*) as total_players
	FROM
		hlstats_Players
	WHERE
		kills >= ' . $csstats_db->check_value($rank_minkills) . '
	AND
		kills <= ' . $csstats_db->check_value($rank_maxkills);
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);
$total_players = $row['total_players'];

$pagination = pagination(30, $total_players, ilink(sprintf('%s/rankinfo/%s.html', $page['furl'], $rank_image)));

$sql = '
	SELECT
		playerId,
		connection_time,
		lastName,
		kills
	FROM
		hlstats_Players
	WHERE
		kills >= ' . $csstats_db->check_value($rank_minkills) . '
	AND
		kills <= ' . $csstats_db->check_value($rank_maxkills) . '
	ORDER BY
		kills DESC
	LIMIT
		' . $pagination['start'] . ', ' . $pagination['on_page'];
$result = $csstats_db->query($sql);

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars('players', array(
		'KILLS'    => num_format($row['kills']),
		'NAME'     => htmlspecialchars(abridgement($row['lastName'], 20)),
		'PROGRESS' => ( $rank_minkills > $row['kills'] ) ? '0%' : sprintf('%d%%', (($row['kills'] - $rank_minkills) / ($rank_maxkills - $rank_minkills + 1)) * 100),

		'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
	);

	$rank_time += $row['connection_time'];
}

$csstats_db->freeresult($result);

$template->vars(array(
	'IMAGE'         => $rank_image,
	'MAX_KILLS'     => num_format($rank_maxkills),
	'MIN_KILLS'     => num_format($rank_minkills),
	'RANK_TIME'     => create_time(intval($rank_time / $total_players), true),
	'TITLE'         => $rank_title,
	'TOTAL_PLAYERS' => $total_players)
);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/ranks.html', $page['furl'])), 'Звания', 'medal');
navigation_link_custom(ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $rank_image)), $rank_title);

page_header($rank_title);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
