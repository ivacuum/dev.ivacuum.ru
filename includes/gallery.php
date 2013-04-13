<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use app\models\page;

/**
* Галерея изображений
*/
class gallery extends page
{
	/**
	* Загруженные картинки
	*/
	public function index()
	{
		$this->user->is_auth('redirect');

		$total_images = $total_size = $total_traffic = $total_views = 0;

		$sql = '
			SELECT
				COUNT(*) AS total_images,
				SUM(image_size) AS total_size,
				SUM(image_views) AS total_views,
				SUM(image_size * image_views) AS total_traffic
			FROM
				site_images
			WHERE
				user_id = ?';
		$this->db->query($sql, [$this->user['user_id']]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		$total_images  = $row['total_images'];
		$total_size    = $row['total_size'];
		$total_traffic = $row['total_traffic'];
		$total_views   = $row['total_views'];

		if (!$total_images)
		{
			trigger_error('Вы еще не <a href="http://up.ivacuum.ru/"><b>загрузили</b></a> ни одного изображения.');
		}

		$pagination = pagination($this->config['gallery.images.on_page'], $total_images, ilink($this->url));
		
		/* Последние загруженные изображения */
		$sql = '
			SELECT
				*
			FROM
				site_images
			WHERE
				user_id = ?
			ORDER BY
				image_time DESC';
		$this->db->query_limit($sql, [$this->user['user_id']], $pagination['on_page'], $pagination['offset']);

		while ($row = $this->db->fetchrow())
		{
			$outdate = $row['image_touch'] ? time() - $row['image_touch'] : time() - $row['image_time'];

			$this->template->append('images', [
				'DATE'    => $row['image_date'],
				'ID'      => $row['image_id'],
				'OUTDATE' => $outdate > $this->config['gallery.images.purge_interval'] ? $outdate : '',
				'TIME'    => $this->user->create_date($row['image_time'], '|j F Y|, H:i', false, true),
				'URL'     => $row['image_url'],
				'VIEWS'   => $row['image_views'],
			]);
		}

		$this->db->freeresult();

		$this->template->assign([
			'TOTAL_IMAGES'  => $total_images,
			'TOTAL_SIZE'    => $total_size,
			'TOTAL_TRAFFIC' => $total_traffic,
			'TOTAL_VIEWS'   => $total_views,
		]);
	}

	/**
	* Удаление одиночного изображения
	*/
	public function delete_image()
	{
		$this->user->is_auth('redirect');

		$image_id = $this->request->variable('image_id', 0);
		
		if ($image_id < 1)
		{
			trigger_error('DATA_NOT_FOUND');
		}

		$sql = '
			SELECT
				*
			FROM
				site_images
			WHERE
				image_id = ?
			AND
				user_id = ?';
		$this->db->query($sql, [$image_id, $this->user['user_id']]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		if (!$row)
		{
			trigger_error('DATA_NOT_FOUND');
		}

		$date = implode('/', str_split($row['image_date'], 2));

		@unlink("{$this->config['gallery.images.upload_dir']}{$date}/{$row['image_url']}");
		@unlink("{$this->config['gallery.images.upload_dir']}{$date}/t/{$row['image_url']}");
		@unlink("{$this->config['gallery.images.upload_dir']}{$date}/s/{$row['image_url']}");

		$sql = '
			DELETE
			FROM
				site_images
			WHERE
				image_id = ?';
		$this->db->query($sql, [$image_id]);

		$this->request->redirect(ilink($this->get_handler_url('index')));
	}

	/**
	* Удаление нескольких картинок
	*/
	public function delete_images()
	{
		$this->user->is_auth('redirect');

		$images_id = $this->request->variable('images_id', '');
		
		if (!$images_id || !$this->request->is_ajax)
		{
			trigger_error('DATA_NOT_FOUND');
		}

		/* images_id = 5000,7525,10759,23409 */
		$images_id = implode(',', array_map('intval', explode(',', $images_id)));

		$sql = '
			SELECT
				*
			FROM
				site_images
			WHERE
				image_id IN (:images_id)
			AND
				user_id = ?';
		$result = $this->db->query($sql, [$this->user['user_id'], ':images_id' => $images_id]);

		while ($row = $this->db->fetchrow($result))
		{
			$date = implode('/', str_split($row['image_date'], 2));

			@unlink("{$this->config['gallery.images.upload_dir']}{$date}/{$row['image_url']}");
			@unlink("{$this->config['gallery.images.upload_dir']}{$date}/t/{$row['image_url']}");
			@unlink("{$this->config['gallery.images.upload_dir']}{$date}/s/{$row['image_url']}");

			$sql = '
				DELETE
				FROM
					site_images
				WHERE
					image_id = ?';
			$this->db->query($sql, [$row['image_id']]);
		}

		$this->db->freeresult($result);
		exit;
	}

	/**
	* Ссылки на изображения
	*/
	public function links()
	{
		$this->user->is_auth('redirect');

		/* images_id = 5000,7525,10759,23409 */
		$images_id = $this->request->variable('images_id', '');

		if (!$this->request->is_ajax || !$images_id)
		{
			trigger_error('PAGE_NOT_FOUND');
		}

		$files       = substr_count($images_id, ',') + 1;
		$files_thumb = 0;
		$images_id   = implode(',', array_map('intval', explode(',', $images_id)));

		$sql = '
			SELECT
				*
			FROM
				site_images
			WHERE
				image_id IN (:images_id)
			AND
				user_id = ?';
		$this->db->query($sql, [$this->user['user_id'], ':images_id' => $images_id]);

		while ($row = $this->db->fetchrow())
		{
			$thumb = file_exists($this->config['gallery.images.upload_dir'] . implode('/', str_split($row['image_date'], 2)) . '/s/' . $row['image_url']);
			$files_thumb += $thumb ? 1 : 0;

			$this->template->append('files', [
				'DATE'  => $row['image_date'],
				'ID'    => $row['image_id'],
				'THUMB' => $thumb,
				'TIME'  => $this->user->create_date($row['image_time']),
				'URL'   => $row['image_url']
			]);
		}

		$this->db->freeresult();

		$this->template->assign([
			'FILES'       => $files,
			'FILES_THUMB' => $files_thumb
		]);
	}

	/**
	* Предпросмотр картинки
	*/
	public function preview()
	{
		$image_id = (int) $this->page;

		if ($image_id < 1)
		{
			trigger_error('Изображение не выбрано.');
		}

		/**
		* Просмотр отдельного уменьшенного изображения
		*/
		$sql = '
			SELECT
				i.*,
				u.username,
				u.user_url,
				u.user_colour
			FROM
				site_images i,
				site_users u
			WHERE
				i.user_id = u.user_id
			AND
				i.image_id = ?';
		$this->db->query($sql, [$image_id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		if (!$row)
		{
			trigger_error('Изображение не найдено.');
		}

		$thumb_exist = file_exists($this->config['gallery.images.upload_dir'] . implode('/', str_split($row['image_date'], 2)) . '/s/' . $row['image_url']);

		$this->breadcrumbs('Изображение #' . $image_id, ilink($this->get_handler_url('preview', [$image_id])));

		$this->template->assign([
			'DATE'   => $row['image_date'],
			'ID'     => $row['image_id'],
			'THUMB'  => $thumb_exist,
			'URL'    => $row['image_url'],
		]);
	}

	/**
	* Просмотр картинки
	*/
	public function view()
	{
		$image_id = (int) $this->page;

		if ($image_id < 1)
		{
			trigger_error('Изображение не выбрано.');
		}

		/**
		* Просмотр отдельного полноразмерного изображения
		*/
		$sql = '
			SELECT
				*
			FROM
				site_images
			WHERE
				image_id = ?';
		$this->db->query($sql, [$image_id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		if (!$row)
		{
			trigger_error('Изображение не найдено.');
		}

		$thumb_exist = file_exists($this->config['gallery.images.upload_dir'] . implode('/', str_split($row['image_date'], 2)) . '/s/' . $row['image_url']);

		$this->breadcrumbs('Изображение #' . $image_id, ilink($this->get_handler_url('view', [$image_id])));

		$this->template->assign([
			'DATE'  => $row['image_date'],
			'ID'    => $row['image_id'],
			'THUMB' => $thumb_exist,
			'URL'   => $row['image_url']
		]);
	}
}
