<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use app\models\page;

/**
* Пользователи
*/
class users extends page
{
	public function index()
	{
		trigger_error('NOT_IMPLEMENTED');
	}
	
	/**
	* Просмотр профиля
	*/
	public function profile($user_id)
	{
		if ($user_id != $this->user['user_id'])
		{
			$sql = 'SELECT * FROM site_users WHERE user_id = ?';
			$this->db->query($sql, [$user_id]);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
		}
		else
		{
			/* Просмотр своего профиля */
			$row = $this->user->data;
		}
		
		if (!$row)
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$this->breadcrumbs($row['username'], $this->user_profile_link('raw', $row['username'], false, $row['user_url'], $row['user_id']), 'card_address');
		
		/* Загрузка званий */
		$ranks = $this->cache->obtain_ranks();
		
		$this->template->assign([
			'COMMENTS'   => $row['user_posts'],
			'FROM'       => $row['user_from'],
			'INTERESTS'  => $row['user_interests'],
			'ICQ'        => number_format(intval($row['user_icq']), 0, '.', '-'),
			'IP'         => $row['user_ip'],
			'JID'        => $row['user_jid'],
			'LASTVISIT'  => $row['user_last_visit'] ? $this->user->create_date($row['user_last_visit']) : '',
			'OCCUPATION' => $row['user_occ'],
			'ONLINE'     => $this->user->ctime - $row['user_last_visit'] < $this->config['load_online_time'],
			
			'RANK_IMG'   => isset($ranks[$row['user_rank']]['rank_image']) ? $ranks[$row['user_rank']]['rank_image'] : '',
			'RANK_TITLE' => isset($ranks[$row['user_rank']]['rank_title']) ? $ranks[$row['user_rank']]['rank_title'] : '',
			'REGDATE'    => $this->user->create_date($row['user_regdate']),
			'USERNAME'   => $this->user_profile_link('plain', $row['username'], $row['user_colour'])
		]);
	}
}
