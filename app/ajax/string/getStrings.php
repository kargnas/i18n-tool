<?
	/**
	 * 번역 진행중인 언어 데이터를 DB에서 불러오는 기능.
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	if (!$request) $request = (object) $_GET;

	$locale  = $request->lang;
	$type    = $request->type;

	$stringInfoList = \App\Model\StringInfoList::ByLocaleAndType($locale, $type);
	echo json_encode($stringInfoList->getJsonItems());