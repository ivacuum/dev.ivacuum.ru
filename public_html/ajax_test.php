<?php
/**
*
* @package vacuum.kaluga.spark
* @copyright (c) 2012
*
*/

define('IN_SITE', true);
define('IN_AJAX', true);
$site_root_path = '/srv/www/vhosts/dev.ivacuum.ru/';
require($site_root_path . 'engine/engine.php');
require($site_root_path . 'engine/ajax.php');

class ajax extends ajax_common
{
	function __construct()
	{
		$this->valid_actions += array(
			'check_auth'      => 'guest',
			'get_image_views' => 'guest',
			'test'            => 'admin'
		);

		parent::__construct();
	}

	/**
	* Авторизован ли пользователь
	*/
	protected function check_auth()
	{
		global $user;

		$user->session_begin(false);
		$user->preferences();

		$this->response['check_auth'] = (int) $user->is_registered;
	}

	/**
	* Общее количество просмотров изображений
	*/
	protected function get_image_views()
	{
		global $config;

		$this->response['image_views'] = (int) $config['image_views'];
	}

	protected function test()
	{
		$this->response['html'] = 'some text';
		$this->response['update_ids'] = array('json_container' => 'its gonna take the new text');
	}
}

$ajax = new ajax();
$ajax->exec();

?>