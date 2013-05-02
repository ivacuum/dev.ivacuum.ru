<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use app\models\page;

class base extends page
{
	protected $authorize_endpoint;
	protected $access_token_endpoint;
	protected $api_base_url;
	protected $api_provider;
	
	public function index()
	{
		$url = $this->authorize_endpoint . '?' . http_build_query($this->get_authorize_params());
		$this->request->redirect($url);
	}
	
	public function callback()
	{
		trigger_error('NOT_IMPLEMENTED');
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

	protected function get_access_token_params()
	{
		return [];
	}
	
	protected function get_authorize_params()
	{
		return [];
	}
	
	protected function get_openid_user_id($uid)
	{
		$sql = 'SELECT user_id FROM site_openid_identities WHERE openid_uid = ? AND openid_provider = ?';
		$this->db->query($sql, [$uid, $this->api_provider]);
		$user_id = $this->db->fetchfield('user_id');
		$this->db->freeresult();
		
		return $user_id;
	}
	
	protected function get_redirect_uri()
	{
		return 'http://' . $this->request->server_name . ilink($this->get_handler_url('callback'));
	}
	
	/**
	* Редирект на форму аутентификации, если пользователь отказался от входа через внешний сервис
	*/
	protected function redirect_if_user_denied()
	{
		if ($this->request->is_set('error'))
		{
			$this->request->redirect(ilink($this->urls['_signin']));
		}
	}
}
