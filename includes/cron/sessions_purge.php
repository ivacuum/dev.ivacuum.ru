<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app\cron;

use fw\cron\task;

/**
* Чистка устаревших сессий
*/
class sessions_purge extends task
{
	public function run()
	{
		/* Удаляем сессии гостей */
		$sql = '
			DELETE
			FROM
				' . SESSIONS_TABLE . '
			WHERE
				user_id = 0
			AND
				session_time < ' . $this->db->check_value($this->ctime - $this->config['session_length']);
		$this->db->query($sql);
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
				' . SESSIONS_TABLE . '
			WHERE
				session_time < ' . $this->db->check_value($this->ctime - $this->config['session_length']) . '
			GROUP BY
				user_id,
				session_page';
		$result = $this->db->query($sql);
		$del_users_id = array();

		while ($row = $this->db->fetchrow($result))
		{
			$sql_ary = array(
				'user_session_page' => (string) $row['session_page'],
				'user_last_visit'   => (int) $row['recent_time'],
				'user_ip'           => (string) $row['session_ip']
			);

			$sql = '
				UPDATE
					' . USERS_TABLE . '
				SET
					' . $this->db->build_array('UPDATE', $sql_ary) . '
				WHERE
					user_id = ' . $this->db->check_value($row['user_id']);
			$this->db->query($sql);

			$del_users_id[] = (int) $row['user_id'];
		}

		$this->db->freeresult($result);

		if (!empty($del_users_id))
		{
			$sql = '
				DELETE
				FROM
					' . SESSIONS_TABLE . '
				WHERE
					' . $this->db->in_set('user_id', $del_users_id) . '
				AND
					session_time < ' . $this->db->check_value($this->ctime - $this->config['session_length']);
			$this->db->query($sql);
			$this->log('Удалено сессий пользователей: ' . sizeof($del_users_id));
		}

		/**
		* Удаляем ключи автовхода
		*/
		if ($this->config['autologin_time'])
		{
			$sql = '
				DELETE
				FROM
					' . SESSIONS_KEYS_TABLE . '
				WHERE
					last_login < ' . $this->db->check_value($this->ctime - (86400 * $this->config['autologin_time']));
			$this->db->query($sql);
			$this->log('Удалено ключей сессий: ' . $this->db->affected_rows());

			$sql = 'OPTIMIZE TABLE ' . SESSIONS_KEYS_TABLE;
			$this->db->query($sql);
		}

		$sql = 'OPTIMIZE TABLE ' . SESSIONS_TABLE;
		$this->db->query($sql);
		
		return true;
	}
}
