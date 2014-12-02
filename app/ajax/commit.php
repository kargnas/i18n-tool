<?
	/**
	 * GIT 으로 Push
	 */

	use App\Exception\CommitSourceNotChangedException;

	include "../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	if (!$request) $request = (object) $_GET;

	$type = $request->type;

	$modified = false;

	switch ($type) {
		case 'iphone':
			$languages = \App\Source\Git\iOS::getLanguageCodes();
			$ios       = new \App\Source\Git\iOS();
			foreach ($languages as $lang) {
				$stringInfoList     = \App\Model\StringInfoList::ByLocaleAndType($lang, $type);
				$baseStringInfoList = \App\Model\StringInfoList::BaseByType($type);

				$stringBaseDictionary = new \App\LocalizationDictionary\StringDictionary($baseStringInfoList);
				$stringDictionary     = new \App\LocalizationDictionary\StringDictionary($stringInfoList);

				$stringDictionary->setBaseStringsByDictionary($stringBaseDictionary);
				try {
					$res      = $ios->commitDictionary($lang, $stringDictionary, '번역툴에서 언어 파일 수정 (' . $lang . ') by ' . $_SERVER['REMOTE_ADDR']);
					$modified = true;
				} catch (CommitSourceNotChangedException $e) {
				}
			}
			break;

		case 'android':
			$languages = \App\Source\Git\Android::getLanguageCodes();
			$android   = new App\Source\Git\Android();
			foreach ($languages as $lang) {
				$stringInfoList     = \App\Model\StringInfoList::ByLocaleAndType($lang, $type);
				$baseStringInfoList = \App\Model\StringInfoList::BaseByType($type);

				$stringBaseDictionary = new \App\LocalizationDictionary\StringDictionary($baseStringInfoList);
				$stringDictionary     = new \App\LocalizationDictionary\StringDictionary($stringInfoList);

				$stringDictionary->setBaseStringsByDictionary($stringBaseDictionary);
				try {
					$res      = $android->commitDictionary($lang, $stringDictionary, '번역툴에서 언어 파일 수정 (' . $lang . ') by ' . $_SERVER['REMOTE_ADDR']);
					$modified = true;
				} catch (CommitSourceNotChangedException $e) {
				}
			}
			break;

		case 'server':
			$languages = \App\Source\Git\Server::getLanguageCodes();
			$server    = new App\Source\Git\Server();
			foreach ($languages as $lang) {
				$stringInfoList     = \App\Model\StringInfoList::ByLocaleAndType($lang, $type);
				$baseStringInfoList = \App\Model\StringInfoList::BaseByType($type);

				$stringBaseDictionary = new \App\LocalizationDictionary\StringDictionary($baseStringInfoList);
				$stringDictionary     = new \App\LocalizationDictionary\StringDictionary($stringInfoList);

				$stringDictionary->setBaseStringsByDictionary($stringBaseDictionary);
				try {
					$res      = $server->commitDictionary($lang, $stringDictionary, '번역툴에서 언어 파일 수정 (' . $lang . ') by ' . $_SERVER['REMOTE_ADDR']);
					$modified = true;
				} catch (CommitSourceNotChangedException $e) {
				}
			}
			break;
	}

	if ($modified) $message = '데이터가 업로드 되었습니다. 개발자의 확인을 거쳐 반영됩니다.';
	else $message = "데이터가 변경되지 않았습니다.";

	echo json_encode(array(
		                 'success' => true,
		                 'message' => $message
	                 ));