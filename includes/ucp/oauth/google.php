<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\ucp\oauth;

use Guzzle\Http\Client as http_client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class google extends base
{
	protected $authorize_endpoint    = 'https://accounts.google.com/o/oauth2/auth';
	protected $access_token_endpoint = 'https://accounts.google.com/o/oauth2/token';
	protected $api_base_url          = 'https://www.googleapis.com/oauth2/v3';
	protected $api_provider          = 'google';
}
