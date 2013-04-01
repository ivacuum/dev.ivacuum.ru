<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use app\models\page;
use Guzzle\Http\Client as http_client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class vk extends page
{
	public function index()
	{
		$url = 'https://oauth.vk.com/authorize?' . http_build_query([
			'client_id'     => $this->config['oauth.vk.app_id'],
			'scope'         => 'friends',
			'redirect_uri'  => 'http://' . $this->request->server_name . ilink($this->get_handler_url('callback')),
			'response_type' => 'code',
		]);
			
		$this->request->redirect($url);
	}
	
	public function callback()
	{
		$code = $this->request->get('code', '');

		$client = new http_client('https://oauth.vk.com');
		
		try
		{
			$response = $client->get(['access_token{?client_id,client_secret,code,redirect_uri}', [
				'client_id'     => $this->config['oauth.vk.app_id'],
				'client_secret' => $this->config['oauth.vk.app_secret'],
				'code'          => $code,
				'redirect_uri'  => 'http://' . $this->request->server_name . ilink($this->get_handler_url('callback')),
			]])->send()->json();
		}
		catch (ClientErrorResponseException $e)
		{
			$this->profiler->log($e->getMessage());
			return;
		}
		
		if (isset($response['error']))
		{
			trigger_error($response['error_description']);
		}
		
		$this->profiler->log($response);

		extract($response);

		$client->setBaseUrl('https://api.vk.com/method');
		
		$response = $client->get(['users.get{?uids,fields}', [
			'uids'   => $user_id,
			'fields' => 'sex, bdate, photo_big',
		]])->send()->json();
		
		$this->profiler->log($response);
		
		$response = $client->get(['friends.getAppUsers{?access_token}', ['access_token' => $access_token]])->send()->json();
		$this->profiler->log($response);
	}
}