<?php
/***************************************************************************
 *								ucp_pm.php								   *
 *						  ----------------------						   *
 *   begin				: Wednesday, February 16, 2005					   *
 *   copyright			: © 2005 V@cuum									   *
 *   email				: knifevacuum@rambler.ru						   *
 *																		   *
 *   $Id: ucp_pm.php, v 1.00 2005/02/16 11:53:00 V@cuum Exp $			   *
 *																		   *
 *																		   *
 ***************************************************************************/

if( !defined('IN_SITE') )
{
	die('Попытка взлома');
}

$userdata = session_pagestart($user_ip, PAGE_UCP_PM);

switch( $mode )
{
	case 'pm_view':
		// ---------------
		// Просмотр сообщений
		//
		ucp_header('Просмотр сообщений');

		$template->set_filenames(array(
			'body' => 'ucp_pm_view.html')
		);

		$template->pparse('body');
		break;
		//
		// ---------------
	case 'pm_compose':
		// ---------------
		// Написать сообщение
		//
		ucp_header('Написать сообщение');
		break;
		//
		// ---------------
	case 'pm_unread':
		// ---------------
		// Непрочитанные сообщения
		//
		ucp_header('Непрочитанные сообщения');
		break;
		//
		// ---------------
	case 'pm_options':
		// ---------------
		// Настройки
		//
		ucp_header('Настройки личных сообщений');
		break;
		//
		// ---------------
	default:
		// Вывод ошибки при вызове несуществующей страницы
		site_message('Страница не найдена.');
		break;
}

?>