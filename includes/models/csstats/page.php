<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app\models\csstats;

use app\models\page as base_page;

class page extends base_page
{
	protected $csstats_db;
	
	function __construct()
	{
		global $csstats_dbname, $csstats_dbuser, $csstast_dbpass, $dbhost, $dbport, $dbsock, $dbpers;
		
		parent::__construct();
		
		$this->csstats_db = new \engine\db\mysqli($dbhost, $csstats_dbuser, $csstats_dbpass, $csstats_dbname, $dbport, $dbsock, $dbpers);
	}
	
	/**
	* Предустановки
	*/
	public function _setup()
	{
		$this->set_site_submenu();
	}
	
	/**
	* Ссылка на сортировку колонки
	*/
	public function create_sort_link($sk, $params = '', $url = '')
	{
		global $pagination, $sort_dir, $sort_key;

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
			return ilink(sprintf('%s?p=%d&amp;sd=%s&amp;sk=%s%s', $this->url, $pagination['p'], $sd, $sk, $params));
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
	public function get_time_played($time)
	{
		return ( $time > 0 ) ? sprintf('%02d:%02d:%02d', floor($time / 3600), floor(($time % 3600) / 60), floor($time % 60)) : '---';
	}
	
	/**
	* Посетитель из локальной сети провайдера Спарк или из интернета?
	*
	* @param	string	$ip		IP-адрес посетителя
	*
	* @return	int				0 - интернет, 1 - спарк-калуга, 2 - спарк других городов
	*/
	public function is_spark($ip)
	{
		list($ip1, $ip2, $ip3, $ip4) = explode('.', $ip);

		if( $ip1 == 10 )
		{
			if( $ip2 == 161 || $ip2 == 162 || $ip2 == 163 || $ip2 == 164 || $ip2 == 165 )
			{
				/* Спарк-Калуга */
				return 1;
			}
			else
			{
				/* Спарк других городов */
				return 2;
			}
		}
		elseif( $ip1 == 86 && $ip2 == 110 )
		{
			if( $ip3 == 162 || $ip3 == 163 || $ip3 == 164 || $ip3 = 172 )
			{
				/* Спарк-Калуга */
				return 1;
			}
		}

		return 0;
	}

	/**
	* Возвращает долю от общего количества
	*
	* @param	int	$value	Текущее значение
	* @param	int	$total	Общее количество
	*
	* @return	int			Доля текущего значения от общего количества
	*/
	public function num_percent_of($value, $total)
	{
		return ( $total > 0 && $value ) ? sprintf('%d%%', ($value / $total) * 100) : '-';
	}
	
	public function page_footer()
	{
		$this->csstats_db->close();
		
		parent::page_footer();
	}
}
