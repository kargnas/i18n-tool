<?
	/**
	 * 대상 Key 를 DB에서 완전히 소멸 시켜버리는 기능
	 */

	include "../../../_head.php";

	$request = json_decode(file_get_contents("php://input"));
	$lang    = $request->lang;
	$type    = $request->type;
	$content = $request->content;

	$infoListJson = array(
		'items' => array()
	);

	try {
		/**
		 * 줄바꿈 처리 떄문에 파싱이 복잡해서 정규식으로 처리함.
		 *
		 * - 텍스트가 줄바꿈이 들어가면 스트링 앞뒤에 " 가 들어감
		 * - "가 들어간 상태에서, 실제 쌍따옴표가 스트링에 포함되어 있으면 escape 된 문자인 \" 가 들어감.
		 * - COMMON_ALERT_IMAGE_UPLOAD_FAILED1 의 두번쨰 줄은 " 로 끝나지만, Escape 된 문자열이므로 스트링의 끝이 아님.
		 *
		 * 예제 텍스트:
COMMON_ALERT_IMAGE_UPLOAD_FAILED1	"写真のアップロ\"\"ーjni ni j no ドに失敗しました。
しばらく後にもう}:{SD:F{SD: F\"
い！"
COMMON_ALERT_IMAGE_UPLOAD_FAILED2	"ABC"
COMMON_ALERT_IMAGE_UPLOAD_FAILED3	"ABC
D"
COMMON_ALERT_IMAGE_UPLOAD_FAILED4
COMMON_ALERT_IMAGE_UPLOAD_FAILED5	A
COMMON_ALERT_IMAGE_UPLOAD_FAILED6	少々お待ちください
		 */
		$content .= "\r\n";
		$content = str_replace("\r", "", $content);
		preg_match_all('/(.*?)\t(?:"((?:.[\S\s\n]*?)[^\\\])"|([^"\n](?:.*?)[^"]))\n/im', $content, $pregs);

		foreach ($pregs[1] as $i => $preg) {
			$key    = $preg;
			if ($pregs[2][$i]) {
				$string = $pregs[2][$i];
			} else if ($pregs[3][$i]) {
				$string = str_replace("\\n", "\n", $pregs[3][$i]);
			} else if ($pregs[4][$i]) {
				$string = $pregs[4][$i];
			}

			if (!$string) continue;

			// `Key` 에는 따옴표 등의 문자가 들어가면 안됨.
			if (preg_match("/[\s\n\"']/i", $key)) {
				throw new Exception("비정상적인 형식의 데이터가 있습니다.\r\n" . $key);
				continue;
			}

			$infoListJson['items'][] = array(
				'key'    => trim($key),
				'string' => trim($string)
			);
		}

		// 기존에 저장된 리스트를 가져옴. (새로 추가된 Key 를 구별하기 위해서)
		$baseStringInfoList = \App\Model\StringInfoList::BaseByType($type);

		$infoList = new \App\Model\StringInfoList($infoListJson);
		$store    = new \App\DataStore();
		$store->setDictionary($lang, $type, $infoList);

		$keys = $baseStringInfoList->findNewKeys($infoList);
		$store->setKeysArray($type, $keys, true);

		echo json_encode(array(
			                 'success' => true
		                 ));
	} catch (Exception $e) {
		echo $e->getMessage();
	}