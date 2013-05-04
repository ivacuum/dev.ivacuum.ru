<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use fw\core\errorhandler;
use Guzzle\Http\Client as http_client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class google extends base
{
	protected $authorize_endpoint    = 'https://accounts.google.com/o/oauth2/auth';
	protected $access_token_endpoint = 'https://accounts.google.com/o/oauth2/token';
	protected $api_base_url          = 'https://www.googleapis.com/oauth2/v3';
	protected $api_provider          = 'google';

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
			errorhandler::log_mail(print_r($e->getMessage(), true), 'Google OAuth Error');
			trigger_error('Произошла ошибка. Пожалуйста, повторите попытку позднее.');
		}
		
		/* Запрос информации о пользователе */
		$client->setBaseUrl($this->api_base_url);
		$params = ['userinfo{?access_token}', ['access_token' => $json['access_token']]];
		$json = $client->get($params)->send()->json();
		
		$user_id = $this->get_openid_user_id($json['sub']);
		
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
	*     [access_token] => ya29.AHESkduiosA7Awoiexckjf6w
	*     [token_type] => Bearer
	*     [expires_in] => 3600
	*     [id_token] => lpQ2owKqxvblwi-2uz-f69QCLXqyU
	* )
	*/
	protected function get_access_token_params()
	{
		return [
			'client_id'     => $this->config["oauth.{$this->api_provider}.app_id"],
			'client_secret' => $this->config["oauth.{$this->api_provider}.app_secret"],
			'code'          => $this->request->variable('code', ''),
			'grant_type'    => 'authorization_code',
			'redirect_uri'  => $this->get_redirect_uri(),
		];
	}
	
	protected function get_authorize_params()
	{
		$_SESSION["oauth.{$this->api_provider}.state"] = $state = make_random_string(10);

		return [
			'client_id'     => $this->config["oauth.{$this->api_provider}.app_id"],
			'redirect_uri'  => $this->get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
			'state'         => $state,
		];
	}
	
	/**
	* Array
	* (
	*     [sub] => 123456789
	*     [name] => Full Name
	*     [given_name] => First Name
	*     [family_name] => Last Name
	*     [profile] => https://plus.google.com/{sub}
	*     [picture] => https://lh3.googleusercontent.com/-tgggDenqufs/AAAAAAAAAAI/AAAAAAAAD4M/yPsLxWfWXtQ/photo.jpg
	*     [email] => mail@example.com
	*     [email_verified] => 1
	*     [gender] => male
	*     [locale] => en
	* )
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
			'openid_uid'        => $json['sub'],
			'openid_identity'   => $json['profile'],
			'openid_first_name' => isset($json['given_name']) ? $json['given_name'] : '',
			'openid_last_name'  => isset($json['family_name']) ? $json['family_name'] : '',
			'openid_dob'        => '',
			'openid_gender'     => $gender,
			'openid_email'      => $json['email'],
			'openid_photo'      => isset($json['picture']) ? $json['picture'] : '',
		];
	}
}
