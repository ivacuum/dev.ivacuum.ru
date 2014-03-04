<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use app\models\page;

/**
* Программное обеспечение
*/
class soft extends page
{
	/**
	* Список ПО
	*/
	public function index()
	{
		foreach ($this->get_page_descendants() as $row) {
			$this->template->append('soft', [
				'COVER' => $row['page_url'],
				'TITLE' => $row['page_name'],

				'U_DETAIL' => $this->descendant_link($row),
			]);
		}
	}
}
