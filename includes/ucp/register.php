<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp;

use app\models\page;

class register extends page
{
	public function index()
	{
		if ($this->user->is_registered) {
			$this->request->redirect(ilink());
		}
	}
	
	/**
	* Выполнение регистрации
	*/
	public function index_post()
	{
		$username      = $this->request->post('username', '');
		$user_email    = mb_strtolower($this->request->post('email', ''));
		$user_password = $this->request->post('password', '');
		
		$error_ary = [];
		
		if (!$username || mb_strlen($username) < 3 || mb_strlen($username) > 30) {
			$error_ary[] = 'Введите логин от 3 до 30 символов';
		}
		
		if (!$user_email) {
			$error_ary[] = 'Вы не указали адрес электронной почты';
		} elseif (!preg_match(sprintf('#%s#', get_preg_expression('email')), $user_email)) {
			$error_ary[] = 'Неверно введен адрес электронной почты';
		}
		
		if (!$user_password || mb_strlen($user_password) < 6 || mb_strlen($user_password) > 60) {
			$error_ary[] = 'Введите пароль от 6 до 60 символов';
		}
		
		if (!$this->captcha_validator->is_solved()) {
			$error_ary[] = 'Неверно введен код подтверждения';
		}
		
		$username_clean = mb_strtolower($username);

		/* Проверка существования пользователя с подобным ником */
		if ($username_clean) {
			$sql = 'SELECT user_id FROM site_users WHERE username_clean = ?';
			$this->db->query($sql, [$username_clean]);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if ($row) {
				$error_ary[] = 'Данный логин уже занят';
				
				$username = '';
			}
		}
		
		if ($user_email) {
			$sql = 'SELECT user_id FROM site_users WHERE user_email = ?';
			$this->db->query($sql, [$user_email]);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if ($row) {
				$error_ary[] = 'Данный адрес электронной почты уже зарегистрирован';
				
				$user_email = '';
			}
		}
		
		if (sizeof($error_ary)) {
			$this->template->assign([
				'errors' => $error_ary,
				'me'     => compact('user_email', 'username'),
			]);
			
			return;
		}
		
		$salt = make_random_string(5);
		
		$sql_ary = array_merge([
			'user_password'  => md5($user_password . $salt),
			'user_salt'      => $salt,
			'user_regdate'   => $this->request->time,
			'user_language'  => $this->request->language,
		], compact('username', 'username_clean', 'user_email'));
		
		$sql = 'INSERT INTO site_users ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);

		/* Обновление последнего зарегистрированного пользователя */
		$this->config->set('newest_user_id', $this->db->insert_id(), 0);
		$this->config->set('newest_username', $username, 0);
		$this->config->increment('num_users', 1, 0);
		
		$this->auth->login($username, $user_password);
		$this->request->redirect(ilink());
	}
	
	/**
	* Завершение регистрации
	* Вызывается при добавлении гостем социального профиля
	*/
	public function complete()
	{
		if (empty($_SESSION['oauth.saved'])) {
			$this->request->redirect(ilink());
		}
		
		$email    = $_SESSION['oauth.saved']['email'];
		$redirect = !empty($_SESSION['request.redirect']) ? $_SESSION['request.redirect'] : '';
		
		$sql = 'SELECT user_id FROM site_users WHERE user_email = ?';
		$this->db->query($sql, [$email]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		$this->template->assign([
			'email_exists' => !empty($row),
			'redirect'     => $redirect,
			'saved_email'  => $email,
		]);
	}
	
	public function complete_post()
	{
		if (empty($_SESSION['oauth.saved'])) {
			$this->request->redirect(ilink());
		}

		$have_login        = $this->request->is_set_post('have_login');
		$password          = $this->request->post('password', '');
		$redirect          = !empty($_SESSION['request.redirect']) ? $_SESSION['request.redirect'] : '';
		$register          = $this->request->is_set_post('register');
		$user_email        = mb_strtolower($this->request->post('email', ''));
		$username_or_email = $this->request->post('username', '');

		if ($have_login) {
			$result = $this->auth->login($username_or_email, $password);

			if ($result['status'] == 'OK') {
				$this->request->redirect(ilink($redirect));
			}
		} else {
			$error_ary = [];
			
			if (!$user_email) {
				$error_ary[] = 'Вы не указали адрес электронной почты';
			} elseif (!preg_match(sprintf('#%s#', get_preg_expression('email')), $user_email)) {
				$error_ary[] = 'Неверно введен адрес электронной почты';
			}

			$sql = 'SELECT user_id FROM site_users WHERE user_email = ?';
			$this->db->query($sql, [$user_email]);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if ($row) {
				$error_ary[] = 'Данный адрес электронной почты уже зарегистрирован';
			}

			if (sizeof($error_ary)) {
				$this->template->assign([
					'email_exists' => true,
					'errors'       => $error_ary,
					'saved_email'  => $_SESSION['oauth.saved']['email'],
				]);
				
				return;
			}
			
			$salt = make_random_string(5);
			$username = $username_clean = $user_email;
		
			$sql_ary = array_merge([
				'user_password'  => '',
				'user_salt'      => $salt,
				'user_regdate'   => $this->request->time,
				'user_language'  => $this->request->language,
			], compact('username', 'username_clean', 'user_email'));
		
			$sql = 'INSERT INTO site_users ' . $this->db->build_array('INSERT', $sql_ary);
			$this->db->query($sql);
			
			$user_id = $this->db->insert_id();

			/* Обновление последнего зарегистрированного пользователя */
			$this->config->set('newest_user_id', $user_id, 0);
			$this->config->set('newest_username', $username, 0);
			$this->config->increment('num_users', 1, 0);
			
			/* Привязывание социальной учетки */
			$openid_provider = $_SESSION['oauth.saved']['provider'];
			$openid_uid      = $_SESSION['oauth.saved']['uid'];
			
			$sql = 'UPDATE site_openid_identities SET user_id = ? WHERE openid_uid = ? AND openid_provider = ? AND user_id = 0';
			$this->db->query($sql, [$user_id, $openid_uid, $openid_provider]);
			
			$this->user->session_end(false);
			$this->user->session_create(false, $user_id, true, false, $openid_provider);
			$this->request->redirect(ilink($redirect));
		}
	}
}
