<?php
/**
*
* @package ivacuum.ru
* @copyright (c) 2012
*
*/

namespace app\stats;

use app\models\page;

/**
* Статистика закачек
*/
class downloads extends page
{
	/**
	* Сводная таблица
	*/
	public function index()
	{
		$today_dlc = array();
		$today_traffic = $total_dlc = $total_files = $total_size = $total_today_dlc = $total_traffic = 0;

		/**
		* Количество закачек за последние сутки
		*/
		$sql = '
			SELECT
				file_id,
				COUNT(*) as total_dlc
			FROM
				' . DOWNLOADS_TABLE . '
			WHERE
				dl_time >= UNIX_TIMESTAMP(CURRENT_DATE())
			GROUP BY
				file_id';
		$this->db->query($sql, 300);

		while( $row = $this->db->fetchrow() )
		{
			$today_dlc[$row['file_id']] = $row['total_dlc'];
			$total_today_dlc += $row['total_dlc'];
		}

		$this->db->freeresult();

		$sql = '
			SELECT
				*,
				(file_size * download_count) AS traffic
			FROM
				' . FILES_TABLE . '
			ORDER BY
				download_count DESC';
		$this->db->query($sql, 300);

		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('files', array(
				'DLC'       => num_format($row['download_count']),
				'ID'        => $row['file_id'],
				'NAME'      => $row['file_name'],
				'SIZE'      => humn_size($row['file_size']),
				'TODAY_DLC' => isset($today_dlc[$row['file_id']]) ? $today_dlc[$row['file_id']] : '',
				'TRAFFIC'   => humn_size($row['traffic']),

				'U_DETAILS'  => ilink(sprintf('%s%d', $this->urls['history'], $row['file_id'])),
			));

			$today_traffic += isset($today_dlc[$row['file_id']]) ? $today_dlc[$row['file_id']] * $row['file_size'] : 0;
			$total_dlc     += $row['download_count'];
			$total_files++;
			$total_size    += $row['file_size'];
			$total_traffic += $row['traffic'];
		}

		$this->db->freeresult();

		$this->template->assign(array(
			'TODAY_TRAFFIC'   => humn_size($today_traffic),
			'TOTAL_DLC'       => num_format($total_dlc),
			'TOTAL_FILES'     => num_format($total_files),
			'TOTAL_SIZE'      => humn_size($total_size),
			'TOTAL_TODAY_DLC' => num_format($total_today_dlc),
			'TOTAL_TRAFFIC'   => humn_size($total_traffic)
		));
	}
	
	/**
	* История закачек
	*/
	public function history($file_id = false)
	{
		$file_id = (int) $file_id;
		
		if( !$file_id )
		{
			trigger_error('Не указан файл для поиска.');
		}
		
		$sql = '
			SELECT
				*
			FROM
				' . FILES_TABLE . '
			WHERE
				file_id = ' . $this->db->check_value($file_id);
		$this->db->query($sql);
		$file = $this->db->fetchrow();
		$this->db->freeresult();

		if( !$file )
		{
			trigger_error('FILE_NOT_FOUND');
		}

		$sql = '
			SELECT
				COUNT(dl_id) as download_count,
				FROM_UNIXTIME(dl_time, "%Y-%m-%d") as date
			FROM
				' . DOWNLOADS_TABLE . '
			WHERE
				file_id = ' . $this->db->check_value($file_id) . '
			GROUP BY
				date
			ORDER BY
				date ASC';
		$this->db->query($sql, 900);
		$dl_data = '';
		$i = $max_value = 0;

		while( $row = $this->db->fetchrow() )
		{
			if( $row['download_count'] > $max_value )
			{
				$max_value = $row['download_count'];
			}

			if( $i == 0 )
			{
				$start_date = date_create($row['date']);
			}

			$end_date = date_create($row['date']);

			while( $start_date != $end_date )
			{
				date_modify($start_date, '+1 day');

				if( $start_date != $end_date )
				{
					$dl_data = sprintf('%s["%s",0],', $dl_data, date_format($start_date, 'Y-m-d'));
				}
			}

			$dl_data = sprintf('%s["%s",%d],', $dl_data, $row['date'], $row['download_count']);
			$i++;
		}

		$this->db->freeresult();

		navigation_link(ilink(sprintf('%s/%d', $this->url, $file['file_id'])), $file['file_name'], 'document_text');

		$this->template->assign(array(
			'DL_DATA'   => substr($dl_data, 0, -1),
			'MAX_VALUE' => max(25, $max_value)
		));
	}

	/**
	* Последние закачки
	*/
	public function realtime()
	{
		if( $this->request->is_ajax )
		{
			$timestamp = $this->request->variable('t', $this->user->ctime);

			/* Можно получить список файлов не более чем за пять последних минут */
			$timestamp = max($timestamp, $this->user->ctime - 300);

			$sql = '
				SELECT
					d.*,
					f.file_name,
					f.file_size
				FROM
					' . DOWNLOADS_TABLE . ' d,
					' . FILES_TABLE . ' f
				WHERE
					d.file_id = f.file_id
				AND
					d.dl_time > ' . $this->db->check_value($timestamp) . '
				ORDER BY
					d.dl_time DESC';
		}
		else
		{
			$sql = '
				SELECT
					d.*,
					f.file_name,
					f.file_size
				FROM
					' . DOWNLOADS_TABLE . ' d,
					' . FILES_TABLE . ' f
				WHERE
					d.file_id = f.file_id
				ORDER BY
					d.dl_time DESC
				LIMIT
					0, 15';
		}

		$this->db->query($sql);

		while( $row = $this->db->fetchrow() )
		{
			$this->template->append('dls', array(
				'ID'   => $row['file_id'],
				'IP'   => $row['dl_ip'],
				'NAME' => $row['file_name'],
				'SIZE' => humn_size($row['file_size']),
				'TIME' => $this->user->create_date($row['dl_time']),

				'U_DETAILS'  => ilink(sprintf('%s%d', $this->urls['history'], $row['file_id'])),
			));
		}

		$this->db->freeresult();

		if( $this->request->is_ajax )
		{
			// page_header();
			// 
			// $this->template->assign('TIMESTAMP', $this->user->ctime);
			// 
			// $template->file = 'ajax/new_dls.html';
			// 
			// page_footer();
		}

		$this->template->assign(array(
			'TIMESTAMP' => $this->user->ctime,

			'U_REALTIME' => ilink($this->url)
		));
	}
}
