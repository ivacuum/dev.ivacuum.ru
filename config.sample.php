<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

$app = array_merge($app, [
	/* Настройки подключения к БД */
	'db.options' => [
		'host' => 'localhost',
		'name' => '',
		'user' => '',
		'pass' => '',
	],
]);

/* Настройки отладки */
$app['errorhandler.options']['debug.ips'] = $app['profiler.options']['debug.ips'] = ['127.0.0.1', '::1'];
$app['errorhandler.options']['email.404'] = '';

/* Ссылки */
$app['urls'] = array_merge($app['urls'], [
	'register' => '/ucp/register/',
	'signin'   => '/ucp/signin/',
	'signout'  => '/ucp/signout/',
]);
