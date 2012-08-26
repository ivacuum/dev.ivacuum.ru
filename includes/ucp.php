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
* Панель управления пользователя
*/
class ucp extends page
{
	function __construct()
	{
		parent::__construct();

		/* Поисковым роботам панель недоступна */
		if( $this->user->is_bot )
		{
			redirect(ilink(), 301);
		}
	}
	
	public function index()
	{
		trigger_error('В разработке');
	}
	
	/**
	* Авторизация
	*/
	public function login()
	{
		if( $this->user->is_registered )
		{
			redirect(ilink());
		}
		
		$goto = $this->request->variable('goto', '');
		
		$this->template->assign(array(
			'GOTO' => $goto,
			
			'U_ACTION' => ilink($this->url)
		));
		
		$login_explain = '';
		
		if( $this->request->get('goto', '') )
		{
			$login_explain = 'Для просмотра страницы необходимо авторизоваться.';
		}
		
		login_box($goto, $login_explain);
	}
	
	/**
	* Выход
	*/
	public function logout()
	{
		$close_sessions = $this->request->post('close_sessions', false);
		$redirect       = $this->request->variable('goto', $this->user->page_prev);
		
		if( $this->user->is_registered )
		{
			if( $close_sessions )
			{
				$this->user->reset_login_keys(false, false);
			}
			
			$this->user->session_end();
		}
		
		redirect(ilink($redirect));
	}
}
