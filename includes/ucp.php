<?php namespace app;

use app\models\page;

class ucp extends page
{
	public function _setup()
	{
		$this->user->is_auth('redirect');
		$this->append_menu('2nd_level_menu');
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
		
		if (!$old_password || !($this->user['user_salt'] && md5($old_password . $this->user['user_salt']) == $this->user['user_password']) || (!$this->user['user_salt'] && md5($old_password) == $this->user['user_password'])) {
			$error_ary[] = 'Текущий пароль введен неверно';
		}
		if (!$password || !$password_confirmation || mb_strlen($password) < 6 || mb_strlen($password) > 60) {
			$error_ary[] = 'Введите новый пароль от 6 до 60 символов';
		}
		if ($password != $password_confirmation) {
			$error_ary[] = 'Введенные пароли не совпадают';
		}
		
		if (sizeof($error_ary)) {
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
		$username        = $this->request->post('username', '');
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
		
		if (!$username || mb_strlen($username) < 3 || mb_strlen($username) > 30) {
			$error_ary[] = 'Введите логин от 3 до 30 символов';
		}
		if (!$user_email) {
			$error_ary[] = 'Вы не указали адрес электронной почты';
		}
		if (!preg_match(sprintf('#%s#', get_preg_expression('email')), $user_email)) {
			$error_ary[] = 'Неверно введен адрес электронной почты';
		}

		$username_clean = mb_strtolower($username);

		/* Проверка существования пользователя с подобным ником */
		if ($username_clean) {
			$sql = 'SELECT user_id FROM site_users WHERE username_clean = ? AND user_id != ?';
			$this->db->query($sql, [$username_clean, $this->user['user_id']]);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if ($row) {
				$error_ary[] = 'Данный логин уже занят';
			}
		}

		if ($user_email) {
			$sql = 'SELECT user_id FROM site_users WHERE user_email = ? AND user_id != ?';
			$this->db->query($sql, [$user_email, $this->user['user_id']]);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if ($row) {
				$error_ary[] = 'Данный адрес электронной почты уже зарегистрирован';
			}
		}

		if (sizeof($error_ary)) {
			$this->template->assign([
				'errors' => $error_ary,
				'me'     => $this->user->data,
			]);
			
			return;
		}
		
		$this->user->user_update(compact('username', 'username_clean', 'user_first_name', 'user_last_name', 'user_email', 'user_icq', 'user_jid', 'user_website', 'user_from', 'user_occ', 'user_interests'));
		
		$this->template->assign([
			'me'     => $this->user->data,
			'status' => 'OK',
		]);
	}

	public function social()
	{
		$sql = 'SELECT * FROM site_openid_identities WHERE user_id = ? ORDER BY openid_provider';
		$this->db->query($sql, [$this->user['user_id']]);
		
		while ($row = $this->db->fetchrow()) {
			$row['LAST_USE'] = $this->user->create_date($row['openid_last_use']);
			$row['U_DELETE'] = $this->append_link_params("provider={$row['openid_provider']}&uid={$row['openid_uid']}", $this->get_handler_url('social_delete'));
			
			$this->template->append('social', $row);
		}
		
		$this->db->freeresult();
	}
	
	public function social_delete()
	{
		$uid      = $this->request->variable('uid', '');
		$provider = $this->request->variable('provider', '');
		
		$sql = 'DELETE FROM site_openid_identities WHERE user_id = ? AND openid_uid = ? AND openid_provider = ?';
		$this->db->query($sql, [$this->user['user_id'], $uid, $provider]);
		
		$this->request->redirect(ilink($this->get_handler_url('social')));
	}
}
