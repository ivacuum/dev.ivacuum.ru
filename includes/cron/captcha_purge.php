<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app\cron;

use fw\cron\task;

/**
* Чистка неактуальных капч
*/
class captcha_purge extends task
{
	public function run()
	{
		$sql = '
			SELECT DISTINCT
				c.session_id
			FROM
				' . CONFIRM_TABLE . ' c
			LEFT JOIN
				' . SESSIONS_TABLE . ' s ON (s.session_id = c.session_id)
			WHERE
				s.session_id IS NULL';
		$result = $this->db->query($sql);
		$sql_in = [];
		
		while ($row = $this->db->fetchrow($result))
		{
			$sql_in[] = (string) $row['session_id'];
		}
		
		$this->db->freeresult($result);
		
		if (!empty($sql_in))
		{
			$sql = '
				DELETE
				FROM
					' . CONFIRM_TABLE . '
				WHERE
					' . $this->db->in_set('session_id', $sql_in);
			$this->db->query($sql);
			$this->log('Капч удалено: ' . $this->db->affected_rows());
		}
	}
}
