<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use Guzzle\Http\Client as http_client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class yandex extends base
{
	protected $authorize_endpoint    = 'https://oauth.yandex.ru/authorize';
	protected $access_token_endpoint = 'https://oauth.yandex.ru/token';
	protected $api_base_url          = 'https://login.yandex.ru';
	protected $api_provider          = 'yandex';
	
	public function callback()
	{
		$this->check_oauth_state();
		$this->redirect_if_user_denied();

		$client = new http_client();
		
		try
		{
			$json = $client->post($this->access_token_endpoint, null, http_build_query($this->get_access_token_params()))->send()->json();
		}
		catch (ClientErrorResponseException $e)
		{
			$this->profiler->log($e->getMessage());
			trigger_error('Произошла ошибка. Пожалуйста, повторите попытку позднее.');
		}
		
		if (isset($json['error']))
		{
			trigger_error($json['error_description']);
		}
		
		/**
		* $json = Array
		* (
		*     [access_token] => 3d30eb26086f49c7be24f2cfb164c064
		*     [token_type] => bearer
		* )
		*/
		$oauth_token = $json['access_token'];
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['info{?oauth_token}', compact('oauth_token')];
		$json = $client->get($params)->send()->json();
		
		/**
		* $json = Array
		* (
		*     [display_name] => Username
		*     [real_name] => Lastname Firstname
		*     [sex] => male
		*     [birthday] => 1990-01-01
		*     [id] => 1
		*     [default_email] => mail@example.com
		* )
		*
		* profile = https://{display_name}.ya.ru/
		*/
		$uid = (int) $json['id'];
		$display_name = $json['display_name'];
		list($last_name, $first_name) = explode(' ', $json['real_name']);
		
		switch ($json['sex'])
		{
			case 'female': $gender = 1; break;
			case 'male': $gender = 2; break;
			default: $gender = 0;
		}

		if (false === $user_id = $this->get_openid_user_id($uid))
		{
			/* Новые данные */
			$sql_ary = [
				'user_id'           => 0,
				'openid_time'       => $this->user->ctime,
				'openid_provider'   => $this->api_provider,
				'openid_uid'        => $uid,
				'openid_identity'   => "http://{$display_name}.ya.ru/",
				'openid_first_name' => $first_name,
				'openid_last_name'  => $last_name,
				'openid_dob'        => $json['birthday'],
				'openid_gender'     => $gender,
				'openid_email'      => $json['default_email'],
			];
			
			$sql = 'INSERT INTO site_openid_identities ' . $this->db->build_array('INSERT', $sql_ary);
			$this->db->query($sql);
		}
		
		if ($user_id > 0)
		{
			/* Данные закреплены за пользователем, можно аутентифицировать */
			$this->user->session_end(false);
			$this->user->session_create(false, $user_id, true, false, $this->api_provider);
			$this->request->redirect(ilink());
		}
		
		trigger_error('Дорегистрация');
	}
	
	protected function get_access_token_params()
	{
		return [
			'client_id'     => $this->config["oauth.{$this->api_provider}.app_id"],
			'client_secret' => $this->config["oauth.{$this->api_provider}.app_secret"],
			'code'          => $this->request->variable('code', ''),
			'grant_type'    => 'authorization_code',
		];
	}
	
	protected function get_authorize_params()
	{
		$_SESSION["oauth.{$this->api_provider}.state"] = $state = make_random_string(10);

		return [
			'client_id'     => $this->config["oauth.{$this->api_provider}.app_id"],
			'response_type' => 'code',
			'state'         => $state,
		];
	}
}
