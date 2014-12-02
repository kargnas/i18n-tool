<?
	/**
	 * 새로운 번역본이나, 새로운 스트링을 DB 에 추가하는 기능. (새로운 스트링은 따로 빼야될수도?)
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request               = json_decode(file_get_contents("php://input"));
	$locale                = $request->lang;
	$type                  = $request->type;
	$key                   = $request->key;
	$string                = $request->string;
	$isMarkingAfterEditing = $request->isMarkingAfterEditing;

	if ($isMarkingAfterEditing) {
		$keyModified = 1;
	} else {
		$stringInfoList = \App\Model\StringInfoList::ByLocaleAndTypeAndKey($locale, $type, $key);
		if ($stringInfoList) {
			if ($stringInfoList->items[0]->modified) {
				$keyModified = 1;
			} else {
				$keyModified = 0;
			}
		} else {
			$keyModified = 1;
		}
	}

	// null 일 땐 디비에서 삭제
	if ($string === null) {
		\App\Model\StringInfoList::DeleteString($locale, $type, $key);
	} else {
		\App\Model\StringInfoList::InsertNewString($locale, $type, $key, $string, $keyModified);
	}

	$stringInfoList = \App\Model\StringInfoList::ByLocaleAndTypeAndKey($locale, $type, $key);

	echo json_encode(array(
		                 'success' => true,
		                 'info'    => $stringInfoList->items[0]->getJson()
	                 ));