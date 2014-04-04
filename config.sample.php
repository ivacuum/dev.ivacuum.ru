<?php namespace app;

$app = array_merge($app, [
	/* Настройки подключения к БД */
	'db.options' => array_merge($app['db.options'], [
		'host' => 'localhost',
		'name' => '',
		'user' => '',
		'pass' => '',
	]),

	'urls' => array_merge($app['urls'], [
		'register' => '/ucp/register/',
		'signin'   => '/ucp/signin/',
		'signout'  => '/ucp/signout/',
		'static'   => '',
	]),
]);

/* Настройки отладки */
$app['errorhandler.options']['debug.ips'] = $app['profiler.options']['debug.ips'] = ['127.0.0.1', '::1'];
$app['errorhandler.options']['email.404'] = '';
