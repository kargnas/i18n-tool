<?
	/**
	 * 번역 진행중인 언어 데이터를 DB에서 불러오는 기능.
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	$type    = $request->type;

	$group = \App\Model\StringInfoGroup::ByType($type);
	echo json_encode($group->getArrayAggregatedByKey());