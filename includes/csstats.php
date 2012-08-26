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
* Ссылка на сортировку колонки
*/
function create_sort_link($sk, $params = '', $url = '')
{
	global $mode, $page, $pagination, $sort_dir, $sort_key;

	$sd = $sort_dir;

	if( $sort_key == $sk )
	{
		$sd = ( $sd == 'd' ) ? 'a' : 'd';
	}
	else
	{
		$sd = 'd';
	}

	if( $params )
	{
		$params = '&amp;' . $params;
	}

	if( !$url )
	{
		return ilink(sprintf('%s/%s.html?p=%d&amp;sd=%s&amp;sk=%s%s', $page['furl'], $mode, $pagination['p'], $sd, $sk, $params));
	}
	else
	{
		return ilink(sprintf('%s?p=%d&amp;sd=%s&amp;sk=%s%s', $url, $pagination['p'], $sd, $sk, $params));
	}
}

/**
* Возвращает преобразованное к формату сыгранное время
*
* 86399 -> 23:59:59
*
* @param	int	$time	Время
*
* @return	int			Преобразованное к формату игровое время
*/
function get_time_played($time)
{
	return ( $time > 0 ) ? sprintf('%02d:%02d:%02d', floor($time / 3600), floor(($time % 3600) / 60), floor($time % 60)) : '---';
}

/**
* Возвращает долю от общего количества
*
* @param	int	$value	Текущее значение
* @param	int	$total	Общее количество
*
* @return	int			Доля текущего значения от общего количества
*/
function num_percent_of($value, $total)
{
	return ( $total > 0 && $value ) ? sprintf('%d%%', ($value / $total) * 100) : '-';
}

/**
* Обработка ЧПУ
*/
$sections = substr_count($path, '/');

if( $sections === 1 )
{
	/**
	* /csstats/mapinfo/de_dust.html
	* /csstats/playerinfo/521.html
	*/
	preg_match('#^([a-z]+)/([A-Za-z\d\_\-\.]+).html$#', $path, $matches);

	if( !empty($matches) )
	{
		$_REQUEST['mode'] = $matches[1];
		$_REQUEST['url']  = $matches[2];
	}
}
elseif( !$sections )
{
	/**
	* /csstats/actions.html
	* /csstats/chat.html?p=10
	* /csstats/players.html
	*/
	preg_match('#^([a-z]+).html$#', $path, $matches);

	if( !empty($matches) )
	{
		$_REQUEST['mode'] = $matches[1];
	}
}

/**
* Определяем переменные
*/
$action   = getvar('action', '');
$mode     = getvar('mode', '');
$sort_dir = getvar('sd', 'd');
$sort_key = getvar('sk', 'a');
$submit   = ( isset($_POST['submit']) ) ? true : false;
$url      = getvar('url', '');

/* Навигационная цепочка */
navigation_link();

$template->vars(array(
	'U_ACTIONS'        => ilink(sprintf('%s/actions.html', $page['furl'])),
	'U_AWARDS'         => ilink(sprintf('%s/awards.html', $page['furl'])),
	'U_BANLIST'        => ilink(sprintf('%s/banlist.html', $page['furl'])),
	'U_CHAT'           => ilink(sprintf('%s/chat.html', $page['furl'])),
	'U_CSSTATS_FAQ'    => ilink(sprintf('%s/faq.html', $page['furl'])),
	'U_CSSTATS_SEARCH' => ilink(sprintf('%s/search.html', $page['furl'])),
	'U_HISTORY'        => ilink(sprintf('%s/history.html', $page['furl'])),
	'U_MANAGE'         => ilink(sprintf('%s/manage.html', $page['furl'])),
	'U_MAPS'           => ilink(sprintf('%s/maps.html', $page['furl'])),
	'U_PLAYERS'        => ilink(sprintf('%s/players.html', $page['furl'])),
	'U_RANKS'          => ilink(sprintf('%s/ranks.html', $page['furl'])),
	'U_SERVERS'        => ilink($page['furl']),
	'U_WEAPONS'        => ilink(sprintf('%s/weapons.html', $page['furl'])))
);

if( $mode != 'faq' )
{
	$csstats_db = new db_mysqli();
	$csstats_db->connect($dbhost, 'csstats', 'dfHYpWSR9T23vQtY', 'csstats', $dbport, $dbsock);
}

switch( $mode )
{
	case 'actioninfo':
	case 'actions':
	case 'banlist':
	case 'chat':
	case 'faq':
	case 'history':
	case 'killingstreaks':
	case 'manage':
	case 'mapinfo':
	case 'maps':
	case 'playerinfo':
	case 'players':
	case 'rankinfo':
	case 'ranks':
	case 'search':
	case 'serverinfo':
	case 'today':
	case 'weaponinfo':
	case 'weapons':

		include($site_root_path . 'includes/csstats/' . $mode . '.php');

	break;
	default:

		include($site_root_path . 'includes/csstats/index.php');

	break;
}

?>
