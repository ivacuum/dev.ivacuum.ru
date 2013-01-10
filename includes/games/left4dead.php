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
* Состояние сервера
*/
function get_server_status($ip, $port)
{
	global $config;

	/**
	* Инициализируем переменные
	*/
	$ary = array(
		'ip'			=> $ip,
		'port'			=> $port,
		'name'			=> '',
		'mode'			=> '',
		'difficulty'	=> '',
		'map'			=> '',
		'active'		=> 0,
		'max'			=> 0,
		'online'		=> 0
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

	$st = substr($st, 6);
	$ary['name'] = substr($st, 0, strpos($st, chr(0)));
	$st = substr($st, strpos($st, 0, chr(0)) + 1);
	$ary['map'] = substr($st, 0, strpos($st, chr(0)));
	$st = substr($st, strpos($st, chr(0)) + 1);
	$st = substr($st, strpos($st, chr(0)) + 1);

	/**
	* L4D - Co-op - Normal...
	* L4D - Versus - Hard...
	*/
	preg_match('#L4D - (Co-op|Versus) - (Easy|Normal|Advanced|Expert).*#', $st, $matches);

	if( !empty($matches) )
	{
		$ary['mode'] = $matches[1];
		$ary['difficulty'] = $matches[2];
	}
	else
	{
		preg_match('#L4D - (Versus|Survival).*#', $st, $matches);

		if( !empty($matches) )
		{
			$ary['mode'] = $matches[1];
			$ary['difficulty'] = 'Normal';
		}
	}
	
	$st = substr($st, strpos($st, chr(0)) + 1);
	$ary['active'] = ord(substr($st, 2, 1));
	$ary['max'] = ord(substr($st, 3, 1));
	$ary['online'] = 1;
	
	return $ary;
}

/**
* Список серверов
*/
/*
$servers_list = array(
	array('ip' => '86.110.163.182', 'port' => 27015),
	array('ip' => '86.110.163.182', 'port' => 27018)
);
*/
$servers_list = array(
	array('ip' => '86.110.181.153', 'port' => 27015),
	array('ip' => '86.110.181.153', 'port' => 27016),
	array('ip' => '86.110.181.153', 'port' => 27017),
	array('ip' => '86.110.181.153', 'port' => 27018),
	array('ip' => '86.110.181.153', 'port' => 27025),
	array('ip' => '86.110.181.153', 'port' => 27026),
	array('ip' => '86.110.181.153', 'port' => 27030),
	array('ip' => '86.110.181.153', 'port' => 27031),
	array('ip' => '86.110.181.153', 'port' => 27032),
	array('ip' => '86.110.181.153', 'port' => 27033)
);

for( $i = 0, $size = count($servers_list); $i < $size; $i++ )
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
		'DIFFICULTY'		=> $row['difficulty'],
		'IP'				=> $row['ip'],
		'MAP'				=> $row['map'],
		'MAP_IMAGE_EXIST'	=> file_exists('/srv/www/vhosts/static.ivacuum.ru/i/games/left4dead/maps/' . $row['map'] . '.jpg'),
		'MAX'				=> $row['max'],
		'MODE'				=> $row['mode'],
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

get_game_links();
get_game_files();

$template->vars(array(
	'S_MODE'			=> $mode)
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

			$template->file = 'games/left4dead_body.html';

			page_footer();
		}
		else
		{
			trigger_error('PAGE_NOT_FOUND');
		}

	break;
}

?>