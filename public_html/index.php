<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

require('../../_/fw/master/bootstrap.php');

/* Маршрутизация запроса */
$app['router']->_init()->handle_request();
