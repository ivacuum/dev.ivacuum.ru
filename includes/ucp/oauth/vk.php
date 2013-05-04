<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use fw\core\errorhandler;
use Guzzle\Http\Client as http_client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class vk extends base
{
	protected $authorize_endpoint    = 'https://oauth.vk.com/authorize';
	protected $access_token_endpoint = 'https://oauth.vk.com/access_token';
	protected $api_base_url          = 'https://api.vk.com/method';
	protected $api_provider          = 'vk';
	
	public function callback()
	{
		$this->check_oauth_state();
		$this->redirect_if_user_denied();
		
		$client = new http_client();
		$params = $this->access_token_endpoint . '?' . http_build_query($this->get_access_token_params());
		
		try
		{
			$json = $client->get($params)->send()->json();
			$this->exit_if_error($json);
		}
		catch (ClientErrorResponseException $e)
		{
			errorhandler::log_mail(print_r($e->getMessage(), true), 'VK OAuth Error');
			trigger_error('Произошла ошибка. Пожалуйста, повторите попытку позднее.');
		}
		
		$access_token = $json['access_token'];
		$fields       = 'sex, bdate, photo_max_orig';
		$uids         = $json['user_id'];
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['users.get{?uids,fields}', compact('uids', 'fields')];
		$json = $client->get($params)->send()->json()['response'][0];
		
		$user_id = $this->get_openid_user_id($json['uid']);

		$this->save_openid_data($json);
		$this->auth_if_guest($user_id);
		$this->redirect_if_user_logged_in();

		/* Кто из друзей уже зарегистрирован */
		// $params = ['friends.getAppUsers{?access_token}', compact('access_token')];
		// $json = $client->get($params)->send()->json();
		// $this->profiler->log($json);
		trigger_error('Дорегистрация');
	}
	
	/**
	* В ответ придет
	*
	* $json = Array
	* (
	*     [access_token] => 304f1263064fce435add6a49743b54acc82adac39a8d0c8bee10bc3e92429b641b7c3e62e34c1a1916a41
	*     [expires_in] => 86399
	*     [user_id] => 123456789
	* )
	*/
	protected function get_access_token_params()
	{
		return [
			'client_id'     => $this->config['oauth.vk.app_id'],
			'client_secret' => $this->config['oauth.vk.app_secret'],
			'code'          => $this->request->variable('code', ''),
			'redirect_uri'  => $this->get_redirect_uri(),
		];
	}
	
	protected function get_authorize_params()
	{
		$_SESSION["oauth.{$this->api_provider}.state"] = $state = make_random_string(10);

		/* scope notify нужен для работы параметра state */
		return [
			'client_id'     => $this->config['oauth.vk.app_id'],
			'redirect_uri'  => $this->get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => 'friends',
			'state'         => $state,
		];
	}

	/**
	* $json = Array
	* (
	*     [uid] => 1
	*     [first_name] => Имя
	*     [last_name] => Фамилия
	*     [sex] => 2
	*     [bdate] => 1.1.1990
	*     [photo_max_orig] => http://cs319030.vk.me/v319030401/5cf9/XcoGsk3ltUI.jpg
	* )
	*
	* profile = https://vk.com/id{id}
	*/
	protected function get_openid_insert_data($json)
	{
		return [
			'user_id'           => $this->user['user_id'],
			'openid_time'       => $this->user->ctime,
			'openid_last_use'   => $this->user->ctime,
			'openid_provider'   => $this->api_provider,
			'openid_uid'        => $json['uid'],
			'openid_identity'   => "https://vk.com/id{$json['uid']}",
			'openid_first_name' => $json['first_name'],
			'openid_last_name'  => $json['last_name'],
			'openid_dob'        => isset($json['bdate']) ? $json['bdate'] : '',
			'openid_gender'     => isset($json['sex']) ? $json['sex'] : 0,
			'openid_email'      => '',
			'openid_photo'      => $json['photo_max_orig'],
		];
	}
}
