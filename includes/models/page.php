<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app\models;

use fw\models\page as base_page;

class page extends base_page
{
	public function page_header()
	{
		parent::page_header();
		
		$this->template->assign(array(
			'S_BASE_JS_MTIME' => filemtime($this->config['images_dir'] . 'bootstrap/' . $this->config['bootstrap_version'] . '/plugins.js'),
			'S_MAIN_JS_MTIME' => filemtime($this->config['js_dir'] . 'base.js'),
			'S_STYLE_MTIME'   => filemtime($this->config['images_dir'] . 'bootstrap/' . $this->config['bootstrap_version'] . '/expansion.css'),
			
			'U_LOCAL' => ilink('', 'http://local.ivacuum.ru')
		));
	}
}
