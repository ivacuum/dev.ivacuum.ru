<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app\cron\config;

use fw\cron\task;

/**
* Пересчет значений динамических переменных
*/
class sync extends task
{
	public function run()
	{
		/* Количество комментариев */
		$sql = 'SELECT COUNT(*) as total FROM site_comments';
		$this->db->query($sql);
		$num_comments = $this->db->fetchfield('total');
		$this->db->freeresult();
		$this->config->set('num_comments', $num_comments, 0);
		$this->log('num_comments: ' . $num_comments);
		
		/* Количество новостей (для каждого сайта своё) */
		$sql = ' SELECT site_id, COUNT(*) as total FROM site_news GROUP BY site_id';
		$result = $this->db->query($sql);

		while ($row = $this->db->fetchrow($result))
		{
			$this->config->set('num_news', $row['total'], $row['site_id']);
			$this->log(sprintf('num_news #%d: %d', $row['site_id'], $row['total']));
		}

		$this->db->freeresult($result);

		/* Количество зарегистрированных посетителей */
		$sql = 'SELECT COUNT(*) as total FROM site_users';
		$this->db->query($sql);
		$num_users = $this->db->fetchfield('total');
		$this->db->freeresult();
		$this->config->set('num_users', $num_users, 0);
		$this->log('num_users: ' . $num_users);

		/* Последний зарегистрированный посетитель */
		$sql = 'SELECT user_id, username FROM site_users ORDER BY user_id DESC';
		$this->db->query_limit($sql, [], 1);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		$this->config->set('newest_user_id', $row['user_id'], 0);
		$this->config->set('newest_username', $row['username'], 0);
		$this->log('newest_user_id: ' . $row['user_id']);
		$this->log('newest_username: ' . $row['username']);
		
		return true;
	}
}
