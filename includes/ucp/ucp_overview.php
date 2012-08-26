<?php
/***************************************************************************
 *							 ucp_overview.php							   *
 *						  ----------------------						   *
 *   begin				: Tuesday, February 15, 2005					   *
 *   copyright			: © 2005 V@cuum									   *
 *   email				: knifevacuum@rambler.ru						   *
 *																		   *
 *   $Id: ucp_overview.php, v 1.03 2005/10/28 22:53:00 V@cuum Exp $		   *
 *																		   *
 *																		   *
 ***************************************************************************/

if( !defined('IN_SITE') )
{
	die('Попытка взлома');
}

$userdata = session_pagestart($user_ip, PAGE_UCP_OVERVIEW);

switch( $mode )
{
	case 'overview_front':
		// ---------------
		// Обзор
		//
		ucp_header('Обзор');

		$template->set_filenames(array(
			'body' => 'ucp_overview_front.html')
		);

		$template->assign_vars(array(
			'LASTUPDATE'	=> create_date('D d M, Y H:i', $userdata['user_session_time'], $site_config['site_tz']),
			'POSTS'			=> $userdata['user_posts'],
			'REGDATE'		=> create_date('D d M, Y H:i', $userdata['user_regdate'], $site_config['site_tz']))
		);

		$template->pparse('body');
		break;
		//
		// ---------------
	case 'overview_transfer_kr':
		// ---------------
		// Перевод кредитов
		//
		if( isset($_POST['submit']) )
		{
			include($site_root_path . 'includes/combats.php');

			// Определяем переменные
			$bank_id	= request_var('bank_id', '');
			$money		= request_var('money', 0);
			$password	= request_var('password', '');

			//
			// Получаем данные банковского счета
			//
			if( $bank_id <= 0 || $bank_id == '' )
			{
				site_message('Не указан номер банковского счета.');
			}
			else
			{
				$sql = "SELECT * FROM " . BK_BANK_TABLE . " WHERE `bank_id` = " . $bank_id;
				if( !$result = $db->sql_query($sql) )
				{
					site_message('Не могу получить данные банковского счета.', '', __LINE__, __FILE__, $sql);
				}

				$row = $db->sql_fetchrow($result);
			}
			// ----------

			//
			// Проверки
			//
			if( !preg_match('#^[0-9]+$#', $bank_id) )
			{
				site_message('Номер счета введен неправильно.');
			}
			elseif( !preg_match('#^[0-9]+$#', $bank_id) )
			{
				site_message('Количество переводимых денег введено неправильно.');
			}
			elseif( !$row['bank_id'] || $row['bank_id'] <= 0 )
			{
				site_message('Банковский счет с таким номером не существует.');
			}
			elseif( intval($money) <= 0 )
			{
				site_message('Что, денег жалко?');
			}
			elseif( $userdata['user_money'] < $money )
			{
				site_message('Вам такой перевод не по карману...');
			}
			elseif( strlen($password) < 3 || strlen($password) > 30 )
			{
				site_message('Неверная длина пароля.');
			}
			elseif( $userdata['user_password'] != md5($password) )
			{
				site_message('Вы ввели неправильный пароль.');
			}
			// ----------

			// Снимаем деньги
			$sql = "UPDATE " . USERS_TABLE . " SET user_money = ( user_money - " . intval($money) . " ) WHERE `user_id` = " . $userdata['user_id'];
			if( !$db->sql_query($sql) )
			{
				site_message('Не могу обновить данные персонажа.', '', __LINE__, __FILE__, $sql);
			}

			// Переводим деньги
			$sql = "UPDATE " . BK_BANK_TABLE . " SET bank_money = ( bank_money + " . intval($money) . " ) WHERE `bank_id` = " . $bank_id;
			if( !$db->sql_query($sql) )
			{
				site_message('Не могу перевести деньги на банковский счет.', '', __LINE__, __FILE__, $sql);
			}

			// Запись в личное дело
//			$combats->add_admin_log_message(

			//
			// Задание успешно выполнено... сообщаем об этом...
			//
			meta_refresh(3, append_sid($site_root_path . 'ucp.php?mode=' . $mode));

			$info_text = 'Успешно переведа сумма ' . intval($money) . ' кр. на счет №' . $bank_id . '.<br /><br />Нажмите <a href="' . append_sid($site_root_path . 'ucp.php?mode=' . $mode) . '">здесь</a>, чтобы вернуться в панель управления пользователем.';

			site_message($info_text, 'Информация');
			// ----------
		}

		ucp_header('Перевод кредитов');

		$template->set_filenames(array(
			'body' => 'ucp_overview_transfer_kr.html')
		);

		$template->assign_vars(array(
			'MAX_SUM'	=> intval($userdata['user_money']))
		);

		$template->pparse('body');
		break;
		//
		// ---------------
	default:
		// Вывод ошибки при вызове несуществующей страницы
		site_message('Страница не найдена.');
		break;
}

?>