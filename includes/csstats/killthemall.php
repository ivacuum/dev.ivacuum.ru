<?php
/**
*
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*
*/

if( $_SERVER['REMOTE_ADDR'] != '10.164.248.166' )
{
	die('Нельзя');
}

define('IN_SITE', true);
$site_root_path = './../../';
require($site_root_path . 'engine/engine_lite.php');
require($site_root_path . 'engine/functions.php');
$db->connect('localhost', 'csstats', 'dfHYpWSR9T23vQtY', 'csstats');

/*
$sql = '
	SELECT
		playerId,
		lastAddress
	FROM
		hlstats_Players
	WHERE
		kills = 0
	AND
		deaths = 0';
$result = $db->query($sql);
*/

$sql = '
	SELECT
		playerId,
		last_event,
		lastAddress,
		kills,
		deaths
	FROM
		hlstats_Players
	WHERE
		last_event < ' . ( time() - ( 86400 * 90 ) ) . '
	ORDER BY
		last_event ASC';
$result = $db->query($sql);

$i = 0;

while( $row = $db->fetchrow($result) )
{
	/*
	$sql = '
		UPDATE
			hlstats_Players
		SET
			hideranking = 1
		WHERE
			playerId = ' . $db->check_value($row['playerId']);
	$db->query($sql);
	*/
	/*
	if( is_spark($row['lastAddress']) === 1 )
	{
		continue;
	}
	$sql = '
		DELETE
		FROM
			hlstats_PlayerNames
		WHERE
			playerId = ' . $db->check_value($row['playerId']);
	$db->query($sql);

	$sql = '
		DELETE
		FROM
			hlstats_Players_History
		WHERE
			playerId = ' . $db->check_value($row['playerId']);
	$db->query($sql);

	$sql = '
		DELETE
		FROM
			hlstats_PlayerUniqueIds
		WHERE
			playerId = ' . $db->check_value($row['playerId']);
	$db->query($sql);

	$sql = '
		DELETE
		FROM
			hlstats_Players
		WHERE
			playerId = ' . $db->check_value($row['playerId']);
	$db->query($sql);
	*/
	// printf('Игрок #%d (%s) удалён.<br />', $row['playerId'], $row['lastAddress']);
	
	printf('Найден игрок #%d (%s). Счёт %d:%d. Замечен: %s<br />', $row['playerId'], $row['lastAddress'], $row['kills'], $row['deaths'], date('j F Y, H:i', $row['last_event']));

	$i++;
}

// printf('<br />Операция завершена. Удалено записей: %d. Всего запросов: %d', $i, $db->total_queries);

$db->freeresult($result);

garbage_collection();
exit;