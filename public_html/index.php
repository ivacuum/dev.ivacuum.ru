<?php namespace app;

require '../fw.php';

/* Маршрутизация запроса */
$app['router']->_init()->handle_request();
