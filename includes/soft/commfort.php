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
		/* Чат доступен только из локальной сети */
		if ($this->request->isp == 'internet' && !$this->auth->acl_get('a_'))
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$this->set_site_submenu();
	}
	
	public function index()
	{
		
	}
	
	public function static_page()
	{
		$this->template->file = 'soft/commfort_static_page.html';
	}
}
