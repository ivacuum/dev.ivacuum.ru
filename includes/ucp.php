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
		$this->template->assign('me', $this->user->data);
	}
	
	public function profile_post()
	{
		$user_first_name = $this->request->post('first_name', '');
		$user_last_name  = $this->request->post('last_name', '');
		$user_email      = mb_strtolower($this->request->post('email', ''));
		$user_icq        = $this->request->post('icq', '');
		$user_jid        = $this->request->post('jid', '');
		$user_website    = $this->request->post('website', '');
		$user_from       = $this->request->post('from', '');
		$user_occ        = $this->request->post('occ', '');
		$user_interests  = $this->request->post('interests', '');
		
		$error_ary = [];
		
		if (!$user_email)
		{
			$error_ary[] = 'Вы не указали адрес электронной почты';
		}
		if (!preg_match(sprintf('#%s#', get_preg_expression('email')), $user_email))
		{
			$error_ary[] = 'Неверно введен адрес электронной почты';
		}

		if (sizeof($error_ary))
		{
			$this->template->assign([
				'errors' => $error_ary,
				'me'     => $this->user->data,
			]);
			
			return;
		}
		
		$this->user->user_update(compact('user_first_name', 'user_last_name', 'user_email', 'user_icq', 'user_jid', 'user_website', 'user_from', 'user_occ', 'user_interests'));
		
		$this->template->assign([
			'me'     => $this->user->data,
			'status' => 'OK',
		]);
	}
}
