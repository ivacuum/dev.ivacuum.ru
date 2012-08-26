<?php
/***************************************************************************
 *							   ucp_prefs.php							   *
 *						  ----------------------						   *
 *   begin				: Tuesday, February 16, 2005					   *
 *   copyright			: © 2005 V@cuum									   *
 *   email				: knifevacuum@rambler.ru						   *
 *																		   *
 *   $Id: ucp_prefs.php, v 1.01 2005/10/28 23:01:00 V@cuum Exp $		   *
 *																		   *
 *																		   *
 ***************************************************************************/

if( !defined('IN_SITE') )
{
	die('Попытка взлома');
}

$userdata = session_pagestart($user_ip, PAGE_UCP_PREFS);

switch( $mode )
{
	case 'prefs_personal':
		// ---------------
		// Персональные настройки
		//
		ucp_header('Персональные настройки');

		$template->set_filenames(array(
			'body'	=> 'ucp_prefs_personal.html')
		);

//		$template->pparse('body');
		break;
		//
		// ---------------
	case 'prefs_news':
		// ---------------
		// Настройки новостной ленты
		//
		if( isset($_POST['submit']) )
		{
			//
			// Переменные из формы
			//
			$dotted_news	= ( isset($_POST['dotted_news']) ) ? intval($_POST['dotted_news']) : 0;
			$short_news		= ( isset($_POST['short_news']) ) ? intval($_POST['short_news']) : 0;
			$news_on_page	= ( isset($_POST['news_on_page']) ) ? intval($_POST['news_on_page']) : 10;
			// ----------

			//
			// Обновляем данные
			//
			$sql = "UPDATE " . USERS_TABLE . " SET " . $db->sql_build_array('UPDATE', array(
				'user_dotted_news'		=> $dotted_news,
				'user_short_news'		=> $short_news,
				'user_news_on_page'		=> $news_on_page)) . " WHERE `user_id` = " . $userdata['user_id'];
			if( !$db->sql_query($sql) )
			{
				site_message('Не могу обновить настройки новостной ленты.', '', __LINE__, __FILE__, $sql);
			}
			// ----------

			meta_refresh(2, append_sid($site_root_path . 'ucp.php?mode=' . $mode));

			$info_text = 'Настройки новостной ленты успешно обновлены.<br /><br />Нажмите <a href="' . append_sid($site_root_path . 'ucp.php?mode=' . $mode) . '">здесь</a>, чтобы вернуться в панель управления пользователем.';

			site_message($info_text, 'Информация');
		}

		// Определяем переменные
		$dotted_news_yes	= ( $userdata['user_dotted_news'] == 1 ) ? ' checked="checked"' : '';
		$dotted_news_no		= ( $userdata['user_dotted_news'] == 0 ) ? ' checked="checked"' : '';
		$short_news_yes		= ( $userdata['user_short_news'] == 1 ) ? ' checked="checked"' : '';
		$short_news_no		= ( $userdata['user_short_news'] == 0 ) ? ' checked="checked"' : '';

		ucp_header('Настройки новостей');

		$template->set_filenames(array(
			'body' => 'ucp_prefs_news.html')
		);

		$template->assign_vars(array(
			'DOTTED_NEWS_YES'			=> $dotted_news_yes,
			'DOTTED_NEWS_NO'			=> $dotted_news_no,
			'NEWS_ON_PAGE'				=> $userdata['user_news_on_page'],
			'SHORT_NEWS_YES'			=> $short_news_yes,
			'SHORT_NEWS_NO'				=> $short_news_no,
			
			'S_UCP_PREFS_NEWS_ACTION'	=> append_sid($site_root_path . 'ucp.php?mode=' . $mode))
		);

		$template->pparse('body');
		break;
		//
		// ---------------
	case 'prefs_enc':
		// ---------------
		// Настройки энциклопедии
		//
		ucp_header('Настройки энциклопедии');
		break;
		//
		// ---------------
	default:
		// Вывод ошибки при вызове несуществующей страницы
		site_message('Страница не найдена.');
		break;
}

?>