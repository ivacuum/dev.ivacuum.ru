<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2009
*/

define('IN_SITE', true);
$site_root_path = './../../';
require($site_root_path . 'engine/engine_lite.php');
$db->connect('localhost', 'csstats', 'dfHYpWSR9T23vQtY', 'csstats', false, '/var/run/mysql/mysql.sock');

$server_id = getvar('server_id', 1);

$sql = '
	SELECT
		*
	FROM
		hlstats_Servers
	WHERE
		serverId = ' . $db->check_value($server_id);
$result = $db->query($sql);
$server = $db->fetchrow($result);
$db->freeresult($result);

/**
* Список карт сервера
*/
$sql = '
	SELECT
		map
	FROM
		hlstats_Servers_Maps
	WHERE
		server_id = ' . $db->check_value($server_id) . '
	AND
		active = 1
	ORDER BY
		map';
$result = $db->query($sql);

while( $row = $db->fetchrow($result) )
{
	$template->cycle_vars('maps', array(
		'NAME' => $row['map'])
	);
}

$db->freeresult($result);

$template->vars(array(
	'ACTIVE' => $server['active'],
	'ID'     => $server_id,
	'NAME'   => $server['name'])
);

$template->file = 'csstats/motd.html';
$template->go($template->file);
garbage_collection();
exit;

?>
