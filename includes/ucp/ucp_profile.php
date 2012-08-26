<?php
/***************************************************************************
 *							 ucp_profile.php							   *
 *						  ----------------------						   *
 *   begin				: Tuesday, February 15, 2005					   *
 *   copyright			: © 2005 V@cuum									   *
 *   email				: knifevacuum@rambler.ru						   *
 *																		   *
 *   $Id: ucp_profile.php, v 1.03 2005/10/26 21:49:00 V@cuum Exp $		   *
 *																		   *
 *																		   *
 ***************************************************************************/

if( !defined('IN_SITE') )
{
	die('Попытка взлома');
}

$userdata = session_pagestart($user_ip, PAGE_UCP_PROFILE);

switch( $mode )
{
	case 'profile_info':
		// ---------------
		// Контактная информация
		//
		if( isset($_POST['submit']) )
		{
			$user_icq = ( isset($_POST['icq']) ) ? intval($_POST['icq']) : '';
			$user_website = ( isset($_POST['website']) ) ? trim($_POST['website']) : '';
			$user_from = ( isset($_POST['from']) ) ? trim($_POST['from']) : '';
			$user_occ = ( isset($_POST['occ']) ) ? trim($_POST['occ']) : '';
			$user_interests = ( isset($_POST['interests']) ) ? trim($_POST['interests']) : '';

			//
			// Проверка ввода
			//
			if( !preg_match('/^[0-9]+$/', $user_icq))
			{
				$user_icq = '';
			}

			if( $user_website != '' )
			{
				if( !preg_match('#^http[s]?:\/\/#i', $user_website))
				{
					$user_website = 'http://' . $user_website;
				}

				if( !preg_match('#^http[s]?://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i', $user_website))
				{
					$user_website = '';
				}
			}
			// ----------

			//
			// Обновляем данные
			//
			$sql = "UPDATE " . USERS_TABLE . " SET " . $db->sql_build_array('UPDATE', array(
				'user_icq'			=> $user_icq,
				'user_website'		=> $user_website,
				'user_from'			=> $user_from,
				'user_occ'			=> $user_occ,
				'user_interests'	=> $user_interests)) . " WHERE `user_id` = " . $userdata['user_id'];
			if( !$db->sql_query($sql) )
			{
				site_message('Не могу обновить данные пользователя.', '', __LINE__, __FILE__, $sql);
			}
			// ----------

			//
			// Сообщаем об обновлении
			//
			meta_refresh(2, append_sid($site_root_path . 'ucp.php?mode=' . $mode));

			$info_text = 'Контактная информация успешно обновлена.<br /><br />Нажмите <a href="' . append_sid($site_root_path . 'ucp.php?mode=' . $mode) . '">здесь</a>, чтобы вернуться в панель управления пользователем.';

			site_message($info_text, 'Информация');
			// ----------
		}

		ucp_header('Контактная информация');

		$template->set_filenames(array(
			'body'	=> 'ucp_profile_info.html')
		);

		$template->assign_vars(array(
			'FROM'						=> $userdata['user_from'],
			'ICQ'						=> $userdata['user_icq'],
			'INTERESTS'					=> $userdata['user_interests'],
			'OCC'						=> $userdata['user_occ'],
			'WEBSITE'					=> $userdata['user_website'],
			
			'S_UCP_PROFILE_INFO_ACTION'	=> append_sid($site_root_path . 'ucp.php?mode=' . $mode))
		);

		$template->pparse('body');
		break;
		//
		// ---------------
	case 'profile_reg_details':
		// ---------------
		// Регистрационные данные
		//
		if( isset($_POST['submit']) )
		{
			$email = ( isset($_POST['email']) ) ? trim($_POST['email']) : '';
			$email_confirm = ( isset($_POST['email_confirm']) ) ? trim($_POST['email_confirm']) : '';
			$password = ( isset($_POST['password']) ) ? trim($_POST['password']) : '';
			$password_confirm = ( isset($_POST['password_confirm']) ) ? trim($_POST['password_confirm']) : '';
			$cur_password = ( isset($_POST['cur_password']) ) ? trim($_POST['cur_password']) : '';
			$sql_ary = array();

			//
			// Проверяем правильность ввода текущего пароля
			//
			if( md5($cur_password) != $userdata['user_password'] )
			{
				site_message('Вы ввели неправильный пароль.');
			}
			// ----------

			if( $email != $userdata['user_email'] )
			{
				//
				// Смена почтового адреса (проверки)
				//
				if( $email != $email_confirm )
				{
					site_message('Введенные почтовые адреса не совпадают.');
				}
				elseif( strlen($email) < 6 )
				{
					site_message('Введенный почтовый адрес слишком короткий.');
				}
				// ----------

				$sql_ary = array('user_email'	=> $email);
			}
			
			if( $password != '' )
			{
				//
				// Смена пароля
				//
				if( $password != $password_confirm )
				{
					site_message('Введенные пароли не совпадают.');
				}
				elseif( strlen($password) < 3 )
				{
					site_message('Введенный пароль слишком короткий.');
				}
				elseif( strlen($password) > 30 )
				{
					site_message('Введенный пароль слишком длинный.');
				}
				// ----------

				$sql_ary = array('user_password' => md5($password));
			}

			//
			// Обновляем данные
			//
			$sql = "UPDATE " . USERS_TABLE . " SET " . $db->sql_build_array('UPDATE', $sql_ary) . " WHERE `user_id` = " . $userdata['user_id'];
			if( !$db->sql_query($sql) )
			{
				site_message('Не могу обновить регистрационные данные пользователя.', '', __LINE__, __FILE__, $sql);
			}
			// ----------

			//
			// Сообщаем об обновлении
			//
			meta_refresh(2, append_sid($site_root_path . 'ucp.php?mode=' . $mode));

			$info_text = 'Регистрационные данные успешно обновлены.<br /><br />Нажмите <a href="' . append_sid($site_root_path . 'ucp.php?mode=' . $mode) . '">здесь</a>, чтобы вернуться в панель управления пользователем.';

			site_message($info_text, 'Информация');
			// ----------
		}

		ucp_header('Регистрационные данные');

		$template->set_filenames(array(
			'body'	=> 'ucp_profile_reg_details.html')
		);

		$template->assign_vars(array(
			'EMAIL'								=> $userdata['user_email'],
			'NAME'								=> $userdata['username'],
			
			'S_UCP_PROFILE_REG_DETAILS_ACTION'	=> append_sid($site_root_path . 'ucp.php?mode=' . $mode))
		);

		$template->pparse('body');
		break;
		//
		// ---------------
	case 'profile_signature':
		// ---------------
		// Подпись
		//
		ucp_header('Подпись');

		$template->set_filenames(array(
			'body' => 'ucp_profile_signature.html')
		);

		$template->assign_vars(array(
			'S_UCP_PROFILE_SIGNATURE_ACTION'	=> append_sid($site_root_path . 'ucp.php?mode=' . $mode))
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