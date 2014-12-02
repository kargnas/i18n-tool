<?
	/**
	 * 번역의 기반이 되는 한국어 데이터를 불러오는 기능.
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	$type    = $request->type;

	$stringInfoList = \App\Model\StringInfoList::BaseByType($type);
	echo json_encode($stringInfoList->getJsonItems());