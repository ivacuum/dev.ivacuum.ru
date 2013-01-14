<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

require('/srv/www/vhosts/_/fw/master/bootstrap.php');

/**
* Создание сессии
* Инициализация привилегий
*/
$app['user']->session_begin();
$app['auth']->init($app['user']->data);
$app['user']->setup();

/* Домен временно закрыт для публики */
if ($app['request']->header('Host') == 'dev.ivacuum.ru' && $app['user']['user_id'] != 1 && $app['user']->ip != '192.168.1.1' && $app['user']->ip != '79.175.20.190')
{
	// redirect(ilink('', 'http://ivacuum.ru'));
}

/* Маршрутизация запроса */
$app['router']->_init()->handle_request();
