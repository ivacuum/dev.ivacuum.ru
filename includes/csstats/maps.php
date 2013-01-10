<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*/

if( !defined('IN_SITE') )
{
	exit;
}

$sort_key_sql = array(
	'a' => 'map_kills',
	'b' => 'map_headshots',
	'c' => 'hpk'
);

/**
* Проверка значений $sort_key
*/
if( strlen($sort_key) != 1 )
{
	trigger_error('PAGE_NOT_FOUND');
}
elseif( !preg_match('#[abc]#', $sort_key) )
{
	trigger_error('PAGE_NOT_FOUND');
}

/* Порядок вывода */
$order_by = $sort_key_sql[$sort_key] . ' ' . ( ( $sort_dir == 'a' ) ? 'ASC' : 'DESC');

/**
* Список карт
*/
$sql = '
	SELECT
		*,
		IFNULL(map_headshots / map_kills, 0) AS hpk
	FROM
		hlstats_Maps
	WHERE
		map_game = ' . $csstats_db->check_value('cstrike') . '
	AND
		map_kills > 0
	ORDER BY
		' . $order_by;
$result = $csstats_db->query($sql);
$total_maps = 0;

while( $row = $csstats_db->fetchrow($result) )
{
	$rounds = $row['map_ct_wins'] + $row['map_ts_wins'];

	$template->cycle_vars($mode, array(
		'CT_WINS_PERCENT' => ( $rounds > 0 ) ? sprintf('%d', ($row['map_ct_wins'] / $rounds) * 100) : 0,
		'HEADSHOTS'       => num_format($row['map_headshots']),
		'HPK'             => ( $row['hpk'] ) ? sprintf('%d%%', $row['hpk'] * 100) : '-',
		'KILLS'           => num_format($row['map_kills']),
		'NAME'            => $row['map_name'],
		'T_WINS_PERCENT'  => ( $rounds > 0 ) ? sprintf('%d', ($row['map_ts_wins'] / $rounds) * 100) : 0,
		
		'U_MAPINFO' => ilink(sprintf('%s/mapinfo/%s.html', $page['furl'], $row['map_name'])))
	);

	$total_maps++;
}

$csstats_db->freeresult($result);

$template->vars(array(
	'SORT_DIR'   => $sort_dir,
	'SORT_KEY'   => $sort_key,
	'TOTAL_MAPS' => $total_maps,

	'U_SORT_HEADSHOTS' => create_sort_link('b'),
	'U_SORT_HPK'       => create_sort_link('c'),
	'U_SORT_KILLS'     => create_sort_link('a'))
);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Карты', 'maps');

page_header('Карты');

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>
