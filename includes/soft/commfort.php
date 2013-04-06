<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\soft;

use app\models\page;

/**
* Commfort-чат
*/
class commfort extends page
{
	public function _setup()
	{
		$this->append_menu('3rd_level_menu');
	}
	
	public function index()
	{
		$this->template->file = 'soft/commfort_static_page.html';
	}
	
	public function static_page()
	{
		$this->template->file = 'soft/commfort_static_page.html';
	}
}
