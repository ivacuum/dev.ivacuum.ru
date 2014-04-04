<?php

use Phinx\Migration\AbstractMigration;

class Origin extends AbstractMigration
{
	public function up()
	{
		$this->import_sql_dump(SITE_DIR . '../migrations/schema.sql');
	}
	
	public function down()
	{
		$this->dropTable('site_comments');
		$this->dropTable('site_downloads');
		$this->dropTable('site_files');
		$this->dropTable('site_images');
		$this->dropTable('site_image_albums');
		$this->dropTable('site_image_refs');
		$this->dropTable('site_image_views');
		$this->dropTable('site_image_watermarks');
		$this->dropTable('site_quotes');
		$this->dropTable('site_quotes_votes');
		$this->dropTable('site_ranks');
		$this->dropTable('site_smilies');
	}
	
	protected function import_sql_dump($file)
	{
		$sql_ary = file_get_contents($file);
		$sql_ary = $this->split_sql_file($sql_ary, ';');
	
		foreach ($sql_ary as $sql) {
			$this->execute($sql);
		}
	}
	
	protected function split_sql_file($sql, $delimiter)
	{
		$sql = str_replace("\r" , '', $sql);
		$data = preg_split('/' . preg_quote($delimiter, '/') . '$/m', $sql);
		$data = array_map('trim', $data);

		$end_data = end($data);

		if (empty($end_data)) {
			unset($data[key($data)]);
		}

		return $data;
	}
}
