<?php
	namespace App;

	class ErrorPage
	{
		static public function printError($subject, $content) {
			header('Content-Type: text/html; charset=utf-8');
			echo "<h1>".$subject."</h1>";
			echo $content;
			exit;
		}
	}