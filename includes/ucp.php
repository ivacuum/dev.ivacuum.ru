<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use app\models\page;

/**
* Панель управления пользователя
*/
class ucp extends page
{
	public function _setup()
	{
		$this->user->is_auth('redirect');
		$this->append_menu('ucp_menu');
	}
	
	public function index()
	{
	}
	
	public function password()
	{
	}
	
	public function profile()
	{
	}
}
