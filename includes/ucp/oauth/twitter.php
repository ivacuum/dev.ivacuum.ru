<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use Guzzle\Plugin\Oauth\OauthPlugin;
use fw\core\errorhandler;

class twitter extends base
{
	protected $authorize_endpoint     = 'https://api.twitter.com/oauth/authenticate';
	protected $access_token_endpoint  = 'https://api.twitter.com/oauth/access_token';
	protected $request_token_endpoint = 'https://api.twitter.com/oauth/request_token';
	protected $api_base_url           = 'https://api.twitter.com/1.1';
	protected $api_provider           = 'twitter';
	
	public function index()
	{
		$this->http_client->addSubscriber(new OauthPlugin([
			'consumer_key'    => $this->config["oauth.{$this->api_provider}.app_id"],
			'consumer_secret' => $this->config["oauth.{$this->api_provider}.app_secret"],
			'callback'        => $this->get_redirect_uri(),
		]));
		
		$response = $this->http_client->post($this->request_token_endpoint)->send()->getBody();
		parse_str($response, $json);

		/**
		* [oauth_token] => 83CLd52SernwYZNVskoPhSdVgy3ndZmbCUe2lk
		* [oauth_token_secret] => Fsold6MYpwK0NuwgXj4tOiyIccpTGqHcvVhqI
		* [oauth_callback_confirmed] => true
		*
		* Сохранение параметров для сравнения на следующем шаге
		*/
		$_SESSION["oauth.{$this->api_provider}.token"] = $oauth_token = $json['oauth_token'];

		if ($json['oauth_callback_confirmed'] !== 'true')
		{
			$this->request->redirect(ilink($this->urls['_signin']));
		}
		
		$url = $this->authorize_endpoint . '?' . http_build_query(compact('oauth_token'));
		$this->request->redirect($url);
	}
	
	/**
	* $_REQUEST = Array
	* (
	*     [oauth_token] => 8RnFojwUARuBOIygLcFeH7jB4uWXhkAc9ONFYM
	*     [oauth_verifier] => XR19yyUUNUxIOjKIGoyr9xfy0vrebpDdS6rA
	* )
	*/
	public function callback()
	{
		if ($this->request->variable('denied', ''))
		{
			$this->request->redirect(ilink($this->urls['_signin']));
		}

		$oauth_token    = $this->request->variable('oauth_token', '');
		$oauth_verifier = $this->request->variable('oauth_verifier', '');
		
		if (empty($_SESSION["oauth.{$this->api_provider}.token"]) || $_SESSION["oauth.{$this->api_provider}.token"] != $oauth_token)
		{
			$this->request->redirect(ilink($this->urls['_signin']));
		}

		$this->http_client->addSubscriber(new OauthPlugin([
			'consumer_key' => $this->config["oauth.{$this->api_provider}.app_id"],
			'token'        => $oauth_token,
			'verifier'     => $oauth_verifier,
		]));
		
		$response = $this->http_client->post($this->access_token_endpoint)->send()->getBody();
		parse_str($response, $json);
		
		/**
		* [oauth_token] => 1-JRtvP6oy4gWvdJGy9pYmpUAMatF0xj1j
		* [oauth_token_secret] => SxpurRNZCItO7uyzP4UfGoAZPRyKio
		* [user_id] => 1
		* [screen_name] => username
		*/
		$oauth_token = $json['oauth_token'];
		$oauth_token_secret = $json['oauth_token_secret'];
		
		$this->http_client->addSubscriber(new OauthPlugin([
			'consumer_key'    => $this->config["oauth.{$this->api_provider}.app_id"],
			'consumer_secret' => $this->config["oauth.{$this->api_provider}.app_secret"],
			'token'           => $oauth_token,
			'token_secret'    => $oauth_token_secret,
		]));

		$this->http_client->setBaseUrl($this->api_base_url);
		$json = $this->http_client->get('account/verify_credentials.json?skip_status=1')->send()->json();
		
		$this->openid_uid = $json['id'];
		
		$user_id = $this->get_openid_user_id();

		$this->save_openid_data($user_id, $json);
		$this->auth_if_guest($user_id);
		$this->redirect_if_user_logged_in();
		$this->memorize_openid_credentials();
		$this->request->redirect(ilink($this->get_handler_url('ucp\register::complete')));
	}
	
	/**
	* [name] => name
	* [id] => 1
	* [lang] => en
	* [screen_name] => username
	* [time_zone] => Moscow
	* [utc_offset] => 14400
	* [profile_image_url] => http://a0.twimg.com/profile_images/1392520926/512_Finder_Leopard_normal.png
	*
	* profile = https://twitter.com/account/redirect_by_id?id={id}
	*/
	protected function get_openid_insert_data($json)
	{
		return [
			'user_id'           => $this->user['user_id'],
			'openid_time'       => $this->request->time,
			'openid_last_use'   => $this->request->time,
			'openid_provider'   => $this->api_provider,
			'openid_uid'        => $this->openid_uid,
			'openid_identity'   => "https://twitter.com/account/redirect_by_id?id={$this->openid_uid}",
			'openid_first_name' => '',
			'openid_last_name'  => '',
			'openid_dob'        => '',
			'openid_gender'     => '',
			'openid_email'      => $this->openid_email,
			'openid_photo'      => str_replace('_normal.', '.', $json['profile_image_url']),
		];
	}
}
