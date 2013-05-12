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
		/* Поисковым роботам недоступен данный раздел */
		if ($this->user->is_bot)
		{
			$this->request->redirect(ilink(), 301);
		}
	}
	
	public function activate_password($hash)
	{
		$this->template->assign('me', ['user_newpasswd' => $hash]);
	}
	
	public function activate_password_post($hash)
	{
		$sql = 'SELECT user_id, username FROM site_users WHERE user_newpasswd = ?';
		$this->db->query($sql, [$hash]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row)
		{
			trigger_error('Код не найден. Попробуйте <a href="' . $this->get_handler_url('sendpassword') . '">восстановить пароль</a> еще раз.');
		}

		$user_password = $this->request->post('password', '');
		
		$error_ary = [];

		if (!$user_password || mb_strlen($user_password) < 6 || mb_strlen($user_password) > 60)
		{
			$error_ary[] = 'Введите пароль от 6 до 60 символов';
		}

		if (sizeof($error_ary))
		{
			$this->template->assign([
				'errors' => $error_ary,
				'me'     => ['user_newpasswd' => $hash],
			]);
			
			return;
		}
		
		$salt = make_random_string(5);
		
		$sql_ary = [
			'user_password'  => md5($user_password . $salt),
			'user_salt'      => $salt,
			'user_newpasswd' => '',
		];
		
		$this->user->user_update($sql_ary, $row['user_id']);
		$this->user->reset_login_keys($row['user_id'], false);
		$this->auth->login($row['username'], $user_password);
		$this->request->redirect(ilink());
	}
	
	public function sendpassword()
	{
		if ($this->user->is_registered)
		{
			$username       = $this->user['username'];
			$user_email     = $this->user['user_email'];
			$user_newpasswd = md5(microtime(true));
		
			$this->user->user_update(compact('user_newpasswd'));

			$this->mailer->set_to($user_email)->postpone($this->data['page_name']);
		
			$this->template->assign([
				'me'     => compact('username', 'user_email', 'user_newpasswd'),
				'status' => 'OK',
			]);
		}
	}
	
	public function sendpassword_post()
	{
		if ($this->user->is_registered)
		{
			return;
		}
		
		$user_email = mb_strtolower($this->request->post('email', ''));
		
		$error_ary = [];

		if (!$user_email)
		{
			$error_ary[] = 'Вы не указали адрес электронной почты';
		}
		elseif (!preg_match(sprintf('#%s#', get_preg_expression('email')), $user_email))
		{
			$error_ary[] = 'Неверно введен адрес электронной почты';
			$user_email = '';
		}

		if (sizeof($error_ary))
		{
			$this->template->assign('errors', $error_ary);
			
			return;
		}
		
		$sql = 'SELECT user_id, username, user_salt FROM site_users WHERE user_email = ?';
		$this->db->query($sql, [$user_email]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row)
		{
			$error_ary[] = 'Адрес электронной почты не найден';
			
			$user_email = '';
		}
		
		if (sizeof($error_ary))
		{
			$this->template->assign('errors', $error_ary);
			
			return;
		}
		
		$username       = $row['username'];
		$user_newpasswd = md5(microtime(true));
		
		$this->user->user_update(compact('user_newpasswd'), $row['user_id']);

		$this->mailer->set_to($user_email)->postpone($this->data['page_name']);
		
		$this->template->assign([
			'me'     => compact('username', 'user_email', 'user_newpasswd'),
			'status' => 'OK',
		]);
	}

	public function signin()
	{
		if ($this->user->is_registered)
		{
			$this->request->redirect(ilink());
		}
		
		$goto = $this->request->variable('goto', '');
		$_SESSION['request.redirect'] = $goto;
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
