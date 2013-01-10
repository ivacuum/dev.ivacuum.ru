<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*/

if( !defined('IN_SITE') )
{
	exit;
}

if( !$auth->check('admin_lead') )
{
	trigger_error('PAGE_NOT_FOUND');
}

navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Админка', 'block');

$section = getvar('section', '');

$template->vars(array(
	'U_MANAGE_ADMIN_ACTIONS' => ilink(sprintf('%s/manage.html?section=admin_actions', $page['furl'])),
	'U_MANAGE_PLAYERS'       => ilink(sprintf('%s/manage.html?section=players', $page['furl'])),
	'U_MANAGE_RCON'          => ilink(sprintf('%s/manage.html?section=rcon', $page['furl'])),
	'U_MANAGE_SERVERS'       => ilink(sprintf('%s/manage.html?section=servers', $page['furl'])))
);

if( $section == 'servers' )
{
	/**
	* Управление серверами
	*/
	$base_url = sprintf('%s/%s.html?section=%s', $page['furl'], $mode, $section);
	$power    = getvar('power', '');

	navigation_link_custom(ilink($base_url), 'Серверы', 'servers');

	if( $power == 'on' || $power == 'off' )
	{
		/**
		* Включение и выключение ведения статистики на серверах
		*/
		$active    = ( $power == 'on' ) ? 1 : 0;
		$server_id = getvar('server_id', 0);

		$sql = '
			SELECT
				serverId
			FROM
				hlstats_Servers
			WHERE
				serverId = ' . $csstats_db->check_value($server_id);
		$result = $csstats_db->query($sql);
		$row = $csstats_db->fetchrow($result);
		$csstats_db->freeresult($result);

		if( !$row )
		{
			trigger_error('SERVER_NOT_FOUND');
		}

		$sql = '
			UPDATE
				hlstats_Servers
			SET
				active = ' . $active . '
			WHERE
				serverId = ' . $csstats_db->check_value($server_id);
		$csstats_db->query($sql);

		redirect(ilink($base_url));
	}

	$sql = '
		SELECT
			*
		FROM
			hlstats_Servers
		WHERE
			game = ' . $csstats_db->check_value('cstrike');
	$result = $csstats_db->query($sql);

	while( $row = $csstats_db->fetchrow($result) )
	{
		$template->cycle_vars('servers', array(
			'ACTIVE'      => $row['active'],
			'ACT_PLAYERS' => $row['act_players'],
			'IP'          => $row['address'],
			'MAP'         => $row['act_map'],
			'MAX_PLAYERS' => $row['max_players'],
			'NAME'        => $row['name'],
			'PORT'        => $row['port'],

			'U_DELETE'    => ilink(sprintf('%s&action=delete&server_id=%d', $base_url, $row['serverId'])),
			'U_EDIT'      => ilink(sprintf('%s&action=edit&server_id=%d', $base_url, $row['serverId'])),
			'U_MAPS'      => ilink(sprintf('%s&show=maps&server_id=%d', $base_url, $row['serverId'])),
			'U_POWER_OFF' => ilink(sprintf('%s&power=off&server_id=%d', $base_url, $row['serverId'])),
			'U_POWER_ON'  => ilink(sprintf('%s&power=on&server_id=%d', $base_url, $row['serverId'])))
		);
	}

	$csstats_db->freeresult($result);

	$template->file = 'csstats/manage_' . $section . '.html';
}
else
{
	navigation_link_custom(ilink(sprintf('%s/%s.html', $page['furl'], $mode)), 'Информация', 'information_balloon');

	$sql = '
		SELECT
			e.*,
			p.lastName,
			s.name
		FROM
			hlstats_Events_Admin e,
			hlstats_Players p,
			hlstats_Servers s
		WHERE
			s.serverId = e.serverId
		AND
			p.playerId = e.playerId
		ORDER BY
			e.eventTime DESC
		LIMIT
			0, 5';
	$result = $csstats_db->query($sql);

	while( $row = $csstats_db->fetchrow($result) )
	{
		$template->cycle_vars('actions', array(
			'EVENT'  => $row['type'],
			'ID'     => $row['id'],
			'PLAYER' => htmlspecialchars(abridgement($row['lastName'], 20)),
			'SERVER' => $row['name'],
			'TEXT'   => htmlspecialchars($row['text']),
			'TIME'   => $user->create_date($row['eventTime']),

			'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['playerId'])),
			'U_SERVERINFO' => ilink(sprintf('%s/serverinfo/%d.html', $page['furl'], $row['serverId'])))
		);
	}

	$csstats_db->freeresult($result);

	$sql = '
		SELECT
			e.*,
			s.name
		FROM
			hlstats_Events_Rcon e,
			hlstats_Servers s
		WHERE
			s.serverId = e.serverId
		ORDER BY
			e.eventTime DESC
		LIMIT
			0, 5';
	$result = $csstats_db->query($sql);

	while( $row = $csstats_db->fetchrow($result) )
	{
		$template->cycle_vars('rcon', array(
			'COMMAND'  => $row['command'],
			'IP'       => $row['remoteIp'],
			'PASSWORD' => $row['password'],
			'SERVER'   => $row['name'],
			'TIME'     => $user->create_date($row['eventTime']),
			'TYPE'     => $row['type'],

			'U_SEARCHIP'   => ilink(sprintf('%s/search.html?search=%s&search_in=ips', $page['furl'], $row['remoteIp'])),
			'U_SERVERINFO' => ilink(sprintf('%s/serverinfo/%d.html', $page['furl'], $row['serverId'])))
		);
	}

	$csstats_db->freeresult($result);

	$template->file = 'csstats/manage.html';
}

$db->total_queries += $csstats_db->total_queries;
$csstats_db->close();

page_header('Админ центр');

page_footer();

?>