<?php namespace app\cron\newpasswd;

use fw\cron\task;

/**
* Чистка ключей для восстановления пароля
*/
class purge extends task
{
	public function run()
	{
		$sql = 'UPDATE site_users SET user_newpasswd = ""';
		$this->db->query($sql);
		$this->log('Удалено ключей для восстановления пароля: ' . $this->db->affected_rows());
		
		return true;
	}
}
