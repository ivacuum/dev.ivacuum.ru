<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app;

use app\models\page;

class who_is_online extends page
{
	public function index()
	{
		$groups_list   = '';
		$guests_online = 0;
		$online_list   = '';
		$prev_id       = array();
		$prev_ip       = array();
		$users_online  = 0;

		/**
		* Получаем данные пользователей, которые посетили сайт в последние $config['load_online_time'] минут
		*/
		$sql = '
			SELECT
				s.session_time,
				s.session_ip,
				s.session_browser,
				s.session_domain,
				s.session_page,
				s.session_referer,
				u.user_id,
				u.username,
				u.user_url,
				u.user_colour
			FROM
				' . SESSIONS_TABLE . ' s
			LEFT JOIN
				' . USERS_TABLE . ' u ON (u.user_id = s.user_id)
			WHERE
				s.user_id > 0
			AND
				s.session_time >= ' . ($this->user->ctime - ($this->config['load_online_time'])) . '
			ORDER BY
				s.session_time DESC';
		$this->db->query($sql);

		while( $row = $this->db->fetchrow() )
		{
			if( !isset($prev_id[$row['user_id']]) )
			{
				$patterns[0] = '/ ?\.net clr \d\.\d\.\d{1,5}(\.\d{1,5})?;?/i';
				$patterns[1] = '/ ?mrsputnik \d\, \d\, \d\, \d{1,3};?/i';
				$patterns[2] = '/ sputnik \d\.\d\.\d\.\d{1,3};?/i';
				$patterns[3] = '/ \(khtml\, like gecko\)/i';
				$patterns[4] = '/ mra \d\.\d \(build \d{1,5}\);?/i';
				$patterns[5] = '/ trident\/\d\.\d;?/i';
				$row['session_browser'] = preg_replace($patterns, '', $row['session_browser']) . '<br />';

				$this->template->append('users', array(
					'BROWSER' => $row['session_browser'],
					'DOMAIN'  => $row['session_domain'],
					'IP'      => $row['session_ip'],
					'NAME'    => $this->user_profile_link('', $row['username'], $row['user_colour'], $row['user_url'], $row['user_id']),
					'PAGE'    => $row['session_page'],
					'REFERER' => urldecode($row['session_referer']),
					'TIME'    => $this->user->create_date($row['session_time']))
				);

				$prev_id[$row['user_id']] = 1;
				$users_online++;
			}
		}

		$this->db->freeresult();

		$sql = '
			SELECT
				session_time,
				session_ip,
				session_browser,
				session_domain,
				session_page,
				session_referer
			FROM
				' . SESSIONS_TABLE . '
			WHERE
				user_id = 0
			AND
				session_time >= ' . $this->db->check_value($this->user->ctime - $this->config['load_online_time']) . '
			ORDER BY
				session_time DESC';
		$this->db->query($sql);

		while( $row = $this->db->fetchrow() )
		{
			if( !isset($prev_ip[$row['session_ip']]) )
			{
				$patterns[0] = '/ ?\.net clr \d\.\d\.\d{1,5}(\.\d{1,5})?;?/i';
				$patterns[1] = '/ ?mrsputnik \d\, \d\, \d\, \d{1,3};?/i';
				$patterns[2] = '/ sputnik \d\.\d\.\d\.\d{1,3};?/i';
				$patterns[3] = '/ \(khtml\, like gecko\)/i';
				$patterns[4] = '/ mra \d\.\d \(build \d{1,5}\);?/i';
				$patterns[5] = '/ trident\/\d\.\d;?/i';
				$row['session_browser'] = preg_replace($patterns, '', $row['session_browser']) . '<br />';

				$this->template->append('users', array(
					'BROWSER' => $row['session_browser'],
					'DOMAIN'  => $row['session_domain'],
					'IP'      => $row['session_ip'],
					'NAME'    => $this->user->lang('GUEST'),
					'PAGE'    => $row['session_page'],
					'REFERER' => urldecode($row['session_referer']),
					'TIME'    => $this->user->create_date($row['session_time']))
				);

				$prev_ip[$row['session_ip']] = 1;
				$guests_online++;
			}
		}

		$this->db->freeresult();

		/**
		* Сейчас посетителей на сайте: 41 :: зарегистрированных: 2, гостей: 39.
		*/
		$online_list = sprintf($this->user->lang['ONLINE_LIST_TOTAL'], $users_online + $guests_online);
		$online_list .= sprintf($this->user->lang['ONLINE_LIST_REG'], $users_online);
		$online_list .= sprintf($this->user->lang['ONLINE_LIST_GUESTS'], $guests_online);

		/**
		* Список групп (для легенды)
		*/
		$groups = $this->cache->obtain_groups();

		foreach( $groups as $row )
		{
			if( !$row['group_legend'] )
			{
				continue;
			}

			$groups_link = '<span style="color: #' . $row['group_colour'] . ';">' . $this->user->lang($row['group_name']) . '</span>';

			$groups_list .= ( $groups_list ) ? ', ' . $groups_link : $groups_link;
		}

		$this->template->assign(array(
			'GROUPS_LIST' => !empty($groups_list) ? $groups_list : '',
			'ONLINE_LIST' => $online_list,
			'ONLINE_TIME' => sprintf($this->user->lang['ONLINE_TIME'], $this->config['load_online_time'] / 60)
		));
	}
}
