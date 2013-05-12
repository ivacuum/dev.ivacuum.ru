<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use fw\core\errorhandler;
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
		$params = http_build_query($this->get_access_token_params());
		
		try
		{
			$json = $client->post($this->access_token_endpoint, null, $params)->addHeader('Accept', 'application/json')->send()->json();
			$this->exit_if_error($json);
		}
		catch (ClientErrorResponseException $e)
		{
			errorhandler::log_mail(print_r($e->getMessage(), true), 'GitHub OAuth Error');
			trigger_error('Произошла ошибка. Пожалуйста, повторите попытку позднее.');
		}
		
		$access_token = $json['access_token'];
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['user{?access_token}', compact('access_token')];
		$json = $client->get($params)->send()->json();
		
		$params = ['user/emails{?access_token}', compact('access_token')];
		$json_emails = $client->get($params)->addHeader('Accept', 'application/vnd.github.v3')->send()->json();

		$this->openid_uid = $json['id'];
		$this->openid_email = $this->get_primary_email($json_emails);
		
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
	*     [access_token] => 79024c4d8f093e5e310719a323f22
	*     [token_type] => bearer
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
			'client_id'    => $this->config["oauth.{$this->api_provider}.app_id"],
			'redirect_uri' => $this->get_redirect_uri(),
			'scope'        => 'user:email',
			'state'        => $state,
		];
	}

	/**
	* $json = Array
	* (
	*     [login] => Username
	*     [id] => 1
	*     [gravatar_id] => 6a3c6f7aaz02930251a455fee94989f7
	*     [html_url] => https://github.com/Username
	*     [name] => Full name
	*     [email] => mail@example.com
	* )
	*
	* profile = https://github.com/{login}
	* picture = http://www.gravatar.com/avatar/{gravatar_id}?s=1024
	*/
	protected function get_openid_insert_data($json)
	{
		return [
			'user_id'           => $this->user['user_id'],
			'openid_time'       => $this->request->time,
			'openid_last_use'   => $this->request->time,
			'openid_provider'   => $this->api_provider,
			'openid_uid'        => $this->openid_uid,
			'openid_identity'   => $json['html_url'],
			'openid_first_name' => '',
			'openid_last_name'  => '',
			'openid_dob'        => '',
			'openid_gender'     => '',
			'openid_email'      => $this->openid_email,
			'openid_photo'      => "http://www.gravatar.com/avatar/{$json['gravatar_id']}?s=400",
		];
	}
	
	/**
	* Array
	* (
	*     [0] => Array
	*         (
	*             [email] => mail-reserve@example.com
	*             [primary] => 
	*             [verified] => 1
	*         )
    * 
	*     [1] => Array
	*         (
	*             [email] => mail@example.com
	*             [primary] => 1
	*             [verified] => 1
	*         )
    * 
	* )
	*/
	protected function get_primary_email($json)
	{
		foreach ($json as $email)
		{
			if ($email['primary'])
			{
				return $email['email'];
			}
		}
		
		return '';
	}
}
