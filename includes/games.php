<?php namespace app;

use app\models\page;

class games extends page
{
	public function index()
	{
		foreach ($this->get_page_descendants() as $row) {
			$this->template->append('games', [
				'COVER' => $row['page_url'],
				'TITLE' => $row['page_name'],
		
				'U_DETAIL' => $this->descendant_link($row),
			]);
		}
	}
}
