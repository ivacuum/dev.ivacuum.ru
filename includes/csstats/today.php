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
* Список игроков, посетивших игровые сервера за последние сутки
*/
$sql = '
	SELECT
		playerId,
		lastName,
		lastAddress
	FROM
		hlstats_Players
	WHERE
		last_event >= UNIX_TIMESTAMP(CURRENT_DATE())
	ORDER BY
		lastName ASC';
$result = $csstats_db->query($sql);
$total_players = 0;

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars($mode, array(
		'IP'			=> $row['lastAddress'],
		'N'				=> $total_players + 1,
		'NAME'			=> htmlspecialchars(abridgement($row['lastName'], 20)),
		'SPARK'			=> is_spark($row['lastAddress']),
		
		'U_PLAYERINFO'	=> ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
	);

	$total_players++;
}

$csstats_db->freeresult($result);
$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Онлайн за сегодня', 'monitor');

page_header();

$template->vars(array(
	'COLUMN_MOD'	=> round($total_players / 3) + 1,
	'COLUMN_MOD2'	=> round($total_players / 3),
	'TOTAL_PLAYERS'	=> $total_players)
);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>