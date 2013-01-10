<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2010
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
	@fwrite($fp, "\x21\x21\x21\x21\x00");

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

	$buffer_s = ( $st[4] == "\x00" ) ? substr($st, 5) : '';

	if( !$buffer_s )
	{
		@fclose($fp);
		return $ary;
	}

	$buffer_s = str_replace("\xa0", "\x20", $buffer_s);
	$buffer_s = substr($buffer_s, 5);

	$st = substr($buffer_s, 0, 4);
	$buffer_s = substr($buffer_s, 4);
	list(,$st) = unpack('S', $st);
	$ary['port'] = $st;

	$buffer_s = substr($buffer_s, 5);

	$ary['name'] = substr($buffer_s, 0, strpos($buffer_s, chr(0)));
	$buffer_s = substr($buffer_s, strpos($buffer_s, chr(0)) + 2);
	$ary['map'] = substr($buffer_s, 0, strpos($buffer_s, chr(0)));
	$buffer_s = substr($buffer_s, strpos($buffer_s, chr(0)) + 1);
	$buffer_s = substr($buffer_s, strpos($buffer_s, chr(0)) + 1);

	$st = substr($buffer_s, 0, 4);
	$buffer_s = substr($buffer_s, 4);
	list(,$st) = unpack('S', $st);
	$ary['active'] = $st;

	$st = substr($buffer_s, 0, 4);
	$buffer_s = substr($buffer_s, 4);
	list(,$st) = unpack('S', $st);
	$ary['max'] = $st;
	$ary['online'] = 1;

	return $ary;
}

/**
* Список серверов
*/
$servers_list = array(
	array('ip' => '86.110.181.151', 'port' => 7708),
	array('ip' => '86.110.181.151', 'port' => 7728),
	array('ip' => '86.110.181.151', 'port' => 7718),
	array('ip' => '86.110.181.151', 'port' => 7748)
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
		'ACTIVE'	=> $row['active'],
		'IP'		=> $row['ip'],
		'MAP'		=> ( mb_strlen($row['map']) > 17 ) ? mb_substr($row['map'], 0, 17) : $row['map'],
		'MAX'		=> $row['max'],
		'N'			=> $i + 1,
		'NAME'		=> $row['name'],
		'ONLINE'	=> $row['online'],
		'PORT'		=> $row['port'])
	);

	if( $update_cache )
	{
		$cache->write('gameserver_' . $servers_list[$i]['ip'] . ':' . $servers_list[$i]['port'], $row, $config['gameservers_interval']);
	}
}

get_game_links();
get_game_files();

$template->vars(array(
	'S_MODE'	=> $mode)
);

switch( $mode )
{
	default:

		if( isset($page[2]) )
		{
			page_header();

			$template->vars(array(
				'PAGE_IMAGE'	=> $page[2]['page_image'],
				'TEXT'			=> $page[2]['page_text'],
				'TITLE'			=> $page[2]['page_title'])
			);

			$template->file = 'games/killing_floor_body.html';

			page_footer();
		}
		else
		{
			trigger_error('PAGE_NOT_FOUND');
		}

	break;
}

?>