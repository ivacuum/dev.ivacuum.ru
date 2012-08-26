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
		'ip'		=> $ip,
		'port'		=> $port,
		'name'		=> '',
		'map'		=> '',
		'mode'		=> '',
		'version'	=> '',
		'active'	=> 0,
		'max'		=> 0,
		'online'	=> 0
	);

	/* 1500000 msec = 1 sec + 500000 msec */
	$timeout_s = intval($config['gameservers_timeout'] / 1000000);
	$timeout_msec = $config['gameservers_timeout'] - $timeout_s;

	$fp = @fsockopen('udp://' . $ip, $port);

	if( !$fp )
	{
		@fclose($fp);
		return $ary;
	}

	@fwrite($fp, "\xFF\xFF\xFF\xFFgetstatus");
	
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

	$part = explode("\n", $st);
	array_pop($part);
	$item = explode('\\', $part[1]);

	foreach( $item as $key => $value )
	{
		switch( $value )
		{
			case 'g_gametype':		$ary['mode'] = $item[$key + 1]; break;
			case 'mapname':			$ary['map'] = $item[$key + 1]; break;
			case 'shortversion':	$ary['version'] = $item[$key + 1]; break;
			case 'sv_maxclients':	$ary['max'] = $item[$key + 1]; break;
		}
	}

	$ary['active'] = ( isset($part[2]) ) ? sizeof($part) - 2 : 0;
	$ary['online'] = 1;

	return $ary;
}

/**
* Список серверов
*/
$servers_list = array(
	array('ip' => '10.100.0.35', 'port' => 28960),
	array('ip' => '10.100.0.35', 'port' => 28961),
	array('ip' => '10.100.0.35', 'port' => 28962),
	array('ip' => '10.100.0.35', 'port' => 28963),
	array('ip' => '10.100.0.35', 'port' => 28964)
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
		'MAP_IMAGE_EXIST'	=> file_exists('/srv/www/vhosts/static.ivacuum.ru/i/games/cod4/maps/' . $row['map'] . '.jpg'),
		'MAX'				=> $row['max'],
		'MODE'				=> $row['mode'],
		'N'					=> $i + 1,
		'NAME'				=> $row['name'],
		'ONLINE'			=> $row['online'],
		'PORT'				=> $row['port'],
		'VERSION'			=> $row['version'])
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

			$template->file = 'games/cod4_body.html';

			page_footer();
		}
		else
		{
			trigger_error('PAGE_NOT_FOUND');
		}

	break;
}

?>