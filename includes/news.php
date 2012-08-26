<?php
/**
*
* @package ivacuum.ru
* @copyright (c) 2012
*
*/

namespace app;

use app\models\page;

/**
* Новостная лента
*/
class news extends page
{
	/**
	* Список новостей
	*/
	public function index($year = false, $month = false, $day = false)
	{
		$this->check_input_date($year, $month, $day);
		
		$on_page    = $this->config['news_on_page'];
		$pagination = pagination($on_page, $this->get_news_count($year, $month, $day), ilink($this->full_url));
		
		$sql_array = array(
			'SELECT'    => 'n.*, u.username, u.user_url, u.user_colour',
			'FROM'      => NEWS_TABLE . ' n',
			'LEFT_JOIN' => USERS_TABLE . ' u ON (u.user_id = n.user_id)',
			'WHERE'     => array('n.site_id = ' . $this->db->check_value($this->data['site_id'])),
			'ORDER_BY'  => 'n.news_time DESC'
		);
		
		/* Новости за определенный интервал времени */
		if( false !== $interval = $this->calculate_interval($year, $month, $day) )
		{
			$sql_array['WHERE'][] = sprintf('n.news_time BETWEEN %d AND %d', $interval['start'], $interval['end']);
		}
		
		$this->db->query_limit($this->db->build_query('SELECT', $sql_array), $pagination['on_page'], $pagination['offset']);
		
		while( $row = $this->db->fetchrow() )
		{
			/* /новости/2010/12/25/новость.html */
			$params = array(
				date('Y', $row['news_time']),
				date('m', $row['news_time']),
				date('d', $row['news_time']),
				$row['news_url']
			);
			
			$this->template->append('news', array(
				'AUTHOR'   => $this->user_profile_link('', $row['username'], $row['user_colour'], $row['user_url'], $row['user_id']),
				'COMMENTS' => plural($row['news_comments'], $this->user->lang['plural']['COMMENTS']),
				'TEXT'     => prepare_text_for_print($row['news_text']),
				'TIME'     => $this->user->create_date($row['news_time']),
				'TITLE'    => $row['news_subject'],
				'VIEWS'    => plural($row['news_views'], $this->user->lang['plural']['VIEWS']),
	
				'U_COMMENTS' => ilink($this->get_handler_url('display_single', $params))
			));
		}
	
		$this->db->freeresult();
		
		$this->template->file = 'news_index.html';
	}
	
	/**
	* Обратная совместимость
	*/
	public function bc()
	{
		preg_match('#^страница-(\d+)$#', $this->page, $matches);
		
		if( !empty($matches) )
		{
			$page = (int) $matches[1];
			
			redirect($this->get_handler_url('index') . '?p=' . $page, 301);
		}
		
		preg_match(sprintf('#^(\d+)-(%s)$#', get_preg_expression('url_symbols')), $this->page, $matches);
		
		if( empty($matches) )
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$news_id = (int) $matches[1];
		
		$sql = '
			SELECT
				news_url,
				news_time
			FROM
				' . NEWS_TABLE . '
			WHERE
				news_id = ' . $this->db->check_value($news_id) . '
			AND
				site_id = ' . $this->db->check_value($this->data['site_id']);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if( !$row )
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$params = array(
			date('Y', $row['news_time']),
			date('m', $row['news_time']),
			date('d', $row['news_time']),
			$row['news_url']
		);
		
		redirect($this->get_handler_url('display_single', $params), 301);
	}
	
	/**
	* Просмотр новостей за день
	*/
	public function day($year = false, $month = false, $day = false)
	{
		return $this->index($year, $month, $day);
	}

	/**
	* Вывод одной новости
	*/
	public function display_single($year = false, $month = false, $day = false)
	{
		/* Границы дня, в который была опубликована новости */
		if( false === checkdate($month, $day, $year) )
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$day_start = mktime(0, 0, 0, $month, $day, $year);
		$day_end   = mktime(0, 0, 0, $month, $day + 1, $year) - 1;
		
		/**
		* Проверяем существование новости
		* Если новости нет, то выводим ошибку
		*/
		$sql = '
			SELECT
				n.*,
				u.username,
				u.user_url,
				u.user_colour
			FROM
				' . NEWS_TABLE . ' n
			LEFT JOIN
				' . USERS_TABLE . ' u ON (u.user_id = n.user_id)
			WHERE
				n.news_time BETWEEN ' . $day_start . ' AND ' . $day_end . '
			AND
				n.news_url = ' . $this->db->check_value($this->page);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
	
		if( !$row )
		{
			trigger_error('NEWS_NOT_FOUND');
		}
	
		$this->template->vars(array(
			'AUTHOR'   => $this->user_profile_link('', $row['username'], $row['user_colour'], $row['user_url'], $row['news_author_id']),
			'COMMENTS' => plural($row['news_comments'], $this->user->lang['plural']['COMMENTS']),
			'TEXT'     => prepare_text_for_print($row['news_text']),
			'TITLE'    => $row['news_subject'],
			'TIME'     => $this->user->create_date($row['news_time']),
			'USERNAME' => ( $this->user->is_registered ) ? $this->user_profile_link('plain', $this->user['username'], $this->user['user_colour']) : '',
			'VIEWS'    => plural($row['news_views'] + 1, $this->user->lang['plural']['VIEWS']))
		);
		
		$this->template->file = 'news_detail.html';
	}
	
	/**
	* Просмотр новостей за месяц
	*/
	public function month($year = false, $month = false)
	{
		return $this->index($year, $month);
	}
	
	/**
	* Просмотр новостей за год
	*/
	public function year($year = false)
	{
		return $this->index($year);
	}
	
	/**
	* Интервал времени для SQL-запроса
	*/
	protected function calculate_interval($year = false, $month = false, $day = false)
	{
		if( !$year && !$month && !$day )
		{
			return false;
		}
		
		/* Новости за день */
		if( $year && $month && $day )
		{
			return array(
				'start' => mktime(0, 0, 0, $month, $day, $year),
				'end'   => mktime(0, 0, 0, $month, $day + 1, $year) - 1
			);
		}
		
		/* Новости за месяц */
		if( $year && $month )
		{
			return array(
				'start' => mktime(0, 0, 0, $month, 1, $year),
				'end'   => mktime(0, 0, 0, $month + 1, 1, $year) - 1
			);
		}
		
		/* Новости за год */
		if( $year )
		{
			return array(
				'start' => mktime(0, 0, 0, 1, 1, $year),
				'end'   => mktime(0, 0, 0, 1, 1, $year + 1) - 1
			);
		}
	}

	/**
	* Проверка даты на корректность
	*/
	protected function check_input_date($year = false, $month = false, $day = false)
	{
		if( $year && $month && $day )
		{
			/* Новости за день */
			if( false === checkdate($month, $day, $year) )
			{
				trigger_error('PAGE_NOT_FOUND');
			}
		}
		elseif( $year && $month )
		{
			/* Новости за месяц */
			if( false === checkdate($month, 1, $year) )
			{
				trigger_error('PAGE_NOT_FOUND');
			}
		}
		elseif( $year )
		{
			/* Новости за год */
			if( false === checkdate(1, 1, $year) )
			{
				trigger_error('PAGE_NOT_FOUND');
			}
		}
	}
	
	/**
	* Количество новостей на языке сайта
	*/
	protected function get_news_count($year = false, $month = false, $day = false)
	{
		if( !$year && !$month && !$day )
		{
			return $this->config['num_news'];
		}
		
		$sql_array = array(
			'SELECT' => 'COUNT(*) AS total',
			'FROM'   => NEWS_TABLE,
			'WHERE'  => array('site_id = ' . $this->data['site_id']),
		);
		
		$interval = $this->calculate_interval($year, $month, $day);
		$sql_array['WHERE'][] = sprintf('news_time BETWEEN %d AND %d', $interval['start'], $interval['end']);
		
		$this->db->query($this->db->build_query('SELECT', $sql_array));
		$total_news = $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total_news;
	}
}
