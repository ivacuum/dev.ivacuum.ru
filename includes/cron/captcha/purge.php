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
		$sql = 'DELETE FROM site_confirm WHERE expire < ?';
		$this->db->query($sql, [$this->ctime]);
		$this->log('Капч удалено: ' . $this->db->affected_rows());
		
		return true;
	}
}
