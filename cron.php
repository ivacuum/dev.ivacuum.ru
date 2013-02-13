#!/usr/bin/php
<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use fw\cron\manager;

/* Установка недостающих переменных */
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public_html';
$_SERVER['SERVER_NAME'] = basename(__DIR__);

require('../_/fw/master/bootstrap.php');

/* Выполнение задач */
$cron = new manager($app['dir.logs'], $app['file.cron.allowed'], $app['file.cron.running'], $app['db']);
$cron->run();
