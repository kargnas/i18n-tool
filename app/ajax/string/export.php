<?
	/**
	 * 파일 Download
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = (object) $_GET;
	$lang    = $request->lang;
	$type    = $request->type;
	$export  = $request->export;

	$stringInfoList     = \App\Model\StringInfoList::ByLocaleAndType($lang, $type);
	$baseStringInfoList = \App\Model\StringInfoList::BaseByType($type);

	$baseDictionary   = new \App\LocalizationDictionary\StringDictionary($baseStringInfoList);
	$dictionary       = new \App\LocalizationDictionary\StringDictionary($stringInfoList);
	$dictionary->lang = $lang;
	$dictionary->type = $type;

	$dictionary->setBaseStringsByDictionary($baseDictionary);

	switch ($export) {
		case 'excel':
			$dictionary->exportExcel();
			break;

		default;
			switch ($type) {
				case 'iphone':
					$dictionary->exportString(\App\LocalizationDictionary\StringDictionary::RESOURCE_TYPE_IPHONE);
					break;
				case 'android':
					$dictionary->exportString(\App\LocalizationDictionary\StringDictionary::RESOURCE_TYPE_ANDROID);
					break;
				case 'server':
					$dictionary->exportString(\App\LocalizationDictionary\StringDictionary::RESOURCE_TYPE_SERVER);
					break;
				default:
					throw new Exception("알 수 없는 타입입니다.");
			}
	}
