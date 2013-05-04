<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use fw\core\errorhandler;
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
		$params = http_build_query($this->get_access_token_params());
		
		try
		{
			$json = $client->post($this->access_token_endpoint, null, $params)->send()->json();
			$this->exit_if_error($json);
		}
		catch (ClientErrorResponseException $e)
		{
			errorhandler::log_mail(print_r($e->getMessage(), true), 'Yandex OAuth Error');
			trigger_error('Произошла ошибка. Пожалуйста, повторите попытку позднее.');
		}
		
		$oauth_token = $json['access_token'];
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['info{?oauth_token}', compact('oauth_token')];
		$json = $client->get($params)->send()->json();
		
		$user_id = $this->get_openid_user_id($json['id']);

		$this->save_openid_data($json);
		$this->auth_if_guest($user_id);
		$this->redirect_if_user_logged_in();
		
		trigger_error('Дорегистрация');
	}
	
	/**
	* В ответ придет
	*
	* $json = Array
	* (
	*     [access_token] => 3d30eb26086f49c7be24f2cfb164c064
	*     [token_type] => bearer
	* )
	*/
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
	protected function get_openid_insert_data($json)
	{
		$display_name = $json['display_name'];
		list($last_name, $first_name) = explode(' ', $json['real_name']);
		
		switch ($json['sex'])
		{
			case 'female': $gender = 1; break;
			case 'male': $gender = 2; break;
			default: $gender = 0;
		}

		return [
			'user_id'           => $this->user['user_id'],
			'openid_time'       => $this->user->ctime,
			'openid_last_use'   => $this->user->ctime,
			'openid_provider'   => $this->api_provider,
			'openid_uid'        => $json['id'],
			'openid_identity'   => "http://{$json['display_name']}.ya.ru/",
			'openid_first_name' => $first_name,
			'openid_last_name'  => $last_name,
			'openid_dob'        => $json['birthday'],
			'openid_gender'     => $gender,
			'openid_email'      => $json['default_email'],
			'openid_photo'      => '',
		];
	}
}
