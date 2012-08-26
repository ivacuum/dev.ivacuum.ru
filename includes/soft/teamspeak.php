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

redirect(ilink('', 'http://ts.ivacuum.ru'));

/**
* Состояние сервера
*/
function get_server_status($ip, $port)
{
	/**
	* Инициализируем переменные
	*/
	$ary = array(
		'ip'     => $ip,
		'port'   => $port,
		'name'   => 'TeamSpeak3 Server',
		'uptime' => 0,
		'active' => 0,
		'max'    => 0,
		'online' => 0
	);

	$fp = @fsockopen($ip, 10011);

	if( !$fp )
	{
		@fclose($fp);
		return $ary;
	}

	/* TS3 */
	$st = str_replace(array("\r", "\n"), array('', ''), @fread($fp, 4096));

	if( $st != 'TS3' )
	{
		@fclose($fp);
		return $ary;
	}

	@fwrite($fp, "use sid=1\n");

	/* OK */
	$st = str_replace(array("\r", "\n"), array('', ''), @fread($fp, 4096));

	@fwrite($fp, "serverinfo\n");

	stream_set_timeout($fp, 1);
	$st = fread($fp, 1);
	$r = stream_get_meta_data($fp);
	$r = $r['unread_bytes'];
	
	if( $r == 0 )
	{
		@fclose($fp);
		return $ary;
	}

	/* Server info */
	$st .= fread($fp, $r);
	fwrite($fp, "quit\n");
	@fclose($fp);

	/* param=value param2=value2 ... */
	$row = explode(' ', $st);
	$serverinfo = array();

	foreach( $row as $k => $v )
	{
		$v = str_replace(array("\r", "\n"), array('', ''), $v);
		$pos = strpos($v, '=');

		if( $pos !== false )
		{
			/* $ary[param] = value */
			$serverinfo[substr($v, 0, $pos)] = substr($v, $pos + 1);
		}
	}

	$ary['active'] = $serverinfo['virtualserver_clientsonline'];
	$ary['max']    = $serverinfo['virtualserver_maxclients'];
	$ary['uptime'] = $serverinfo['virtualserver_uptime'] / 1000;
	$ary['online'] = 1;

	if( isset($serverinfo['virtualserver_queryclientsonline']) )
	{
		$ary['active'] -= $serverinfo['virtualserver_queryclientsonline'];
	}
	
	return $ary;
}

/**
* Список серверов
*/
$servers_list = array(
	array('ip' => '85.21.240.187', 'port' => 9987)
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
		'ACTIVE' => $row['active'],
		'IP'     => str_replace('85.21.240.187', 'ts.ivacuum.ru', $row['ip']),
		'MAX'    => $row['max'],
		'N'      => $i + 1,
		'NAME'   => $row['name'],
		'ONLINE' => $row['online'],
		'PORT'   => $row['port'],
		'UPTIME' => create_time($row['uptime'], true))
	);

	if( $update_cache )
	{
		$cache->write('gameserver_' . $servers_list[$i]['ip'] . ':' . $servers_list[$i]['port'], $row, $config['gameservers_interval']);
	}
}

get_soft_links();
get_soft_files();

$template->setvar('S_MODE', $mode);

switch( $mode )
{
	default:

		if( isset($page[2]) )
		{
			page_header();

			$template->vars(array(
				'PAGE_IMAGE' => $page[2]['page_image'],
				'TEXT'       => $page[2]['page_text'],
				'TITLE'      => $page[2]['page_title'])
			);

			$template->file = 'soft/teamspeak_body.html';

			page_footer();
		}
		else
		{
			trigger_error('PAGE_NOT_FOUND');
		}

	break;
}

?>