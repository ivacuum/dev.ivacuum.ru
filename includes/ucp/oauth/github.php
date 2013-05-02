<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use Guzzle\Http\Client as http_client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class github extends base
{
	protected $authorize_endpoint    = 'https://github.com/login/oauth/authorize';
	protected $access_token_endpoint = 'https://github.com/login/oauth/access_token';
	protected $api_base_url          = 'https://api.github.com';
	protected $api_provider          = 'github';
	
	public function callback()
	{
		$this->check_oauth_state();
		$this->redirect_if_user_denied();

		$client = new http_client();
		
		try
		{
			$json = $client->post($this->access_token_endpoint, null, http_build_query($this->get_access_token_params()))
				->addHeader('Accept', 'application/json')
				->send()
				->json();
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
		*     [access_token] => 79024c4d8f093e5e310719a323f22
		*     [token_type] => bearer
		* )
		*/
		$access_token = $json['access_token'];
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['user{?access_token}', compact('access_token')];
		$json = $client->get($params)->send()->json();
		
		/**
		* $json = Array
		* (
		*     [login] => Username
		*     [id] => 1
		*     [gravatar_id] => 6a3c6f7aaz02930251a455fee94989f7
		*     [html_url] => https://github.com/Username
		*     [name] => Firstname Lastname
		*     [email] => mail@example.com
		* )
		*
		* profile = https://github.com/{login}
		* picture = http://www.gravatar.com/avatar/{gravatar_id}?s=1024
		*/
		$uid = (int) $json['id'];
		list($first_name, $last_name) = explode(' ', $json['name']);
		
		if (false === $user_id = $this->get_openid_user_id($uid))
		{
			/* Новые данные */
			$sql_ary = [
				'user_id'           => 0,
				'openid_time'       => $this->user->ctime,
				'openid_provider'   => $this->api_provider,
				'openid_uid'        => $uid,
				'openid_identity'   => $json['html_url'],
				'openid_first_name' => $first_name,
				'openid_last_name'  => $last_name,
				'openid_email'      => $json['email'],
				'openid_photo'      => "http://www.gravatar.com/avatar/{$json['gravatar_id']}?s=400",
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
			'redirect_uri'  => $this->get_redirect_uri(),
		];
	}
	
	protected function get_authorize_params()
	{
		$_SESSION["oauth.{$this->api_provider}.state"] = $state = make_random_string(10);

		return [
			'client_id'    => $this->config["oauth.{$this->api_provider}.app_id"],
			'redirect_uri' => $this->get_redirect_uri(),
			'scope'        => 'user:email',
			'state'        => $state,
		];
	}
}
