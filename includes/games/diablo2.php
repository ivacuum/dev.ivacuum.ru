<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2008
*/

if( !defined('IN_SITE') )
{
	exit;
}

function d2ladder_get_int($binary)
{
	return sprintf('%u', (ord($binary{0}) | (@ord($binary{1}) << 8) | (@ord($binary{2}) << 16) | (@ord($binary{3}) << 24))) + 0.0;
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
		return '<img class="icons icon-pvpgn-game shadow-light" src="http://static.ivacuum.ru/i/games/_icons/' . $row['ctag'] . '.jpg" alt="' . $row['ctag'] . '" /><a href="http://ivacuum.ru/pvpgn/stats.php?game=' . $row['ctag'] . '&amp;user=' . $row['name'] . '"><b>' . $row['name'] . '</b></a><br />';
	}
	else
	{
		return '<img class="icons icon-pvpgn-game shadow-light" src="http://static.ivacuum.ru/i/games/_icons/' . $row['ctag'] . '.jpg" alt="' . $row['ctag'] . '" / ><b>' . $row['name'] . '</b><br />';
	}
}

get_game_links();
get_game_files();
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
			'NAME'	=> '<img class="icons icon-pvpgn-game shadow-light" src="http://static.ivacuum.ru/i/games/_icons/' . $value['ctag'] . '.jpg" alt="' . $value['ctag'] . '" /><b>' . $value['name'] . '</b><br />')
		);
	}
}
else
{
	$template->vars(array(
		'NO_GAMES'	=> true)
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
	case 'ладдер':

		$s_init	= 0x1;
		$s_exp	= 0x20;
		$s_hc	= 0x04;
		$s_dead	= 0x08;

		$sexes = array(
			'Amazon'		=> 'f',
			'Sorceress'		=> 'f',
			'Necromancer'	=> 'm',
			'Paladin'		=> 'm',
			'Barbarian'		=> 'm',
			'Druid'			=> 'm',
			'Assassin'		=> 'f'
		);

		$diff = array(
			'D2XP' => array(
				1 => array(
					'SC' => array('m' => 'Slayer', 'f' => 'Slayer'),
					'HC' => array('m' => 'Destroyer', 'f' => 'Destroyer')),
				2 => array(
					'SC' => array('m' => 'Champion', 'f' => 'Champion'),
					'HC' => array('m' => 'Conqueror', 'f' => 'Conqueror')),
				3 => array(
					'SC' => array('m' => 'Patriarch', 'f' => 'Matriarch'),
					'HC' => array('m' => 'Guardian', 'f' => 'Guardian'))),
			'D2DV' => array(
				1 => array(
					'SC' => array('m' => 'Sir', 'f' => 'Dame'),
					'HC' => array('m' => 'Count', 'f' => 'Countess')),
				2 => array(
					'SC' => array('m' => 'Lord', 'f' => 'Lady'),
					'HC' => array('m' => 'Duke', 'f' => 'Duchess')),
				3 => array(
					'SC' => array('m' => 'Baron', 'f' => 'Baroness'),
					'HC' => array('m' => 'King', 'f' => 'Queen'))
			)
		);

		$classes = array('Amazon', 'Sorceress', 'Necromancer', 'Paladin', 'Barbarian', 'Druid', 'Assassin');
		$hnames  = $cache->get('d2ladder_heroes_names');

		$d2ladder_file		= '/srv/www/vhosts/ivacuum.ru/pvpgn/ladder.D2DV';
		$d2ladder_filemtime	= filemtime($d2ladder_file);
		$d2ladder_time		= $cache->get('d2ladder_time');

		/**
		* Обновляем ладдер
		* Интервал: ~15 минут
		*/
		if( $d2ladder_time === false || $d2ladder_filemtime > $d2ladder_time )
		{
			$fp				= fopen($d2ladder_file, 'rb');
			$size			= filesize($d2ladder_file);
			$maxtype		= fread($fp, 4);
			$maxtypei		= d2ladder_get_int($maxtype);
			$checksum		= fread($fp, 4);
			$checksumi		= d2ladder_get_int($checksum);
			$size -= 8;

			for( $i = 0; $i < $maxtypei; $i++ )
			{
				$type	= d2ladder_get_int(fread($fp, 4));
				$offset = d2ladder_get_int(fread($fp, 4));
				$number	= d2ladder_get_int(fread($fp, 4));
				$size -= 12;
			}

			for( $i = 0; $size > 0; $i++ )
			{
				$xp			= d2ladder_get_int(fread($fp, 4));
				$status		= d2ladder_get_int(fread($fp, 2));
				$level		= d2ladder_get_int(fread($fp, 1));
				$class		= d2ladder_get_int(fread($fp, 1));
				$charname	= trim(fread($fp, 16));
				$size -= 24;

				if( !$charname )
				{
					continue;
				}

				if( $status & $s_hc )
				{
					$dead = ( $status & $s_dead ) ? 'DEAD' : 'ALIVE';
					$hc = 'HC';
				}
				else
				{
					$dead = 'ALIVE';
					$hc = 'SC';
				}

				$difficulty = floor((($status >> 0x08) & 0x0f) / 5);
				$game = ( $status & $s_exp ) ? 'D2XP' : 'D2DV';
				$prefix = ( $difficulty > 0 ) ? $diff[$game][$difficulty][$hc][$sexes[$classes[$class]]] : '';

				$ary = array(
					'charname'		=> $charname,
					'title'			=> $prefix,
					'level'			=> $level,
					'class'			=> $classes[$class],
					'experience'	=> $xp,
					'type'			=> $hc,
					'dead'			=> $dead,
					'game'			=> $game
				);

				if( $hnames === false || !isset($hnames[$charname]) || $hnames[$charname]['experience'] != $xp )
				{
					/**
					* Обновляем только изменившихся персонажей
					*/
					$sql = '
						REPLACE INTO
							d2ladder
						SET
							' . $pvpgn_db->build_array('UPDATE', $ary);
					$pvpgn_db->query($sql);
				}
			}

			fclose($fp);

			$cache->write('d2ladder_time', $user->ctime);
			$cache->clean('d2ladder_heroes');
			$cache->clean('d2ladder_heroes_names');
		}

		require('diablo2_ladder_exp.php');

		$heroes = $cache->get('d2ladder_heroes');
		$update_hnames = false;
		
		if( $heroes === false || $hnames === false )
		{
			$sql = '
				SELECT
					*
				FROM
					d2ladder
				WHERE
					game = ' . $pvpgn_db->check_value('D2XP') . '
				AND
					type = ' . $pvpgn_db->check_value('SC') . '
				AND
					experience > 0
				ORDER BY
					experience DESC';
			$result = $pvpgn_db->query($sql);
			$heroes = $pvpgn_db->fetchall($result);
			$pvpgn_db->freeresult($result);
			$cache->write('d2ladder_heroes', $heroes);
			$update_hnames = true;
		}

		$n = 1;

		foreach( $heroes as $k => $row )
		{
			if( $update_hnames )
			{
				$hnames[$row['charname']] = $row;
			}

			if( $row['experience'] > 1822000000 )
			{
				$row['level'] += ( $row['level'] < 512 ) ? 512 : 0;
			}
			elseif( $row['experience'] > 664000000 )
			{
				$row['level'] += ( $row['level'] < 256 ) ? 256 : 0;
			}

			if( isset($exp[$row['level']]) && isset($exp[$row['level'] + 1]) )
			{
				if( $row['experience'] > $exp[$row['level'] + 1] )
				{
					$row['level_percent'] = '100%';
				}
				else
				{
					$row['level_percent'] = sprintf('%d%%', (($row['experience'] - $exp[$row['level']]) / ($exp[$row['level'] + 1] - $exp[$row['level']])) * 100);
				}
			}
			else
			{
				$row['level_percent'] = '1%';
			}

			$template->cycle_vars('heroes', array(
				'CLASS'			=> $row['class'],
				'EXP'			=> number_format($row['experience'], 0, '', ' '),
				'LEVEL'			=> $row['level'],
				'LEVEL_PERCENT'	=> $row['level_percent'],
				'N'				=> $n,
				'NAME'			=> $row['charname'],
				'TITLE'			=> $row['title'])
			);

			$n++;
		}

		if( $update_hnames )
		{
			$cache->write('d2ladder_heroes_names', $hnames);
		}

		$db->total_queries += $pvpgn_db->total_queries;
		$pvpgn_db->close();

		page_header();

		$template->vars(array(
			'PAGE_IMAGE'	=> $page[2]['page_image'],
			'TITLE'			=> $page[2]['page_title'])
		);

		$template->file = 'games/diablo2_ladder.html';
		page_footer();

	break;
	default:

		$db->total_queries += $pvpgn_db->total_queries;
		$pvpgn_db->close();

		if( isset($page[2]) )
		{
			page_header();

			$template->vars(array(
				'PAGE_IMAGE'	=> $page[2]['page_image'],
				'TEXT'			=> $page[2]['page_text'],
				'TITLE'			=> $page[2]['page_title'])
			);

			$template->file = 'games/diablo2_body.html';

			page_footer();
		}
		else
		{
			trigger_error('PAGE_NOT_FOUND');
		}

	break;
}

?>