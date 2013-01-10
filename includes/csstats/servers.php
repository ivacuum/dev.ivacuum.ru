<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app\csstats;

use app\models\csstats\page;

class servers extends page
{
	/**
	* Обзор серверов
	*/
	public function index()
	{
		$sql = '
			SELECT
				serverId,
				name,
				IF(publicaddress != "", publicaddress, CONCAT(address, ":", port)) AS addr,
				statusurl,
				kills,
				headshots,
				act_players,
				max_players,
				act_map,
				map_started,
				map_ct_wins,
				map_ts_wins
			FROM
				hlstats_Servers
			WHERE
				game = ' . $this->csstats_db->check_value('cstrike') . '
			AND
				active = 1
			ORDER BY
				serverId ASC';
		$result = $this->csstats_db->query($sql);

		while( $row = $this->csstats_db->fetchrow($result) )
		{
			$time_played = ( $row['map_started'] > 0 ) ? $this->user->ctime - $row['map_started'] : 0;

			/**
			* Список игроков для каждого сервера
			*/
			$sql = '
				SELECT
					*
				FROM
					hlstats_Livestats
				WHERE
					server_id = ' . $this->csstats_db->check_value($row['serverId']) . '
				ORDER BY
					kills DESC,
					deaths ASC';
			$result2 = $this->csstats_db->query($sql);

			$total = array(
				'ct' => array(
					'deaths'    => 0,
					'headshots' => 0,
					'kills'     => 0,
					'players'   => 0,
					'skill'     => 0
				),
				't' => array(
					'deaths'    => 0,
					'headshots' => 0,
					'kills'     => 0,
					'players'   => 0,
					'skill'     => 0
				),
				's' => array(
					'deaths'    => 0,
					'headshots' => 0,
					'kills'     => 0,
					'players'   => 0,
					'skill'     => 0
				)
			);

			$players = array();

			while( $data = $this->csstats_db->fetchrow($result2) )
			{
				$team = ( $data['team'] == 'CT' ) ? 'ct' : (($data['team'] == 'TERRORIST') ? 't' : 's' );

				$total[$team]['deaths']    += $data['deaths'];
				$total[$team]['headshots'] += $data['headshots'];
				$total[$team]['kills']     += $data['kills'];
				$total[$team]['players']++;
				$total[$team]['skill']     += $data['skill'];

				$data['n']    = $total[$team]['players'];
				$data['team'] = $team;
				$players[]    = $data;
			}

			$this->csstats_db->freeresult($result2);

			$this->template->append('servers', array(
				'ADDRESS'    => $row['addr'],
				'HEADSHOTS'  => num_format($row['headshots']),
				'ID'         => $row['serverId'],
				'KILLS'      => num_format($row['kills']),
				'NAME'       => htmlspecialchars($row['name']),
				'NUMPLAYERS' => $total['ct']['players'] + $total['t']['players'] + $total['s']['players'],
				'MAP'        => $row['act_map'],
				'MAXPLAYERS' => $row['max_players'],
				'PLAYED'     => $this->get_time_played($time_played),

				'CT_DEATHS'    => $total['ct']['deaths'],
				'CT_HEADSHOTS' => $total['ct']['headshots'],
				'CT_KILLS'     => $total['ct']['kills'],
				'CT_PLAYERS'   => $total['ct']['players'],
				'CT_SKILL'     => ( $total['ct']['players'] > 0 ) ? num_format($total['ct']['skill'] / $total['ct']['players']) : '',
				'CT_WINS'      => $row['map_ct_wins'],

				'T_DEATHS'    => $total['t']['deaths'],
				'T_HEADSHOTS' => $total['t']['headshots'],
				'T_KILLS'     => $total['t']['kills'],
				'T_PLAYERS'   => $total['t']['players'],
				'T_SKILL'     => ( $total['t']['players'] > 0 ) ? num_format($total['t']['skill'] / $total['t']['players']) : '',
				'T_WINS'      => $row['map_ts_wins'],

				'S_DEATHS'    => $total['s']['deaths'],
				'S_HEADSHOTS' => $total['s']['headshots'],
				'S_KILLS'     => $total['s']['kills'],
				'S_PLAYERS'   => $total['s']['players'],
				'S_SKILL'     => ( $total['s']['players'] > 0 ) ? num_format($total['s']['skill'] / $total['s']['players']) : '',

				'U_DETAILS' => ilink(sprintf('%s/%d', $this->get_handler_url('servers::single'), $row['serverId'])),
				'U_MAP'     => ilink(sprintf('%s/%s', $this->get_handler_url('maps::single'), $row['act_map']))
			));

			foreach( $players as $k => $data )
			{
				$time_played = $this->user->ctime - $data['connected'];

				$this->template->append('servers.' . $data['team'], array(
					'ACCURACY'     => $this->num_percent_of($data['hits'], $data['shots']),
					'DEATHS'       => $data['deaths'],
					'HAS_BOMB'     => $data['has_bomb'],
					'HEADSHOTS'    => $data['headshots'],
					'HPK'          => $this->num_percent_of($data['headshots'], $data['kills']),
					'IP'           => $data['address'],
					'IS_DEAD'      => $data['is_dead'],
					'KILLS'        => $data['kills'],
					'N'            => $data['n'],
					'NAME'         => $data['name'],
					'PING'         => ( $data['ping'] ) ? $data['ping'] : '-',
					'PLAYED'       => $this->get_time_played($time_played),
					'SKILL'        => num_format($data['skill']),
					'SKILL_CHANGE' => ( $data['skill_change'] ) ? sprintf('%+d', $data['skill_change']) : '-',
					'SPARK'        => $this->is_spark($data['address']),

					'U_DETAILS' => ilink(sprintf('%s/playerinfo/%d.html', $this->url, $data['player_id'])))
				);
			}

			unset($total);
			unset($players);
		}

		$this->csstats_db->freeresult($result);
	}
	
	/**
	* Информация о сервере
	*/
	public function single($server_id = '')
	{
		$server_id = (int) $server_id;
		
		$sql = '
			SELECT
				*
			FROM
				hlstats_Servers
			WHERE
				serverId = ' . $this->csstats_db->check_value($server_id);
		$this->csstats_db->query($sql);
		$row = $this->csstats_db->fetchrow();
		$this->csstats_db->freeresult();

		if( !$row )
		{
			trigger_error('SERVER_NOT_FOUND');
		}

		/**
		* Определяем переменные
		*/
		$server_name = htmlspecialchars($row['name']);
		$time_played = $this->user->ctime - $row['map_started'];

		$this->template->assign(array(
			'ACT_PLAYERS'   => $row['act_players'],
			'ADDRESS'       => $row['address'],
			'BOMBS_DEFUSED' => num_format($row['bombs_defused']),
			'BOMBS_PLANTED' => num_format($row['bombs_planted']),
			'CT_WINS'       => num_format($row['ct_wins']),
			'HEADSHOTS'     => num_format($row['headshots']),
			'HPK'           => $this->num_percent_of($row['headshots'], $row['kills']),
			'ID'            => $row['serverId'],
			'KICKS'         => num_format($row['kicks']),
			'KILLS'         => num_format($row['kills']),
			'MAP'           => $row['act_map'],
			'MAP_CHANGES'   => num_format($row['map_changes']),
			'MAX_PLAYERS'   => $row['max_players'],
			'NAME'          => htmlspecialchars($row['name']),
			'NEXTMAP'       => $row['nextmap'],
			'PLAYED'        => $this->get_time_played($time_played),
			'PLAYERS'       => num_format($row['players']),
			'PORT'          => $row['port'],
			'ROUNDS'        => num_format($row['rounds']),
			'SLAPS'         => num_format($row['slaps']),
			'SLAYS'         => num_format($row['slays']),
			'SUICIDES'      => num_format($row['suicides']),
			'T_WINS'        => num_format($row['ts_wins']),

			'U_ACTION'  => ilink(sprintf('%s/%d', $this->get_handler_url('servers::single'), $server_id)),
			'U_MAP'     => ilink(sprintf('%s/%s', $this->get_handler_url('maps::single'), $row['act_map'])),
			'U_NEXTMAP' => ilink(sprintf('%s/%s', $this->get_handler_url('maps::single'), $row['nextmap']))
		));

		/**
		* Список карт сервера
		*/
		$sql = '
			SELECT
				*
			FROM
				hlstats_Servers_Maps
			WHERE
				server_id = ' . $this->csstats_db->check_value($server_id) . '
			ORDER BY
				active DESC,
				map_last_time DESC';
		$this->csstats_db->query($sql);

		while( $row = $this->csstats_db->fetchrow() )
		{
			$this->template->append('maplist', array(
				'ACTIVE'    => $row['active'],
				'NAME'      => $row['map'],
				'LAST_TIME' => $this->user->create_date($row['map_last_time']),

				'U_MAPINFO' => ilink(sprintf('%s/%s', $this->get_handler_url('maps::single'), $row['map']))
			));
		}

		$this->csstats_db->freeresult($result);

		navigation_link(ilink(sprintf('%s/%d', $this->get_handler_url('servers::single'), $server_id)), $server_name, 'server');
	}
}
