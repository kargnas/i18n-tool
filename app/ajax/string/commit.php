<?
	/**
	 * GIT 으로 Push
	 */

	use App\Exception\CommitSourceNotChangedException;

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	if (!$request) $request = (object) $_GET;
	$lang    = $request->lang;
	$type    = $request->type;

	$stringInfoList     = \App\Model\StringInfoList::ByLocaleAndType($lang, $type);
	$baseStringInfoList = \App\Model\StringInfoList::BaseByType($type);

	$stringBaseDictionary = new \App\LocalizationDictionary\StringDictionary($baseStringInfoList);
	$stringDictionary     = new \App\LocalizationDictionary\StringDictionary($stringInfoList);

	$stringDictionary->setBaseStringsByDictionary($stringBaseDictionary);

	try {
		switch ($type) {
			case 'iphone':
				$ios = new \App\Source\Git\iOS();
				$res = $ios->commitDictionary($lang, $stringDictionary, '번역툴에서 언어 파일 수정 (' . $lang . ') by ' . $_SERVER['REMOTE_ADDR']);
//				\App\Model\StringInfoList::ClearModifiedByType($type);
				break;

			case 'android':
				$android = new App\Source\Git\Android();
				$res     = $android->commitDictionary($lang, $stringDictionary, '번역툴에서 언어 파일 수정 (' . $lang . ') by ' . $_SERVER['REMOTE_ADDR']);
//				\App\Model\StringInfoList::ClearModifiedByType($type);
				break;

			case 'server':
				$server = new App\Source\Git\Server();
				$res     = $server->commitDictionary($lang, $stringDictionary, '번역툴에서 언어 파일 수정 (' . $lang . ') by ' . $_SERVER['REMOTE_ADDR']);
//				\App\Model\StringInfoList::ClearModifiedByType($type);
				break;
		}

		$message = '데이터가 업로드 되었습니다. 개발자의 확인을 거쳐 반영됩니다.';
	} catch (CommitSourceNotChangedException $e) {
		// 소스 변화 없음.
		$res     = true;
		$message = $e->getMessage();
	}

	if (!$res)
		die("Fail!");

	echo json_encode(array(
		                 'success'  => true,
		                 'message'  => $message,
		                 'location' => $res->links->html->href
	                 ));