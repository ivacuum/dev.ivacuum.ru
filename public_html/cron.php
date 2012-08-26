#!/usr/bin/php
<?php
/**
*
* @package ivacuum.ru
* @copyright (c) 2012
*
*/

namespace app;

if( PHP_SAPI != 'cli' )
{
	exit;
}

/* Установка недостающих переменных */
$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);
$_SERVER['SERVER_NAME'] = 'dev.ivacuum.ru';

require('/srv/www/vhosts/src/bootstrap.php');

/* Выполнение задач */
$cron = new \engine\cron\manager();
$cron->run();
