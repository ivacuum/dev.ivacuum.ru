<?php
/**
*
* @package vacuum.kaluga.spark
* @copyright (c) 2010
*
*/

if( !defined('IN_SITE') )
{
	exit;
}

/**
* Состояние сервера
*/
function get_server_status($ip, $port)
{
	global $config;

	/**
	* Инициализируем переменные
	*/
	$ary = array(
		'ip'     => $ip,
		'port'   => $port,
		'name'   => '',
		'map'    => '',
		'active' => 0,
		'max'    => 0,
		'online' => 0
	);

	/* 1500000 msec = 1 sec + 500000 ms */
	$timeout_s = intval($config['gameservers_timeout'] / 1000000);
	$timeout_msec = $config['gameservers_timeout'] - $timeout_s;

	$fp = @fsockopen('udp://' . $ip, $port);

	if( !$fp )
	{
		@fclose($fp);
		return $ary;
	}

	/* Server info */
	@fwrite($fp, "\xFF\xFF\xFF\xFF\x54\x53\x6F\x75\x72\x63\x65\x20\x45\x6E\x67\x69\x6E\x65\x20\x51\x75\x65\x72\x79\x00" . chr(10));
	
	stream_set_timeout($fp, $timeout_s, $timeout_msec);
	$st = fread($fp, 1);
	$r = stream_get_meta_data($fp);
	$r = $r['unread_bytes'];
	
	if( $r == 0 )
	{
		@fclose($fp);
		return $ary;
	}
	
	$st .= fread($fp, $r);
	@fclose($fp);

	if( $ip != '86.110.181.146' )
	{
		$st = substr($st, 5);
		$st = substr($st, strpos($st, chr(0)) + 1);
		$ary['name'] = substr($st, 0, strpos($st, chr(0)));
		$st = substr($st, strpos($st, chr(0)) + 1);
		$ary['map'] = substr($st, 0, strpos($st, chr(0)));
		$st = substr($st, strpos($st, chr(0)) + 1);
		$st = substr($st, strpos($st, chr(0)) + 1);
		$st = substr($st, strpos($st, chr(0)) + 1);
		$ary['active'] = ord(substr($st, 0, 1));
		$ary['max'] = ord(substr($st, 1, 1));
	}
	else
	{
		$st = substr($st, 6);
		$ary['name'] = substr($st, 0, strpos($st, chr(0)));
		$st = substr($st, strpos($st, chr(0)) + 1);
		$ary['map'] = substr($st, 0, strpos($st, chr(0)));
		$st = substr($st, strpos($st, chr(0)) + 1);
		$st = substr($st, strpos($st, chr(0)) + 1);
		$st = substr($st, strpos($st, chr(0)) + 1);
		$ary['active'] = ord(substr($st, 2, 1));
		$ary['max'] = ord(substr($st, 3, 1));
	}

	$ary['online'] = 1;
	
	return $ary;
}

/**
* Список серверов
*/
$servers_list = array(
	array('ip' => '10.221.0.9', 'port' => 27017),
	array('ip' => '10.221.0.9', 'port' => 27018),
	array('ip' => '10.100.0.35', 'port' => 27025),
	array('ip' => '10.100.0.35', 'port' => 27026),
	array('ip' => '10.100.0.35', 'port' => 27029)
);

for( $i = 0, $size = sizeof($servers_list); $i < $size; $i++ )
{
	$row = $cache->get('gameserver_' . $servers_list[$i]['ip'] . ':' . $servers_list[$i]['port']);
	$update_cache = false;

	if( $row === false )
	{
		$row = get_server_status($servers_list[$i]['ip'], $servers_list[$i]['port']);
		$update_cache = true;
	}

	$template->cycle_vars('gameservers', array(
		'ACTIVE'			=> $row['active'],
		'IP'				=> $row['ip'],
		'MAP'				=> $row['map'],
		'MAP_IMAGE_EXIST'	=> file_exists('/srv/www/vhosts/static.ivacuum.ru/i/games/cstrike/maps/' . $row['map'] . '.jpg'),
		'MAX'				=> $row['max'],
		'N'					=> $i + 1,
		'NAME'				=> $row['name'],
		'ONLINE'			=> $row['online'],
		'PORT'				=> $row['port'])
	);

	if( $update_cache )
	{
		$cache->write('gameserver_' . $servers_list[$i]['ip'] . ':' . $servers_list[$i]['port'], $row, $config['gameservers_interval']);
	}
}

//$auth->write(132, 0, 12, 0, 1);
//$auth->update(132);

$agree = false;
$message = '';

get_game_links();
get_game_files('cstrike');

$template->vars(array(
	'S_MODE'		=> $mode)
);

switch( $mode )
{
	/**
	* Банлист
	*/
	case 'банлистmoved':

		switch( $action )
		{
			/**
			* Добавление бана
			*/
			case 'add':

				if( !$auth->check('cs_admin') )
				{
					trigger_error('PAGE_NOT_FOUND');
				}

				$ip		= getvar('ip', '');
				$reason	= getvar('reason', '');
				$submit	= getvar('send', false);
				$time	= getvar('time', 1440);

				if( $submit )
				{
					if( !preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $ip) )
					{
						$message .= '<li>IP-адрес указан неверно.</li>';
					}
					else
					{
						list($ip_1, $ip_2, $ip_3, $ip_4) = explode('.', $ip);

						if( $ip_1 == '10' )
						{
							$ip_4 -= $ip_4 % 8;

							$ip = implode('.', array($ip_1, $ip_2, $ip_3, $ip_4)) . '/29';
						}
						else
						{
							$ip .= '/32';
						}

						$csdb = new db_mysqli();
		
						$csdb->connect('localhost', 'csstats', 'dfHYpWSR9T23vQtY', 'csstats');

						$sql = '
							SELECT
								p.playerId as user_id
							FROM
								hlstats_Players p,
								hlstats_PlayerUniqueIds pu
							WHERE
								pu.uniqueId IN (' . $db->check_value(implode('.', array($ip_1, $ip_2, $ip_3, $ip_4 + 2))) . ', ' . $db->check_value(implode('.', array($ip_1, $ip_2, $ip_3, $ip_4 + 3))) . ', ' . $db->check_value(implode('.', array($ip_1, $ip_2, $ip_3, $ip_4 + 4))) . ', ' . $db->check_value(implode('.', array($ip_1, $ip_2, $ip_3, $ip_4 + 5))) . ', ' . $db->check_value(implode('.', array($ip_1, $ip_2, $ip_3, $ip_4 + 6))) . ')
							AND
								p.playerId = pu.playerId
							AND
								p.game = ' . $db->check_value('cstrike') . '
							ORDER BY
								p.last_event DESC
							LIMIT
								0, 1';
						$result = $csdb->query($sql);
						$row = $csdb->fetchrow($result);
						$csdb->freeresult($result);

						if( !$row )
						{
							$message .= '<li>Игрок не найден.</li>';
						}
						else
						{
							if( !$agree )
							{
								$sql = '
									SELECT
										ban_date,
										ban_time
									FROM
										hlstats_Banlist
									WHERE
										ban_ip = ' . $db->check_value($ip) . '
									LIMIT
										0, 1';
								$result = $csdb->query($sql);
								$row2 = $csdb->fetchrow($result);
								$csdb->freeresult($result);

								if( $row2 )
								{
									$message .= '<li>Этого игрока уже банили &laquo;' . $user->create_date($row2['ban_date']) . '&raquo; на &laquo;' . trim(create_time($row2['ban_time'] * 60, true)) . '&raquo;.</li>';
									$agree = true;
								}
								else
								{
									$sql = '
										INSERT INTO
											hlstats_Banlist
										' . $db->build_array('INSERT', array(
											'ban_game'			=> 'cstrike',
											'ban_user_id'		=> $row['user_id'],
											'ban_admin_id'		=> $user['user_id'],
											'ban_date'			=> time(),
											'ban_time'			=> $time,
											'ban_ip'			=> $ip,
											'ban_reason'		=> $reason)
										);
									$csdb->query($sql);
									$csdb->close();

									meta_refresh(1, ilink($page['url'], $page['furl']));
									trigger_error('SUCCESSFULLY_BANNED');
								}
							}
							else
							{
								$sql = '
									INSERT INTO
										hlstats_Banlist
									' . $db->build_array('INSERT', array(
										'ban_game'			=> 'cstrike',
										'ban_user_id'		=> $row['user_id'],
										'ban_admin_id'		=> $user['user_id'],
										'ban_date'			=> time(),
										'ban_time'			=> $time,
										'ban_ip'			=> $ip,
										'ban_reason'		=> $reason)
									);
								$csdb->query($sql);
								$csdb->close();

								meta_refresh(1, ilink($page['url'], $page['furl']));
								trigger_error('SUCCESSFULLY_BANNED');
							}
						}
					}
				}

				$template->vars(array(
					'AGREE'			=> $agree,
					'IP'			=> getvar('ip', ''),
					'MESSAGE'		=> ( $message ) ? '<ul style="color: red;">' . $message . '</ul>' : '',
					'REASON'		=> $reason,
					'TIME'			=> $time
				));

			break;
			default:

				$csdb = new db_mysqli();
		
				$csdb->connect('localhost', 'csstats', 'dfHYpWSR9T23vQtY', 'csstats');

				$sql = '
					SELECT
						b.*,
						p.lastName as username
					FROM
						hlstats_Banlist b,
						hlstats_Players p
					WHERE
						b.ban_user_id = p.playerId
					AND
						b.ban_game = ' . $db->check_value('cstrike') . '
					ORDER BY
						b.ban_date DESC
					LIMIT
						0, 10';
				$result = $csdb->query($sql);

				while( $row = $csdb->fetchrow($result) )
				{
					$template->cycle_vars('bans', array(
						'DATE'			=> $user->create_date($row['ban_date']),
						'IP'			=> $row['ban_ip'],
						'NICK'			=> $row['username'],
						'REASON'		=> $row['ban_reason'],
						'TIME'			=> ( $row['ban_time'] == 0 ) ? $user->lang['PERMANENTLY'] : trim(create_time($row['ban_time'] * 60, true)),
						'UNBAN_DATE'	=> ( $row['ban_time'] == 0 ) ? $user->lang['NEVER'] : $user->create_date($row['ban_date'] + ( $row['ban_time'] * 60 )))
					);
				}

				$csdb->freeresult($result);
				$csdb->close();

			break;
		}

		page_header(array($page[1]['page_title'], $page[2]['page_title']));

		$template->vars(array(
			'TEXT'				=> $page[2]['page_text'],
			'TITLE'				=> $page[2]['page_title'],

			'S_ACTION'			=> $action,
			'S_CS_ADMIN'		=> $auth->check('cs_admin'),
				
			'U_ADD'				=> ilink($page['url'] . '&amp;action=add', $page['furl'] . '?action=add'),
			'U_BANLIST'			=> ilink($page['url'], $page['furl']))
		);

		$template->file = 'games/cstrike_banlist.html';

		page_footer();

	break;
	default:

		if( isset($page[2]) )
		{
			page_header();

			$template->vars(array(
				'PAGE_IMAGE'	=> $page[2]['page_image'],
				'TEXT'			=> $page[2]['page_text'],
				'TITLE'			=> $page[2]['page_title'])
			);

			$template->file = 'games/cstrike_body.html';

			page_footer();
		}
		else
		{
			trigger_error('PAGE_NOT_FOUND');
		}

	break;
}

?>