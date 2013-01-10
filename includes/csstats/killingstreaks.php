<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*/

if( !defined('IN_SITE') )
{
	exit;
}

$sql = '
	SELECT
		COUNT(*) as total_streaks
	FROM
		hlstats_Players
	WHERE
		hideranking = 0
	AND
		killing_streak > 0';
$result = $csstats_db->query($sql);
$row = $csstats_db->fetchrow($result);
$csstats_db->freeresult($result);
$total_streaks = $row['total_streaks'];

$pagination = pagination(20, $total_streaks, ilink(sprintf('%s/%s.html', $page['furl'], $mode)));

$sort_key_sql = array(
	'a'	=> 'killing_streak',
	'b' => 'killing_streak_hs',
	'c' => 'round_killing_streak'
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

$sql = '
	SELECT
		playerId,
		lastName,
		killing_streak,
		killing_streak_hs,
		round_killing_streak
	FROM
		hlstats_Players
	WHERE
		hideranking = 0
	AND
		killing_streak > 0
	ORDER BY
		' . $order_by . '
	LIMIT
		' . $pagination['start'] . ', ' . $pagination['on_page'];
$result = $csstats_db->query($sql);
$i = $pagination['start'] + 1;

while( $row = $csstats_db->fetchrow($result) )
{
	$template->cycle_vars('players', array(
		'HEADSHOTS' => num_format($row['killing_streak_hs']),
		'KILLS'     => num_format($row['killing_streak']),
		'N'         => $i,
		'NAME'      => htmlspecialchars(abridgement($row['lastName'], 30)),
		'ROUND'     => num_format($row['round_killing_streak']),

		'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
	);

	$i++;
}

$csstats_db->freeresult($result);

$template->vars(array(
	'SORT_DIR' => $sort_dir,
	'SORT_KEY' => $sort_key,

	'U_SORT_KS'   => create_sort_link('a'),
	'U_SORT_KSHS' => create_sort_link('b'),
	'U_SORT_RKS'  => create_sort_link('c'))
);

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Лучшие серии фрагов', 'lightning');

page_header('Лучшие серии фрагов');

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>