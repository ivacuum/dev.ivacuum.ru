<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use fw\core\errorhandler;

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
		
		$json = $this->http_client->get($this->access_token_endpoint . '?' . $this->get_access_token_params())->send()->json();
		$this->exit_if_error($json);
		
		$access_token = $json['access_token'];
		$fields       = 'sex, bdate, photo_max_orig';
		$uids         = $json['user_id'];
		
		$this->http_client->setBaseUrl($this->api_base_url);
		$params = ['users.get{?uids,fields}', compact('uids', 'fields')];
		$json = $this->http_client->get($params)->send()->json()['response'][0];

		$this->openid_uid = $json['uid'];
		
		$user_id = $this->get_openid_user_id();

		$this->save_openid_data($json);
		$this->auth_if_guest($user_id);
		$this->redirect_if_user_logged_in();
		$this->memorize_openid_credentials();
		$this->request->redirect(ilink($this->get_handler_url('ucp\register::complete')));

		/* Кто из друзей уже зарегистрирован */
		// $params = ['friends.getAppUsers{?access_token}', compact('access_token')];
		// $json = $this->http_client->get($params)->send()->json();
		// $this->profiler->log($json);
		// errorhandler::log_mail(print_r($json, true), 'VK Friends Get App Users');
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
		return http_build_query([
			'client_id'     => $this->config["oauth.{$this->api_provider}.app_id"],
			'client_secret' => $this->config["oauth.{$this->api_provider}.app_secret"],
			'code'          => $this->request->variable('code', ''),
			'redirect_uri'  => $this->get_redirect_uri(),
		]);
	}
	
	protected function get_authorize_params()
	{
		$_SESSION["oauth.{$this->api_provider}.state"] = $state = make_random_string(10);

		return http_build_query([
			'client_id'     => $this->config["oauth.{$this->api_provider}.app_id"],
			'redirect_uri'  => $this->get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => 'friends',
			'state'         => $state,
		]);
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
			'openid_time'       => $this->request->time,
			'openid_last_use'   => $this->request->time,
			'openid_provider'   => $this->api_provider,
			'openid_uid'        => $this->openid_uid,
			'openid_identity'   => "https://vk.com/id{$this->openid_uid}",
			'openid_first_name' => $json['first_name'],
			'openid_last_name'  => $json['last_name'],
			'openid_dob'        => isset($json['bdate']) ? $json['bdate'] : '',
			'openid_gender'     => isset($json['sex']) ? $json['sex'] : 0,
			'openid_email'      => $this->openid_email,
			'openid_photo'      => $json['photo_max_orig'],
		];
	}
}
