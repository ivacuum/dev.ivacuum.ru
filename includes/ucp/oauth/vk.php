<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

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
		*     [access_token] => 304f1263064fce435add6a49743b54acc82adac39a8d0c8bee10bc3e92429b641b7c3e62e34c1a1916a41
		*     [expires_in] => 86399
		*     [user_id] => 1
		* )
		*/
		$access_token = $json['access_token'];
		$fields       = 'sex, bdate, photo_max_orig';
		$uids         = (int) $json['user_id'];
		
		$client->setBaseUrl($this->api_base_url);
		$params = ['users.get{?uids,fields}', compact('uids', 'fields')];
		$json = $client->get($params)->send()->json()['response'][0];
		
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
		$uid = (int) $json['uid'];
		
		if (false === $user_id = $this->get_openid_user_id($uid))
		{
			/* Новые данные */
			$sql_ary = [
				'user_id'           => 0,
				'openid_time'       => $this->user->ctime,
				'openid_provider'   => $this->api_provider,
				'openid_uid'        => $uid,
				'openid_identity'   => "https://vk.com/id{$uid}",
				'openid_first_name' => $json['first_name'],
				'openid_last_name'  => $json['last_name'],
				'openid_dob'        => $json['bdate'],
				'openid_gender'     => $json['sex'],
				'openid_photo'      => $json['photo_max_orig'],
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
		
		/* Кто из друзей уже зарегистрирован */
		// $params = ['friends.getAppUsers{?access_token}', compact('access_token')];
		// $json = $client->get($params)->send()->json();
		// $this->profiler->log($json);
		trigger_error('Дорегистрация');
	}
	
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
			'scope'         => 'friends, notify',
			'state'         => $state,
		];
	}
}
