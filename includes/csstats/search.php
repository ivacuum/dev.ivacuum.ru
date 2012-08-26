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
$search        = getvar('search', '');
$search_in     = getvar('search_in', '');
$total_results = 0;
$uniqueid      = $user->spark_subnet();

if( !$auth->check('admin_lead') && $search && strlen($search) < 3 )
{
	trigger_error('Не менее трёх символов для запроса.');
}

if( $search == 'self' )
{
	/**
	* Игрок ищет себя
	*/
	$sql = '
		SELECT
			playerId
		FROM
			hlstats_Players
		WHERE
			lastAddress = ' . $csstats_db->check_value($uniqueid);
	$result = $csstats_db->query($sql);
	$row = $csstats_db->fetchrow($result);
	$csstats_db->freeresult($result);

	if( !$row )
	{
		trigger_error('PLAYER_NOT_FOUND');
	}

	$csstats_db->close();
	redirect(ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])));
}
elseif( $search )
{
	if( $search_in == 'names' )
	{
		/**
		* Поиск среди имён
		*/
		$sql = '
			SELECT
				pn.playerId
			FROM
				hlstats_Players p,
				hlstats_PlayerNames pn
			WHERE
				pn.name ' . $csstats_db->like_expression(htmlspecialchars_decode($search)) . '
			AND
				p.playerId = pn.playerId
			GROUP BY
				pn.playerId';
		$result = $csstats_db->query($sql);
		$total_results = $csstats_db->affected_rows();
		$csstats_db->freeresult($result);

		$pagination = pagination(20, $total_results, ilink(sprintf('%s/search.html?search=%s&search_in=%s', $page['furl'], $search, $search_in)));

		$sql = '
			SELECT
				p.lastAddress,
				pn.playerId,
				pn.name
			FROM
				hlstats_Players p,
				hlstats_PlayerNames pn
			WHERE
				pn.name ' . $csstats_db->like_expression(htmlspecialchars_decode($search)) . '
			AND
				p.playerId = pn.playerId
			GROUP BY
				pn.playerId
			ORDER BY
				pn.numuses DESC,
				p.lastAddress ASC
			LIMIT
				' . $pagination['start'] . ', ' . $pagination['on_page'];
		$result = $csstats_db->query($sql);

		while( $row = $csstats_db->fetchrow($result) )
		{
			$template->cycle_vars('players', array(
				'IP'   => $row['lastAddress'],
				'NAME' => htmlspecialchars(abridgement($row['name'], 30)),
				
				'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
			);
		}

		$csstats_db->freeresult($result);
	}
	elseif( $search_in == 'ips' )
	{
		if( preg_match('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#', $search) && is_spark($search) )
		{
			list($ip1, $ip2, $ip3, $ip4) = explode('.', $search);

			$ip4 -= $ip4 % 8;
			$search = sprintf('%d.%d.%d.%d', $ip1, $ip2, $ip3, $ip4);
		}

		/**
		* Поиск среди IP адресов
		*/
		$sql = '
			SELECT
				COUNT(*) AS total_results
			FROM
				hlstats_Players
			WHERE
				lastAddress ' . $csstats_db->like_expression($search);
		$result = $csstats_db->query($sql);
		$row = $csstats_db->fetchrow($result);
		$csstats_db->freeresult($result);
		$total_results = $row['total_results'];

		$pagination = pagination(20, $total_results, ilink(sprintf('%s/search.html?search=%s&search_in=%s', $page['furl'], $search, $search_in)));

		$sql = '
			SELECT
				playerId,
				lastName,
				lastAddress
			FROM
				hlstats_Players
			WHERE
				lastAddress ' . $csstats_db->like_expression($search) . '
			ORDER BY
				lastAddress ASC
			LIMIT
				' . $pagination['start'] . ', ' . $pagination['on_page'];
		$result = $csstats_db->query($sql);

		while( $row = $csstats_db->fetchrow($result) )
		{
			$template->cycle_vars('players', array(
				'IP'   => $row['lastAddress'],
				'NAME' => htmlspecialchars(abridgement($row['lastName'], 30)),
				
				'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])))
			);
		}

		$csstats_db->freeresult($result);
	}
	else
	{
		trigger_error('PAGE_NOT_FOUND');
	}
}

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

$template->vars(array(
	'SEARCH_RESULTS' => ( $search ) ? true : false,
	'TOTAL_RESULTS'  => $total_results,
	
	'U_ACTION' => ilink(sprintf('%s/%s.html', $page['furl'], $mode)))
);

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Поиск', 'magnifier');

page_header('Поиск');

$template->file = 'csstats/' . $mode . '.html';

page_footer();

?>