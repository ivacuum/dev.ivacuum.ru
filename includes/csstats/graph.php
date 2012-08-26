<?php
/**
*
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*
*/

define('IN_SITE', true);
$site_root_path = './../../';
require($site_root_path . 'engine/engine_lite.php');
$db->connect('localhost', 'csstats', 'dfHYpWSR9T23vQtY', 'csstats', false, '/var/run/mysql/mysql.sock');

$font_file = '/srv/www/vhosts/ivacuum.ru/engine/fonts/calibri.ttf';

function image_dashed_line($image, $x1, $y1, $x2, $y2, $style)
{
	if( sizeof($style) > 0 )
	{
		$temp_x1 = $x1;
		$temp_y1 = $y1;
		$count = 0;

		while( $temp_x1 < $x2 || $temp_y1 < $y2 )
		{
			$my_style = $style[$count % sizeof($style)];
			$step = 0;

			while( $my_style != $style[(($count + $step) % sizeof($style))] )
			{
				$step++;
			}

			if( $step == 0 )
			{
				$step++;
			}

			if( $x1 != $x2 )
			{
				if( $my_style != -1 )
				{
					imageline($image, $temp_x1, $y1, $temp_x1 + $step, $y2, $my_style);
				}

				$temp_x1 += $step;
			}

			if( $y1 != $y2 )
			{
				if( $my_style != -1 )
				{
					imageline($image, $x1, $temp_y1, $x2, $temp_y1 + $step, $my_style);
				}

				$temp_y1 += $step;
			}

			$count += $step;
		}
	}
	else
	{
		imageline($image, $x1, $y1, $x2, $y2, $style[0]);
	}
}

function draw_items($image, $bounds, $data_array, $max_index, $name, $dot, $make_grid, $write_timestamp, $write_legend, $color)
{
	global $bar_type, $legend_x, $max_pos_y, $server_load_type;
	static $first_entry = 0;

	$first_entry++;

	if( !isset($max_pos_y[$max_index]) )
	{
		$max_pos_y[$max_index] = 0;
	}

	foreach( $data_array as $row )
	{
		if( $row[$name] >= $max_pos_y[$max_index] )
		{
			if( $row[$name] < 100 )
			{
				$max_pos_y[$max_index] = $row[$name] + 2;
			}
			elseif( $row[$name] < 200 )
			{
				$max_pos_y[$max_index] = $row[$name] + 10;
			}
			elseif( $row[$name] < 10000 )
			{
				$max_pos_y[$max_index] = $row[$name] * 1.3;
			}
			else
			{
				$max_pos_y[$max_index] = $row[$name] - ($row[$name] % 50000) + 100000;
			}

			if( $make_grid > 0 )
			{
				if( $max_pos_y[$max_index] % 2 != 0 )
				{
					$max_pos_y[$max_index]++;
				}

				$i = 0;

				while( $i < 10 && $max_pos_y[$max_index] % 4 != 0 )
				{
					$max_pos_y[$max_index]++;
					$i++;
				}
			}

			if( $max_pos_y[$max_index] == 0 )
			{
				$max_pos_y[$max_index] = 1;
			}
		}
	}

	if( $write_legend > 0 )
	{
		/**
		* Легенда вверху
		*/
		if( $legend_x == 0 )
		{
			$legend_x = $bounds['indent_x'][0] + 10;
		}

		imagesetpixel($image, $legend_x,   $bounds['indent_y'][0] - 7, $color[0]);
		imagesetpixel($image, $legend_x+1, $bounds['indent_y'][0] - 7, $color[0]);
		imagesetpixel($image, $legend_x+2, $bounds['indent_y'][0] - 7, $color[0]);
		imagesetpixel($image, $legend_x,   $bounds['indent_y'][0] - 8, $color[0]);
		imagesetpixel($image, $legend_x+1, $bounds['indent_y'][0] - 8, $color[0]);
		imagesetpixel($image, $legend_x+2, $bounds['indent_y'][0] - 8, $color[0]);
		imagesetpixel($image, $legend_x,   $bounds['indent_y'][0] - 9, $color[0]);
		imagesetpixel($image, $legend_x+1, $bounds['indent_y'][0] - 9, $color[0]);
		imagesetpixel($image, $legend_x+2, $bounds['indent_y'][0] - 9, $color[0]);
		$legend_x += 7;
		imagestring($image, 1, $legend_x, $bounds['indent_y'][0] - 12, $name , $color[2]);
		$legend_x += (imagefontwidth(1) * strlen($name)) + 7;
	}

	$start_pos = array('x' => $bounds['width'] - $bounds['indent_x'][1], 'y' => $bounds['indent_y'][1]);

	$pos = $start_pos;
	$cache = array('x' => 0, 'y' => 0);

	$step_y = ($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) / 10;

	if( $step_y < 15 )
	{
		$step_y = 15;
	}

	$last_map = '';
	$last_map_posx = '';
	$bk_color = 0;

	global $gray_border;

	if( $first_entry == 1 && $make_grid > 0 && $server_load_type == 1 )
	{
		foreach( $data_array as $key => $row )
		{
			if( $row['map'] !== $last_map || $pos['x'] <= $bounds['indent_x'][0] + 1 )
			{
				if( $last_map == '' )
				{
					$last_map = $row['map'];
					$last_map_posx = $pos['x'];
				}
				else
				{
					$last_map = $row['map'];

					while( $last_map_posx > $pos['x'] )
					{
						if( $bk_color % 2 == 0 )
						{
							imageline($image, $last_map_posx, $bounds['indent_y'][0] + 1, $last_map_posx, $bounds['height'] - $bounds['indent_y'][1] - 1, $color[4]);
						}

						$last_map_posx--;
					}

					$bk_color++;

					imageline($image, $pos['x'] + 1, $bounds['indent_y'][0] + 1, $pos['x'] + 1, $bounds['height'] - $bounds['indent_y'][1] - 1, $gray_border);

					$last_map_posx = $pos['x'];
				}
			}

			$pos['x'] -= 3;

			if( $pos['x'] < $bounds['indent_x'][0] )
			{
				break;
			}
		}
	}

	if( $make_grid > 0 )
	{
		$step_diff = 0;
		$step_width = 0;

		while( $step_diff < 15 )
		{
			$step_width++;

			if( $max_pos_y[$max_index] % $step_width == 0 )
			{
				$steps = $max_pos_y[$max_index] / $step_width;

				if( $steps > 0 )
				{
					$step_diff = ($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) / $steps;
				}
				else
				{
					$step_diff = 15;
				}
			}
			else
			{
				$step_diff = 0;
			}
		}

		for( $i = 1; $i < $steps; $i++ )
		{
			$temp_y = (($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) - ((($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) / $max_pos_y[$max_index]) * ($i * $step_width))) + $bounds['indent_y'][0];

			if( $temp_y > $bounds['indent_y'][0] + 5 )
			{
				image_dashed_line($image, $bounds['indent_x'][0] + 1, $temp_y, ($bounds['width'] - $bounds['indent_x'][1] - 1), $temp_y, array($color[4], $color[4], $color[4], -1, -1 ,-1));
				imageline($image, $bounds['indent_x'][0] + 1, $temp_y, $bounds['indent_x'][0] + 4, $temp_y, $color[3]);

				$str = ( $max_pos_y[$max_index] > 10000 ) ? sprintf('%.0fk', ($i * $step_width) / 1000) : sprintf('%.0f', $i * $step_width);
				$str_pos = $bounds['indent_x'][0] - (imagefontwidth(1) * strlen($str)) - 2;

				imagestring($image, 1, $str_pos, $temp_y - 3, $str, $color[2]);
			}

			$str = ( $max_pos_y[$max_index] > 10000 ) ? sprintf('%.0fk', $max_pos_y[$max_index] / 1000) : sprintf('%.0f', $max_pos_y[$max_index]);
			$str_pos = $bounds['indent_x'][0] - (imagefontwidth(1) * strlen($str)) - 2;

			imagestring($image, 1, $str_pos, $bounds['indent_y'][0] - 3, $str, $color[2]);
		}
	}

	$last_month = 0;
	$last_month_timestamp = 0;
	$last_day = 0;
	$last_day_timestamp = 0;
	$first_day = 0;
	$first_timestamp = 0;

	switch( $server_load_type )
	{
		case 1:
		case 2: $mov_avg_precision = 5; break;
		case 3:
		case 4: $mov_avg_precision = 10; break;
		default: $mov_avg_precision = 1; break;
	}

	$mov_avg_array = array();
	$mov_avg_value = 0;
	$mov_avg_display_value = array();
	$i = 0;

	while( $i < sizeof($data_array) && $i < $mov_avg_precision / 2 )
	{
		$row = $data_array[$i];
		$mov_avg_array[] = (($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) - ((($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) / $max_pos_y[$max_index]) * $row[$name])) + $bounds['indent_y'][0];
		$mov_avg_display_value[] = $row[$name];
		$i++;
	}

	$last_map = '';
	$last_map_posx = 0;
	$bk_color = 0;

	foreach( $data_array as $key => $row )
	{
		$pos['y'] = (($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) - ((($bounds['height'] - $bounds['indent_y'][0] - $bounds['indent_y'][1]) / $max_pos_y[$max_index]) * $row[$name])) + $bounds['indent_y'][0];

		if( $first_entry == 2 && $server_load_type == 1 )
		{
			if( $row['map'] != $last_map )
			{
				if( $last_map == '' )
				{
					$last_map = $row['map'];
					$last_map_posx = $pos['x'];

					if( $row['map'] != '' )
					{
						$str_height = $bounds['indent_y'][0] + (imagefontwidth(1) * strlen($row['map'])) + 2;
						imagestringup($image, 1, $pos['x'] - 8, $str_height, $row['map'], $color[0]);
					}
				}
				else
				{
					$last_map = $row['map'];
					$str_height = $bounds['indent_y'][0] + (imagefontwidth(1) * strlen($row['map'])) + 2;
					imagestringup($image, 1, $pos['x'] - 8, $str_height, $row['map'], $color[0]);
					$last_map_posx = $pos['x'];
				}
			}
		}

		$mov_avg_array[] = $pos['y'];
		$mov_avg_value = $pos['y'];

		if( sizeof($mov_avg_array) > $mov_avg_precision )
		{
			array_shift($mov_avg_array);
		}

		$mov_avg_sum = 0;
		$mov_avg_display_sum = 0;

		foreach( $mov_avg_array as $entry )
		{
			$mov_avg_sum += $entry;
		}

		$mov_avg_value = sprintf('%d', ($mov_avg_sum / sizeof($mov_avg_array)));
		$pos['y'] = $mov_avg_value;

		if( $key > 0 && $name != 'max_players' )
		{
			/**
			* Основная линия
			*/
			imageline($image, $cache['x'], $cache['y'], $pos['x'], $pos['y'], $color[0]);
		}

		if( $key == 0 )
		{
			foreach( $mov_avg_display_value as $entry )
			{
				$mov_avg_display_sum += $entry;
			}

			$display_value = sprintf('%d', ($mov_avg_display_sum / sizeof($mov_avg_display_value)));

			if( $display_value > 10000 )
			{
				$str = sprintf('%.1fk', $display_value / 1000);
			}
			else
			{
				$str = sprintf('%.0f', $display_value);
			}

			if( $make_grid == 0 )
			{
				/**
				* Сноски справа
				*/
				imagestring($image, 1, $pos['x'] + 3, $pos['y'] - 4, $str, $color[2]);
			}
		}

		if( $first_timestamp == 0 )
		{
			$first_timestamp = $row['timestamp'];
		}

		$this_month = date('m', $row['timestamp']);

		if( $this_month > $last_month + 1 )
		{
			$last_month = $this_month + 1;
		}

		if( $last_month == 0 )
		{
			$last_month = $this_month;
			$last_month_timestamp = $row['timestamp'];
		}

		if( $last_month == $this_month )
		{
			$last_month_timestamp = $row['timestamp'];
		}

		$this_day = date('d', $row['timestamp']);

		if( $this_day > $last_day + 1 )
		{
			$last_day = $this_day + 1;
		}

		if( $last_day == 0 )
		{
			$last_day = $this_day;
			$last_day_timestamp = $row['timestamp'];
		}

		if( $last_day == $this_day )
		{
			$last_day_timestamp = $row['timestamp'];
		}

		switch( $server_load_type )
		{
			default:

				if( $write_timestamp && $key > 0 && $key % 12 == 0 )
				{
					global $font_file;
					/**
					* Метки времени внизу
					*/
					image_dashed_line($image, $pos['x'], $pos['y'], $pos['x'], $bounds['height'] - $bounds['indent_y'][1], array($color[1], $color[1], $color[1], -1, -1, -1));
					$str = date('H:i', $row['timestamp']);
					imagestring($image, 1, $pos['x'] - 10, $bounds['height'] - $bounds['indent_y'][1] + 3, $str, $color[2]);
					# $colortext = imagecolorallocate($image, mt_rand(0, 200), mt_rand(0, 200), mt_rand(0, 200));
					# imagettftext($image, 12, 0, $pos['x'] - 10, $bounds['height'] - $bounds['indent_y'][1] + 13, $colortext, $font_file, $str);
					# imagecolordeallocate($image, $colortext);
					# imagettftext($image, $font_size, $font_angle, $posx, $posy + rand(-2, +2), $colortext, $font_file, mb_substr($msg, $i, 1, 'utf-8'));
				}
				
			break;
		}

		if( $dot > 0 )
		{
			imagesetpixel($image, $pos['x'],   $pos['y'],   $color[0]);
			imagesetpixel($image, $pos['x']-1, $pos['y'],   $color[0]);
			imagesetpixel($image, $pos['x']-1, $pos['y']-1, $color[0]);
			imagesetpixel($image, $pos['x']-1, $pos['y']+1, $color[0]);
			imagesetpixel($image, $pos['x']+1, $pos['y'],   $color[0]);
			imagesetpixel($image, $pos['x']+1, $pos['y']-1, $color[0]);
			imagesetpixel($image, $pos['x']+1, $pos['y']+1, $color[0]);
			imagesetpixel($image, $pos['x'],   $pos['y']-1, $color[0]);
			imagesetpixel($image, $pos['x'],   $pos['y']+1, $color[0]);
		}
		else
		{
			if( $name != 'max_players' )
			{
				imagesetpixel($image, $pos['x'], $pos['y'], $color[0]);
			}
		}

		$cache['x'] = $pos['x'];
		$cache['y'] = $pos['y'];

		$step_x = 3;

		$pos['x'] -= $step_x;

		if( $pos['x'] < $bounds['indent_x'][0] )
		{
			break;
		}
	}
}

function hex2rgb($s = '')
{
	$s = preg_replace('#[^a-fA-F\d]#', '', $s);

	if( strlen($s) != 6 )
	{
		return false;
	}

	$ary = explode(' ', chunk_split($s, 2, ' '));
	$ary = array_map('hexdec', $ary);

	return array('red' => $ary[0], 'green' => $ary[1], 'blue' => $ary[2]);
}

$g_options = array(
	'graphbg_trend'		=> 'dbdad9',
	'graphgb_load'		=> 'abccd6',
	'graphtxt_trend'	=> '000000',
	'graphtxt_load'		=> '000000',
	'imgdir'			=> 'http://static.ivacuum.ru/i/games/cstrike/trend',
	'imgpath'			=> '/srv/www/vhosts/static.ivacuum.ru/i/games/cstrike/trend',
	'sig_font'			=> 'tahoma.ttf',
	'trendgraphfile'	=> 'trendgraph.png'
);

$bar_type			= 0;
$bg_color			= hex2rgb(getvar('bgcolor', 'fafafa'));
$bg_id				= $bg_color['red'] + $bg_color['green'] + $bg_color['blue'];
$color				= hex2rgb(getvar('color', '000000'));
$ctime				= time();
$game				= getvar('game', 'cstrike');
$height				= getvar('height', 200);
$player				= getvar('player', 1);
$server_id			= getvar('server_id', 1);
$server_load_type	= getvar('range', 1);
$web_dir			= '/srv/www/vhosts/static.ivacuum.ru/i/games/cstrike/trend';
$width				= getvar('width', 900);

switch( $server_load_type )
{
	case 1: $avg_step = 1; break;
	case 2: $avg_step = 7; break;
	case 3: $avg_step = 33; break;
	case 4: $avg_step = 400; break;
	default: $avg_step = 1; break;
}

$filename = sprintf('server_%d_%d_%d_%s_%d_%d.png', $width, $height, $bar_type, $game, $server_id, $server_load_type);

if( $bar_type != 2 )
{
	if( file_exists($web_dir . '/' . $filename) )
	{
		$file_timestamp = filemtime($web_dir . '/' . $filename);

		if( $ctime - $file_timestamp < ( 300 * $avg_step ) )
		{
			if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) )
			{
				$browser_timestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

				if( $ctime - $browser_timestamp < ( 300 * $avg_step ) )
				{
					header('Cache-Control: no-cache, pre-check=0, post-check=0');
					header('Expires: -1');
					header('Pragma: no-cache');
					header('HTTP/1.0 304 Not Modified');
					garbage_collection();
					exit;
				}
			}

			header('Cache-Control: no-cache, pre-check=0, post-check=0');
			header('Expires: -1');
			header('Pragma: no-cache');
			header('Last-Modified: ' . date('D, d M Y H:i:s \G\M\T', $file_timestamp));
			header('Location: http://static.ivacuum.ru/i/games/cstrike/trend/' . $filename);
			garbage_collection();
			exit;
		}
	}
}

$legend_x	= 0;
$max_pos_y	= array();

$image = imagecreatetruecolor($width, $height);
imagealphablending($image, false);
imagesavealpha($image, true);

if( function_exists('imageantialias') )
{
	imageantialias($image, true);
}

$drawbg = true;

$normal_color	= imagecolorallocate($image, 0xEF, 0xEF, 0xEF);
$light_color	= imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
$dark_color		= imagecolorallocate($image, 0x99, 0xAA, 0xAA);

$font_color		= imagecolorallocate($image, $color['red'], $color['green'], $color['blue']);
$main_color		= imagecolorallocate($image, $bg_color['red'], $bg_color['green'], $bg_color['blue']);

$blue			= imagecolorallocate($image, 0, 0, 255);
$black			= imagecolorallocate($image, 0, 0, 0);
$red			= imagecolorallocate($image, 255, 0, 0);
$white			= imagecolorallocate($image, 255, 255, 255);
$orange			= imagecolorallocate($image, 255, 165, 0);
$gray			= imagecolorallocate($image, 105, 105, 105);
$light_gray		= imagecolorallocate($image, 0xEF, 0xEF, 0xEF); 
$green			= imagecolorallocate($image, 255, 0, 255);
$gray_border	= imagecolorallocate($image, 0xE0, 0xE0, 0xE0);

if( $bar_type == 0 )
{
	$indent_x = array(30, 30);
	$indent_y = array(15, 15);

	if( $drawbg )
	{
		imagefilledrectangle($image, 0, 0, $width, $height, $main_color);
		imagerectangle($image, $indent_x[0], $indent_y[0], $width - $indent_x[1], $height - $indent_y[1], $dark_color);
		imagefilledrectangle($image, $indent_x[0] + 1, $indent_y[0] + 1, $width - $indent_x[1] - 1, $height - $indent_y[1] - 1, $light_color);
	}

	$limit = ( $avg_step < 10 ) ? ' LIMIT 0, 2500' : '';

	$data_array = array();
	$sql = '
		SELECT
			*
		FROM
			hlstats_server_load
		WHERE
			server_id = ' . $db->check_value($server_id) . '
		ORDER BY
			timestamp DESC
		' . $limit;
	$result = $db->query($sql);

	$i = 0;
	$avg_values = array();

	while( $row = $db->fetchrow($result) )
	{
		$i++;

		$avg_values[] = array(
			'timestamp'		=> $row['timestamp'],
			'act_players'	=> $row['act_players'],
			'min_players'	=> $row['min_players'],
			'max_players'	=> $row['max_players'],
			'map'			=> $row['map']
		);

		if( $i == $avg_step )
		{
			$insert_values = array();
			$insert_values['timestamp'] = $avg_values[ceil($avg_step / 2) - 1]['timestamp'];
			$insert_values['act_players'] = 0;
			$insert_values['min_players'] = 0;
			$insert_values['max_players'] = 0;
			$insert_values['map'] = '';

			foreach( $avg_values as $entry )
			{
				$insert_values['act_players'] += $entry['act_players'];
				$insert_values['min_players'] += $entry['min_players'];
				$insert_values['max_players'] += $entry['max_players'];
				$insert_values['map'] = $entry['map'];
			}

			$insert_values['act_players'] = round($insert_values['act_players'] / $avg_step);
			$insert_values['min_players'] = round($insert_values['min_players'] / $avg_step);
			$insert_values['max_players'] = round($insert_values['max_players'] / $avg_step);

			$data_array[] = $insert_values;
			$avg_values = array();
			$i = 0;
		}
	}

	$db->freeresult($result);

	$last_map = '';

	if( $avg_step == 1 )
	{
		$sql = '
			SELECT
				act_players,
				max_players
			FROM
				hlstats_Servers
			WHERE
				serverId = ' . $db->check_value($server_id);
		$result = $db->query($sql);
		$row = $db->fetchrow($result);
		$db->freeresult($result);

		array_unshift($data_array, array(
			'timestamp'		=> $ctime,
			'act_players'	=> $row['act_players'],
			'min_players'	=> $data_array[0]['min_players'],
			'max_players'	=> $row['max_players'],
			'map'			=> $last_map
		));
	}

	if( sizeof($data_array) > 1 )
	{
		$bounds = array('width' => $width, 'height' => $height, 'indent_x' => $indent_x, 'indent_y' => $indent_y);

		draw_items($image, $bounds, $data_array, 0, 'max_players', 0, 1, 0, 0, array($gray, $red, $font_color, $dark_color, $light_gray));
		draw_items($image, $bounds, $data_array, 0, 'min_players', 0, 0, 0, 0, array($dark_color, $red, $font_color, $dark_color));
		draw_items($image, $bounds, $data_array, 0, 'act_players', 0, 0, 1, 1, array($blue, $red, $font_color, $dark_color));
	}
}
elseif( $bar_type == 1 )
{
	$indent_x = array(35, 35);
	$indent_y = array(15, 15);

	if( $drawbg )
	{
		imagefilledrectangle($image, 0, 0, $width, $height, $main_color);
		imagerectangle($image, $indent_x[0], $indent_y[0], $width - $indent_x[1], $height - $indent_y[1], $dark_color);
		imagefilledrectangle($image, $indent_x[0] + 1, $indent_y[0] + 1, $width - $indent_x[1] - 1, $height - $indent_y[1] - 1, $light_color);
	}

	$data_array = array();

	$sql = '
		SELECT
			*
		FROM
			hlstats_Trend
		WHERE
			game = ' . $db->check_value('cstrike') . '
		ORDER BY
			timestamp DESC
		LIMIT
			0, 350';
	$result = $db->query($sql);

	while( $row = $db->fetchrow($result) )
	{
		$data_array[] = array(
			'timestamp'		=> $row['timestamp'],
			'players'		=> $row['players'],
			'kills'			=> $row['kills'],
			'headshots'		=> $row['headshots'],
			'act_slots'		=> $row['act_slots'],
			'max_slots'		=> $row['max_slots'],
			'map'			=> ''
		);
	}

	$db->freeresult($result);

	$sql = '
		SELECT
			COUNT(*) as total_players
		FROM
			hlstats_Players
		WHERE
			game = ' . $db->check_value('cstrike');
	$result = $db->query($sql);
	$row = $db->fetchrow($result);
	$db->freeresult($result);
	$total_players = $row['total_players'];

	if( sizeof($data_array) > 1 )
	{
		$bounds = array('width' => $width, 'height' => $height, 'indent_x' => $indent_x, 'indent_y' => $indent_y);

		draw_items($image, $bounds, $data_array, 0, 'kills', 0, 1, 0, 1, array($orange, $red, $font_color, $dark_color, $light_gray));
		draw_items($image, $bounds, $data_array, 0, 'kills', 0, 0, 0, 0, array($orange, $red, $font_color, $dark_color, $light_gray));
		draw_items($image, $bounds, $data_array, 0, 'headshots', 0, 0, 0, 1, array($dark_color, $red, $font_color, $dark_color, $light_gray));
		draw_items($image, $bounds, $data_array, 0, 'players', 0, 0, 0, 1, array($red, $red, $font_color, $dark_color, $light_gray));
	}
}

imagetruecolortopalette($image, 0, 65535);

//$bar_type = 2;

header('Content-Type: image/png');

if( $bar_type != 2 )
{
	imagepng($image, $web_dir . '/' . $filename);
	imagedestroy($image);
	chmod(0666, $web_dir . '/' . $filename);

	header('Cache-Control: no-cache, pre-check=0, post-check=0');
	header('Expires: -1');
	header('Pragma: no-cache');
	header('Last-Modified: ' . date('D, d M Y H:i:s \G\M\T', $ctime));
	header('Location: http://static.ivacuum.ru/i/games/cstrike/trend/' . $filename);
}
else
{
	imagepng($image);
	imagedestroy($image);
}

garbage_collection();
exit;

?>