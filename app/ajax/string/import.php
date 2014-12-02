<?
	/**
	 * git 등 원본 소스에서 데이터를 새로 받아오는 기능.
	 */

	include "../../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	if (!$request) $request = (object) $_GET;

	$lang = $request->lang;
	$type = $request->type;

	try {
		switch ($type) {
			case 'iphone':
				// 서버에서 새로운 파일들을 받기 위한 클래스 생성
				$ios = new App\Source\Git\iOS();

				$xcodeDic = \App\LocalizationDictionary\StringDictionary::getByParsingStringsFile(\App\LocalizationDictionary\StringDictionary::RESOURCE_TYPE_IPHONE, $ios->getLastStrings($lang));

				// 저장하기
				$store = new \App\DataStore();
				$store->setKeys($type, $xcodeDic->stringInfoList, 0);
				$store->setDictionary($lang, $type, $xcodeDic->stringInfoList);
				break;

			case 'android':
				$android = new App\Source\Git\Android();

				$dic = \App\LocalizationDictionary\StringDictionary::getByParsingStringsFile(\App\LocalizationDictionary\StringDictionary::RESOURCE_TYPE_ANDROID, $android->getLastStrings($lang));

				// 저장하기
				$store = new \App\DataStore();
				$store->setKeys($type, $dic->stringInfoList, 0);
				$store->setDictionary($lang, $type, $dic->stringInfoList);
				break;

			case 'server':
				$android = new App\Source\Git\Server();

				$dic = \App\LocalizationDictionary\StringDictionary::getByParsingStringsFile(\App\LocalizationDictionary\StringDictionary::RESOURCE_TYPE_SERVER, $android->getLastStrings($lang));

				// 저장하기
				$store = new \App\DataStore();
				$store->setKeys($type, $dic->stringInfoList, 0);
				$store->setDictionary($lang, $type, $dic->stringInfoList);
				break;
		}

		echo json_encode(array(
			                 'success' => true
		                 ));
	} catch (Exception $e) {
		echo json_encode(array(
			                 'success' => false,
			                 'message' => $e->getMessage()
		                 ));
	}