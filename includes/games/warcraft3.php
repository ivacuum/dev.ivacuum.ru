<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*/

if( !defined('IN_SITE') )
{
	exit;
}

function get_server_status()
{
	$ini_array = array();
	$lines = file('/srv/pvpgn/var/status/server.dat');

	foreach( $lines as $line )
	{
		$line = trim($line);

		if( $line == '' )
		{
			continue;
		}
		elseif( $line[0] == '[' && $line[strlen($line) - 1] == ']' )
		{
			$sec_name = substr($line, 1, strlen($line) - 2);
		}
		else
		{
			$pos = strpos($line, '=');
			$property = substr($line, 0, $pos);
			$value = substr($line, $pos + 1);

			if( $sec_name == 'CHANNELS' )
			{
				continue;
			}
			elseif( $sec_name == 'USERS' || $sec_name == 'GAMES' )
			{
				list($ini_array[$sec_name][$property]['ctag'], $ini_array[$sec_name][$property]['name']) = explode(',', $value);
			}
			else
			{
				$ini_array[$sec_name][$property] = $value;
			}
		}
	}

	return $ini_array;
}

function pvpgn_profile_link($row, $ctag = false)
{
	if( $row['ctag'] != 'CHAT' && $row['ctag'] != 'D2XP' && $row['ctag'] != 'D2DV' )
	{
		return '<img class="icons icon-pvpgn-game shadow-light" src="http://static.ivacuum.ru/i/games/_icons/' . $row['ctag'] . '.jpg" alt="' . $row['ctag'] . '" /><a href="/pvpgn/stats.php?game=' . $row['ctag'] . '&amp;user=' . $row['name'] . '"><b>' . $row['name'] . '</b></a><br />';
	}
	else
	{
		return '<img class="icons icon-pvpgn-game shadow-light" src="http://static.ivacuum.ru/i/games/_icons/' . $row['ctag'] . '.jpg" alt="' . $row['ctag'] . '" / ><b>' . $row['name'] . '</b><br />';
	}
}

get_game_links();
get_game_files('wc3', 'files');
$status = get_server_status();

$status['STATUS']['Uptime'] = str_replace(array('days', 'day', 'hours', 'hour', 'minutes', 'minute', 'seconds', 'second'), '', $status['STATUS']['Uptime']);
$status['STATUS']['Uptime'] = trim(str_replace('  ', ' ', $status['STATUS']['Uptime']));

$spaces = substr_count($status['STATUS']['Uptime'], ' ');

switch( $spaces )
{
	case 0:

		$time = $status['STATUS']['Uptime'];

	break;
	case 1:

		list($minutes, $seconds) = explode(' ', $status['STATUS']['Uptime']);
		$time = ( $minutes * 60 ) + $seconds;

	break;
	case 2:

		list($hours, $minutes, $seconds) = explode(' ', $status['STATUS']['Uptime']);
		$time = ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;

	break;
	case 3:

		list($days, $hours, $minutes, $seconds) = explode(' ', $status['STATUS']['Uptime']);
		$time = ( $days * 86400 ) + ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;

	break;
}

$status['STATUS']['Uptime'] = create_time($time, true);

if( $status['STATUS']['Games'] > 0 )
{
	foreach( $status['GAMES'] as $key => $value )
	{
		$template->cycle_vars('gamepage_games', array(
			'NAME'			=> '<img class="icons icon-pvpgn-game shadow-light" src="http://static.ivacuum.ru/i/games/_icons/' . $value['ctag'] . '.jpg" alt="' . $value['ctag'] . '" /><b>' . $value['name'] . '</b><br />')
		);
	}
}
else
{
	$template->vars(array(
		'NO_GAMES'		=> true)
	);
}

if( $status['STATUS']['Users'] > 0 )
{
	foreach( $status['USERS'] as $key => $value )
	{
		$template->cycle_vars('gamepage_users', array(
			'PROFILE'		=> pvpgn_profile_link($value))
		);
	}
}
else
{
	$template->vars(array(
		'NO_PLAYERS'	=> true)
	);
}

$pvpgn_db = new db_mysqli();
$pvpgn_db->connect($dbhost, 'pvpgn', 'DKW3B9sq3uR9BDUF', 'pvpgn', $dbport, $dbsock);

$sql = '
	SELECT
		acct_username,
		acct_lastlogin_clienttag,
		acct_lastlogin_ip,
		acct_lastlogin_time
	FROM
		BNET
	WHERE
		acct_lastlogin_time > ' . ( $user->ctime - 86400 ) . '
	ORDER BY
		acct_lastlogin_time DESC';
$result = $pvpgn_db->query($sql);

while( $row = $pvpgn_db->fetchrow($result) )
{
	$template->cycle_vars('gamepage_lastusers', array(
		'TEXT'		=> '<img class="icons icon-pvpgn-game shadow-light" src="http://static.ivacuum.ru/i/games/_icons/' . $row['acct_lastlogin_clienttag'] . '.jpg" alt="" />' . date('H:i', $row['acct_lastlogin_time']) . ' <b>' . $row['acct_username'] . '</b><br />')
	);
}

$pvpgn_db->freeresult($result);

$template->vars(array(
	'GAMES'				=> $status['STATUS']['Games'],
	'PLAYERS'			=> $status['STATUS']['Users'],
	'SERVER_UPTIME'		=> $status['STATUS']['Uptime'],
	'SERVER_VERSION'	=> $status['STATUS']['Version'],

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

			$template->file = 'games/warcraft3_body.html';

			page_footer();
		}
		else
		{
			trigger_error('PAGE_NOT_FOUND');
		}

	break;
}

?>