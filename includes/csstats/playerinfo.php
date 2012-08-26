<?php
/**
*
* @package ivacuum.ru
* @copyright (c) 2012
*
*/

namespace app\csstats;

use app\models\page;

class playerinfo extends page
{
	public function index()
	{
		trigger_error('index');
	}
}

// /**
// * Информация об игроке
// */
// $sql = '
// 	SELECT
// 		*
// 	FROM
// 		hlstats_Players
// 	WHERE
// 		playerId = ' . $csstats_db->check_value($url);
// $result = $csstats_db->query($sql);
// $player = $csstats_db->fetchrow($result);
// $csstats_db->freeresult($result);
// 
// if( !$player )
// {
// 	trigger_error('PLAYER_NOT_FOUND');
// }
// 
// /**
// * Определяем переменные
// */
// $action = getvar('action', '');
// $tab    = getvar('tab', '');
// 
// if( $tab == 'actions' )
// {
// 	/**
// 	* Действия
// 	*/
// 	$sql = '
// 		SELECT
// 			a.code,
// 			a.reward_player,
// 			a.reward_team,
// 			a.description,
// 			COUNT(*) as count
// 		FROM
// 			hlstats_Actions a,
// 			hlstats_Events_PlayerActions pa
// 		WHERE
// 			pa.actionId = a.id
// 		AND
// 			pa.playerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		GROUP BY
// 			a.code
// 		ORDER BY
// 			count DESC';
// 	$result = $csstats_db->query($sql);
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		$template->cycle_vars('actions', array(
// 			'COUNT'  => $row['count'],
// 			'NAME'   => $row['description'],
// 			'REWARD' => sprintf('%+d', $row['reward_player'] * $row['count'] + $row['reward_team'] * $row['count']),
// 
// 			'U_ACTIONINFO' => ilink(sprintf('%s/actioninfo/%s.html', $page['furl'], $row['code'])))
// 		);
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$template->file = 'csstats/' . $mode . '_actions.html';
// }
// elseif( $tab == 'aliases' )
// {
// 	/**
// 	* Имена
// 	*/
// 	$sql = '
// 		SELECT
// 			COUNT(*) AS total_players
// 		FROM
// 			hlstats_PlayerNames
// 		WHERE
// 			playerId = ' . $csstats_db->check_value($player['playerId']);
// 	$result = $csstats_db->query($sql);
// 	$row = $csstats_db->fetchrow($result);
// 	$csstats_db->freeresult($result);
// 
// 	$pagination = pagination(20, $row['total_players'], ilink(sprintf('%s/playerinfo/%s.html?tab=%s', $page['furl'], $url, $tab)));
// 
// 	$sql = '
// 		SELECT
// 			*
// 		FROM
// 			hlstats_PlayerNames
// 		WHERE
// 			playerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		ORDER BY
// 			numuses DESC
// 		LIMIT
// 			' . $pagination['start'] . ', ' . $pagination['on_page'];
// 	$result = $csstats_db->query($sql);
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		$template->cycle_vars('aliases', array(
// 			'ACCURACY'  => num_percent_of($row['hits'], $row['shots']),
// 			'DEATHS'    => num_format($row['deaths']),
// 			'HEADSHOTS' => num_format($row['headshots']),
// 			'HPK'       => num_percent_of($row['headshots'], $row['kills']),
// 			'KILLS'     => num_format($row['kills']),
// 			'NAME'      => htmlspecialchars(abridgement($row['name'], 30)),
// 			'NUMUSES'   => num_format($row['numuses']))
// 		);
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$template->file = 'csstats/' . $mode . '_aliases.html';
// }
// elseif( $tab == 'awards' )
// {
// 	/**
// 	* Награды
// 	*/
// 	$template->file = 'csstats/' . $mode . '_awards.html';
// }
// elseif( $tab == 'chat' )
// {
// 	/**
// 	* Сообщения
// 	*/
// 	$sql = '
// 		SELECT
// 			COUNT(*) AS total_messages
// 		FROM
// 			hlstats_Events_Chat
// 		WHERE
// 			playerId = ' . $csstats_db->check_value($player['playerId']);
// 	$result = $csstats_db->query($sql);
// 	$row = $csstats_db->fetchrow($result);
// 	$csstats_db->freeresult($result);
// 
// 	$pagination = pagination(20, $row['total_messages'], ilink(sprintf('%s/playerinfo/%s.html?tab=%s', $page['furl'], $url, $tab)));
// 
// 	$sql = '
// 		SELECT
// 			eventTime,
// 			message_mode,
// 			message
// 		FROM
// 			hlstats_Events_Chat
// 		WHERE
// 			playerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		ORDER BY
// 			eventTime DESC
// 		LIMIT
// 			' . $pagination['start'] . ', ' . $pagination['on_page'];
// 	$result = $csstats_db->query($sql);
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		$template->cycle_vars('chat', array(
// 			'MESSAGE'      => wordwrap(htmlspecialchars($row['message']), 50, '<br />', true),
// 			'MESSAGE_MODE' => $row['message_mode'],
// 			'TIME'         => $user->create_date($row['eventTime'], '|j F Y|, H:i'))
// 		);
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$template->file = 'csstats/' . $mode . '_chat.html';
// }
// elseif( $tab == 'kills' )
// {
// 	/**
// 	* Фраги
// 	*/
// 	$sql = '
// 		SELECT
// 			COUNT(killerId) AS kills
// 		FROM
// 			hlstats_Events_Frags
// 		WHERE
// 			killerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		GROUP BY
// 			victimId
// 		HAVING
// 			kills >= 5';
// 	$result = $csstats_db->query($sql);
// 	$total_players = $csstats_db->affected_rows();
// 	$csstats_db->freeresult($result);
// 
// 	$pagination = pagination(20, $total_players, ilink(sprintf('%s/playerinfo/%s.html?tab=%s', $page['furl'], $url, $tab)));
// 
// 	$sql = '
// 		SELECT
// 			f.victimId,
// 			COUNT(f.killerId) AS kills,
// 			SUM(f.headshot = 1) AS headshots,
// 			p.lastName
// 		FROM
// 			hlstats_Events_Frags f,
// 			hlstats_Players p
// 		WHERE
// 			f.killerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		AND
// 			f.victimId = p.playerId
// 		GROUP BY
// 			f.victimId
// 		HAVING
// 			kills >= 5
// 		ORDER BY
// 			kills DESC
// 		LIMIT
// 			' . $pagination['start'] . ', ' . $pagination['on_page'];
// 	$result = $csstats_db->query($sql);
// 	$total_players = 0;
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		$template->cycle_vars('players', array(
// 			'HEADSHOTS' => num_format($row['headshots']),
// 			'HPK'       => num_percent_of($row['headshots'], $row['kills']),
// 			'KILLS'     => num_format($row['kills']),
// 			'NAME'      => htmlspecialchars($row['lastName']),
// 
// 			'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $page['furl'], $row['victimId'])))
// 		);
// 
// 		$total_players++;
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$template->file = 'csstats/' . $mode . '_kills.html';
// }
// elseif( $tab == 'maps' )
// {
// 	/**
// 	* Карты
// 	*/
// 	$sql = '
// 		SELECT
// 			COUNT(killerId) as kills,
// 			SUM(headshot = 1) as headshots,
// 			map
// 		FROM
// 			hlstats_Events_Frags
// 		WHERE
// 			killerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		GROUP BY
// 			map
// 		ORDER BY
// 			kills DESC';
// 	$result = $csstats_db->query($sql);
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		$template->cycle_vars('maps', array(
// 			'HEADSHOTS' => num_format($row['headshots']),
// 			'HPK'       => num_percent_of($row['headshots'], $row['kills']),
// 			'KILLS'     => num_format($row['kills']),
// 			'NAME'      => $row['map'],
// 
// 			'U_MAPINFO' => ilink(sprintf('%s/mapinfo/%s.html', $page['furl'], $row['map'])))
// 		);
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$template->file = 'csstats/' . $mode . '_maps.html';
// }
// elseif( $tab == 'progress' )
// {
// 	/**
// 	* Прогресс
// 	*/
// 	$sql = '
// 		SELECT
// 			COUNT(*) as total_dates
// 		FROM
// 			hlstats_Players_History
// 		WHERE
// 			playerId = ' . $csstats_db->check_value($player['playerId']);
// 	$result = $csstats_db->query($sql);
// 	$row = $csstats_db->fetchrow($result);
// 	$csstats_db->freeresult($result);
// 	$total_dates = $row['total_dates'];
// 
// 	$pagination = pagination(30, $total_dates, ilink(sprintf('%s/playerinfo/%s.html?tab=%s', $page['furl'], $url, $tab)));
// 
// 	$sort_key_sql = array(
// 		'a' => 'eventTime',
// 		'b' => 'connection_time',
// 		'c' => 'kills',
// 		'd' => 'deaths',
// 		'e' => 'headshots',
// 		'f' => 'hpk',
// 		'g' => 'accuracy'
// 	);
// 
// 	/**
// 	* Проверка значений $sort_key
// 	*/
// 	if( strlen($sort_key) != 1 )
// 	{
// 		trigger_error('PAGE_NOT_FOUND');
// 	}
// 	elseif( !preg_match('#[abcdefg]#', $sort_key) )
// 	{
// 		trigger_error('PAGE_NOT_FOUND');
// 	}
// 
// 	/* Порядок вывода */
// 	$order_by = $sort_key_sql[$sort_key] . ' ' . ( ( $sort_dir == 'a' ) ? 'ASC' : 'DESC');
// 
// 	$sql = '
// 		SELECT
// 			*,
// 			IFNULL(hits / shots, 0) AS accuracy,
// 			IFNULL(headshots / kills, 0) AS hpk
// 		FROM
// 			hlstats_Players_History
// 		WHERE
// 			playerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		ORDER BY
// 			' . $order_by . '
// 		LIMIT
// 			' . $pagination['start'] . ', ' . $pagination['on_page'];
// 	$result = $csstats_db->query($sql);
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		$template->cycle_vars('progress', array(
// 			'ACCURACY'     => ( $row['accuracy'] ) ? sprintf('%d%%', $row['accuracy'] * 100) : '-',
// 			'DATE'         => $row['eventTime'],
// 			'DEATHS'       => num_format($row['deaths']),
// 			'HEADSHOTS'    => num_format($row['headshots']),
// 			'HPK'          => ( $row['hpk'] ) ? sprintf('%d%%', $row['hpk'] * 100) : '-',
// 			'KILLS'        => num_format($row['kills']),
// 			'SKILL_CHANGE' => ( $row['skill_change'] != 0 ) ? sprintf('%+d', $row['skill_change']) : 0,
// 			'SKILL'        => num_format($row['skill']),
// 			'TIME'         => ( $row['connection_time'] > 86400 ) ? sprintf('%d&nbsp;дн.&nbsp;%02d:%02d:%02d', $row['connection_time'] / 86400, $row['connection_time'] / 3600 % 24, $row['connection_time'] / 60 % 60, $row['connection_time'] % 60) : sprintf('%02d:%02d:%02d', $row['connection_time'] / 3600 % 24, $row['connection_time'] / 60 % 60, $row['connection_time'] % 60))
// 		);
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$sort_link_url = ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $url));
// 
// 	$template->vars(array(
// 		'SORT_DIR'         => $sort_dir,
// 		'SORT_KEY'         => $sort_key,
// 
// 		'U_SORT_ACCURACY'  => create_sort_link('g', 'tab=' . $tab, $sort_link_url),
// 		'U_SORT_DATE'      => create_sort_link('a', 'tab=' . $tab, $sort_link_url),
// 		'U_SORT_DEATHS'    => create_sort_link('d', 'tab=' . $tab, $sort_link_url),
// 		'U_SORT_HEADSHOTS' => create_sort_link('e', 'tab=' . $tab, $sort_link_url),
// 		'U_SORT_HPK'       => create_sort_link('f', 'tab=' . $tab, $sort_link_url),
// 		'U_SORT_KILLS'     => create_sort_link('c', 'tab=' . $tab, $sort_link_url),
// 		'U_SORT_TIME'      => create_sort_link('b', 'tab=' . $tab, $sort_link_url))
// 	);
// 
// 	$template->file = 'csstats/' . $mode . '_progress.html';
// }
// elseif( $tab == 'weapons' )
// {
// 	/**
// 	* Оружие
// 	*/
// 	$sql = '
// 		SELECT
// 			f.weapon,
// 			COUNT(f.weapon) as kills,
// 			SUM(f.headshot = 1) as headshots
// 		FROM
// 			hlstats_Events_Frags f
// 		WHERE
// 			f.killerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		GROUP BY
// 			f.weapon
// 		ORDER BY
// 			kills DESC';
// 	$result = $csstats_db->query($sql);
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		$template->cycle_vars('weapons', array(
// 			'HEADSHOTS' => num_format($row['headshots']),
// 			'HPK'       => num_percent_of($row['headshots'], $row['kills']),
// 			'KILLS'     => num_format($row['kills']),
// 			'NAME'      => $row['weapon'],
// 
// 			'U_WEAPONINFO' => ilink(sprintf('%s/weaponinfo/%s.html', $page['furl'], $row['weapon'])))
// 		);
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$template->file = 'csstats/' . $mode . '_weapons.html';
// }
// else
// {
// 	$ban_addtime = getvar('ban_addtime', 43200);
// 	$ban_reason  = getvar('ban_reason', '');
// 
// 	if( $action == 'addban' )
// 	{
// 		/**
// 		* Бан игрока
// 		*/
// 		if( $auth->check('admin_lead') && $ban_reason && $submit )
// 		{
// 			$sql_ary = array(
// 				'ban_game'   => 'cstrike',
// 				'ban_date'   => $user->ctime,
// 				'ban_time'   => $ban_addtime,
// 				'ban_ip'     => $player['lastAddress'],
// 				'ban_reason' => $ban_reason
// 			);
// 
// 			/* Добавление записи в банлист */
// 			$sql = 'INSERT INTO hlstats_Banlist ' . $csstats_db->build_array('INSERT', $sql_ary);
// 			$csstats_db->query($sql);
// 
// 			/* Обновление статуса */
// 			$sql = '
// 				UPDATE
// 					hlstats_Players
// 				SET
// 					hideranking = 1
// 				WHERE
// 					playerId = ' . $csstats_db->check_value($player['playerId']);
// 			$csstats_db->query($sql);
// 			$player['hideranking'] = 1;
// 		}
// 	}
// 	elseif( $action == 'delete_playerinfo' )
// 	{
// 		/**
// 		* Удаление игрока и всей информации о нём
// 		*/
// 		if( $auth->check('admin_lead') && $submit )
// 		{
// 			$sql = '
// 				DELETE
// 				FROM
// 					hlstats_Events_Frags
// 				WHERE
// 					(killerId = ' . $csstats_db->check_value($player['playerId']) . ' OR victimId = ' . $csstats_db->check_value($player['playerId']) . ')';
// 			$csstats_db->query($sql);
// 
// 			$sql = '
// 				DELETE
// 				FROM
// 					hlstats_PlayerNames
// 				WHERE
// 					playerId = ' . $csstats_db->check_value($player['playerId']);
// 			$csstats_db->query($sql);
// 
// 			$sql = '
// 				DELETE
// 				FROM
// 					hlstats_Players_History
// 				WHERE
// 					playerId = ' . $csstats_db->check_value($player['playerId']);
// 			$csstats_db->query($sql);
// 
// 			$sql = '
// 				DELETE
// 				FROM
// 					hlstats_PlayerUniqueIds
// 				WHERE
// 					playerId = ' . $csstats_db->check_value($player['playerId']);
// 			$csstats_db->query($sql);
// 
// 			$sql = '
// 				DELETE
// 				FROM
// 					hlstats_Players
// 				WHERE
// 					playerId = ' . $csstats_db->check_value($player['playerId']);
// 			$csstats_db->query($sql);
// 
// 			trigger_error('Игрок удален.');
// 		}
// 	}
// 	elseif( $action == 'hide0' || $action == 'hide1' || $action == 'hide2' )
// 	{
// 		/**
// 		* Изменение статуса (видимый, скрытый, читер)
// 		*/
// 		if( $auth->check('admin_lead') )
// 		{
// 			$player['hideranking'] = ( $action == 'hide1' ) ? 1 : ( ( $action == 'hide2' ) ? 2 : 0);
// 
// 			$sql = '
// 				UPDATE
// 					hlstats_Players
// 				SET
// 					hideranking = ' . $csstats_db->check_value($player['hideranking']) . '
// 				WHERE
// 					playerId = ' . $csstats_db->check_value($player['playerId']);
// 			$csstats_db->query($sql);
// 		}
// 	}
// 	elseif( $action == 'reset_ks' )
// 	{
// 		/**
// 		* Сброс серии фрагов
// 		*/
// 		if( $auth->check('admin_lead') || $user->spark_subnet() == $player['lastAddress'] )
// 		{
// 			$sql = '
// 				UPDATE
// 					hlstats_Players
// 				SET
// 					killing_streak = 0,
// 					killing_streak_hs = 0
// 				WHERE
// 					playerId = ' . $csstats_db->check_value($player['playerId']);
// 			$csstats_db->query($sql);
// 			$player['killing_streak'] = 0;
// 		}
// 	}
// 
// 	/**
// 	* Общая информация
// 	*/
// 	$rank = 1;
// 
// 	if( $player['kills'] > 100 && !$player['hideranking'] )
// 	{
// 		/**
// 		* Позиция среди игроков
// 		*/
// 		$sql = '
// 			SELECT
// 				COUNT(*) as total_rank
// 			FROM
// 				hlstats_Players
// 			WHERE
// 				game = ' . $csstats_db->check_value('cstrike') . '
// 			AND
// 				skill > ' . $player['skill'] . '
// 			AND
// 				kills > 100
// 			AND
// 				hideranking = 0';
// 		$result = $csstats_db->query($sql);
// 		$row = $csstats_db->fetchrow($result);
// 		$csstats_db->freeresult($result);
// 
// 		$rank += $row['total_rank'];
// 	}
// 	else
// 	{
// 		$rank = '---';
// 	}
// 
// 	/**
// 	* Проверка банлиста
// 	*/
// 	$sql = '
// 		SELECT
// 			*
// 		FROM
// 			hlstats_Banlist
// 		WHERE
// 			ban_ip = ' . $csstats_db->check_value($player['lastAddress']) . '
// 		ORDER BY
// 			ban_date DESC';
// 	$result = $csstats_db->query($sql);
// 	$ban = $csstats_db->fetchrow($result);
// 	$bans = $csstats_db->affected_rows();
// 	$csstats_db->freeresult($result);
// 
// 	/**
// 	* Любимое оружие
// 	*/
// 	$sql = '
// 		SELECT
// 			f.weapon,
// 			COUNT(f.weapon) AS kills
// 		FROM
// 			hlstats_Events_Frags f
// 		WHERE
// 			f.killerId = ' . $csstats_db->check_value($player['playerId']) . '
// 		GROUP BY
// 			f.weapon
// 		ORDER BY
// 			kills DESC
// 		LIMIT
// 			0, 1';
// 	$result = $csstats_db->query($sql);
// 	$row = $csstats_db->fetchrow($result);
// 	$csstats_db->freeresult($result);
// 	$weapon = ( $row['weapon'] ) ? $row['weapon'] : '---';
// 
// 	/**
// 	* Звания
// 	*/
// 	$sql = '
// 		SELECT
// 			*
// 		FROM
// 			hlstats_Ranks
// 		WHERE
// 			game = ' . $csstats_db->check_value('cstrike') . '
// 		ORDER BY
// 			minKills ASC';
// 	$result = $csstats_db->query($sql);
// 
// 	while( $row = $csstats_db->fetchrow($result) )
// 	{
// 		if( $player['kills'] >= $row['maxKills'] )
// 		{
// 			$template->cycle_vars('ranks', array(
// 				'IMAGE' => $row['image'],
// 				'TITLE' => $row['rankName'],
// 
// 				'U_RANKINFO' => ilink(sprintf('%s/rankinfo/%s.html', $page['furl'], $row['image'])))
// 			);
// 		}
// 		else
// 		{
// 			$template->vars(array(
// 				'RANK_KILLS_NEED' => num_format($row['maxKills'] - $player['kills'] + 1),
// 				'RANK_IMAGE'      => $row['image'],
// 				'RANK_PROGRESS'   => ( $row['minKills'] > $player['kills'] ) ? '0%' : sprintf('%d%%', (($player['kills'] - $row['minKills']) / ($row['maxKills'] - $row['minKills'] + 1)) * 100),
// 				'RANK_TITLE'      => $row['rankName'],
// 
// 				'U_RANKINFO' => ilink(sprintf('%s/rankinfo/%s.html', $page['furl'], $row['image'])))
// 			);
// 
// 			break;
// 		}
// 	}
// 
// 	$csstats_db->freeresult($result);
// 
// 	$banned = false;
// 	$ban_time = '';
// 
// 	if( $ban && ( $ban['ban_time'] == 0 || intval($ban['ban_date'] + ($ban['ban_time'] * 60) - $user->ctime) > 0 ) )
// 	{
// 		/**
// 		* На данный момент игрок всё ещё в бане
// 		*/
// 		$banned = true;
// 		$ban_time = ( $ban['ban_time'] == 0 ) ? 0 : $user->create_date($ban['ban_date'] + $ban['ban_time'] * 60, '|j F Y|, H:i');
// 	}
// 
// 	$template->vars(array(
// 		'ACCURACY'        => num_percent_of($player['hits'], $player['shots']),
// 		'BANNED'          => $banned,
// 		'BAN_ADDTIME'     => $ban_addtime,
// 		'BAN_TIME'        => $ban_time,
// 		'BANS'            => $bans,
// 		'BESTKS'          => $player['killing_streak'],
// 		'BESTKSH'         => $player['killing_streak_hs'],
// 		'BESTRKS'         => $player['round_killing_streak'],
// 		'CONNECTION_TIME' => create_time($player['connection_time'], true),
// 		'DEATHS'          => num_format($player['deaths']),
// 		'HEADSHOTS'       => num_format($player['headshots']),
// 		'HIDERANKING'     => $player['hideranking'],
// 		'HPK'             => num_percent_of($player['headshots'], $player['kills']),
// 		'ID'              => $player['playerId'],
// 		'IP'              => $player['lastAddress'],
// 		'KILLS'           => num_format($player['kills']),
// 		'LAST_EVENT'      => $user->create_date($player['last_event']),
// 		'LAST_SKILL'      => ( $player['last_skill_change'] != 0 ) ? sprintf('%+d', $player['last_skill_change']) : 0,
// 		'NAME'            => htmlspecialchars($player['lastName']),
// 		'RANK'            => ( $player['kills'] > 100 ) ? num_format($rank) : '---',
// 		'ROUNDS'          => num_format($player['rounds']),
// 		'ROUNDS_SURVIVE'  => num_format($player['rounds_survive']),
// 		'ROUNDS_WIN'      => num_format($player['rounds_win']),
// 		'ROUNDS_WINP'     => num_percent_of($player['rounds_win'], $player['rounds']),
// 		'SKILL'           => num_format($player['skill']),
// 		'SPARK'           => is_spark($player['lastAddress']),
// 		'SUICIDES'        => num_format($player['suicides']),
// 		'TEAMKILLS'       => num_format($player['teamkills']),
// 		'WEAPON'          => $weapon,
// 
// 		'S_RESET_KS'   => ( $auth->check('admin_lead') || $user->spark_subnet() == $player['lastAddress'] ) ? true : false,
// 
// 		'U_ACTION'     => ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $url)),
// 		'U_HIDE0'      => ilink(sprintf('%s/%s/%s.html?action=hide0', $page['furl'], $mode, $url)),
// 		'U_HIDE1'      => ilink(sprintf('%s/%s/%s.html?action=hide1', $page['furl'], $mode, $url)),
// 		'U_HIDE2'      => ilink(sprintf('%s/%s/%s.html?action=hide2', $page['furl'], $mode, $url)),
// 		'U_KS'         => ilink(sprintf('%s/killingstreaks.html', $page['furl'])),
// 		'U_RESET_KS'   => ilink(sprintf('%s/%s/%s.html?action=reset_ks', $page['furl'], $mode, $url)),
// 		'U_WEAPONINFO' => ilink(sprintf('%s/weaponinfo/%s.html', $page['furl'], $weapon)))
// 	);
// 
// 	$tab = 'general';
// 	$template->file = 'csstats/' . $mode . '.html';
// }
// 
// $template->vars(array(
// 	'ACTIVE_TAB'       => $tab,
// 
// 	'U_PLAYERACTIONS'  => ilink(sprintf('%s/%s/%s.html?tab=actions', $page['furl'], $mode, $url)),
// 	'U_PLAYERALIASES'  => ilink(sprintf('%s/%s/%s.html?tab=aliases', $page['furl'], $mode, $url)),
// 	'U_PLAYERAWARDS'   => ilink(sprintf('%s/%s/%s.html?tab=awards', $page['furl'], $mode, $url)),
// 	'U_PLAYERCHAT'     => ilink(sprintf('%s/%s/%s.html?tab=chat', $page['furl'], $mode, $url)),
// 	'U_PLAYERINFO'     => ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $url)),
// 	'U_PLAYERKILLS'    => ilink(sprintf('%s/%s/%s.html?tab=kills', $page['furl'], $mode, $url)),
// 	'U_PLAYERMAPS'     => ilink(sprintf('%s/%s/%s.html?tab=maps', $page['furl'], $mode, $url)),
// 	'U_PLAYERPROGRESS' => ilink(sprintf('%s/%s/%s.html?tab=progress', $page['furl'], $mode, $url)),
// 	'U_PLAYERWEAPONS'  => ilink(sprintf('%s/%s/%s.html?tab=weapons', $page['furl'], $mode, $url)))
// );
// 
// $db->total_queries += $csstats_db->total_queries;
// $csstats_db->close();
// 
// navigation_link_custom(ilink(sprintf('%s/players.html', $page['furl'])), 'Игроки', 'user_black');
// navigation_link_custom(ilink(sprintf('%s/%s/%s.html', $page['furl'], $mode, $url)), htmlspecialchars($player['lastName']), 'card_address');
// 
// page_header(htmlspecialchars($player['lastName']));
// 
// page_footer();
