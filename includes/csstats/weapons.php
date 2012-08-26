<?php
/**
*
* @package ivacuum.ru
* @copyright (c) 2012
*
*/

namespace app\csstats;

use app\models\csstats\page;

class weapons extends page
{
	public function index()
	{
		$sort_dir = $this->request->variable('sd', 'd');
		$sort_key = $this->request->variable('sk', 'a');
		$sort_key_sql = array(
			'a'	=> 'kills',
			'b' => 'headshots',
			'c' => 'hpk'
		);

		/**
		* Проверка значений $sort_key
		*/
		if( strlen($sort_key) != 1 )
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		elseif( !preg_match('#[abc]#', $sort_key) )
		{
			trigger_error('PAGE_NOT_FOUND');
		}

		/* Порядок вывода */
		$order_by = $sort_key_sql[$sort_key] . ' ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');

		/**
		* Список оружия
		*/
		$sql = '
			SELECT
				*,
				IFNULL(headshots / kills, 0) AS hpk
			FROM
				hlstats_Weapons
			WHERE
				game = ' . $this->csstats_db->check_value('cstrike') . '
			AND
				kills > 0
			ORDER BY
				' . $order_by;
		$this->csstats_db->query($sql);
		$total_weapons = 0;

		while( $row = $this->csstats_db->fetchrow() )
		{
			$this->template->append('weapons', array(
				'CODE'      => $row['code'],
				'HEADSHOTS' => num_format($row['headshots']),
				'HPK'       => ($row['hpk']) ? sprintf('%d%%', $row['hpk'] * 100) : '-',
				'KILLS'     => num_format($row['kills']),
				'MF'        => $row['modifier'],
				'NAME'      => $row['name'],

				'U_WEAPONINFO' => ilink(sprintf('%s/%s', $this->urls['single'], $row['code'])))
			);

			$total_weapons++;
		}
		
		$this->csstats_db->freeresult();

		$this->template->assign(array(
			'SORT_DIR'      => $sort_dir,
			'SORT_KEY'      => $sort_key,
			'TOTAL_WEAPONS' => $total_weapons,

			'U_SORT_HEADSHOTS' => $this->create_sort_link('b'),
			'U_SORT_HPK'       => $this->create_sort_link('c'),
			'U_SORT_KILLS'     => $this->create_sort_link('a'))
		);
	}
	
	/**
	* Информация об оружии
	*/
	public function single($weapon = '')
	{
		if( !$weapon )
		{
			trigger_error('WEAPON_NOT_FOUND');
		}
		
		$sql = '
			SELECT
				*
			FROM
				hlstats_Weapons w
			WHERE
				game = ' . $this->csstats_db->check_value('cstrike') . '
			AND
				code = ' . $this->csstats_db->check_value($weapon);
		$this->csstats_db->query($sql);
		$row = $this->csstats_db->fetchrow();
		$this->csstats_db->freeresult();

		if( !$row )
		{
			trigger_error('WEAPON_NOT_FOUND');
		}

		$weapon_name = htmlspecialchars($row['name']);

		$this->template->assign(array(
			'CODE'      => $row['code'],
			'HEADSHOTS' => num_format($row['headshots']),
			'HPK'       => $this->num_percent_of($row['headshots'], $row['kills']),
			'KILLS'     => num_format($row['kills']),
			'NAME'      => $row['name'])
		);

		$sql = '
			SELECT
				f.killerId,
				p.lastName,
				COUNT(f.map) as kills,
				SUM(f.headshot = 1) as headshots
			FROM
				hlstats_Events_Frags f,
				hlstats_Players p
			WHERE
				p.playerId = f.killerId
			AND
				f.weapon = ' . $this->csstats_db->check_value($weapon) . '
			AND
				p.game = ' . $this->csstats_db->check_value('cstrike') . '
			AND
				p.hideranking = 0
			GROUP BY
				f.killerId
			ORDER BY
				kills DESC';
		$this->csstats_db->query_limit($sql, 50);

		while( $row = $this->csstats_db->fetchrow() )
		{
			$this->template->append('players', array(
				'HEADSHOTS' => num_format($row['headshots']),
				'HPK'       => $this->num_percent_of($row['headshots'], $row['kills']),
				'KILLS'     => num_format($row['kills']),
				'NAME'      => $row['lastName'],

				'U_PLAYERINFO' => ilink(sprintf('%s/%d', $this->get_handler_url('csstats\\players::single'), $row['killerId'])))
			);
		}

		$this->csstats_db->freeresult();

		navigation_link(ilink(sprintf('%s/%s', $this->url, $weapon)), $weapon_name);
	}
}
