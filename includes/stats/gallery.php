<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app\stats;

use app\models\page;

/**
* Статистика галереи
*/
class gallery extends page
{
	/**
	* Сводка
	*/
	public function index()
	{
		$stats = $this->cache->obtain_image_stats();

		$sql = '
			SELECT
				COUNT(i.image_views) as total_images,
				SUM(i.image_size) as total_size,
				SUM(i.image_views) as total_views,
				u.user_id,
				u.username,
				u.user_url,
				u.user_colour
			FROM
				' . IMAGES_TABLE . ' i,
				' . USERS_TABLE . ' u
			WHERE
				i.user_id = u.user_id
			GROUP BY
				i.user_id
			ORDER BY
				total_views DESC';
		$this->db->query_limit($sql, 50, 0, 60);

		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('users', [
				'IMAGES'  => num_format($row['total_images']),
				'PROFILE' => $this->user_profile_link('', $row['username'], $row['user_colour'], $row['user_url'], $row['user_id']),
				'SIZE'    => $row['total_size'],
				'VIEWS'   => num_format($row['total_views'])
			]);
		}

		$this->db->freeresult();

		$this->template->vars([
			'TODAY_IMAGES'  => num_format($stats['today_images']),
			'TOTAL_IMAGES'  => num_format($stats['total_images']),
			'TOTAL_SIZE'    => $stats['total_size'],
			'TOTAL_TRAFFIC' => $stats['total_traffic'],
			'TOTAL_VIEWS'   => num_format($stats['total_views'])
		]);
	}
	
	/**
	* Где смотрят изображения
	*/
	public function referers()
	{
		$sql = '
			SELECT
				*
			FROM
				' . IMAGE_VIEWS_TABLE . '
			ORDER BY
				views_count DESC';
		$this->db->query($sql);

		while( $row = $this->db->fetchrow() )
		{
			$this->template->assign(mb_strtoupper($row['views_from']) . '_VIEWS', num_format($row['views_count']));
		}

		$this->db->freeresult();

		$sql = '
			SELECT
				*
			FROM
				' . IMAGE_REFS_TABLE . '
			ORDER BY
				ref_views DESC';
		$this->db->query_limit($sql, 50);
		$i = 1;

		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('ref', [
				'DOMAIN' => $row['ref_domain'],
				'VIEWS'  => num_format($row['ref_views'])
			]);

			$i++;
		}

		$this->db->freeresult();
	}
}
