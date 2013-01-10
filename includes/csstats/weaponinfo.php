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
* Информация об оружии
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Weapons w
	WHERE
		game = ' . $csstats_db->check_value('cstrike') . '
	AND
		code = ' . $csstats_db->check_value($url);
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);

if( !$row )
{
	trigger_error('WEAPON_NOT_FOUND');
}

$weapon_name = htmlspecialchars($row['name']);

$template->vars(array(
	'CODE'      => $row['code'],
	'HEADSHOTS' => num_format($row['headshots']),
	'HPK'       => num_percent_of($row['headshots'], $row['kills']),
	'KILLS'     => num_format($row['kills']),
	'NAME'      => $row['name'])
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
		f.weapon = ' . $csstats_db->check_value($url) . '
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
		'HEADSHOTS' => num_format($row['headshots']),
		'HPK'       => num_percent_of($row['headshots'], $row['kills']),
		'KILLS'     => num_format($row['kills']),
		'NAME'      => htmlspecialchars(abridgement($row['lastName'], 20)),

		'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['killerId'])))
	);
}

$csstats_db->freeresult($result);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/weapons.html', $page['furl'])), 'Оружие', 'rocket_fly');
navigation_link_custom(ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $url)), $weapon_name);

page_header($weapon_name);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
