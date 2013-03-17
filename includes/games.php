<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use app\models\page;

/**
* Игры
*/
class games extends page
{
	/**
	* Список игр
	*/
	public function index()
	{
		foreach ($this->get_page_descendants() as $row)
		{
			$this->template->append('games', [
				'COVER' => $row['page_url'],
				'TITLE' => $row['page_name'],
		
				'U_DETAIL' => $this->descendant_link($row),
			]);
		}
	}
}
