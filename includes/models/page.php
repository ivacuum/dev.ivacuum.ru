<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\models;

use fw\models\page as base_page;

class page extends base_page
{
	public function page_header()
	{
		parent::page_header();
		
		$this->template->assign([
			'U_LOCAL' => ilink('', 'http://local.ivacuum.ru'),
		]);
	}
}
