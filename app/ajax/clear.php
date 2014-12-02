<?
	/**
	 * 대상 Key 를 DB에서 완전히 소멸 시켜버리는 기능
	 */

	include "../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	$type    = $request->type;

	\App\Model\StringInfoList::ClearByType($type);

	echo json_encode(array(
		                 'success' => true
	                 ));