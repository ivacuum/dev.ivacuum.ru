<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app;

use app\models\page;

/**
* Сборник цитат
*/
class memopu3 extends page
{
	/**
	* Предварительная настройка
	*/
	public function _setup()
	{
		// rss_add('rss/memopu3.xml', true);

		$this->template->assign([
			'U_MEMOPU3' => ilink($this->urls['index'])
		]);
		
		$this->set_site_submenu();
	}
	
	/**
	* Последние цитаты
	*/
	public function index()
	{
		$pagination = pagination(25, $this->config['bash_quotes_approved'], ilink($this->url));

		/**
		* Получаем данные из БД
		*/
		$sql = '
			SELECT
				*
			FROM
				' . QUOTES_TABLE . '
			WHERE
				quote_approver_time > 0
			ORDER BY
				quote_sender_time DESC';
		$this->db->query_limit($sql, $pagination['on_page'], $pagination['offset']);

		while ($row = $this->db->fetchrow())
		{
			/* Поддержка изображений */
			$row['quote_text'] = preg_replace('#\[url=([^\]]+)\]\[img\]([^\[]+)\[\/img\]\[\/url\]#', '<a href="\1"><img src="\2" alt="" /></a>', $row['quote_text']);

			$this->template->append('quotes', [
				'COMMENTS' => plural($row['quote_comments'], $this->user->lang['plural']['COMMENTS']),
				'ID'       => $row['quote_id'],
				'TEXT'     => nl2br($row['quote_text']),
				'TIME'     => $this->user->create_date($row['quote_approver_time']),
				'VOTES'    => (int) $row['quote_votes'],

				'U_DETAILS' => ilink(sprintf('%s%d', $this->urls['view'], $row['quote_id'])),
				'U_MINUS'   => ilink(sprintf('%s%d/-', $this->urls['view'], $row['quote_id'])),
				'U_PLUS'    => ilink(sprintf('%s%d/+', $this->urls['view'], $row['quote_id']))
			]);
		}

		$this->db->freeresult();
	}
	
	/**
	* Добавление цитаты
	*/
	public function add()
	{
		$this->user->is_auth('redirect');
		
		$submit = $this->request->is_set_post('submit');
		$text   = $this->request->variable('text', '');
		
		/* Добавление цитаты на рассмотрение */
		if ($submit)
		{
			if (mb_strlen($text) < 10 || mb_strlen($text) > 2000)
			{
				trigger_error('Ваша цитата не подходит по размеру (от 10 до 2000 символов).');
			}
			
			$sql = 'INSERT INTO ' . QUOTES_TABLE . ' ' .
				$this->db->build_array('INSERT', [
					'quote_sender_id'	=> $this->user['user_id'],
					'quote_sender_name'	=> $this->user['username'],
					'quote_sender_time'	=> $this->user->ctime,
					'quote_text'		=> $text
				]);
			$this->db->query($sql);

			/* На рассмотрении +1 */
			$this->config->increment('bash_quotes_wait');
			meta_refresh(2, ilink($this->url));
			trigger_error('Цитата отправлена на рассмотрение.');
		}
		
		$this->template->assign('U_ACTION', ilink($this->urls['add']));
	}
	
	/**
	* Лучшие цитаты
	*/
	public function best()
	{
		$pagination = pagination(25, $this->config['bash_quotes_approved'], ilink($this->url));

		/**
		* Получаем данные из БД
		*/
		$sql = '
			SELECT
				*
			FROM
				' . QUOTES_TABLE . '
			WHERE
				quote_approver_time > 0
			ORDER BY
				quote_votes DESC';
		$this->db->query_limit($sql, $pagination['on_page'], $pagination['offset']);

		while ($row = $this->db->fetchrow())
		{
			/* Поддержка изображений */
			$row['quote_text'] = preg_replace('#\[url=([^\]]+)\]\[img\]([^\[]+)\[\/img\]\[\/url\]#', '<a href="\1"><img src="\2" alt="" /></a>', $row['quote_text']);

			$this->template->append('quotes', [
				'COMMENTS' => plural($row['quote_comments'], $this->user->lang['plural']['COMMENTS']),
				'ID'       => $row['quote_id'],
				'TEXT'     => nl2br($row['quote_text']),
				'TIME'     => $this->user->create_date($row['quote_approver_time']),
				'VOTES'    => (int) $row['quote_votes'],

				'U_DETAILS' => ilink(sprintf('%s%d', $this->urls['view'], $row['quote_id'])),
				'U_MINUS'   => ilink(sprintf('%s%d/-', $this->urls['view'], $row['quote_id'])),
				'U_PLUS'    => ilink(sprintf('%s%d/+', $this->urls['view'], $row['quote_id']))
			]);
		}

		$this->db->freeresult();
	}
	
	/**
	* Случайные цитаты
	*/
	public function random()
	{
		$offset = [];
		$find = true;

		for ($i = 0; $i < 10; $i++)
		{
			do
			{
				$sql = '
					SELECT
						FLOOR(RAND() * COUNT(*)) AS offset
					FROM
						' . QUOTES_TABLE;
				$this->db->query($sql);
				$row = $this->db->fetchrow();
				$this->db->freeresult();

				$find = false;

				for ($k = 0, $_k = sizeof($offset); $k < $_k; $k++)
				{
					if ($offset[$k] == $row['offset'])
					{
						$find = true;
						break;
					}
				}

				if (false === $find)
				{
					$offset[] = $row['offset'];
				}
			}
			while (true === $find);

			$sql = '
				SELECT
					*
				FROM
					' . QUOTES_TABLE . '
				WHERE
					quote_approver_time > 0';
			$this->db->query_limit($sql, 1, $row['offset']);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			
			if (!$row)
			{
				continue;
			}

			/* Поддержка изображений */
			$row['quote_text'] = preg_replace('#\[url=([^\]]+)\]\[img\]([^\[]+)\[\/img\]\[\/url\]#', '<a href="\1"><img src="\2" alt="" /></a>', $row['quote_text']);

			$this->template->append('quotes', [
				'COMMENTS' => plural($row['quote_comments'], $this->user->lang['plural']['COMMENTS']),
				'ID'       => $row['quote_id'],
				'TEXT'     => nl2br($row['quote_text']),
				'TIME'     => $this->user->create_date($row['quote_approver_time']),
				'VOTES'    => (int) $row['quote_votes'],

				'U_DETAILS' => ilink(sprintf('%s%d', $this->urls['view'], $row['quote_id'])),
				'U_MINUS'   => ilink(sprintf('%s%d/-', $this->urls['view'], $row['quote_id'])),
				'U_PLUS'    => ilink(sprintf('%s%d/+', $this->urls['view'], $row['quote_id']))
			]);
		}
	}
	
	/**
	* Просмотр цитаты
	*/
	public function view($quote_id = false, $mode = false)
	{
		$quote_id = (int) $quote_id;
		
		if ($quote_id < 1)
		{
			trigger_error('Не указана цитата для поиска.');
		}
		
		$sql = '
			SELECT
				*
			FROM
				' . QUOTES_TABLE . '
			WHERE
				quote_id = ' . $this->db->check_value($quote_id) . '
			AND
				quote_approver_time > 0';
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		if (!$row)
		{
			trigger_error('Цитата не найдена.');
		}

		/* Повышение/понижение рейтинга */
		if ($mode == '+' || $mode == '-')
		{
			$this->vote($quote_id, $mode);
		}
		
		navigation_link(ilink(sprintf('%s%d', $this->urls['view'], $quote_id)), 'Цитата #' . $quote_id);
		
		/* TODO */
		/* коменты */

		/**
		* Лог голосов
		*/
		$sql = '
			SELECT
				v.*,
				u.username,
				u.user_url,
				u.user_colour
			FROM
				' . QUOTES_VOTES_TABLE . ' v
			LEFT JOIN
				' . USERS_TABLE . ' u ON (u.user_id = v.user_id)
			WHERE
				v.quote_id = ' . $this->db->check_value($quote_id) . '
			ORDER BY
				v.vote_time DESC';
		$this->db->query($sql);

		while ($votes = $this->db->fetchrow())
		{
			$this->template->append('votes', [
				'ID'   => $votes['vote_id'],
				'IP'   => $votes['user_ip'],
				'TIME' => $this->user->create_date($votes['vote_time'], 'd F Y'),
				'TYPE' => $votes['vote_option'] ? 'plus' : 'minus',
				'USER' => $this->user_profile_link('', $votes['username'], $votes['user_colour'], $votes['user_url'], $votes['user_id'])
			]);
		}

		$this->db->freeresult();

		$sql = '
			SELECT
				*
			FROM
				' . QUOTES_VOTES_TABLE . '
			WHERE
				quote_id = ' . $this->db->check_value($quote_id) . '
			AND
				user_id = 0
			ORDER BY
				vote_time DESC';
		$this->db->query($sql);

		while ($votes = $this->db->fetchrow())
		{
			$this->template->append('votes_guest', [
				'ID'   => $votes['vote_id'],
				'IP'   => $votes['user_ip'],
				'TIME' => $this->user->create_date($votes['vote_time'], 'd F Y'),
				'TYPE' => $votes['vote_option'] ? 'plus' : 'minus'
			]);
		}

		$this->db->freeresult();

		/* Поддержка изображений */
		$row['quote_text'] = preg_replace('#\[url=([^\]]+)\]\[img\]([^\[]+)\[\/img\]\[\/url\]#', '<a href="\1"><img src="\2" alt="" /></a>', $row['quote_text']);

		$this->template->assign([
			'COMMENTS' => plural($row['quote_comments'], $this->user->lang['plural']['COMMENTS']),
			'ID'       => $row['quote_id'],
			'TEXT'     => nl2br($row['quote_text']),
			'TIME'     => $this->user->create_date($row['quote_approver_time']),
			'VOTES'    => $row['quote_votes'],

			'U_DETAILS' => ilink(sprintf('%s%d', $this->urls['view'], $quote_id)),
			'U_MINUS'   => ilink(sprintf('%s%d/-', $this->urls['view'], $quote_id)),
			'U_PLUS'    => ilink(sprintf('%s%d/+', $this->urls['view'], $quote_id))
		]);
	}
	
	/**
	* Голосование за цитату
	*/
	private function vote($quote_id, $mode)
	{
		/* Запрещаем поисковым ботам голосовать */
		if ($this->user->is_bot)
		{
			trigger_error('Вы не можете голосовать.');
		}

		$sql = '
			SELECT
				vote_id
			FROM
				' . QUOTES_VOTES_TABLE . '
			WHERE
				quote_id = ' . $this->db->check_value($quote_id) . '
			AND
				' . (($this->user->is_registered) ? '(user_id = ' . $this->db->check_value($this->user['user_id']) . ' OR user_ip = ' . $this->db->check_value($this->user->ip) . ')' : 'user_ip = ' . $this->db->check_value($this->user->ip));
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		/* Найдены прежние голоса */
		if ($row)
		{
			if (!$this->request->is_ajax)
			{
				$redirect = ilink(sprintf('%s%d', $this->urls['view'], $quote_id));

				meta_refresh(2, $redirect);
				trigger_error('С вашего айпи уже оценили эту цитату. <a href="' . $redirect . '">Вернуться</a>.');
			}
			
			json_output(['votes' => '(Голос уже учтён)']);
		}
		
		/**
		* Голосуем
		*/
		$sql = 'INSERT INTO ' . QUOTES_VOTES_TABLE . ' ' .
			$this->db->build_array('INSERT', [
				'user_id'     => $this->user['user_id'],
				'user_ip'     => $this->user->ip,
				'quote_id'    => $quote_id,
				'vote_option' => $mode == '+' ? 1 : 0,
				'vote_time'   => $this->user->ctime
			]);
		$this->db->query($sql);

		$sql = '
			UPDATE
				' . QUOTES_TABLE . '
			SET
				quote_votes = quote_votes ' . (($mode == '+') ? '+ 1' : '- 1') . '
			WHERE
				quote_id = ' . $this->db->check_value($quote_id);
		$this->db->query($sql);

		if (!$this->request->is_ajax)
		{
			$this->request->redirect(ilink(sprintf('%s%d', $this->urls['view'], $quote_id)));
		}
		
		json_output(['votes' => '(Голос учтён)']);
	}
	
	/**
	* Удаление голосов
	*/
	public function vote_delete($vote_id)
	{
		if (!$this->request->is_ajax)
		{
			garbage_collection();
			exit;
		}
		
		if (!$this->auth->acl_get('a_'))
		{
			garbage_collection();
			exit;
		}

		$vote_id = (int) $vote_id;

		$sql = '
			SELECT
				vote_option
			FROM
				' . QUOTES_VOTES_TABLE . '
			WHERE
				vote_id = ' . $this->db->check_value($vote_id);
		$this->db->query($sql);
		$vote = $this->db->fetchrow();
		$this->db->freeresult();

		if (!$vote)
		{
			garbage_collection();
			exit;
		}

		$this->db->transaction('begin');
		$sql = '
			DELETE
			FROM
				' . QUOTES_VOTES_TABLE . '
			WHERE
				vote_id = ' . $this->db->check_value($vote_id);
		$this->db->query($sql);

		$sql = '
			UPDATE
				' . QUOTES_TABLE . '
			SET
				quote_votes = quote_votes ' . (($vote['vote_option']) ? '- 1' : '+ 1') . '
			WHERE
				quote_id = ' . $this->db->check_value($row['quote_id']);
		$this->db->query($sql);
		$this->db->transaction('commit');

		json_output([
			'success'  => 1,
			'quote_id' => $row['quote_id'],
			'votes'    => $vote['vote_option'] ? $row['quote_votes'] - 1 : $row['quote_votes'] + 1
		]);
	}
}
