<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\cron\captcha;

use fw\cron\task;

/**
* Чистка неактуальных капч
*/
class purge extends task
{
	public function run()
	{
		$sql = '
			SELECT DISTINCT
				c.session_id
			FROM
				site_confirm c
			LEFT JOIN
				site_sessions s ON (s.session_id = c.session_id)
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
			$sql = 'DELETE FROM site_confirm WHERE :session_ids';
			$this->db->query($sql, [':session_ids' => $this->db->in_set('session_id', $sql_in)]);
			$this->log('Капч удалено: ' . $this->db->affected_rows());
		}
	}
}
