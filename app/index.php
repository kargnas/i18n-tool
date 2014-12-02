<?
	include "../_head.php";

	if (\App\Util\Login::checkPassword()) {
		$html = file_get_contents("tool.html");
		echo $html;
	} else {
		$html = file_get_contents("login.html");
		echo $html;
	}
?>