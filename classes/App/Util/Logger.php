<?php
	/**
	 * User: kars
	 * Date: 14. 10. 20. 오후 2:51
	 */

	namespace App\Util;

	class Logger
	{
		static function Log($text) {
			$text = date("Y-m-d H:i:s") . ") {$text}\r\n";
			$fp   = fopen("/tmp/log", "a");
			fwrite($fp, $text);
			fclose($fp);
		}
	}