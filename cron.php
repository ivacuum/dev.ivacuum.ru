<?php namespace app;

/* Установка недостающих переменных */
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public_html';
$_SERVER['SERVER_NAME'] = basename(__DIR__);

require 'fw.php';

/* Выполнение задач */
$app['cron']->run();
