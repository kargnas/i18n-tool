<?php
	/**
	 * User: kars
	 * Date: 14. 10. 20. 오후 2:51
	 */

	namespace App;

	use App\Source\Git\Android;
	use App\Source\Git\iOS;
	use App\Source\Git\Server;

	class Config
	{
		static $config;

		static public function setConfig($config) {
			self::$config = $config;

			$languages = \App\Config::get('source.android.languages');
			if ($languages) {
				foreach ($languages as $language) {
					Android::$languageMap[$language['lang']] = $language;
				}
			}

			$languages = \App\Config::get('source.ios.languages');
			if ($languages) {
				foreach ($languages as $language) {
					iOS::$languageMap[$language['lang']] = $language;
				}
			}

			$languages = \App\Config::get('source.server.languages');
			if ($languages) {
				foreach ($languages as $language) {
					Server::$languageMap[$language['lang']] = $language;
				}
			}
		}

		static public function get($key = null) {
			$data = static::$config;
			if ($key) $keys = explode('.', $key);
			else $keys = null;
			if ($keys) {
				foreach ($keys as $key) {
					$data = $data[$key];
				}
			}
			return $data;
		}
	}