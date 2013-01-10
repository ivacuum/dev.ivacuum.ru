<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use app\models\page;

/**
* Ресурсы
*/
class services extends page
{
	public function index()
	{
		if( $this->page != 'index' )
		{
			trigger_error('PAGE_NOT_FOUND');
		}
	}
}
