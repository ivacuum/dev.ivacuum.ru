<?php namespace app;

use app\models\page;

class news extends page
{
	protected $news;
	
	public function _setup()
	{
		parent::_setup();
		
		$this->news = $this->getApi('News', $this->data['site_id']);
	}
	
	/**
	* Список новостей
	*/
	public function index($year = false, $month = false, $day = false)
	{
		$on_page = $this->config['news.on_page'];
		
		foreach ($this->news->get(compact('year', 'month', 'day', 'on_page')) as $row) {
			$this->appendNews('news', $row);
		}
		
		foreach ($this->news->getMostDiscussed() as $row) {
			$this->appendNews('most_discussed_news', $row);
		}
		
		foreach ($this->news->getMostViewed() as $row) {
			$this->appendNews('most_viewed_news', $row);
		}
		
		/* Другие методы вызывают index, всем надо установить один шаблон */
		$this->template->file = 'news_index.html';
	}
	
	/**
	* Обратная совместимость
	*/
	public function bc()
	{
		preg_match('#^страница-(\d+)$#', $this->page, $matches);
		
		if (!empty($matches)) {
			$page = (int) $matches[1];
			
			$this->request->redirect($this->get_handler_url('index') . '?p=' . $page, 301);
		}
		
		preg_match(sprintf('#^(\d+)-(%s)$#', get_preg_expression('url_symbols')), $this->page, $matches);
		
		if (empty($matches)) {
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$news_id = (int) $matches[1];
		
		$sql = 'SELECT news_url, news_time FROM site_news WHERE news_id = ? AND site_id = ?';
		$this->db->query($sql, [$news_id, $this->data['site_id']]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row) {
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$params = [
			date('Y', $row['news_time']),
			date('m', $row['news_time']),
			date('d', $row['news_time']),
			$row['news_url']
		];
		
		$this->request->redirect($this->get_handler_url('single', $params), 301);
	}
	
	/**
	* Просмотр новостей за день
	*/
	public function day($year, $month, $day)
	{
		return $this->index($year, $month, $day);
	}
	
	/**
	* Просмотр новостей за месяц
	*/
	public function month($year, $month)
	{
		return $this->index($year, $month);
	}
	
	/**
	* Вывод одной новости
	*/
	public function single($year, $month, $day)
	{
		try {
			$row = $this->news->getByUrl($this->page, $year, $month, $day);
		} catch (\Exception $e) {
			trigger_error($e->getMessage());
		}
		
		$this->breadcrumbs($row['news_subject']);
		$this->appendNews('news', $row);
	}
	
	/**
	* Просмотр новостей за год
	*/
	public function year($year)
	{
		return $this->index($year);
	}
	
	/**
	* Передача новости шаблонизатору
	*/
	protected function appendNews($loop_name, &$row)
	{
		$this->template->append($loop_name, [
			'COMMENTS'  => $row['news_comments'],
			'DATE'      => $this->user->create_date($row['news_time']),
			'TEXT'      => prepare_text_for_print($row['news_text']),
			'TIME'      => $row['news_time'],
			'TITLE'     => $row['news_subject'],
			'VIEWS'     => $row['news_views'],
			'URL'       => $row['news_url'],
			'USER_ID'   => $row['user_id'],
			'USERNAME'  => $row['username'],
		]);
	}
}
