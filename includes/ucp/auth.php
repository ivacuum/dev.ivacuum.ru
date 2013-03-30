<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp;

use app\models\page;

/**
* Аутентификация
*/
class auth extends page
{
	public function _setup()
	{
		/* Поисковым роботам вход/выход недоступны */
		if ($this->user->is_bot)
		{
			$this->request->redirect(ilink(), 301);
		}
	}
	
	public function signin()
	{
		if ($this->user->is_registered)
		{
			$this->request->redirect(ilink());
		}
		
		$goto = $this->request->variable('goto', '');
		
		$this->template->assign('GOTO', $goto);
		
		/* get для вывода сообщения только при первой попытке входа */
		$login_explain = $this->request->get('goto', '') ? 'Для просмотра страницы необходимо авторизоваться.' : '';
		
		login_box($goto, $login_explain);
	}
	
	public function signout()
	{
		$close_sessions = $this->request->post('close_sessions', false);
		$redirect       = $this->request->variable('goto', $this->user->page_prev);
		
		if ($this->user->is_registered)
		{
			if ($close_sessions)
			{
				$this->user->reset_login_keys(false, false);
			}
			
			$this->user->session_end();
		}
		
		$this->request->redirect(ilink($redirect));
	}
}
