<?php
	/**
	 * User: kars
	 * Date: 14. 10. 20. 오후 2:51
	 */

	namespace App\Util;

	use App\Config;
	use App\ErrorPage;

	class Login
	{
		static function errorIfNotLogin() {
			if (!static::checkPassword()) {
				die("로그인이 되어 있지 않습니다.");
			}
		}

		static function checkPassword() {
			$password = Config::get('webPassword');
			if (!$password) {
				ErrorPage::printError("비밀번호 필요", "설정파일에서 webPassword가 설정되지 않았습니다.");
			}
			return ($password == $_COOKIE['password']);
		}
	}