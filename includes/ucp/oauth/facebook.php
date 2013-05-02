<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use Guzzle\Http\Client as http_client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class facebook extends base
{
	protected $authorize_endpoint    = 'https://www.facebook.com/dialog/oauth';
	protected $access_token_endpoint = 'https://graph.facebook.com/oauth/access_token';
	protected $api_base_url          = 'https://graph.facebook.com';
	protected $api_provider          = 'facebook';
	
	public function callback()
	{
		$this->check_oauth_state();
		$this->redirect_if_user_denied();

		$client = new http_client();
		$params = $this->access_token_endpoint . '?' . http_build_query($this->get_access_token_params());
		
		try
		{
			$response = $client->get($params)->send()->getBody();
			parse_str($response, $json);
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
		*     [access_token] => BAAC7ZCpf8wes
		*     [expires] => 5138462
		* )
		*/
		$access_token = $json['access_token'];
		$fields       = 'first_name, last_name, username, birthday, gender, email';
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['me{?access_token,fields}', compact('access_token', 'fields')];
		$json = $client->get($params)->send()->json();
		
		/**
		* $json = Array
		* (
		*     [id] => 1
		*     [first_name] => Firstname
		*     [last_name] => Lastname
		*     [username] => Username
		*     [birthday] => 01/01/1990
		*     [gender] => male
		*     [email] => mail@example.com
		* )
		*
		* profile = https://facebook.com/{id}
		* picture = https://graph.facebook.com/{id}/picture?width=1024
		*/
		
		$uid = (int) $json['id'];
		
		switch ($json['gender'])
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
				'openid_identity'   => "https://www.facebook.com/{$uid}",
				'openid_first_name' => $json['first_name'],
				'openid_last_name'  => $json['last_name'],
				'openid_dob'        => $json['birthday'],
				'openid_gender'     => $gender,
				'openid_email'      => $json['email'],
				'openid_photo'      => "https://graph.facebook.com/{$uid}/picture?width=1024",
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
			'client_id'     => $this->config['oauth.facebook.app_id'],
			'client_secret' => $this->config['oauth.facebook.app_secret'],
			'code'          => $this->request->variable('code', ''),
			'redirect_uri'  => $this->get_redirect_uri(),
		];
	}
	
	protected function get_authorize_params()
	{
		$_SESSION["oauth.{$this->api_provider}.state"] = $state = make_random_string(10);

		return [
			'client_id'     => $this->config['oauth.facebook.app_id'],
			'redirect_uri'  => $this->get_redirect_uri(),
			'scope'         => 'email, user_birthday',
			'state'         => $state,
		];		
	}
}
