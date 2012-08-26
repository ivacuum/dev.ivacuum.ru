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
* Определяем переменные
*/
$filter   = getvar('filter', '');
$uniqueid = $user->spark_subnet();

if( preg_match('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#', $filter) && is_spark($filter) )
{
	list($ip1, $ip2, $ip3, $ip4) = explode('.', $filter);

	$ip4 -= $ip4 % 8;
	$filter = sprintf('%d.%d.%d.%d', $ip1, $ip2, $ip3, $ip4);
}

$sql = '
	SELECT
		COUNT(*) as total_players
	FROM
		hlstats_Banlist
	WHERE
		ban_game = ' . $csstats_db->check_value('cstrike') .
	( ( $filter ) ? 'AND ban_ip ' . $csstats_db->like_expression(htmlspecialchars_decode($filter)) : '');
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);
$total_players = $row['total_players'];

$filter_url = ( $filter ) ? '?filter=' . $filter : '';
$pagination = pagination(20, $total_players, ilink(sprintf('%s/%s.html%s', $page['furl'], $mode, $filter_url)));

/**
* Банлист
*/
$sql = '
	SELECT
		*
	FROM
		hlstats_Banlist
	WHERE
		ban_game = ' . $csstats_db->check_value('cstrike') . '
	' . ( ( $filter ) ? 'AND ban_ip ' . $csstats_db->like_expression(htmlspecialchars_decode($filter)) : '') . '
	ORDER BY
		ban_date DESC
	LIMIT
		' . $pagination['start'] . ', ' . $pagination['on_page'];
$result = $csstats_db->query($sql);

$i = $pagination['start'] + 1;

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars($mode, array(
		'BANNED' => ( intval($row['ban_date'] + ($row['ban_time'] * 60) - $user->ctime) > 0 ) ? 1 : 0,
		'DATE'   => $user->create_date($row['ban_date'], '|j F Y|, H:i'),
		'IP'     => $row['ban_ip'],
		'N'      => $i,
		'REASON' => $row['ban_reason'],
		'SPARK'  => is_spark($row['ban_ip']),
		'TIME'   => ( $row['ban_time'] > 0 ) ? create_time($row['ban_time'] * 60, true) : 0,
		
		'U_BANINFO' => ilink(sprintf('%s/search.html?search=%s&amp;search_in=ips', $page['furl'], $row['ban_ip'])))
	);

	$i++;
}

$csstats_db->freeresult($result);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Банлист', 'smiley_eek');

page_header('Банлист');

$template->vars(array(
	'FILTER'        => $filter,
	'TOTAL_PLAYERS' => $total_players,

	'U_ACTION' => ilink(sprintf('%s/%s.html', $page['furl'], $mode)))
);

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
