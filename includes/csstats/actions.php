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
* Список действий
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Actions
	WHERE
		game = ' . $csstats_db->check_value('cstrike') . '
	AND
		count > 0
	AND
		(reward_player <> 0 OR reward_team <> 0)
	ORDER BY
		count DESC';
$result = $csstats_db->query($sql);
$total_actions = 0;

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars($mode, array(
		'COUNT'         => num_format($row['count']),
		'NAME'          => $row['description'],
		'REWARD_PLAYER' => ( $row['reward_player'] != 0 ) ? sprintf('%+d', $row['reward_player']) : '-',
		'REWARD_TEAM'   => ( $row['reward_team'] != 0 ) ? sprintf('%+d', $row['reward_team']) : '-',
		
		'U_ACTIONINFO'  => ilink(sprintf('%s/actioninfo/%s.html', $page['furl'], $row['code'])))
	);

	$total_actions++;
}

$csstats_db->freeresult($result);
$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Действия', 'lightning');

page_header('Действия');

$template->vars(array(
	'TOTAL_ACTIONS' => $total_actions)
);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
