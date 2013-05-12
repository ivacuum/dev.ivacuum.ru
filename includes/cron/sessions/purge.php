<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\cron\sessions;

use fw\cron\task;

/**
* Чистка устаревших сессий
*/
class purge extends task
{
	public function run()
	{
		$session_lifetime = ini_get('session.gc_maxlifetime');
		
		/* Удаляем сессии гостей */
		$sql = 'DELETE FROM site_sessions WHERE user_id = 0 AND session_time < ?';
		$this->db->query($sql, [$this->ctime - $session_lifetime]);
		$this->log('Удалено сессий гостей: ' . $this->db->affected_rows());

		/**
		* Получаем сессии с истекшим временем жизни
		* Причем для каждого пользователя получаем только последнюю
		*/
		$sql = '
			SELECT
				user_id,
				session_ip,
				session_page,
				MAX(session_time) AS recent_time
			FROM
				site_sessions
			WHERE
				session_time < ?
			GROUP BY
				user_id,
				session_page';
		$result = $this->db->query($sql, [$this->ctime - $session_lifetime]);
		$del_users_id = [];

		while ($row = $this->db->fetchrow($result))
		{
			$sql_ary = [
				'user_session_page' => (string) $row['session_page'],
				'user_last_visit'   => (int) $row['recent_time'],
				'user_ip'           => (string) $row['session_ip']
			];

			$sql = 'UPDATE site_users SET :update_ary WHERE user_id = ?';
			$this->db->query($sql, [$row['user_id'], ':update_ary' => $this->db->build_array('UPDATE', $sql_ary)]);

			$del_users_id[] = (int) $row['user_id'];
		}

		$this->db->freeresult($result);

		if (!empty($del_users_id))
		{
			$sql = 'DELETE FROM site_sessions WHERE :del_users_id AND session_time < ?';
			$this->db->query($sql, [$this->ctime - $session_lifetime, ':del_users_id' => $this->db->in_set('user_id', $del_users_id)]);
			$this->log('Удалено сессий пользователей: ' . sizeof($del_users_id));
		}

		/**
		* Удаляем ключи автовхода
		*/
		if ($this->config['autologin.time'])
		{
			$sql = 'DELETE FROM site_sessions_keys WHERE last_login < ?';
			$this->db->query($sql, [$this->ctime - (86400 * $this->config['autologin.time'])]);
			$this->log('Удалено ключей сессий: ' . $this->db->affected_rows());

			$sql = 'OPTIMIZE TABLE site_sessions_keys';
			$this->db->query($sql);
		}

		$sql = 'OPTIMIZE TABLE site_sessions';
		$this->db->query($sql);
		
		return true;
	}
}
