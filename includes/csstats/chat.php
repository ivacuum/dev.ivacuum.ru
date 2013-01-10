<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app\csstats;

use app\models\csstats\page;

/**
* Игровой чат
*/
class chat extends page
{
	/**
	* Последние сообщения
	*/
	public function index()
	{
		$filter = $this->request->variable('filter', '');
		$where  = ( $filter ) ? 'AND c.message ' . $this->csstats_db->like_expression(htmlspecialchars_decode($filter)) : '';

		$sql = '
			SELECT
				COUNT(*) as total_messages
			FROM
				hlstats_Events_Chat
			' . ( ( $filter ) ? 'WHERE message ' . $csstats_db->like_expression(htmlspecialchars_decode($filter)) : '' );
		$this->csstats_db->query($sql);
		$total_messages = $this->csstats_db->fetchfield('total_messages');
		$this->csstats_db->freeresult();

		$pagination = pagination(20, $total_messages, ilink(sprintf('%s?%s', $this->url, (($filter) ? '?filter=' . $filter : ''))));

		$sql = '
			SELECT
				c.eventTime,
				c.message_mode,
				c.message,
				p.playerId,
				p.lastName
			FROM
				hlstats_Events_Chat c,
				hlstats_Players p
			WHERE
				p.playerId = c.playerId
			' . $where . '
			ORDER BY
				c.eventTime DESC';
		$this->csstats_db->query_limit($sql, $pagination['on_page'], $pagination['offset']);

		while( $row = $this->csstats_db->fetchrow() )
		{
			$this->template->append($mode, array(
				'MESSAGE'      => wordwrap(htmlspecialchars($row['message']), 50, '<br />', true),
				'MESSAGE_MODE' => $row['message_mode'],
				'PLAYER'       => $row['lastName'],
				'TIME'         => $this->user->create_date($row['eventTime'], '|j F Y|, H:i'),

				'U_PLAYERINFO' => ilink(sprintf('%s/playerinfo/%d.html', $this->url, $row['playerId']))
			));
		}

		$this->csstats_db->freeresult();

		$this->template->assign(array(
			'FILTER'         => $filter,
			'TOTAL_MESSAGES' => $total_messages,

			'U_ACTION' => ilink($this->url)
		));
	}
}
