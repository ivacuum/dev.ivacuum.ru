<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use fw\core\errorhandler;
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
			$this->exit_if_error($json);
		}
		catch (ClientErrorResponseException $e)
		{
			errorhandler::log_mail(print_r($e->getMessage(), true), 'Facebook OAuth Error');
			trigger_error('Произошла ошибка. Пожалуйста, повторите попытку позднее.');
		}
		
		$access_token = $json['access_token'];
		$fields       = 'first_name, last_name, username, birthday, gender, email';
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['me{?access_token,fields}', compact('access_token', 'fields')];
		$json = $client->get($params)->send()->json();
		
		$this->openid_uid = $json['id'];
		$this->openid_email = $json['email'];
		
		$user_id = $this->get_openid_user_id();

		$this->save_openid_data($json);
		$this->auth_if_guest($user_id);
		$this->redirect_if_user_logged_in();
		$this->memorize_openid_credentials();
		$this->request->redirect(ilink($this->get_handler_url('ucp\register::complete')));
	}
	
	/**
	* В ответ придет
	*
	* $json = Array
	* (
	*     [access_token] => BAAC7ZCpf8wes
	*     [expires] => 5138462
	* )
	*/
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
			'client_id'     => $this->config["oauth.{$this->api_provider}.app_id"],
			'redirect_uri'  => $this->get_redirect_uri(),
			'scope'         => 'email, user_birthday',
			'state'         => $state,
		];		
	}

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
	protected function get_openid_insert_data($json)
	{
		switch ($json['gender'])
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
			'openid_uid'        => $this->openid_uid,
			'openid_identity'   => "https://www.facebook.com/{$this->openid_uid}",
			'openid_first_name' => $json['first_name'],
			'openid_last_name'  => $json['last_name'],
			'openid_dob'        => isset($json['birthday']) ? $json['birthday'] : '',
			'openid_gender'     => $gender,
			'openid_email'      => $this->openid_email,
			'openid_photo'      => "https://graph.facebook.com/{$this->openid_uid}/picture?width=1024",
		];
	}
}
