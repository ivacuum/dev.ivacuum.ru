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
$user->session_begin();
$auth->init($user->data);
$user->setup();

/* Домен временно закрыт для публики */
if ($request->header('Host') == 'dev.ivacuum.ru' && $user['user_id'] != 1 && $user->ip != '10.171.2.236' && $user->ip != '79.175.20.190')
{
	// redirect(ilink('', 'http://ivacuum.ru'));
}

/* Маршрутизация запроса */
$router = new \fw\core\router();
$router->handle_request();
