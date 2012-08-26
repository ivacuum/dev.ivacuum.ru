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

$ranks    = array();
$uniqueid = $user->spark_subnet();

$sql = '
	SELECT
		kills
	FROM
		hlstats_Players
	WHERE
		lastAddress = ' . $csstats_db->check_value($uniqueid);
$result = $csstats_db->query($sql);
$player = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);

$kills = ( $player ) ? $player['kills'] : 0;

/**
* Количество игроков, получивших каждое звание
*/
$sql = '
	SELECT
		rankId,
		COUNT(playerId) AS achieved
	FROM
		hlstats_Ranks
	INNER JOIN
		hlstats_Players
	ON
		( hlstats_Ranks.game = hlstats_Players.game )
	WHERE
		kills >= minKills
	AND
		kills <= maxKills
	AND
		hlstats_Ranks.game = ' . $csstats_db->check_value('cstrike') . '
	GROUP BY
		rankId';
$result = $csstats_db->query($sql);

while( $row = $csstats_db->fetchrow($result) )
{
	$ranks[$row['rankId']] = $row['achieved'];
}

$csstats_db->freeresult($result);

/**
* Список званий
*/
$sql = '
	SELECT
		rankId,
		image,
		minKills,
		maxKills,
		rankName
	FROM
		hlstats_Ranks
	WHERE
		game = ' . $csstats_db->check_value('cstrike') . '
	ORDER BY
		minKills ASC';
$result = $csstats_db->query($sql);

while( $row = $csstats_db->fetchrow($result) )
{
	/**
	* Показываем только те звания, которые игроки уже получили
	*/
	if( !isset($ranks[$row['rankId']]) )
	{
		continue;
	}

	$template->cycle_vars('ranks', array(
		'ACHIEVED'  => $ranks[$row['rankId']],
		'CURRENT'   => ( $kills >= $row['minKills'] && $kills <= $row['maxKills'] ) ? 1 : 0,
		'IMAGE'     => $row['image'],
		'MAX_KILLS' => $row['maxKills'],
		'MIN_KILLS' => $row['minKills'],
		'TITLE'     => $row['rankName'],

		'U_RANKINFO' => ilink(sprintf('%s/rankinfo/%s.html', $page['furl'], $row['image'])))
	);
}

$csstats_db->freeresult($result);
$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Звания', 'medal');

page_header();

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
