<?php

/* Установка недостающей переменной */
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/public_html';

require 'fw.php';

return $app->getMigratorOptions('fw');
