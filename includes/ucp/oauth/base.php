<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use app\models\page;
use fw\core\errorhandler;

class base extends page
{
	protected $authorize_endpoint;
	protected $access_token_endpoint;
	protected $api_base_url;
	protected $api_provider;
	protected $openid_email = '';
	protected $openid_uid;
	
	public function index()
	{
		$url = $this->authorize_endpoint . '?' . $this->get_authorize_params();
		$this->request->redirect($url);
	}
	
	public function callback()
	{
		trigger_error('NOT_IMPLEMENTED');
	}
	
	protected function auth_if_guest($user_id)
	{
		if (!$this->user->is_registered && $user_id > 0)
		{
			/* Данные закреплены за пользователем, можно аутентифицировать */
			$this->user->session_end(false);
			$this->user->session_create(false, $user_id, true, false, $this->api_provider);
			$this->request->redirect(ilink());
		}
	}

	/**
	* Защита от CSRF-атак
	*/
	protected function check_oauth_state()
	{
		$state = $this->request->variable('state', '');
		
		if (empty($_SESSION["oauth.{$this->api_provider}.state"]) || $_SESSION["oauth.{$this->api_provider}.state"] != $state)
		{
			trigger_error('Произошла ошибка (CSRF). Пожалуйста, попробуйте войти еще раз.');
		}
		
		unset($_SESSION["oauth.{$this->api_provider}.state"]);
	}
	
	protected function exit_if_error()
	{
		if (!isset($json['error']))
		{
			return false;
		}
		
		trigger_error($json['error_description']);
	}

	protected function get_access_token_params()
	{
		return;
	}
	
	protected function get_authorize_params()
	{
		return;
	}
	
	protected function get_openid_insert_data($json)
	{
		return [];
	}
	
	protected function get_openid_user_id()
	{
		$sql = 'SELECT user_id FROM site_openid_identities WHERE openid_uid = ? AND openid_provider = ?';
		$this->db->query($sql, [$this->openid_uid, $this->api_provider]);
		$user_id = $this->db->fetchfield('user_id');
		$this->db->freeresult();
		
		return $user_id;
	}
	
	protected function get_redirect_uri()
	{
		return 'http://' . $this->request->server_name . ilink($this->get_handler_url('callback'));
	}
	
	/**
	* Сохранение данных социального профиля для завершения регистрации
	*/
	protected function memorize_openid_credentials()
	{
		$_SESSION['oauth.saved'] = [
			'email'    => mb_strtolower($this->openid_email),
			'provider' => $this->api_provider,
			'uid'      => $this->openid_uid,
		];
	}
	
	/**
	* Пользователю добавлен новый социальный профиль, редирект на страницу управления
	*/
	protected function redirect_if_user_logged_in()
	{
		if (!$this->user->is_registered)
		{
			return false;
		}
		
		$this->request->redirect(ilink($this->get_handler_url('ucp::social')));
	}
	
	/**
	* Редирект на форму аутентификации, если пользователь отказался от входа через внешний сервис
	*/
	protected function redirect_if_user_denied()
	{
		if (!$this->request->is_set('error'))
		{
			return false;
		}
		
		$this->request->redirect(ilink($this->urls['_signin']));
	}
	
	protected function save_openid_data($json)
	{
		$sql_ary = $this->get_openid_insert_data($json);
		
		errorhandler::log_mail(print_r($json, true) . print_r($sql_ary, true), 'OAuth data arrived');
		
		$this->db->multi_insert('site_openid_identities', $sql_ary, 'openid_last_use = values(openid_last_use), openid_first_name = values(openid_first_name), openid_last_name = values(openid_last_name), openid_dob = values(openid_dob), openid_gender = values(openid_gender), openid_email = values(openid_email)');
	}
}
