<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
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
		$rows = $this->get_page_descendants();
		
		foreach( $rows as $row )
		{
			$this->template->append('soft', array(
				'COVER' => $row['page_url'],
				'TITLE' => $row['page_name'],

				'U_DETAIL' => $this->descendant_link($row)
			));
		}
	}
}
