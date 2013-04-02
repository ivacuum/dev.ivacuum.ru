<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp;

use app\models\page;

class register extends page
{
	public $openid_response;
	
	public function index()
	{
		if ($this->user->is_registered)
		{
			$this->request->redirect(ilink());
		}
		
		$this->template->assign('U_ACTION', ilink($this->url));
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
		
		if (!$username || mb_strlen($username) < 3 || mb_strlen($username) > 30)
		{
			$error_ary[] = 'Введите логин от 3 до 30 символов';
		}
		if (!$user_email)
		{
			$error_ary[] = 'Вы не указали адрес электронной почты';
		}
		elseif (!preg_match(sprintf('#%s#', get_preg_expression('email')), $user_email))
		{
			$error_ary[] = 'Неверно введен адрес электронной почты';
		}
		if (!$user_password || mb_strlen($user_password) < 6 || mb_strlen($user_password) > 60)
		{
			$error_ary[] = 'Введите пароль от 6 до 60 символов';
		}
		if (!$this->captcha_validator->is_solved())
		{
			$error_ary[] = 'Неверно введен код подтверждения';
		}
		
		$username_clean = mb_strtolower($username);

		/* Проверка существования пользователя с подобным ником */
		if ($username_clean)
		{
			$sql = '
				SELECT
					user_id
				FROM
					' . USERS_TABLE . '
				WHERE
					username_clean = ' . $this->db->check_value($username_clean);
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if ($row)
			{
				$error_ary[] = 'Данный логин уже занят';
				
				$username = '';
			}
		}
		
		if ($user_email)
		{
			$sql = '
				SELECT
					user_id
				FROM
					' . USERS_TABLE . '
				WHERE
					user_email = ' . $this->db->check_value($user_email);
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if ($row)
			{
				$error_ary[] = 'Данный адрес электронной почты уже зарегистрирован';
				
				$user_email = '';
			}
		}
		
		if (sizeof($error_ary))
		{
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
			'user_regdate'   => $this->user->ctime,
			'user_language'  => $this->request->language,
		], compact('username', 'username_clean', 'user_email'));
		
		$sql = 'INSERT INTO ' . USERS_TABLE . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);

		/* Обновление последнего зарегистрированного пользователя */
		$this->config->set('newest_user_id', $this->db->insert_id(), 0);
		$this->config->set('newest_username', $username, 0);
		$this->config->increment('num_users', 1, 0);
		
		$this->auth->login($username, $user_password);
		$this->request->redirect(ilink());
	}
	
	/**
	* OpenID-регистрация
	*/
	public function openid_post()
	{
		$token = $this->request->post('token', '');
		
		if (!$token)
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$url = 'http://loginza.ru/api/authinfo?' . http_build_query([
			'token' => $token,
			'id'    => $this->config['loginza_id'],
			'sig'   => md5($token . $this->config['loginza_secret'])
		]);

		$this->openid_response = json_decode(file_get_contents($url), true);
		
		$this->profiler->log($this->openid_response);
		
		$birth_day   = 0;
		$birth_month = 0;
		$birth_year  = 0;
		$email       = isset($this->openid_response['email']) ? $this->openid_response['email'] : '';
		$first_name  = isset($this->openid_response['name']['first_name']) ? $this->openid_response['name']['first_name'] : '';
		$full_name   = $this->get_openid_user_full_name();
		$gender      = $this->get_openid_user_gender();
		$identity    = $this->openid_response['identity'];
		$last_name   = isset($this->openid_response['name']['last_name']) ? $this->openid_response['name']['last_name'] : '';
		$provider    = $this->openid_response['provider'];
		$uid         = isset($this->openid_response['uid']) ? $this->openid_response['uid'] : '';
		$username    = $this->get_openid_username();
		$website     = $this->get_openid_user_website();
		
		if (isset($this->openid_response['dob']))
		{
			/* Дата рождения */
			$ary = explode('-', $this->openid_response['dob']);
			
			$birth_year  = $ary[0];
			$birth_month = $ary[1];
			$birth_day   = $ary[2];
		}
		
		if (false === $user_id = $this->get_openid_user_id())
		{
			/* Новые OpenID данные */
			$user_id = 0;
			
			$sql_ary = [
				'user_id'           => $user_id,
				'openid_time'       => $this->user->ctime,
				'openid_provider'   => $provider,
				'openid_uid'        => $uid,
				'openid_identity'   => $identity,
				'openid_full_name'  => $full_name,
				'openid_first_name' => $first_name,
				'openid_last_name'  => $last_name,
				'openid_dob'        => isset($this->openid_response['dob']) ? $this->openid_response['dob'] : '',
				'openid_gender'     => isset($this->openid_response['gender']) ? $this->openid_response['gender'] : '',
				'openid_email'      => $email,
				'openid_website'    => $website,
				'openid_photo'      => isset($this->openid_response['photo']) ? $this->openid_response['photo'] : ''
			];
			
			$sql = 'INSERT INTO ' . OPENID_IDENTITIES_TABLE . ' ' . $this->db->build_array('INSERT', $sql_ary);
			$this->db->query($sql);
		}
		
		if ($user_id > 0)
		{
			/* Авторизация прошла успешно */
			$this->user->session_create($user_id, true, false, true, $this->get_openid_provider());
			
			$this->request->redirect(ilink(''));
		}
		
		$s_hidden_fields = build_hidden_fields([
			'birth_day'   => $birth_day,
			'birth_month' => $birth_month,
			'birth_year'  => $birth_year,
			'first_name'  => $first_name,
			'gender'      => $gender,
			'identity'    => $identity,
			'last_name'   => $last_name,
			'provider'    => $provider,
		]);

		// $this->template->file = 'ucp/register_index.html';
	}
	
	/**
	* Провайдер авторизации
	*/
	private function get_openid_provider()
	{
		$ary = parse_url($this->openid_response['provider']);
		
		if (empty($ary))
		{
			return false;
		}
		
		/* Список провайдеров */
		$providers = [
			0 => ['needle' => 'google.com',       'return' => 'google'],
			1 => ['needle' => 'yandex.ru',        'return' => 'yandex'],
			2 => ['needle' => 'rambler.ru',       'return' => 'rambler'],
			3 => ['needle' => 'vkontakte.ru',     'return' => 'vk'],
			4 => ['needle' => 'facebook.com',     'return' => 'facebook'],
			5 => ['needle' => 'odnoklassniki.ru', 'return' => 'odnoklassniki'],
			6 => ['needle' => 'twitter.com',      'return' => 'twitter'],
			7 => ['needle' => 'mail.ru',          'return' => 'mailru'],
			8 => ['needle' => 'livejournal.com',  'return' => 'livejournal'],
		];
		
		foreach ($providers as $key => $row)
		{
			if (false !== strpos($ary['host'], $row['needle']))
			{
				return $row['return'];
			}
		}
		
		return false;
	}
	
	/**
	* Определение логина пользователя
	*/
	private function get_openid_username()
	{
		if (isset($this->openid_response['nickname']))
		{
			return $this->openid_response['nickname'];
		}
		
		if (isset($this->openid_response['email']))
		{
			return mb_substr($this->openid_response['email'], 0, mb_strpos($this->openid_response['email'], '@'));
		}
		
		if (isset($this->openid_response['name']['full_name']))
		{
			return $this->openid_response['name']['full_name'];
		}
		
		/* Шаблоны, по которым выцепляем ник из identity */
		$patterns = [
			'([^\.]+)\.ya\.ru',
			'openid\.mail\.ru\/[^\/]+\/([^\/?]+)',
			'openid\.yandex\.ru\/([^\/?]+)',
			'([^\.]+)\.myopenid\.com'
		];
		
		foreach ($patterns as $pattern)
		{
			if (preg_match('/^https?\:\/\/' . $pattern . '/i', $this->openid_response['identity'], $match))
			{
				return $match[1];
			}
		}
		
		return false;
	}
	
	/**
	* Имя пользователя
	*/
	private function get_openid_user_full_name()
	{
		if (isset($this->openid_response['name']['full_name']))
		{
			return $this->openid_response['name']['full_name'];
		}
		
		if (isset($this->openid_response['name']['first_name']) || isset($this->openid_response['name']['last_name']))
		{
			return trim(@$this->openid_response['name']['last_name'] . ' ' . @$this->openid_response['name']['first_name']);
		}
		
		return '';
	}
	
	/**
	* Пол пользователя
	*/
	private function get_openid_user_gender()
	{
		if (isset($this->openid_response['gender']))
		{
			if ($this->openid_response['gender'] == 'F')
			{
				return 2;
			}
			
			if ($this->openid_response['gender'] == 'M')
			{
				return 1;
			}
		}
		
		return 0;
	}

	/**
	* Поиск данных пользователя в базе
	* Возврат user_id в случае успеха
	*/
	private function get_openid_user_id()
	{
		if (isset($this->openid_response['uid']))
		{
			$sql = '
				SELECT
					user_id
				FROM
					' . OPENID_IDENTITIES_TABLE . '
				WHERE
					openid_uid = ' . $this->db->check_value($this->openid_response['uid']) . '
				AND
					openid_provider = ' . $this->db->check_value($this->openid_response['provider']);
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if (!$row)
			{
				return false;
			}
			
			return $row['user_id'];
		}
		
		$sql = '
			SELECT
				user_id
			FROM
				' . OPENID_IDENTITIES_TABLE . '
			WHERE
				openid_identity = ' . $this->db->check_value($this->openid_response['identity']);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row)
		{
			return false;
		}
		
		return $row['user_id'];
	}

	/**
	* Домашняя страница пользователя
	*/
	private function get_openid_user_website()
	{
		if (isset($this->openid_response['web']['blog']))
		{
			return $this->openid_response['web']['blog'];
		}
		
		if (isset($this->openid_response['web']['default']))
		{
			return $this->openid_response['web']['default'];
		}
		
		return $this->openid_response['identity'];
	}
}
