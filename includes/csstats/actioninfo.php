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
* Информация о действии
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Actions
	WHERE
		game = ' . $csstats_db->check_value('cstrike') . '
	AND
		code = ' . $csstats_db->check_value($url);
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);

if( !$row )
{
	trigger_error('ACTION_NOT_FOUND');
}

$action_name = htmlspecialchars($row['description']);

$template->vars(array(
	'ACTION'        => $row['description'],
	'COUNT'         => num_format($row['count']),
	'REWARD_PLAYER' => ( $row['reward_player'] != 0 ) ? sprintf('%+d', $row['reward_player']) : '-',
	'REWARD_TEAM'   => ( $row['reward_team'] != 0 ) ? sprintf('%+d', $row['reward_team']) : '-',
	'TEAM'          => $row['team'])
);

if( $row['reward_player'] != 0 )
{
	$sql = '
		SELECT
			pa.playerId,
			p.lastName,
			COUNT(pa.playerId) as count
		FROM
			hlstats_Actions a,
			hlstats_Events_PlayerActions pa,
			hlstats_Players p
		WHERE
			pa.actionId = ' . $row['id'] . '
		AND
			p.playerId = pa.playerId
		AND
			pa.actionId = a.id
		GROUP BY
			pa.playerId
		ORDER BY
			count DESC
		LIMIT
			0, 50';
}
else
{
	$sql = '
		SELECT
			tb.playerId,
			p.lastName,
			COUNT(tb.playerId) as count
		FROM
			hlstats_Actions a,
			hlstats_Events_TeamBonuses tb,
			hlstats_Players p
		WHERE
			tb.actionId = ' . $row['id'] . '
		AND
			p.playerId = tb.playerId
		AND
			tb.actionId = a.id
		GROUP BY
			tb.playerId
		ORDER BY
			count DESC
		LIMIT
			0, 50';
}

$result = $csstats_db->query($sql);

while( $row = $db->fetchrow($result) )
{
	$template->cycle_vars('players', array(
		'COUNT' => num_format($row['count']),
		'NAME'  => htmlspecialchars(abridgement($row['lastName'], 20)),

		'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
	);
}

$csstats_db->freeresult($result);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/actions.html', $page['furl'])), 'Действия', 'lightning');
navigation_link_custom(ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $url)), $action_name);

page_header($action_name);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
