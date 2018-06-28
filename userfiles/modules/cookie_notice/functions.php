<?php
api_expose('scwCookie_ajax');
function scwCookie_ajax()
{
	if (isset($_POST['action']) && isset($_POST['id']) && strstr($_POST['id'],'cookie-notice')) {

		$id = $_POST['id'];

		require_once('scwCookie/scwCookie.class.php');

		switch ($_POST['action']) {
			case 'acceptandhide':
				// enable all cookies
				$scwCookie = init_scwCookie($id);
				$return    = [];

				// Update all cookies to allowed
				$choices = [];
				$enabledCookies = $scwCookie->enabledCookies();

				foreach ($enabledCookies as $name => $label) {
					$choices[$name] = 'allowed';
				}
				$scwCookie->setCookie('scwCookie', $scwCookie->encrypt($choices), 52, 'weeks');

				// Set cookie
				ScwCookie\ScwCookie::setCookie('scwCookieHidden', 'true', 52, 'weeks');

				header('Content-Type: application/json');
				die(json_encode(['success' => true]));
				break;

			case 'hide':
				// Set cookie
				ScwCookie\ScwCookie::setCookie('scwCookieHidden', 'true', 52, 'weeks');
				header('Content-Type: application/json');
				die(json_encode(['success' => true]));
				break;

			case 'toggle':
				$scwCookie = init_scwCookie($id);
				$return    = [];

				// Update if cookie allowed or not
				$choices = $scwCookie->getCookie('scwCookie');
				if ($choices == false) {
					$choices = [];
					$enabledCookies = $scwCookie->enabledCookies();
					foreach ($enabledCookies as $name => $label) {
						$choices[$name] = $scwCookie->config['unsetDefault'];
					}
					$scwCookie->setCookie('scwCookie', $scwCookie->encrypt($choices), 52, 'weeks');
				} else {
					$choices = $scwCookie->decrypt($choices);
				}
				$choices[$_POST['name']] = $_POST['value'] == 'true' ? 'allowed' : 'blocked';

				// Remove cookies if now disabled
				if ($choices[$_POST['name']] == 'blocked') {
					$removeCookies = $scwCookie->clearCookieGroup($_POST['name']);
					$return['removeCookies'] = $removeCookies;
				}

				$choices = $scwCookie->encrypt($choices);
				$scwCookie->setCookie('scwCookie', $choices, 52, 'weeks');

				header('Content-Type: application/json');
				die(json_encode($return));
				break;

			case 'load':
				$return    = [];

				if($scwCookie = init_scwCookie($id)) {

					$removeCookies = [];

					foreach ($scwCookie->disabledCookies() as $cookie => $label) {
						$removeCookies = array_merge($removeCookies, $scwCookie->clearCookieGroup($cookie));
					}
					$return['removeCookies'] = $removeCookies;
				}

				header('Content-Type: application/json');
				die(json_encode($return));
				break;

			default:
				header('HTTP/1.0 403 Forbidden');
				throw new Exception("Action not recognised");
				break;
		}

	}
}

function init_scwCookie($id)
{
	$return = false;

	$settings = get_option('settings', $id);

	if($settings) {
		$json = json_decode($settings, true);
		$return = new ScwCookie\ScwCookie($json,$id);
	}

	return $return;
}
