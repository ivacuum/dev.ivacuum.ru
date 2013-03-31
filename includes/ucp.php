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
	
	public function password_post()
	{
		$old_password          = $this->request->post('old_password', '');
		$password              = $this->request->post('password', '');
		$password_confirmation = $this->request->post('password_confirmation', '');
		
		$error_ary = [];
		
		if (!$old_password || !($this->user['user_salt'] && md5($old_password . $this->user['user_salt']) == $this->user['user_password']) || (!$this->user['user_salt'] && md5($old_password) == $this->user['user_password']))
		{
			$error_ary[] = 'Текущий пароль введен неверно';
		}
		if (!$password || !$password_confirmation || mb_strlen($password) < 6 || mb_strlen($password) > 60)
		{
			$error_ary[] = 'Введите новый пароль от 6 до 60 символов';
		}
		if ($password != $password_confirmation)
		{
			$error_ary[] = 'Введенные пароли не совпадают';
		}
		
		if (sizeof($error_ary))
		{
			$this->template->assign('errors', $error_ary);
			return;
		}
		
		$salt = make_random_string(5);
		
		$this->user->user_update([
			'user_password' => md5($password . $salt),
			'user_salt'     => $salt,
		]);
		
		$this->user->reset_login_keys(false, false);
		$this->template->assign('status', 'OK');
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
