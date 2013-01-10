<?php
/**
* @package vacuum.kaluga.spark
* @copyright (c) 2010
*/

define('IN_SITE', true);
$site_root_path = '/srv/www/vhosts/dev.ivacuum.ru/';
require($site_root_path . 'engine/engine.php');

/**
* Определяем переменные
*/
$action = getvar('action', '');

switch( $action )
{
	case 'check_auth':

		$user->session_begin(false);
		$user->preferences();

		$template->vars(array(
			'IS_REGISTERED' => $user->is_registered)
		);

		ajax_output('check_auth.xml');

	break;
	/**
	* Настройки галереи
	*/
	case 'get_gallery_settings':

		$user->session_begin();
		$user->preferences();

		$template->vars(array(
			'GALLERY_STATS'  => $user['user_gallery_stats'],
			'IMAGES_DATE'    => $user['user_gallery_images_date'],
			'IMAGES_ON_PAGE' => ( $user['user_gallery_images_on_page'] ) ? $user['user_gallery_images_on_page'] : $config['gallery_images_on_page'],
			'IMAGES_VIEWS'   => $user['user_gallery_images_views'],
			'NEED_LOGIN'     => sprintf($user->lang['NEED_LOGIN'], ilink('/ucp/login.html')),

			'S_REGISTERED'   => $user->is_registered,

			'U_ACTION'       => ilink(sprintf('%s/?mode=settings', $user->lang['URL_GALLERY'])))
		);

		$template->file = 'ajax/gallery_settings.html';
		$template->go();

	break;
	/**
	* Форма для входа на сайт
	*/
	case 'get_login_form':

		$user->session_begin(false);
		$user->preferences();

		$back_url = getvar('back_url', $user->page_prev);

		$template->vars(array(
			'BACK_URL' => htmlspecialchars($back_url, ENT_QUOTES),

			'P_IMAGES' => $config['images_path'],
			'P_JS'     => $config['js_path'],

			'U_ACTION' => ilink('/ucp/login.html'))
		);

		$template->file = 'ajax/login_form.html';
		$template->go();

	break;
	/**
	* Форма для выхода с сайта
	*/
	case 'get_logout_form':

		$user->session_begin(false);
		$user->preferences();

		$template->vars(array(
			'U_ACTION' => ilink('/ucp/logout.html'))
		);

		$template->file = 'ajax/logout_form.html';
		$template->go();

	break;
	/**
	* Количество просмотров изображений в галерее
	*/
	case 'get_image_views':

		$template->vars(array(
			'IMAGE_VIEWS' => num_format($config['image_views']))
		);

		ajax_output('image_views.xml');

	break;
	case 'try2fly':

		$mode = getvar('mode', '');

		$user->session_begin();
		$user->preferences();

		switch( $mode )
		{
			case 'get_form':

				$template->file = 'ajax/try2fly.html';
				$template->go();

			break;
			case 'gallery':

				require($site_root_path . 'engine/smtp.php');
				$messenger = new messenger();

				$messenger->to('vacuum@example.com');

				$messenger->template('try2fly_gallery', 'ru');
				$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger->headers('X-AntiAbuse: User_id - ' . $user['user_id']);
				$messenger->headers('X-AntiAbuse: Username - ' . $user['username']);
				$messenger->headers('X-AntiAbuse: User IP - ' . $user->ip);

				$messenger->send();

				$messenger2 = new messenger();

				$messenger2->to('try2fly@example.com');

				$messenger2->template('try2fly_gallery2', 'ru');
				$messenger2->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger2->headers('X-AntiAbuse: User_id - ' . $user['user_id']);
				$messenger2->headers('X-AntiAbuse: Username - ' . $user['username']);
				$messenger2->headers('X-AntiAbuse: User IP - ' . $user->ip);

				//$messenger2->send();

			break;
			case 'portfolio':

				require($site_root_path . 'engine/smtp.php');
				$messenger = new messenger();

				$messenger->to('vacuum@example.com');

				$messenger->template('try2fly_portfolio', 'ru');
				$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger->headers('X-AntiAbuse: User_id - ' . $user['user_id']);
				$messenger->headers('X-AntiAbuse: Username - ' . $user['username']);
				$messenger->headers('X-AntiAbuse: User IP - ' . $user->ip);

				$messenger->send();

				$messenger2 = new messenger();

				$messenger2->to('try2fly@example.com');

				$messenger2->template('try2fly_portfolio2', 'ru');
				$messenger2->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger2->headers('X-AntiAbuse: User_id - ' . $user['user_id']);
				$messenger2->headers('X-AntiAbuse: Username - ' . $user['username']);
				$messenger2->headers('X-AntiAbuse: User IP - ' . $user->ip);

				//$messenger2->send();

			break;
			case 'ok':

			break;
		}

	break;
}

garbage_collection(false);
exit;

?>