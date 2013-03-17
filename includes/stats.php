<?php
/**
* @package ivacuum.ru
* @copyright (c) 2013
*/

namespace app;

use app\models\page;

/**
* Статистика
*/
class stats extends page
{
	/**
	* Список разделов
	*/
	public function index()
	{
		foreach ($this->get_page_descendants() as $row)
		{
			$this->template->append('pages', [
				'IMAGE' => $row['page_image'],
				'TITLE' => $row['page_name'],
				
				'U_VIEW' => $this->descendant_link($row),
			]);
		}
	}
	
	/**
	* Статистика сервера
	*/
	public function server()
	{
		$sql = 'SHOW GLOBAL STATUS';
		$this->db->query($sql);

		while ($row = $this->db->fetchrow())
		{
			$info[$row['Variable_name']] = $row['Value'];
		}

		$this->db->freeresult();

		$this->template->assign([
			'ABORTED_CLIENTS'           => $info['Aborted_clients'],
			'ABORTED_CLIENTS_PER_HOUR'  => sprintf('%.2f', (($info['Aborted_clients'] * 3600) / $info['Uptime'])),
			'ABORTED_CLIENTS_PERCENT'   => sprintf('%.2f', (($info['Aborted_clients'] * 100) / $info['Connections'])),
			'ABORTED_CONNECTS'          => $info['Aborted_connects'],
			'ABORTED_CONNECTS_PER_HOUR' => sprintf('%.2f', (($info['Aborted_connects'] * 3600) / $info['Uptime'])),
			'ABORTED_CONNECTS_PERCENT'  => sprintf('%.2f', (($info['Aborted_connects'] * 100) / $info['Connections'])),
			'BYTES_OVERALL'             => $info['Bytes_received'] + $info['Bytes_sent'],
			'BYTES_OVERALL_PER_HOUR'    => (($info['Bytes_received'] + $info['Bytes_sent']) * 3600) / $info['Uptime'],
			'BYTES_RECEIVED'            => $info['Bytes_received'],
			'BYTES_RECEIVED_PER_HOUR'   => ($info['Bytes_received'] * 3600) / $info['Uptime'],
			'BYTES_SENT'                => $info['Bytes_sent'],
			'BYTES_SENT_PER_HOUR'       => ($info['Bytes_sent'] * 3600) / $info['Uptime'],
			'CONNECTIONS'               => $info['Connections'],
			'CONNECTIONS_PER_HOUR'      => sprintf('%.2f', (($info['Connections'] * 3600) / $info['Uptime'])),
			'QUERIES'                   => $info['Questions'],
			'QUERIES_PER_HOUR'          => sprintf('%.2f', (($info['Questions'] * 3600) / $info['Uptime'])),
			'QUERIES_PER_MINUTE'        => sprintf('%.2f', (($info['Questions'] * 60) / $info['Uptime'])),
			'QUERIES_PER_SECOND'        => sprintf('%.2f', ($info['Questions'] / $info['Uptime'])),
			'UPTIME'                    => $info['Uptime'],
		]);
	}
}
