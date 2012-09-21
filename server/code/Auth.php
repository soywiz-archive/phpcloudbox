<?php

class Auth {
	static public function hash($pass) {
		return md5($pass);
	}

	static public function httpAuth() {
		if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
			$authUser = $_SERVER['PHP_AUTH_USER'];
			$authPassHash = Auth::hash($_SERVER['PHP_AUTH_PW']);

			foreach (file(Folders::$root . '/passwd') as $line) {
				list($fileUser, $filePass) = explode(':', trim($line), 2);
				if (($fileUser == $authUser) && ($authPassHash == $filePass)) {
					return $authUser;
				}
			}
		}

		header('WWW-Authenticate: Basic realm="PHP Cloud Box"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'User required';
		exit;
	}
}